<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$browser_public_key = isset($data['public_key']) ? trim($data['public_key']) : '';
$current_session = session_id();

// Fetch the public key AND the active session ID
$stmt = $conn->prepare("SELECT public_key, active_session_id FROM users WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// 1. DEVICE CONFLICT: Check if another browser claimed the session
if (!empty($user['active_session_id']) && $user['active_session_id'] !== $current_session) {
    echo json_encode(['status' => 'device_conflict']);
    $stmt->close();
    exit();
}

// 2. OUTDATED KEY: Check if keys were rotated
if ($browser_public_key !== trim($user['public_key'])) {
    echo json_encode(['status' => 'outdated']);
} else {
    echo json_encode(['status' => 'valid']);
}
$stmt->close();
