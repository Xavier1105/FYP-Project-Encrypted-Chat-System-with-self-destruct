<?php
session_start();
require_once 'db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['message_id']) || !isset($_POST['type'])) {
    die(json_encode(['success' => false, 'error' => 'Invalid request']));
}

$my_id = $_SESSION['user_id'];
$message_id = (int)$_POST['message_id'];
$type = $_POST['type']; // 'me' or 'everyone'

// 1. Get the message details to verify ownership
$stmt = $conn->prepare("SELECT sender_id, receiver_id, file_path FROM messages WHERE message_id = ?");
$stmt->bind_param("i", $message_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) die(json_encode(['success' => false, 'error' => 'Message not found']));
$msg = $res->fetch_assoc();
$stmt->close();

$is_sender = ($msg['sender_id'] == $my_id);
$is_receiver = ($msg['receiver_id'] == $my_id);

if (!$is_sender && !$is_receiver) {
    die(json_encode(['success' => false, 'error' => 'Unauthorized access.']));
}

// 2. Process "Delete for Everyone" (Only Sender can do this)
if ($type === 'everyone') {
    if (!$is_sender) {
        die(json_encode(['success' => false, 'error' => 'Only the sender can delete for everyone.']));
    }

    // Wipe the encrypted payloads and set the flag!
    $update = $conn->prepare("UPDATE messages SET is_deleted_everyone = 1, file_path = NULL, message = '', sender_message = '' WHERE message_id = ?");
    $update->bind_param("i", $message_id);
    $update->execute();

    // Physically delete the attachment file from the server if it exists!
    if (!empty($msg['file_path']) && file_exists($msg['file_path'])) {
        unlink($msg['file_path']);
    }

    echo json_encode(['success' => true]);

    // 3. Process "Delete for Me"
} else if ($type === 'me') {
    if ($is_sender) {
        $update = $conn->prepare("UPDATE messages SET deleted_by_sender = 1 WHERE message_id = ?");
    } else {
        $update = $conn->prepare("UPDATE messages SET deleted_by_receiver = 1 WHERE message_id = ?");
    }
    $update->bind_param("i", $message_id);
    $update->execute();

    echo json_encode(['success' => true]);
}
