<?php
session_start();
require_once 'db_connect.php';

// Security check
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'error' => 'Unauthorized access']));
}

$my_user_id = $_SESSION['user_id'];
$target_username = isset($_POST['target_username']) ? trim($_POST['target_username']) : '';

if (empty($target_username)) {
    die(json_encode(['success' => false, 'error' => 'No target officer specified']));
}

// Find the target officer's user_id
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

// Prevent blocking yourself
if ($my_user_id === $target_user_id) {
    die(json_encode(['success' => false, 'error' => 'You cannot block yourself']));
}

// Insert or Update blocked_users table (Handles re-blocking a previously unblocked user)
$block_query = "INSERT INTO blocked_users (blocker_id, blocked_id, is_active) 
                VALUES (?, ?, 1) 
                ON DUPLICATE KEY UPDATE is_active = 1, created_at = CURRENT_TIMESTAMP";

$block_stmt = $conn->prepare($block_query);
$block_stmt->bind_param("ii", $my_user_id, $target_user_id);

if ($block_stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error during block']);
}

$block_stmt->close();
