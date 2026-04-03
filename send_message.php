<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['receiver_username'])) {
    die(json_encode(['success' => false, 'error' => 'Invalid request']));
}

$my_id = $_SESSION['user_id'];
$receiver_username = trim($_POST['receiver_username']);
$message_text = isset($_POST['message']) ? trim($_POST['message']) : '';
$sender_message = isset($_POST['sender_message']) ? trim($_POST['sender_message']) : '';

$destruct_timer = isset($_POST['destruct_timer']) ? (int)$_POST['destruct_timer'] : 0;

// Check if a file was uploaded
$has_file = isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK;

if (empty($message_text) && !$has_file) {
    die(json_encode(['success' => false, 'error' => 'Cannot send an empty message']));
}

// ==========================================
// EDIT MESSAGE INTERCEPTOR (With 1-Hour Lock)
// ==========================================
$edit_id = isset($_POST['edit_message_id']) ? (int)$_POST['edit_message_id'] : 0;

if ($edit_id > 0) {
    // SECURITY: "AND created_at >= NOW() - INTERVAL 1 HOUR" strictly blocks late edits!
    $stmt = $conn->prepare("UPDATE messages SET message=?, sender_message=?, is_edited=1 WHERE message_id=? AND sender_id=? AND created_at >= NOW() - INTERVAL 1 HOUR");
    $stmt->bind_param("ssii", $message_text, $sender_message, $edit_id, $my_id);
    $stmt->execute();

    // Check if the database actually updated a row (If 0, the time limit expired!)
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Edit failed. Message is older than 1 hour.']);
    }
    $stmt->close();
    exit();
}
// ==========================================

// 1. Get receiver's ID
$stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
$stmt->bind_param("s", $receiver_username);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) die(json_encode(['success' => false, 'error' => 'User not found']));
$receiver_id = $res->fetch_assoc()['user_id'];
$stmt->close();

// 2. Security Check: Are they blocked?
$block_check = $conn->prepare("SELECT block_id FROM blocked_users WHERE ((blocker_id = ? AND blocked_id = ?) OR (blocker_id = ? AND blocked_id = ?)) AND is_active = 1");
$block_check->bind_param("iiii", $my_id, $receiver_id, $receiver_id, $my_id);
$block_check->execute();
if ($block_check->get_result()->num_rows > 0) die(json_encode(['success' => false, 'error' => 'Connection terminated.']));
$block_check->close();

// 3. Handle File Upload (If present)
$file_path = null;
$file_name = null;
$file_type = null;

if ($has_file) {
    $upload_dir = 'uploads/attachments/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $file_info = pathinfo($_FILES['attachment']['name']);
    $ext = strtolower($file_info['extension']);
    $file_name = basename($_FILES['attachment']['name']);
    $file_type = mime_content_type($_FILES['attachment']['tmp_name']);

    // Allowed file types for security
    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip'];
    if (!in_array($ext, $allowed_exts)) {
        die(json_encode(['success' => false, 'error' => 'File type not allowed']));
    }

    $unique_name = uniqid('att_') . '.' . $ext;
    $file_path = $upload_dir . $unique_name;

    if (!move_uploaded_file($_FILES['attachment']['tmp_name'], $file_path)) {
        die(json_encode(['success' => false, 'error' => 'File upload failed']));
    }
}

// Catch the reply ID and the new Forward flag!
$reply_to_id = isset($_POST['reply_to_id']) && $_POST['reply_to_id'] !== '' ? (int)$_POST['reply_to_id'] : NULL;
$is_forwarded = isset($_POST['is_forwarded']) ? (int)$_POST['is_forwarded'] : 0;

// Phase 2 - Parallel Storage for the Audit Log (The Enterprise Storage Fix)
$ciphertext_admin = isset($_POST['ciphertext_admin']) ? trim($_POST['ciphertext_admin']) : '';
$attachment_admin_path = NULL;

if (isset($_FILES['attachment_admin_file']) && $_FILES['attachment_admin_file']['error'] == UPLOAD_ERR_OK) {
    $file_name_admin = "admin_enc_" . time() . "_" . rand(1000, 9999) . ".txt";
    $target_path = "uploads/attachments/" . $file_name_admin;

    if (move_uploaded_file($_FILES['attachment_admin_file']['tmp_name'], $target_path)) {
        $attachment_admin_path = $target_path;
    }
}

// Save Audit Log
if ($ciphertext_admin != '' || $attachment_admin_path != null) {
    $audit_insert = $conn->prepare("INSERT INTO audit_logs (sender_id, receiver_id, ciphertext_admin, attachment_admin) VALUES (?, ?, ?, ?)");
    $audit_insert->bind_param("iiss", $my_id, $receiver_id, $ciphertext_admin, $attachment_admin_path);
    $audit_insert->execute();
    $audit_insert->close();
}

// ==========================================
// NEW: Catch the Dashboard Note Priority
// ==========================================
// If this is a personal note, put "High", "Medium", or "Low" directly into starred_by_sender!
$is_personal_note = isset($_POST['is_personal_note']) ? true : false;
$starred_by_sender = $is_personal_note ? (isset($_POST['priority']) ? $_POST['priority'] : 'Low') : null;

// Notice we only have 11 columns here now, perfectly matching your database!
$insert_query = "INSERT INTO messages (sender_id, receiver_id, message, sender_message, file_path, file_name, file_type, destruct_timer, reply_to_id, is_forwarded, starred_by_sender) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$insert = $conn->prepare($insert_query);

// Safety Net: Tell us if the SQL fails instead of crashing!
if (!$insert) {
    die(json_encode(['success' => false, 'error' => 'SQL Prepare Failed: ' . $conn->error]));
}

// Bind 11 variables: iisssssiiis (The last one is 's' for the String "High/Medium/Low")
$insert->bind_param("iisssssiiis", $my_id, $receiver_id, $message_text, $sender_message, $file_path, $file_name, $file_type, $destruct_timer, $reply_to_id, $is_forwarded, $starred_by_sender);

if ($insert->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database execution error: ' . $insert->error]);
}

$insert->close();
