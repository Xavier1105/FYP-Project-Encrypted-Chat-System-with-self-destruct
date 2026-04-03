<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['message_id'])) {
    die(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

$my_id = $_SESSION['user_id'];
$msg_id = $_POST['message_id'];

// Securely wipe the payload, but leave the row alive as a Tombstone
$stmt = $conn->prepare("UPDATE messages 
                        SET message = '[BURNED]', 
                            sender_message = '[BURNED]', 
                            file_path = NULL, 
                            file_name = NULL, 
                            file_type = NULL, 
                            destruct_timer = 0, 
                            expires_at = NULL 
                        WHERE message_id = ? AND receiver_id = ?");
$stmt->bind_param("ii", $msg_id, $my_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
$stmt->close();
