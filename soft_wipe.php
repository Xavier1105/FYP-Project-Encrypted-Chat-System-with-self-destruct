<?php
session_start();
require_once 'db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
}

$my_id = $_SESSION['user_id'];

// =========================================================================
// 1. NUKE SENT MESSAGES: Hide from chat, remove bookmarks, and drop pins
// =========================================================================
$stmt1 = $conn->prepare("UPDATE messages 
                         SET deleted_by_sender = 1, 
                             starred_by_sender = '0', 
                             pinned_by_sender_until = NULL 
                         WHERE sender_id = ?");
$stmt1->bind_param("i", $my_id);
$stmt1->execute();
$stmt1->close();

// =========================================================================
// 2. NUKE RECEIVED MESSAGES: Hide from chat, remove bookmarks, and drop pins
// =========================================================================
$stmt2 = $conn->prepare("UPDATE messages 
                         SET deleted_by_receiver = 1, 
                             starred_by_receiver = '0', 
                             pinned_by_receiver_until = NULL 
                         WHERE receiver_id = ?");
$stmt2->bind_param("i", $my_id);
$stmt2->execute();
$stmt2->close();

echo json_encode(['status' => 'success']);
