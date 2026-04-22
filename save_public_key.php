<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

// Read the raw input from the fetch request
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (isset($_SESSION['user_id']) && isset($data['public_key'])) {
    $my_id = $_SESSION['user_id'];
    $new_key = $data['public_key'];

    // Clean any accidental quotes
    $clean_key = str_replace(['"', '\\'], '', $new_key);

    // 1. Update the Public Key so people can send you new messages
    $stmt = $conn->prepare("UPDATE users SET public_key = ? WHERE user_id = ?");
    $stmt->bind_param("si", $clean_key, $my_id);

    if ($stmt->execute()) {
        // --- 🚨 REMOVED THE WIPE LOGIC HERE 🚨 ---
        // By removing the UPDATE messages SET deleted = 1 queries, 
        // your history will remain visible when you import your backup!

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid Request Structure']);
}
