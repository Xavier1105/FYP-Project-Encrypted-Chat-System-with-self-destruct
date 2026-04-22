<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Unauthorized']));
}

$current_uid = $_SESSION['user_id'];
$stats = ['messages' => '0', 'conversations' => '0'];

// 1. Get Live Message Count (Excluding wiped messages)
$msg_stmt = $conn->prepare("
    SELECT COUNT(*) as msg_count 
    FROM messages 
    WHERE (sender_id = ? AND deleted_by_sender = 0) 
       OR (receiver_id = ? AND deleted_by_receiver = 0)
");
if ($msg_stmt) {
    $msg_stmt->bind_param("ii", $current_uid, $current_uid);
    $msg_stmt->execute();
    $stats['messages'] = number_format($msg_stmt->get_result()->fetch_assoc()['msg_count']);
    $msg_stmt->close();
}

// 2. Get Live Conversation Count (Excluding wiped messages)
$conv_stmt = $conn->prepare("
    SELECT COUNT(DISTINCT CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END) as conv_count 
    FROM messages 
    WHERE (sender_id = ? AND deleted_by_sender = 0) 
       OR (receiver_id = ? AND deleted_by_receiver = 0)
");
if ($conv_stmt) {
    $conv_stmt->bind_param("iii", $current_uid, $current_uid, $current_uid);
    $conv_stmt->execute();
    $stats['conversations'] = number_format($conv_stmt->get_result()->fetch_assoc()['conv_count']);
    $conv_stmt->close();
}

echo json_encode($stats);
