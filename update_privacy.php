<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['setting']) || !isset($_POST['value'])) {
    die(json_encode(['success' => false, 'error' => 'Invalid request']));
}

$user_id = $_SESSION['user_id'];
$setting = $_POST['setting'];
$value = intval($_POST['value']); // 1 for ON, 0 for OFF

// Security check: Only allow updating these specific columns!
$allowed_settings = ['show_last_seen', 'is_discoverable', 'read_receipts'];

if (!in_array($setting, $allowed_settings)) {
    die(json_encode(['success' => false, 'error' => 'Invalid privacy setting']));
}

// Update the database
$stmt = $conn->prepare("UPDATE users SET $setting = ? WHERE user_id = ?");
$stmt->bind_param("ii", $value, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database update failed']);
}
$stmt->close();
