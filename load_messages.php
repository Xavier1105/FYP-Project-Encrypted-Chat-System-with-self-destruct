<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_GET['username'])) {
    die(json_encode(['error' => 'Invalid request']));
}

$my_id = $_SESSION['user_id'];
$contact_username = trim($_GET['username']);

// 1. Get contact ID
$stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
$stmt->bind_param("s", $contact_username);
$stmt->execute();
$contact = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$contact) {
    die(json_encode(['error' => 'User not found']));
}
$contact_id = $contact['user_id'];

// ========================================================
// PHASE 1: THE DETONATOR & THE SWEEPER
// ========================================================
// 1. Securely wipe expired messages (Replace payload with [BURNED] Tombstone)
$conn->query("UPDATE messages 
              SET message = '[BURNED]', 
                  sender_message = '[BURNED]', 
                  file_path = NULL, 
                  file_name = NULL, 
                  file_type = NULL, 
                  destruct_timer = 0, 
                  expires_at = NULL 
              WHERE expires_at IS NOT NULL AND expires_at <= NOW()");

// 2. The Pin Sweeper 
$conn->query("UPDATE messages SET pinned_by_sender_until = NULL WHERE pinned_by_sender_until <= NOW()");
$conn->query("UPDATE messages SET pinned_by_receiver_until = NULL WHERE pinned_by_receiver_until <= NOW()");


// ========================================================
// PHASE 2: THE TRIGGER (Bulletproof Countdown Starter)
// ========================================================

// 1. ONLY start timers automatically for time-based messages (> 0). 
// The -1 (Burn on Read) messages will wait for the user to click them!
$trigger_sql = "UPDATE messages 
                SET expires_at = DATE_ADD(NOW(), INTERVAL destruct_timer SECOND)
                WHERE sender_id = ? AND receiver_id = ? AND destruct_timer > 0 AND expires_at IS NULL";

$trigger = $conn->prepare($trigger_sql);
$trigger->bind_param("ii", $contact_id, $my_id);
$trigger->execute();
$trigger->close();


// 2. ONLY send the blue ticks if their privacy settings allow it
$stmt_me = $conn->prepare("SELECT read_receipts FROM users WHERE user_id = ?");
$stmt_me->bind_param("i", $my_id);
$stmt_me->execute();
$my_info = $stmt_me->get_result()->fetch_assoc();
$my_read_receipts = isset($my_info['read_receipts']) ? $my_info['read_receipts'] : 1;
$stmt_me->close();

if ($my_read_receipts == 1) {
    $update = $conn->prepare("UPDATE messages SET status = 'read' WHERE sender_id = ? AND receiver_id = ? AND status != 'read'");
    $update->bind_param("ii", $contact_id, $my_id);
    $update->execute();
    $update->close();
}

// ========================================================
// PHASE 3: FETCH SURVIVORS (Get the messages that haven't blown up)
// ========================================================
$sql = "SELECT m.message_id, m.sender_id, m.message, m.sender_message, m.status, m.created_at, 
               m.file_path, m.file_name, m.file_type, m.reply_to_id, m.deleted_by_sender, 
               m.deleted_by_receiver, m.is_deleted_everyone, m.is_forwarded, m.starred_by_sender, 
               m.starred_by_receiver, m.pinned_by_sender_until, m.pinned_by_receiver_until, 
               m.destruct_timer, m.expires_at, m.is_edited, /* <--- ADDED m.is_edited HERE */
               u.username as sender_username 
        FROM messages m
        JOIN users u ON m.sender_id = u.user_id
        
        /* NEW: Only fetch messages where the current user hasn't flagged them as deleted! */
        WHERE (
            (m.sender_id = ? AND m.receiver_id = ? AND m.deleted_by_sender = 0) 
            OR 
            (m.sender_id = ? AND m.receiver_id = ? AND m.deleted_by_receiver = 0)
        )
        ORDER BY m.created_at ASC";

$fetch = $conn->prepare($sql);
$fetch->bind_param("iiii", $my_id, $contact_id, $contact_id, $my_id);
$fetch->execute();
$result = $fetch->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    // Check if I am the sender or receiver
    $is_sender = ($row['sender_id'] == $my_id);

    // Grab the pin expiration based on my role
    $pin_until = $is_sender ? $row['pinned_by_sender_until'] : $row['pinned_by_receiver_until'];

    // Because our "Pin Sweeper" at the top of the file already wiped expired pins,
    // if $pin_until is not NULL, we are 100% sure it is currently pinned!
    $row['is_pinned_for_me'] = ($pin_until !== null) ? 1 : 0;

    $messages[] = $row;
}
$fetch->close();

echo json_encode($messages);
