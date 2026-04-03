<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

// 1. Security Check
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Unauthorized access.']));
}

$my_id = $_SESSION['user_id'];
$backup_data = [
    'metadata' => [
        'generated_at' => date('Y-m-d H:i:s'),
        'user' => $_SESSION['username'],
        'app' => 'Sentinel Secure Chat'
    ],
    'messages' => []
];

// 2. Fetch all messages belonging to this user
// FIXED: Removed m.priority and replaced it with your actual starred_by_sender / receiver columns!
$query = "
    SELECT 
        m.message_id, m.message, m.sender_message, m.created_at, 
        m.file_name, m.file_type, m.status, m.is_forwarded, 
        m.starred_by_sender, m.starred_by_receiver,
        sender.username AS sender_name,
        receiver.username AS receiver_name
    FROM messages m
    JOIN users sender ON m.sender_id = sender.user_id
    JOIN users receiver ON m.receiver_id = receiver.user_id
    WHERE m.sender_id = ? OR m.receiver_id = ?
    ORDER BY m.created_at ASC
";

$stmt = $conn->prepare($query);

// SAFETY NET: If the database query fails, return a clean JSON error instead of crashing PHP!
if (!$stmt) {
    die(json_encode(['error' => 'Database Query Failed: ' . $conn->error]));
}

$stmt->bind_param("ii", $my_id, $my_id);
$stmt->execute();
$result = $stmt->get_result();

// 3. Format the data perfectly for JSON
while ($row = $result->fetch_assoc()) {

    // Check if the current user is the sender or receiver to grab the correct priority tag
    $my_priority = ($row['sender_name'] === $_SESSION['username']) ? $row['starred_by_sender'] : $row['starred_by_receiver'];

    $backup_data['messages'][] = [
        'id' => $row['message_id'],
        'timestamp' => $row['created_at'],
        'sender' => $row['sender_name'],
        'receiver' => $row['receiver_name'],
        'status' => $row['status'],
        'is_forwarded' => (bool)$row['is_forwarded'],
        'priority' => $my_priority,
        // We export the raw encrypted ciphertext to maintain zero-knowledge security!
        'ciphertext' => ($row['sender_name'] === $_SESSION['username']) ? $row['sender_message'] : $row['message'],
        'attachment' => $row['file_name'] ? ['name' => $row['file_name'], 'type' => $row['file_type']] : null
    ];
}
$stmt->close();

// 4. Send it back to the browser
echo json_encode($backup_data);
