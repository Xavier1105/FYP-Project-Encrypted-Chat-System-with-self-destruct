<?php
session_start();

// 1. STRICT SECURITY CHECK
// Only allow the Head of Security to update the Master Padlock
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'hos') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access. Only HOS can update the Master Key.']);
    exit();
}

// 2. RECEIVE THE DATA
// Read the raw JSON data sent by your setup_master_key.php JavaScript
$json_input = file_get_contents('php://input');
$data = json_decode($json_input, true);

// 3. SAVE TO FOLDER
if (isset($data['public_key']) && !empty(trim($data['public_key']))) {
    
    // This function automatically creates master_public_key.txt if it doesn't exist,
    // and overwrites it safely if it already exists!
    $bytes_written = file_put_contents('master_public_key.txt', trim($data['public_key']));
    
    if ($bytes_written !== false) {
        echo json_encode(['success' => true]);
    } else {
        // If XAMPP denies permission to write the file
        echo json_encode(['success' => false, 'error' => 'Server permission denied. Could not write file.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'No public key data received.']);
}
?>