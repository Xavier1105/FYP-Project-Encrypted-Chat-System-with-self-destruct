<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

// 1. Security Check
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Unauthorized access.']));
}

// 2. Validate the uploaded file
if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
    die(json_encode(['error' => 'No valid file was uploaded.']));
}

$json_content = file_get_contents($_FILES['backup_file']['tmp_name']);
$backup_data = json_decode($json_content, true);

// 3. Verify it's a Sentinel Backup
if (!$backup_data || !isset($backup_data['metadata']) || !isset($backup_data['messages'])) {
    die(json_encode(['error' => 'Invalid backup file format.']));
}

// 4. PREVENT HACKING: Ensure they aren't uploading someone else's backup!
if ($backup_data['metadata']['user'] !== $_SESSION['username']) {
    die(json_encode(['error' => 'Security Block: This backup file belongs to a different user account.']));
}

$my_id = $_SESSION['user_id'];
$my_username = $_SESSION['username'];
$restored_count = 0;

// Cache user IDs to prevent hammering the database with queries
$user_id_cache = [$my_username => $my_id];

function getUserId($username, $conn, &$cache)
{
    if (isset($cache[$username])) return $cache[$username];
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $cache[$username] = $row['user_id'];
        return $row['user_id'];
    }
    return null;
}

// 5. Loop through the backup and restore missing messages
foreach ($backup_data['messages'] as $msg) {
    $sender_id = getUserId($msg['sender'], $conn, $user_id_cache);
    $receiver_id = getUserId($msg['receiver'], $conn, $user_id_cache);

    // If either user was permanently deleted from the database, skip the message
    if (!$sender_id || !$receiver_id) continue;

    // Convert the MYT formatted time back into a standard database format
    $timestamp = date('Y-m-d H:i:s', strtotime($msg['timestamp']));

    $ciphertext = $msg['ciphertext'];
    $priority = $msg['priority'] ?? 'Low';
    $file_name = $msg['attachment']['name'] ?? null;
    $file_type = $msg['attachment']['type'] ?? null;

    // Check if this exact message already exists in the database
    $check_stmt = $conn->prepare("SELECT message_id FROM messages WHERE sender_id = ? AND receiver_id = ? AND created_at = ?");
    $check_stmt->bind_param("iis", $sender_id, $receiver_id, $timestamp);
    $check_stmt->execute();
    $check_res = $check_stmt->get_result();

    if ($check_res->num_rows > 0) {
        // Message exists! Check if it was hidden, and UN-HIDE it!
        $row = $check_res->fetch_assoc();
        $msg_id = $row['message_id'];

        if ($msg['sender'] === $my_username) {
            // Restore for Sender: Un-delete it, and fill in ciphertext if it was missing
            $upd = $conn->prepare("UPDATE messages SET sender_message = COALESCE(sender_message, ?), starred_by_sender = COALESCE(starred_by_sender, ?), deleted_by_sender = 0 WHERE message_id = ?");
            $upd->bind_param("ssi", $ciphertext, $priority, $msg_id);
            $upd->execute();
            if ($upd->affected_rows > 0) $restored_count++;
        } else {
            // Restore for Receiver: Un-delete it, and fill in ciphertext if it was missing
            $upd = $conn->prepare("UPDATE messages SET message = COALESCE(message, ?), starred_by_receiver = COALESCE(starred_by_receiver, ?), deleted_by_receiver = 0 WHERE message_id = ?");
            $upd->bind_param("ssi", $ciphertext, $priority, $msg_id);
            $upd->execute();
            if ($upd->affected_rows > 0) $restored_count++;
        }
    } else {
        // Message doesn't exist at all. Insert it fresh!
        $msg_col = ($msg['receiver'] === $my_username) ? $ciphertext : null;
        $snd_col = ($msg['sender'] === $my_username) ? $ciphertext : null;

        $star_rx = ($msg['receiver'] === $my_username) ? $priority : null;
        $star_tx = ($msg['sender'] === $my_username) ? $priority : null;

        $ins = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, sender_message, created_at, file_name, file_type, starred_by_receiver, starred_by_sender, deleted_by_sender, deleted_by_receiver) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0)");
        $ins->bind_param("iisssssss", $sender_id, $receiver_id, $msg_col, $snd_col, $timestamp, $file_name, $file_type, $star_rx, $star_tx);
        $ins->execute();
        if ($ins->affected_rows > 0) $restored_count++;
    }
}

// 6. Return the success report
echo json_encode(['success' => true, 'restored_count' => $restored_count]);
