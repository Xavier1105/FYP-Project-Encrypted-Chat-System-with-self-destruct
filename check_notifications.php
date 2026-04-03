<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

// If they aren't logged in, return 0
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'count' => 0]);
    exit();
}

$my_id = $_SESSION['user_id'];

// 1. Fetch Pending Friend Requests
$friend_stmt = $conn->prepare("SELECT COUNT(*) FROM friend_requests WHERE receiver_id = ? AND status = 'pending'");
$friend_stmt->bind_param("i", $my_id);
$friend_stmt->execute();
$friend_stmt->bind_result($pending_friends);
$friend_stmt->fetch();
$friend_stmt->close();

// 2. Fetch Unread Messages
$msg_stmt = $conn->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND status != 'read'");
$msg_stmt->bind_param("i", $my_id);
$msg_stmt->execute();
$msg_stmt->bind_result($unread_msgs);
$msg_stmt->fetch();
$msg_stmt->close();

// 3. Combine and return!
$total_count = $pending_friends + $unread_msgs;

echo json_encode(['success' => true, 'count' => $total_count]);
