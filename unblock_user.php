<?php
session_start();
require_once 'db_connect.php';

// 1. Security check
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'error' => 'Unauthorized access']));
}

$my_user_id = $_SESSION['user_id'];
$target_username = isset($_POST['target_username']) ? trim($_POST['target_username']) : '';

if (empty($target_username)) {
    die(json_encode(['success' => false, 'error' => 'No target officer specified']));
}

// 2. Find the target officer's user_id
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

// 3. Soft-delete the block record (Keep history, but set to inactive)
$unblock_stmt = $conn->prepare("UPDATE blocked_users SET is_active = 0 WHERE blocker_id = ? AND blocked_id = ?");
$unblock_stmt->bind_param("ii", $my_user_id, $target_user_id);

if ($unblock_stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error during unblock']);
}

$unblock_stmt->close();
