<?php
session_start();
require_once 'db_connect.php';

// 1. Security Check
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'error' => 'Unauthorized access']));
}

$my_user_id = $_SESSION['user_id'];
$target_username = isset($_POST['target_username']) ? trim($_POST['target_username']) : '';

if (empty($target_username)) {
    die(json_encode(['success' => false, 'error' => 'No target officer specified']));
}

// 2. Find the target user's ID based on their username
$stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
$stmt->bind_param("s", $target_username);
$stmt->execute();
$result = $stmt->get_result();
$target_user = $result->fetch_assoc();
$stmt->close();

if (!$target_user) {
    die(json_encode(['success' => false, 'error' => 'Officer not found']));
}

$target_user_id = $target_user['user_id'];

// 3. SECURE WIPE FOR ME ONLY (Using UPDATE to hide it from your view)

// If I am the sender, mark my side as deleted
$stmt1 = $conn->prepare("UPDATE messages SET deleted_by_sender = 1 WHERE sender_id = ? AND receiver_id = ?");
$stmt1->bind_param("ii", $my_user_id, $target_user_id);
$stmt1->execute();
$stmt1->close();

// If I am the receiver, mark my side as deleted
$stmt2 = $conn->prepare("UPDATE messages SET deleted_by_receiver = 1 WHERE receiver_id = ? AND sender_id = ?");
$stmt2->bind_param("ii", $my_user_id, $target_user_id);
$stmt2->execute();
$stmt2->close();

// 4. SMART CLEANUP (Optional but recommended)
// If BOTH users have now deleted the chat, we can safely wipe it from the database permanently to save space!
$clean_stmt = $conn->prepare("DELETE FROM messages WHERE deleted_by_sender = 1 AND deleted_by_receiver = 1 AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))");
$clean_stmt->bind_param("iiii", $my_user_id, $target_user_id, $target_user_id, $my_user_id);
$clean_stmt->execute();
$clean_stmt->close();

// NEW: If the Bulk Wipe button was clicked, just HIDE them from the left sidebar!
if (isset($_POST['remove_contact']) && $_POST['remove_contact'] == '1') {
    $hide_stmt = $conn->prepare("UPDATE contacts SET is_hidden = 1 WHERE user_id = ? AND contact_id = ?");
    $hide_stmt->bind_param("ii", $my_user_id, $target_user_id);
    $hide_stmt->execute();
    $hide_stmt->close();
}

// Return success to the Javascript!
echo json_encode(['success' => true]);
