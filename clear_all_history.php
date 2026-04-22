<?php
session_start();
require_once 'db_connect.php';

// 1. SECURITY CHECK
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'error' => 'Unauthorized access']));
}

$my_user_id = $_SESSION['user_id'];

// 2. BULK WIPE MESSAGES & STARRED STATUS
// We mark them as deleted AND clear the starred status for the current user

// Logic if I am the SENDER
$stmt1 = $conn->prepare("UPDATE messages SET deleted_by_sender = 1, starred_by_sender = NULL WHERE sender_id = ?");
$stmt1->bind_param("i", $my_user_id);
$stmt1->execute();
$stmt1->close();

// Logic if I am the RECEIVER
$stmt2 = $conn->prepare("UPDATE messages SET deleted_by_receiver = 1, starred_by_receiver = NULL WHERE receiver_id = ?");
$stmt2->bind_param("i", $my_user_id);
$stmt2->execute();
$stmt2->close();

// 3. SMART CLEANUP
// Delete rows permanently if both parties have deleted them and no one has them starred anymore
$clean_stmt = $conn->prepare("DELETE FROM messages WHERE deleted_by_sender = 1 AND deleted_by_receiver = 1 AND starred_by_sender IS NULL AND starred_by_receiver IS NULL");
$clean_stmt->execute();
$clean_stmt->close();

echo json_encode(['success' => true]);
