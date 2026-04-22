<?php
session_start();
require_once 'db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Not logged in']));
}

$my_id = $_SESSION['user_id'];

// Get all messages where the current user has set a priority
$sql = "SELECT m.message_id, m.sender_id, m.receiver_id, m.message, m.sender_message, m.created_at, m.file_path, m.file_name, m.file_type,
        CASE WHEN m.sender_id = ? THEN m.starred_by_sender ELSE m.starred_by_receiver END as priority,
        CASE WHEN m.sender_id = ? THEN u_rec.username ELSE u_sen.username END as contact_name
        FROM messages m
        LEFT JOIN users u_sen ON m.sender_id = u_sen.user_id
        LEFT JOIN users u_rec ON m.receiver_id = u_rec.user_id
        WHERE (m.sender_id = ? AND m.starred_by_sender IS NOT NULL AND m.starred_by_sender != '0') 
           OR (m.receiver_id = ? AND m.starred_by_receiver IS NOT NULL AND m.starred_by_receiver != '0')
        ORDER BY m.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $my_id, $my_id, $my_id, $my_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}
$stmt->close();

echo json_encode($messages);
