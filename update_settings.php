<?php
session_start();
require_once 'db_connect.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized access.']));
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// ==========================================
// 1. HANDLE PRIVACY TOGGLES (AJAX/Fetch)
// ==========================================
if ($action === 'toggle_privacy') {
    $setting_name = $_POST['setting_name'];
    $setting_value = (int)$_POST['setting_value'];

    // SECURITY: Strictly restrict which columns can be updated via this function
    $allowed_settings = ['show_last_seen', 'is_discoverable', 'read_receipts'];

    if (in_array($setting_name, $allowed_settings)) {
        $stmt = $conn->prepare("UPDATE users SET $setting_name = ? WHERE user_id = ?");
        $stmt->bind_param("ii", $setting_value, $user_id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error.']);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid setting.']);
    }
    exit;
}

// ==========================================
// 2. HANDLE BACKGROUND UPLOAD (AJAX/Fetch)
// ==========================================
if ($action === 'update_background') {
    if (isset($_FILES['chat_background']) && $_FILES['chat_background']['error'] === 0) {
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['chat_background']['name'];
        $file_tmp = $_FILES['chat_background']['tmp_name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // Validate file type
        if (!in_array($ext, $allowed_exts)) {
            echo json_encode(['status' => 'error', 'message' => 'Only JPG, PNG, and WEBP files are allowed.']);
            exit;
        }

        // Ensure the upload directory exists
        $upload_dir = 'uploads/backgrounds/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Generate a unique filename to prevent overwriting
        $new_filename = 'bg_' . $user_id . '_' . time() . '.' . $ext;
        $destination = $upload_dir . $new_filename;

        // Move the file and save the path to the database
        if (move_uploaded_file($file_tmp, $destination)) {
            $stmt = $conn->prepare("UPDATE users SET chat_background = ? WHERE user_id = ?");
            $stmt->bind_param("si", $destination, $user_id);

            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'filepath' => $destination]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to save to database.']);
            }
            $stmt->close();
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to move uploaded file. Check folder permissions.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No file selected or upload failed.']);
    }
    exit;
}

// ==========================================
// 2.5 HANDLE PRESET BACKGROUND COLOR (AJAX/Fetch)
// ==========================================
if ($action === 'update_background_color') {
    $color = $_POST['color'] ?? '';

    // Quick security check: make sure it is actually a valid hex color code (like #1e293b)
    if (preg_match('/^#[a-fA-F0-9]{6}$/', $color)) {

        // Save the color directly into the chat_background column
        $stmt = $conn->prepare("UPDATE users SET chat_background = ? WHERE user_id = ?");
        $stmt->bind_param("si", $color, $user_id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'filepath' => $color]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error.']);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid color code.']);
    }
    exit;
}

// ==========================================
// 2.6 HANDLE REMOVE BACKGROUND (AJAX/Fetch)
// ==========================================
if ($action === 'remove_background') {
    // Set the column back to NULL to remove the custom background/color
    $stmt = $conn->prepare("UPDATE users SET chat_background = NULL WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
    $stmt->close();
    exit;
}

// ==========================================
// 3. HANDLE PASSWORD CHANGE (AJAX)
// ==========================================
if ($action === 'change_password') {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if ($new_pass !== $confirm_pass) {
        echo json_encode(['status' => 'error', 'message' => 'New passwords do not match.']);
        exit;
    }

    // Fetch current password hash from DB
    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    // Verify current password
    if (password_verify($current_pass, $user['password_hash'])) {
        // Hash the new password and update
        $new_hash = password_hash($new_pass, PASSWORD_BCRYPT);
        $update_stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
        $update_stmt->bind_param("si", $new_hash, $user_id);

        if ($update_stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Password successfully updated!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error. Please try again.']);
        }
        $update_stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Current password is incorrect.']);
    }
    exit;
}

// ==========================================
// 4. HANDLE PANIC MODE TOGGLE (AJAX/Fetch)
// ==========================================
if ($action === 'toggle_panic_mode') {
    $setting_value = isset($_POST['setting_value']) ? (int)$_POST['setting_value'] : 0;

    $stmt = $conn->prepare("UPDATE users SET panic_mode = ? WHERE user_id = ?");
    $stmt->bind_param("ii", $setting_value, $user_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
    $stmt->close();
    exit;
}

// Fallback if no valid action was provided
echo json_encode(['status' => 'error', 'message' => 'Invalid action requested.']);
