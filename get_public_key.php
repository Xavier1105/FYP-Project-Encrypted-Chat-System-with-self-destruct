<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_GET['username'])) {
    die(json_encode(['error' => 'Invalid request']));
}

$contact_username = trim($_GET['username']);

// Fetch the contact's public key from the database
$stmt = $conn->prepare("SELECT public_key FROM users WHERE username = ?");
$stmt->bind_param("s", $contact_username);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if ($result && !empty($result['public_key'])) {
    echo json_encode(['success' => true, 'public_key' => $result['public_key']]);
} else {
    // If they are still NULL, we can't encrypt a message for them yet!
    echo json_encode(['success' => false, 'error' => 'This user has not generated their secure keys yet.']);
}

$stmt->close();
