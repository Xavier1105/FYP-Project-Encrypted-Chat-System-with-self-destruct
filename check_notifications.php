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

// ==========================================
// NEW: THE INSTANT KICK GATEKEEPER
// ==========================================
$check_lock = $conn->prepare("SELECT is_locked FROM users WHERE user_id = ?");
$check_lock->bind_param("i", $my_id);
$check_lock->execute();
$user_data = $check_lock->get_result()->fetch_assoc();
$check_lock->close();

if ($user_data['is_locked'] == 1) {
    // Destroy the session on the server side
    session_unset();
    session_destroy();
    
    // Tell the frontend JavaScript to immediately redirect to login!
    echo json_encode(["success" => false, "force_logout" => true]);
    exit();
}
// ==========================================


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