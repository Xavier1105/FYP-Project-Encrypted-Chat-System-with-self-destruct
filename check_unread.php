<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Unauthorized']));
}

$my_id = $_SESSION['user_id'];

// ========================================================
// NEW: THE "DELIVERED" TRIGGER
// Since this user's app just pinged the server, it means 
// they are online. Mark all their waiting messages as delivered!
// ========================================================
$update_delivery = $conn->prepare("UPDATE messages SET status = 'delivered' WHERE receiver_id = ? AND status = 'sent'");
$update_delivery->bind_param("i", $my_id);
$update_delivery->execute();
$update_delivery->close();


// Count all unread messages meant for me, grouped by the sender's username
$sql = "SELECT u.username, COUNT(m.message_id) as unread_count 
        FROM messages m
        JOIN users u ON m.sender_id = u.user_id
        WHERE m.receiver_id = ? AND m.status != 'read'
        GROUP BY u.username";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $my_id);
$stmt->execute();
$result = $stmt->get_result();

$unread_data = [];
while ($row = $result->fetch_assoc()) {
    $unread_data[$row['username']] = $row['unread_count'];
}
$stmt->close();

// Returns a simple list like: {"Abu": 2, "Siti": 1}
echo json_encode($unread_data);
