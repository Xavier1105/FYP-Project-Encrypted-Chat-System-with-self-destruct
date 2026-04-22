<?php
session_start();
require_once 'db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['message_id']) || !isset($_POST['action'])) {
    die(json_encode(['success' => false, 'error' => 'Invalid request']));
}

$my_id = $_SESSION['user_id'];
$message_id = (int)$_POST['message_id'];
$action = $_POST['action'];

// 1. Verify ownership of the message
$stmt = $conn->prepare("SELECT sender_id, receiver_id FROM messages WHERE message_id = ?");
$stmt->bind_param("i", $message_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) die(json_encode(['success' => false, 'error' => 'Message not found']));
$msg = $res->fetch_assoc();
$stmt->close();

$is_sender = ($msg['sender_id'] == $my_id);
$is_receiver = ($msg['receiver_id'] == $my_id);

if (!$is_sender && !$is_receiver) {
    die(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

// 2. Process the Action
if ($action === 'pin') {
    // --- NEW: MAX 3 PINS RESTRICTION ---
    // First, identify the other officer in this conversation
    $other_id = $is_sender ? $msg['receiver_id'] : $msg['sender_id'];

    // Count how many active pins YOU currently have in this specific chat
    $count_sql = "SELECT COUNT(*) as pin_count FROM messages 
                  WHERE ((sender_id = ? AND receiver_id = ? AND pinned_by_sender_until > NOW()) 
                     OR (sender_id = ? AND receiver_id = ? AND pinned_by_receiver_until > NOW()))";

    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("iiii", $my_id, $other_id, $other_id, $my_id);
    $count_stmt->execute();
    $pin_count = $count_stmt->get_result()->fetch_assoc()['pin_count'];
    $count_stmt->close();

    // If you already have 3, block the request!
    if ($pin_count >= 3) {
        die(json_encode(['success' => false, 'error' => 'MAX_PINS_REACHED']));
    }

    // --- TIME-EXPIRING PIN LOGIC ---
    $hours = isset($_POST['hours']) ? (int)$_POST['hours'] : 24;

    if ($is_sender) {
        $update = $conn->prepare("UPDATE messages SET pinned_by_sender_until = DATE_ADD(NOW(), INTERVAL $hours HOUR) WHERE message_id = ?");
    } else {
        $update = $conn->prepare("UPDATE messages SET pinned_by_receiver_until = DATE_ADD(NOW(), INTERVAL $hours HOUR) WHERE message_id = ?");
    }

    $update->bind_param("i", $message_id);

    if ($update->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database update failed']);
    }
    $update->close();
} else if ($action === 'unpin') {
    // --- UNPIN LOGIC (Set the timer back to NULL) ---
    if ($is_sender) {
        $update = $conn->prepare("UPDATE messages SET pinned_by_sender_until = NULL WHERE message_id = ?");
    } else {
        $update = $conn->prepare("UPDATE messages SET pinned_by_receiver_until = NULL WHERE message_id = ?");
    }
    $update->bind_param("i", $message_id);
    $update->execute();
    $update->close();

    echo json_encode(['success' => true]);
} else if ($action === 'star') {
    // --- BOOKMARK LOGIC ---
    $priority = (isset($_POST['priority']) && $_POST['priority'] !== 'Remove') ? $_POST['priority'] : NULL;

    if ($is_sender) {
        $update = $conn->prepare("UPDATE messages SET starred_by_sender = ? WHERE message_id = ?");
    } else {
        $update = $conn->prepare("UPDATE messages SET starred_by_receiver = ? WHERE message_id = ?");
    }
    $update->bind_param("si", $priority, $message_id);
    $update->execute();
    $update->close();

    echo json_encode(['success' => true]);
}
