<?php
session_start();
require_once 'db_connect.php';

// 1. SECURITY CHECK
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// (Admin block removed so they can load this page)

// 2. INCLUDE LOGIC
require_once 'contact_controller.php';

// ENTERPRISE COLD STORAGE: Hardcoded Master Audit Public Key
$hos_public_key_str = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAwSVgeGxo4/vxgnZUxVaV+WWzQ9rXIyzAxCzCTedF24wfSlbO0IZF/fM5NKfd7fWmm9n+9qI/VaW2XyxhZqZba42DWJ67XAri0isN+oNR58YDdfmB9vPkgjNOXcUtxO6k/Dfpj6BAUwiN/m22EaTp6Mp57ZjAk/Q3uQAuWdXjgU4uVEtJjv21L/5uFA1+SVAEvQASaW8NlcuDdXYoK9dvBeAQ8Nf+NZ1PQXP+axxcIziKED0MvoJ5Bk4nLKwKvdXq5jkOfvuKGjczVH9vd3TNmWf2Mw//QnbAWuZx+nw/1OaUZTL/Rtt77oC+c4pJNHCSiR5WHMsXxYEtKWnQwOhQUQIDAQAB";

// Check if the current user already has a public key stored
$key_check_stmt = $conn->prepare("SELECT public_key FROM users WHERE user_id = ?");
$key_check_stmt->bind_param("i", $_SESSION['user_id']);
$key_check_stmt->execute();
$key_res = $key_check_stmt->get_result()->fetch_assoc();
$has_public_key = !empty($key_res['public_key']) ? 'true' : 'false';
$key_check_stmt->close();

// --- NEW: EDIT PROFILE LOGIC ---
$show_profile_on_load = false; // <-- ADD THIS LINE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $show_profile_on_load = true; // <-- ADD THIS LINE

    $new_username = trim($_POST['new_username']);
    $new_position = trim($_POST['new_position']); // NEW: Grab position
    $uid = $_SESSION['user_id'];
    $update_msg = "";

    // 1. Handle File Upload (Profile Picture)
    $profile_pic_path = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_picture']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            if (!is_dir('uploads')) mkdir('uploads', 0777, true);
            $new_filename = "avatar_" . $uid . "_" . time() . "." . $ext;
            $destination = "uploads/" . $new_filename;

            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $destination)) {
                $profile_pic_path = $destination;
            }
        } else {
            $alert_msg = "<div class='alert alert-danger'>Invalid image format. Only JPG, PNG, and GIF are allowed.</div>";
        }
    }

    // 2. Update Database (Removed officer_id, Added position)
    if ($profile_pic_path) {
        $stmt = $conn->prepare("UPDATE users SET username=?, position=?, profile_picture=? WHERE user_id=?");
        $stmt->bind_param("sssi", $new_username, $new_position, $profile_pic_path, $uid);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username=?, position=? WHERE user_id=?");
        $stmt->bind_param("ssi", $new_username, $new_position, $uid);
    }

    if ($stmt->execute()) {
        $_SESSION['username'] = $new_username;
        $my_username = $new_username;
        // UPDATED: Modern success pill with check icon and close button
        $alert_msg = "<div class='alert alert-success alert-dismissible fade show shadow-sm text-center mb-0 py-2' role='alert'><i class='bi bi-check-circle-fill me-2'></i>Profile updated successfully!<button type='button' class='btn-close btn-sm pt-2' data-bs-dismiss='alert'></button></div>";
    } else {
        // UPDATED: Modern error pill with exclamation icon and close button
        $alert_msg = "<div class='alert alert-danger alert-dismissible fade show shadow-sm text-center mb-0 py-2' role='alert'><i class='bi bi-exclamation-circle-fill me-2'></i>Error updating profile.<button type='button' class='btn-close btn-sm pt-2' data-bs-dismiss='alert'></button></div>";
    }
    $stmt->close();
}

// --- FETCH MY PROFILE DATA (Added position to SELECT) ---
$my_info_sql = "SELECT * FROM users WHERE user_id = ?";
$stmt_info = $conn->prepare($my_info_sql);
$stmt_info->bind_param("i", $_SESSION['user_id']);
$stmt_info->execute();
$my_info = $stmt_info->get_result()->fetch_assoc();
$stmt_info->close();
// ----------------------------------
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light" id="htmlTag">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sentinel - Secure Chat</title>

    <script>
        // Check browser memory BEFORE the page even loads!
        const CHECK_USER_ID = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null'; ?>;

        if (CHECK_USER_ID) {
            const CHECK_KEY_NAME = 'sentinel_private_key_' + CHECK_USER_ID;

            if (!localStorage.getItem(CHECK_KEY_NAME)) {
                // NO KEY FOUND! Instantly kick them to the Secure Gate.
                window.location.replace('secure_gate.php');
            } else {
                // KEY FOUND! Claim this browser as the one true active device!
                fetch('claim_session.php');
            }
        } else {
            // Not even logged in with PHP
            window.location.replace('login.php');
        }
    </script>
    <link rel="icon" type="image/png" href="Sentinel logo.png">

    <script>
        const earlyTheme = localStorage.getItem('sentinel_theme');
        if (earlyTheme === 'dark') {
            document.documentElement.setAttribute('data-bs-theme', 'dark');
        } else if (earlyTheme === 'light') {
            document.documentElement.setAttribute('data-bs-theme', 'light');
        }
    </script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        /* LAYOUT & SCROLLING */
        body {
            background-color: var(--bs-body-bg);
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            font-family: 'Segoe UI', sans-serif;
        }

        .navbar {
            background-color: #1a2e44;
        }

        .chat-container {
            display: flex;
            flex: 1;
            height: calc(100vh - 60px);
        }

        /* SIDEBAR */
        .sidebar {
            width: 350px;
            border-right: 1px solid var(--bs-border-color);
            display: flex;
            flex-direction: column;
            background-color: var(--bs-body-bg);
        }

        .sidebar-header {
            padding: 10px 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--bs-border-color);
            height: 60px;
        }

        .contact-list {
            overflow-y: auto;
            flex: 1;
        }

        /* 1. The base contact item */
        .contact-item {
            padding: 12px 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            cursor: pointer;
            transition: all 0.2s ease;

            /* Nice rounded pill shape with side margins */
            border-radius: 15px !important;
            margin: 4px 12px !important;
            border: none !important;
            /* Removes any old lines */
        }

        /* 2. Hover effect (when NOT active) */
        .contact-item:hover:not(.active) {
            background-color: var(--bs-tertiary-bg);
        }

        /* 3. The Active State - BOLD SOLID CYAN */
        .contact-item.active {
            background-color: #0dcaf0 !important;
            /* Bold solid cyan */
            box-shadow: 0 4px 12px rgba(13, 202, 240, 0.4) !important;
            /* Soft cyan glowing shadow */
            transform: translateY(-1px);
            /* Lifts it up slightly */
        }

        /* 4. Fix text colors inside the bold cyan block for perfect contrast */
        .contact-item.active .fw-bold,
        .contact-item.active small,
        .contact-item.active .text-muted,
        .contact-item.active .text-success {
            color: #ffffff !important;
            /* Forces all text to white so it's readable */
        }

        .avatar {
            width: 45px;
            height: 45px;
            background: #e9ecef;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 15px;
            color: #6c757d;
        }

        /* CHAT AREA (RIGHT SIDE) */
        .chat-area {
            flex: 1;
            background-color: var(--bs-secondary-bg);
            display: flex;
            flex-direction: column;
            position: relative;
            height: 100%;
            /* Ensure it fills height */
        }

        /* --- SIDEBAR MENU STYLES (Smart Light/Dark Mode) --- */
        .menu-link {
            border-radius: 8px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            padding: 10px 15px;
            text-decoration: none;
            color: var(--bs-body-color);
            /* Automatically black in light mode, white in dark mode */
            opacity: 0.75;
        }

        .menu-link:hover {
            opacity: 1;
            background-color: var(--bs-secondary-bg);
            /* Gentle theme-aware hover background */
        }

        /* The Icon is ALWAYS cyan when active */
        .menu-link.active i {
            color: #0dcaf0 !important;
        }

        /* --- LIGHT MODE ACTIVE STATE --- */
        [data-bs-theme="light"] .menu-link.active {
            background-color: rgba(13, 202, 240, 0.1) !important;
            /* Very soft cyan background */
            color: #212529 !important;
            /* Dark/Black text */
            font-weight: bold;
            opacity: 1;
        }

        /* --- DARK MODE ACTIVE STATE --- */
        [data-bs-theme="dark"] .menu-link.active {
            background-color: rgba(13, 202, 240, 0.15) !important;
            /* Deeper cyan-tinted background */
            color: #0dcaf0 !important;
            /* Bright cyan text */
            font-weight: bold;
            opacity: 1;
        }

        .sentinel-icon {
            font-size: 1.2rem;
            margin-right: 15px;
            width: 25px;
            text-align: center;
            color: #6c757d;
            transition: color 0.3s ease;
        }

        .menu-link:hover .sentinel-icon {
            color: rgb(34, 211, 238) !important;
        }

        .menu-link.active .sentinel-icon {
            color: rgb(34, 211, 238) !important;
        }

        .text-online {
            color: #2ecc71 !important;
            font-weight: 600;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
        }

        .text-online::before {
            content: "●";
            margin-right: 5px;
            font-size: 0.7rem;
        }

        .profile-avatar {
            width: 50px;
            height: 50px;
            background-color: rgb(34, 211, 238);
            color: white;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 15px;
            font-weight: bold;
        }

        /* CHAT INTERFACE ELEMENTS */
        #activeChatInterface {
            display: none;
            /* Hidden by default until user clicked */
            flex-direction: column;
            height: 100%;
            width: 100%;
        }

        .chat-header {
            padding: 10px 20px;
            background-color: var(--bs-body-bg);
            border-bottom: 1px solid var(--bs-border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 60px;
            flex-shrink: 0;
        }

        /* Messages Box expands to fill space */
        .messages-box {
            flex-grow: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .input-area {
            padding: 15px 20px;
            background-color: var(--bs-body-bg);
            border-top: 1px solid var(--bs-border-color);
            flex-shrink: 0;
        }

        .input-group-chat {
            background-color: var(--bs-tertiary-bg);
            border-radius: 20px;
            padding: 5px 15px;
            display: flex;
            align-items: center;
        }

        .chat-input {
            border: none;
            background: transparent;
            width: 100%;
            padding: 10px;
            outline: none;
            color: var(--bs-body-color);
        }

        /* Custom Secure Chat Button with Hover Effect */
        .btn-secure-chat {
            background: linear-gradient(135deg, #0dcaf0, #0d6efd);
            color: white;
            border: none;
            border-radius: 8px;
            transition: all 0.2s ease-in-out;
        }

        .btn-secure-chat:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(13, 110, 253, 0.4) !important;
            background: linear-gradient(135deg, #0cbee3, #0b5ed7);
        }

        .btn-secure-chat:active {
            transform: translateY(0);
            box-shadow: 0 2px 5px rgba(13, 110, 253, 0.4) !important;
        }

        /* Tactical Options Hover Effects (Dossier Panel) */
        .tactical-btn {
            padding: 10px 12px !important;
            border-radius: 8px;
            margin-left: -12px;
            /* Pulls it left so the hover box aligns perfectly */
            width: calc(100% + 24px) !important;
            transition: all 0.2s ease-in-out;
        }

        .tactical-btn-export:hover {
            background-color: rgba(108, 117, 125, 0.08);
            /* Faint gray */
            transform: translateX(6px);
        }

        .tactical-btn-wipe:hover {
            background-color: rgba(255, 193, 7, 0.1);
            /* Faint yellow */
            transform: translateX(6px);
        }

        .tactical-btn-block:hover {
            background-color: rgba(220, 53, 69, 0.08);
            /* Faint red */
            transform: translateX(6px);
        }

        /* Custom Context Menu Styling */
        #chatContextMenu .dropdown-item {
            font-size: 0.95rem;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        #chatContextMenu .dropdown-item:hover {
            background-color: var(--bs-secondary-bg);
        }

        /* Make sure the chat messages feel clickable */
        .message-content {
            cursor: context-menu;
        }

        /* ========================================== */
        /* TACTICAL MESSAGE ACTIONS MODAL STYLES      */
        /* ========================================== */
        .action-menu-item {
            border: none !important;
            background-color: transparent !important;
            border-radius: 12px !important;
            margin-bottom: 2px;
            padding: 8px 12px !important;
            display: flex;
            align-items: center;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .action-icon-box {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* --- Individual Tactical Colors (Theme-Aware) --- */
        /* Reply (Blue) */
        .item-reply {
            color: #0ea5e9 !important;
        }

        .item-reply .action-icon-box {
            background-color: rgba(14, 165, 233, 0.15);
            color: #0ea5e9;
        }

        .item-reply:hover {
            background-color: rgba(14, 165, 233, 0.1) !important;
        }

        /* Copy (Grey) - Darkened slightly for Light Mode visibility */
        .item-copy {
            color: #64748b !important;
        }

        .item-copy .action-icon-box {
            background-color: rgba(100, 116, 139, 0.15);
            color: #64748b;
        }

        .item-copy:hover {
            background-color: rgba(100, 116, 139, 0.1) !important;
        }

        /* Edit (Indigo) */
        .item-edit {
            color: #6366f1 !important;
        }

        .item-edit .action-icon-box {
            background-color: rgba(99, 102, 241, 0.15);
            color: #6366f1;
        }

        .item-edit:hover {
            background-color: rgba(99, 102, 241, 0.1) !important;
        }

        /* Forward (Green) */
        .item-forward {
            color: #10b981 !important;
        }

        .item-forward .action-icon-box {
            background-color: rgba(16, 185, 129, 0.15);
            color: #10b981;
        }

        .item-forward:hover {
            background-color: rgba(16, 185, 129, 0.1) !important;
        }

        /* Pin (Purple) */
        .item-pin {
            color: #a855f7 !important;
        }

        .item-pin .action-icon-box {
            background-color: rgba(168, 85, 247, 0.15);
            color: #a855f7;
        }

        .item-pin:hover {
            background-color: rgba(168, 85, 247, 0.1) !important;
        }

        /* Bookmark (Orange) */
        .item-bookmark {
            color: #f97316 !important;
        }

        .item-bookmark .action-icon-box {
            background-color: rgba(249, 115, 22, 0.15);
            color: #f97316;
        }

        .item-bookmark:hover {
            background-color: rgba(249, 115, 22, 0.1) !important;
        }

        /* Delete Me (Red) */
        .item-delete {
            color: #ef4444 !important;
        }

        .item-delete .action-icon-box {
            background-color: rgba(239, 68, 68, 0.15);
            color: #ef4444;
        }

        .item-delete:hover {
            background-color: rgba(239, 68, 68, 0.1) !important;
        }

        /* Delete Everyone (Darker Red) */
        .item-delete-all {
            color: #dc2626 !important;
        }

        .item-delete-all .action-icon-box {
            background-color: rgba(220, 38, 38, 0.15);
            color: #dc2626;
        }

        .item-delete-all:hover {
            background-color: rgba(220, 38, 38, 0.1) !important;
        }

        /* FIXED: Theme-Aware Cancel Button */
        .cancel-btn {
            background-color: rgba(128, 128, 128, 0.1);
            border: 1px solid rgba(128, 128, 128, 0.2);
            color: var(--bs-body-color);
            border-radius: 12px;
            transition: all 0.2s ease;
        }

        .cancel-btn:hover {
            background-color: rgba(128, 128, 128, 0.2);
            color: var(--bs-body-color) !important;
        }

        /* Flashing Online Status Dot */
        @keyframes fadeBlink {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.2;
            }

            100% {
                opacity: 1;
            }
        }

        .status-blink {
            animation: fadeBlink 1.5s ease-in-out infinite;
        }

        /* ========================================= */
        /* CIRCULAR SEND BUTTON (Hover & Glow)       */
        /* ========================================= */
        .btn-send-circular {
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0dcaf0, #0d6efd);
            color: white;
            border: none;
            border-radius: 50%;
            transition: all 0.2s ease;
            box-shadow: 0 2px 5px rgba(13, 110, 253, 0.4);
        }

        .btn-send-circular:hover {
            transform: scale(1.05) translateY(-2px);
            /* Slightly enlarges and floats up */
            box-shadow: 0 6px 15px rgba(13, 110, 253, 0.6);
            /* Brighter blue glow */
            color: white;
        }

        .btn-send-circular:active {
            transform: scale(1) translateY(0);
            /* Snaps back when clicked */
            box-shadow: 0 2px 5px rgba(13, 110, 253, 0.4);
        }

        /* ========================================= */
        /* CUSTOM SELF-DESTRUCT MENU STYLES          */
        /* ========================================= */
        .destruct-menu-container {
            position: relative;
        }

        .destruct-btn-toggle {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 20px;
            background-color: var(--bs-secondary-bg);
            border: 1px solid var(--bs-border-color);
            color: var(--bs-body-color);
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .destruct-btn-toggle:hover {
            background-color: var(--bs-tertiary-bg);
        }

        .destruct-btn-toggle.active-timer {
            background-color: rgba(13, 202, 240, 0.15);
            border-color: #0dcaf0;
            color: #0dcaf0;
        }

        .destruct-dropdown {
            position: absolute;
            bottom: calc(100% + 15px);
            right: 0;
            width: 300px;
            background-color: var(--bs-body-bg);
            border: 1px solid var(--bs-border-color);
            border-radius: 16px;
            z-index: 1050;
            display: none;
            flex-direction: column;
            overflow: hidden;
            transform-origin: bottom right;
            animation: scaleIn 0.2s ease-out forwards;
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .destruct-dropdown-header {
            padding: 15px;
            border-bottom: 1px solid var(--bs-border-color);
        }

        .destruct-dropdown-body {
            max-height: 350px;
            overflow-y: auto;
            padding: 8px;
        }

        .destruct-dropdown-body::-webkit-scrollbar {
            width: 6px;
        }

        .destruct-dropdown-body::-webkit-scrollbar-thumb {
            background: var(--bs-secondary-bg);
            border-radius: 10px;
        }

        .destruct-option {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 4px;
            border: 1px solid transparent;
        }

        .destruct-option:hover {
            background-color: var(--bs-secondary-bg);
        }

        .destruct-option.selected {
            background-color: rgba(13, 202, 240, 0.1);
            border-color: rgba(13, 202, 240, 0.3);
        }

        .destruct-option-icon {
            width: 24px;
            text-align: center;
            font-size: 1.1rem;
            margin-right: 12px;
            color: var(--bs-secondary-color);
        }

        .opt-off i {
            color: #6c757d;
        }

        .opt-burn i {
            color: #ef4444;
        }

        .opt-time i {
            color: #eab308;
        }

        .opt-days i {
            color: #10b981;
        }

        .destruct-option.selected .destruct-option-icon {
            color: #0dcaf0;
        }

        .destruct-option-text {
            flex-grow: 1;
        }

        .destruct-option-title {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--bs-body-color);
            margin-bottom: 2px;
        }

        .destruct-option-desc {
            font-size: 0.75rem;
            color: var(--bs-secondary-color);
        }

        .destruct-option-check {
            color: #0dcaf0;
            font-size: 1.1rem;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .destruct-option.selected .destruct-option-check {
            opacity: 1;
        }

        .destruct-dropdown-footer {
            padding: 12px 15px;
            background-color: var(--bs-secondary-bg);
            font-size: 0.75rem;
            color: var(--bs-secondary-color);
            text-align: center;
            border-top: 1px solid var(--bs-border-color);
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-dark px-3 shadow-sm" style="background: linear-gradient(135deg, #0dcaf0, #0d6efd); border-bottom: none;">
        <div class="d-flex align-items-center">
            <div class="navbar-brand fw-bold text-white d-flex align-items-center">
                <img src="Sentinel logo.png" alt="Sentinel Logo" style="height: 30px; width: auto; object-fit: contain;" class="me-2">
                SENTINEL
            </div>
        </div>
        <div class="d-flex align-items-center">
            <div class="d-inline-flex align-items-center bg-dark text-light rounded-pill px-3 py-2 shadow-sm border me-3" style="border-color: rgba(26, 188, 156, 0.3) !important; box-shadow: 0 0 15px rgba(26, 188, 156, 0.1) !important;">
                <div class="d-flex align-items-center justify-content-center rounded-circle me-2" style="width: 26px; height: 26px; background-color: rgba(26, 188, 156, 0.15);">
                    <i class="bi bi-shield-lock-fill" style="color: #1abc9c; font-size: 0.85rem;"></i>
                </div>
                <span class="text-secondary fw-bold me-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">KEY-ID:</span>
                <span id="keyFingerprintDisplay" class="fw-bold" style="font-family: 'Consolas', monospace; letter-spacing: 1.5px; color: #f8fafc; font-size: 0.9rem;">####</span>
            </div>

            <div class="d-flex align-items-center px-3 py-2 rounded-pill shadow-sm" style="background-color: rgba(255, 255, 255, 0.15); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.25);">

                <div class="d-flex align-items-center justify-content-center bg-white rounded-circle me-2 overflow-hidden" style="width: 28px; height: 28px; color: #0d6efd; flex-shrink: 0;">
                    <?php if (isset($my_info['profile_picture']) && !empty($my_info['profile_picture'])): ?>
                        <img src="<?php echo htmlspecialchars($my_info['profile_picture']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <i class="bi bi-person-fill" style="font-size: 1rem;"></i>
                    <?php endif; ?>
                </div>

                <div class="d-flex align-items-center">
                    <span class="text-white-50 me-2" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">USERNAME: </span>
                    <span class="text-white fw-bold" style="font-size: 0.9rem; letter-spacing: 0.5px;"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
            </div>
        </div>
    </nav>

    <?php if (!empty($alert_msg)): ?>
        <div class="position-absolute w-100 d-flex justify-content-center" style="top: 75px; z-index: 1050; pointer-events: none;">
            <div class="shadow-lg rounded" style="pointer-events: auto; min-width: 350px; animation: slideDown 0.3s ease-out;">
                <?php echo $alert_msg; ?>
            </div>
        </div>
        <style>
            @keyframes slideDown {
                from {
                    opacity: 0;
                    transform: translateY(-20px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        </style>
    <?php endif; ?>

    <div class="chat-container">

        <div class="sidebar position-relative">
            <div id="leftSidebarSearchResults" class="d-none position-absolute top-0 start-0 w-100 h-100 flex-column shadow-lg" style="background-color: var(--bs-body-bg); z-index: 1050; border-right: 1px solid var(--bs-border-color);">

                <div class="p-3 border-bottom d-flex justify-content-between align-items-center" style="background-color: rgba(13, 202, 240, 0.05);">
                    <div class="text-info fw-bold"><i class="bi bi-search me-2"></i>In-Chat Search</div>
                    <button class="btn text-muted shadow-none p-0" onclick="toggleChatSearch()"><i class="bi bi-x-lg fs-5"></i></button>
                </div>

                <div class="p-3 border-bottom" style="background-color: var(--bs-body-bg);">
                    <div class="input-group bg-transparent border rounded-pill overflow-hidden" style="border-color: #06b6d4 !important; box-shadow: 0 0 8px rgba(6, 182, 212, 0.15);">
                        <span class="input-group-text bg-transparent border-0 text-info pe-1"><i class="bi bi-search"></i></span>
                        <input type="text" id="sidebarInChatSearchInput" class="form-control bg-transparent border-0 shadow-none text-body" placeholder="Search this chat..." oninput="executeAdvancedSearch()" style="font-size: 0.9rem;">
                    </div>
                    <div class="text-end mt-1"><small id="searchCountBadge" class="text-muted" style="font-size: 0.75rem;">0 results</small></div>
                </div>

                <div class="flex-grow-1 overflow-y-auto p-2" id="searchResultsList" style="background-color: var(--bs-body-bg);">
                </div>
            </div>

            <div class="sidebar-header" style="border-bottom: none !important; padding-bottom: 0.25rem;">
                <button class="btn btn-link text-body p-1 text-decoration-none fw-bold" type="button" data-bs-toggle="offcanvas" data-bs-target="#appMenu">
                    <i class="bi bi-list fs-4 me-2"></i>
                    <span id="current-section-name" style="position:relative; top:-3px;">Chats</span>
                </button>
            </div>

            <div class="px-3 pb-3 pt-1 border-bottom border-secondary border-opacity-25">

                <div class="input-group bg-transparent border rounded-pill overflow-hidden" style="border-color: var(--bs-border-color) !important;">
                    <span class="input-group-text bg-transparent border-0 text-secondary pe-1"><i class="bi bi-search"></i></span>
                    <input type="text" id="sidebarSearchInput" class="form-control bg-transparent border-0 shadow-none text-body" placeholder="Search contacts..." onkeyup="filterSidebarContacts()" style="font-size: 0.85rem;">
                </div>

                <div class="pt-3 d-flex gap-2 align-items-center">
                    <button class="btn btn-sm rounded-pill px-3 filter-btn" id="filterBtnAll" style="background-color: rgba(13, 202, 240, 0.15); color: #0dcaf0; border: 1px solid rgba(13, 202, 240, 0.3); font-size: 0.75rem; font-weight: bold;" onclick="applyContactFilter('all', this)">All</button>

                    <button class="btn btn-sm rounded-pill px-3 filter-btn" id="filterBtnUnread" style="background-color: var(--bs-tertiary-bg); color: var(--bs-secondary-color); border: 1px solid transparent; font-size: 0.75rem; font-weight: bold;" onclick="applyContactFilter('unread', this)">Unread</button>

                    <button class="btn btn-sm px-2 ms-auto text-body-secondary" id="filterBtnSelect" style="background-color: transparent; border: none; font-size: 0.8rem; font-weight: bold; transition: 0.2s;" onclick="toggleSelectMode(this)">Select</button>
                </div>

            </div>

            <div class="contact-list">
                <?php if (!empty($contact_data)): ?>
                    <?php foreach ($contact_data as $row): ?>

                        <?php $js_pic = !empty($row['profile_picture']) ? htmlspecialchars($row['profile_picture']) : ''; ?>

                        <div class="contact-item"
                            id="contact-<?php echo htmlspecialchars($row['username']); ?>"
                            onclick="selectUser('<?php echo htmlspecialchars($row['username']); ?>', '<?php echo htmlspecialchars($row['header_status']); ?>', '<?php echo htmlspecialchars($row['header_class']); ?>', '<?php echo $js_pic; ?>')">

                            <?php if (!empty($row['profile_picture'])): ?>
                                <img src="<?php echo htmlspecialchars($row['profile_picture']); ?>" class="avatar shadow-sm" style="object-fit: cover; border: 1px solid var(--bs-border-color);">
                            <?php else: ?>
                                <div class="avatar text-white d-flex align-items-center justify-content-center shadow-sm" style="background: linear-gradient(135deg, #0dcaf0, #0d6efd);">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                            <?php endif; ?>

                            <div>
                                <div class="fw-bold"><?php echo htmlspecialchars($row['username']); ?></div>

                                <small class="<?php echo ($row['status'] === 'online') ? 'text-success fw-bold' : 'text-muted'; ?>" style="font-size: 0.75rem;">
                                    <?php echo $row['status_text']; ?>
                                </small>
                            </div>
                            <?php if (isset($row['unread_count']) && $row['unread_count'] > 0): ?>
                                <span class="badge bg-danger rounded-pill ms-auto unread-badge shadow-sm"><?php echo $row['unread_count']; ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php
        // SETUP THE DEFAULT CHAT BACKGROUND
        $chat_bg_style = "background-color: #1e293b;"; // The default appearance color!

        if (!empty($my_info['chat_background'])) {
            $bg = $my_info['chat_background'];
            if (strpos($bg, '#') === 0) {
                // It's a saved hex color
                $chat_bg_style = "background-color: " . htmlspecialchars($bg) . ";";
            } else {
                // It's a saved image
                $chat_bg_style = "background-image: url('" . htmlspecialchars($bg) . "'); background-size: cover; background-position: center; background-repeat: no-repeat; background-blend-mode: overlay; background-color: rgba(0,0,0,0.6);";
            }
        }
        ?>

        <div class="chat-area" id="mainRightArea" style="position: relative; <?php echo $chat_bg_style; ?>">
            <div id="emptyChatInterface" style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; width: 100%;">

                <div class="text-center p-5 rounded-4" style="background: rgba(0, 0, 0, 0.4); backdrop-filter: blur(8px); border: 1px solid rgba(255, 255, 255, 0.1); box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);">

                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-4 shadow"
                        style="width: 100px; height: 100px; background: linear-gradient(135deg, #0dcaf0, #0d6efd);">
                        <img src="Sentinel logo.png" alt="Sentinel Logo" style="max-width: 60%; max-height: 60%; object-fit: contain;">
                    </div>

                    <h3 class="fw-bold text-white" style="text-shadow: 0 2px 4px rgba(0,0,0,0.5);">Sentinel Secure Chat</h3>
                    <p class="text-white mb-0" style="opacity: 0.85; font-size: 1.1rem; text-shadow: 0 1px 3px rgba(0,0,0,0.5);">Select a conversation to start</p>

                </div>
            </div>

            <div id="activeChatInterface" style="display: none; flex-direction: column; height: 100%; width: 100%;">

                <div class="chat-header position-relative d-flex align-items-center p-3" style="min-height: 70px;">

                    <div id="normalChatHeader" class="d-flex justify-content-between align-items-center w-100">
                        <div class="d-flex align-items-center flex-grow-1" style="cursor: pointer;" data-bs-toggle="offcanvas" data-bs-target="#officerDetailsPanel" onclick="populateOfficerPanel()">
                            <div class="avatar text-white d-flex align-items-center justify-content-center shadow-sm" id="chatHeaderAvatar" style="width:35px; height:35px; background: linear-gradient(135deg, #0dcaf0, #0d6efd);">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <div class="ms-2">
                                <h6 class="mb-0 fw-bold" id="chatUserName">Select a chat</h6>
                                <small id="chatUserStatus" class="text-success fw-semibold" style="font-size:0.75rem;">
                                    <i class="bi bi-shield-check me-1"></i>Secure Connection
                                </small>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-1">
                            <button class="btn btn-link text-muted shadow-none px-2 text-decoration-none" onclick="toggleChatSearch()" title="Search Chat">
                                <i class="bi bi-search fs-5"></i>
                            </button>
                            <button class="btn btn-link text-muted shadow-none px-2 text-decoration-none" data-bs-toggle="offcanvas" data-bs-target="#officerDetailsPanel" onclick="populateOfficerPanel()" title="Tactical Options">
                                <i class="bi bi-three-dots-vertical fs-5"></i>
                            </button>
                        </div>
                    </div>

                    <div id="chatSearchBarContainer" class="d-none w-100 position-relative">
                        <div class="d-flex align-items-center w-100 px-3 py-2" style="background-color: rgba(15, 23, 42, 0.8); border: 1px solid #06b6d4; border-radius: 24px; box-shadow: 0 0 12px rgba(6, 182, 212, 0.25);">
                            <i class="bi bi-search" style="color: #06b6d4;"></i>

                            <input type="text" id="inChatSearchInput" class="form-control bg-transparent border-0 text-white shadow-none ms-2" placeholder="Search messages, files, people..." oninput="executeAdvancedSearch()" style="font-size: 0.95rem;">

                            <span id="searchCountBadge" class="badge rounded-pill px-3 py-1 ms-2" style="background-color: rgba(6, 182, 212, 0.15); color: #22d3ee; border: 1px solid rgba(6, 182, 212, 0.3);">0 results</span>

                            <button class="btn btn-link shadow-none p-0 ms-3" onclick="toggleChatSearch()" style="color: #64748b;"><i class="bi bi-x-circle-fill fs-5"></i></button>
                        </div>
                    </div>

                </div>

                <div id="pinnedMessagesContainer" class="w-100 d-flex flex-column" style="z-index: 10;">
                </div>

                <div class="messages-box" id="chatMessages">
                </div>

                <button id="scrollToBottomBtn" class="btn shadow-sm align-items-center justify-content-center rounded-circle"
                    style="display: none !important; position: absolute; bottom: 80px; left: 50%; transform: translateX(-50%); z-index: 1000; width: 40px; height: 40px; transition: opacity 0.2s; opacity: 0; cursor: pointer; background: linear-gradient(135deg, #0dcaf0, #0d6efd); border: none;"
                    onclick="scrollToBottomSmooth()">
                    <i class="bi bi-chevron-down text-white fs-5" style="text-shadow: 0 1px 2px rgba(0,0,0,0.3);"></i>
                </button>

                <div class="input-area">
                    <div id="attachmentPreview" class="d-none px-4 py-3 w-100 border-top" style="background-color: var(--bs-tertiary-bg); position: relative;">
                        <div class="d-flex align-items-center bg-body p-2 shadow-sm" style="max-width: fit-content; border-radius: 12px; border: 1px solid var(--bs-border-color);">

                            <div id="previewThumbnail" class="me-3 d-flex align-items-center justify-content-center overflow-hidden bg-light" style="width: 50px; height: 50px; border-radius: 8px; cursor: pointer; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'" onclick="openPreviewModal()" title="Click to view full size">
                            </div>

                            <div class="d-flex flex-column pe-4">
                                <span id="attachmentName" class="fw-bold text-truncate" style="max-width: 180px; font-size: 0.85rem;">filename.jpg</span>
                                <small id="attachmentSize" class="text-muted" style="font-size: 0.7rem;">0 KB</small>
                            </div>

                            <button class="btn btn-sm text-danger ms-auto p-1" onclick="clearAttachment()" title="Remove file">
                                <i class="bi bi-x-circle-fill fs-5"></i>
                            </button>
                        </div>
                    </div>

                    <div id="replyPreviewBox" class="border-start border-4 border-primary p-2 mb-0 shadow-sm w-100" style="display: none; position: relative; cursor: pointer; border-top-left-radius: 15px; border-top-right-radius: 15px; background-color: var(--bs-tertiary-bg); color: var(--bs-body-color);" onclick="jumpToMessage()">
                        <div class="d-flex justify-content-between align-items-center px-4">
                            <div class="text-truncate" style="max-width: 90%;">
                                <small class="text-primary fw-bold"><i class="bi bi-reply-fill"></i> Replying to message</small><br>
                                <span class="small text-truncate d-inline-flex align-items-center w-100" style="color: var(--bs-body-color);" id="replyPreviewText">...</span>
                            </div>
                            <button type="button" class="btn-close btn-sm" aria-label="Close" onclick="event.stopPropagation(); cancelReply()"></button>
                        </div>
                        <input type="hidden" id="replyMessageId" value="">
                    </div>

                    <div id="editPreviewBox" class="border-start border-4 border-warning p-2 mb-0 shadow-sm w-100" style="display: none; position: relative; border-top-left-radius: 15px; border-top-right-radius: 15px; background-color: var(--bs-tertiary-bg); color: var(--bs-body-color);">
                        <div class="d-flex justify-content-between align-items-center px-4">
                            <div class="text-truncate" style="max-width: 90%;">
                                <small class="text-warning fw-bold"><i class="bi bi-pencil-fill"></i> Editing message</small><br>
                                <span class="small text-truncate d-inline-flex align-items-center w-100" style="color: var(--bs-body-color);" id="editPreviewText">...</span>
                            </div>
                            <button type="button" class="btn-close btn-sm" aria-label="Close" onclick="cancelEdit()"></button>
                        </div>
                        <input type="hidden" id="editMessageId" value="">
                    </div>

                    <div id="normalInputArea" class="w-100 d-flex align-items-center px-4 py-2" style="background-color: var(--bs-body-bg);">
                        <input type="file" id="attachmentInput" class="d-none" onchange="showAttachmentPreview()">
                        <button class="btn btn-link text-muted me-2 px-0" onclick="document.getElementById('attachmentInput').click()"><i class="bi bi-paperclip fs-5"></i></button>

                        <input type="text" id="messageInput" class="form-control bg-secondary bg-opacity-10 border-0 shadow-none px-4" style="border-radius: 20px; height: 42px;" placeholder="Type a secure message..." onkeypress="handleEnter(event)">

                        <div class="destruct-menu-container ms-3">

                            <input type="hidden" id="destructTimer" value="0">

                            <button class="btn destruct-btn-toggle shadow-sm" type="button" id="destructToggleBtn" onclick="toggleDestructMenu()">
                                <i class="bi bi-clock-history"></i> <span id="destructBtnText">Off</span>
                            </button>

                            <div class="destruct-dropdown shadow-lg" id="destructDropdownMenu">
                                <div class="destruct-dropdown-header">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-clock-history text-info me-2 fs-5"></i>
                                        <div>
                                            <h6 class="mb-0 fw-bold" style="font-size: 0.95rem;">Self-Destruct Timer</h6>
                                            <small class="text-body-secondary" style="font-size: 0.75rem;">Choose when your message disappears</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="destruct-dropdown-body">
                                    <div class="destruct-option selected opt-off" onclick="updateDestructUI(0, 'Off', this)">
                                        <div class="destruct-option-icon"><i class="bi bi-clock"></i></div>
                                        <div class="destruct-option-text">
                                            <div class="destruct-option-title">Off</div>
                                            <div class="destruct-option-desc">Message persists normally</div>
                                        </div>
                                        <div class="destruct-option-check"><i class="bi bi-check-circle-fill"></i></div>
                                    </div>

                                    <div class="destruct-option opt-burn" onclick="updateDestructUI(-1, 'Burn', this)">
                                        <div class="destruct-option-icon"><i class="bi bi-fire"></i></div>
                                        <div class="destruct-option-text">
                                            <div class="destruct-option-title">Delete After Read</div>
                                            <div class="destruct-option-desc">Vanishes once opened</div>
                                        </div>
                                        <div class="destruct-option-check"><i class="bi bi-check-circle-fill"></i></div>
                                    </div>

                                    <div class="destruct-option opt-time" onclick="updateDestructUI(3600, '1 hr', this)">
                                        <div class="destruct-option-icon"><i class="bi bi-hourglass-top"></i></div>
                                        <div class="destruct-option-text">
                                            <div class="destruct-option-title">1 Hour</div>
                                            <div class="destruct-option-desc">Auto-deletes in 60 min</div>
                                        </div>
                                        <div class="destruct-option-check"><i class="bi bi-check-circle-fill"></i></div>
                                    </div>

                                    <div class="destruct-option opt-time" onclick="updateDestructUI(28800, '8 hrs', this)">
                                        <div class="destruct-option-icon"><i class="bi bi-hourglass-split"></i></div>
                                        <div class="destruct-option-text">
                                            <div class="destruct-option-title">8 Hours</div>
                                            <div class="destruct-option-desc">Auto-deletes in 8 hrs</div>
                                        </div>
                                        <div class="destruct-option-check"><i class="bi bi-check-circle-fill"></i></div>
                                    </div>

                                    <div class="destruct-option opt-days" onclick="updateDestructUI(432000, '5 days', this)">
                                        <div class="destruct-option-icon"><i class="bi bi-calendar3"></i></div>
                                        <div class="destruct-option-text">
                                            <div class="destruct-option-title">5 Days</div>
                                            <div class="destruct-option-desc">Auto-deletes in 5 days</div>
                                        </div>
                                        <div class="destruct-option-check"><i class="bi bi-check-circle-fill"></i></div>
                                    </div>
                                </div>

                                <div class="destruct-dropdown-footer">
                                    Messages cannot be recovered after deletion
                                </div>
                            </div>
                        </div>
                        <button class="btn p-0 ms-3 flex-shrink-0 btn-send-circular" onclick="sendMessage()">
                            <i class="bi bi-send-fill fs-5"></i>
                        </button>
                    </div>

                    <div id="blockedInputArea" class="d-none w-100 d-flex justify-content-between align-items-center px-4 py-3" style="background-color: rgba(220, 53, 69, 0.05); border-top: 1px solid rgba(220, 53, 69, 0.15);">
                        <span class="text-danger fw-bold"><i class="bi bi-shield-slash-fill me-2 fs-5"></i>You blocked this officer.</span>

                        <button class="btn btn-danger fw-bold px-4 shadow-sm" style="border-radius: 6px; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'" onclick="unblockActiveOfficer()">
                            Unblock
                        </button>
                    </div>
                </div>

            </div>

            <?php include 'saved_messages.php'; ?>
            <?php include 'settings_ui.php'; ?>
            <?php include 'profile_ui.php'; ?>
            <?php include 'contacts_ui.php'; ?>

            <div class="offcanvas offcanvas-start" tabindex="-1" id="appMenu" style="width: 300px;">
                <div class="offcanvas-header p-4" style="background-color: var(--bs-body-bg); border-bottom: 1px solid var(--bs-border-color);">

                    <div class="d-flex align-items-center w-100 p-3 shadow-sm" style="border: 2px solid #0dcaf0; border-radius: 16px; background-color: var(--bs-body-bg);">

                        <div class="me-3">
                            <?php if (!empty($my_info['profile_picture'])): ?>
                                <img src="<?php echo htmlspecialchars($my_info['profile_picture']); ?>" class="rounded-circle shadow-sm" style="width: 52px; height: 52px; object-fit: cover;">
                            <?php else: ?>
                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm" style="width: 52px; height: 52px; background: linear-gradient(135deg, #0dcaf0, #0d6efd); font-size: 1.5rem;">
                                    <?php echo strtoupper(substr($my_username, 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mt-1">
                            <h5 class="mb-0 fw-bold text-body" style="letter-spacing: -0.5px;"><?php echo htmlspecialchars($my_username); ?></h5>
                            <div class="d-flex align-items-center fw-bold mt-1" style="color: #10b981; font-size: 0.85rem;">
                                <i class="bi bi-circle-fill me-1 status-blink" style="font-size: 0.45rem;"></i> Online
                            </div>
                        </div>

                    </div>

                </div>

                <div class="offcanvas-body p-0 pt-2">
                    <a href="#" class="menu-link" id="menu-profile" onclick="openProfile()">
                        <i class="bi bi-person-badge sentinel-icon"></i> My Profile
                    </a>

                    <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'hos'): ?>
                        <a href="admin_dashboard.php" class="menu-link" style="color: #ffc107;">
                            <i class="bi bi-shield-lock sentinel-icon" style="color: #ffc107 !important;"></i> Admin Panel
                        </a>
                    <?php endif; ?>

                    <a href="index.php" class="menu-link active" id="menu-chats" onclick="openChats(); cleanRefreshHome(event);">
                        <i class="bi bi-chat-dots sentinel-icon"></i> Chats
                    </a>
                    <a href="#" class="menu-link" id="menu-contacts" onclick="openContacts()">
                        <i class="bi bi-people sentinel-icon"></i> Contacts
                        <?php
                        // Check if we have incoming requests from the contact_controller
                        $sidebar_req_count = isset($incoming_requests) ? $incoming_requests->num_rows : 0;
                        if ($sidebar_req_count > 0):
                        ?>
                            <span class="badge rounded-pill bg-danger ms-auto shadow-sm" style="font-size: 0.7rem;">
                                <?php echo $sidebar_req_count; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <a href="#" class="menu-link" id="menu-saved" onclick="openSavedMessages()">
                        <i class="bi bi-bookmark sentinel-icon"></i> Saved Messages
                    </a>
                    <a href="#" class="menu-link" id="menu-settings" onclick="activateMenu('menu-settings', 'Settings', 'settingsInterface')">
                        <i class="bi bi-gear sentinel-icon"></i> Settings
                    </a>
                    <hr class="my-2">
                    <div class="menu-link" style="cursor: pointer;" onclick="toggleNightMode()">
                        <i class="bi bi-moon-stars sentinel-icon"></i>
                        <div class="d-flex justify-content-between w-100 align-items-center">
                            <span>Night Mode</span>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="nightModeSwitch" style="cursor: pointer;">
                            </div>
                        </div>
                    </div>
                    <div class="mt-auto p-3 text-center">
                        <a href="logout.php" class="btn btn-outline-danger w-100" onclick="return confirm('Are you sure want to logout?');"><i class="bi bi-box-arrow-left me-2"></i> Logout</a>
                    </div>
                </div>
            </div>

            <div class="offcanvas offcanvas-end shadow-lg" tabindex="-1" id="officerDetailsPanel" style="width: 350px; background-color: var(--bs-body-bg); border-left: 1px solid var(--bs-border-color);">

                <div class="offcanvas-header border-bottom border-secondary border-opacity-10" style="background-color: rgba(0,0,0,0.02);">
                    <h6 class="offcanvas-title fw-bold text-uppercase text-muted small"><i class="bi bi-person-badge-fill me-2"></i>Officer Profile</h6>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>

                <div class="offcanvas-body p-0">

                    <div class="text-center p-4 border-bottom border-secondary border-opacity-10">
                        <div class="position-relative d-inline-block mb-3">
                            <div id="panelProfilePicGenericIcon" class="rounded-circle text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 100px; height: 100px; font-size: 2.5rem; background: linear-gradient(135deg, #0dcaf0, #0d6efd); border: 3px solid var(--bs-body-bg); display: none !important;">
                                <i class="bi bi-person"></i>
                            </div>
                            <img id="panelProfilePic" src="" class="rounded-circle object-fit-cover shadow-sm" style="width: 100px; height: 100px; border: 3px solid var(--bs-body-bg); display: none !important;">
                        </div>

                        <h4 class="fw-bold mb-1" id="panelUsername">Loading...</h4>
                        <p class="text-muted mb-2" id="panelPosition">...</p>

                        <div class="d-flex justify-content-center gap-2 mb-2">
                            <span id="panelRoleBadge" class="badge bg-secondary px-2 py-1">...</span>
                            <span id="panelOfficerId" class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1">ID: ...</span>
                        </div>
                    </div>

                    <div class="p-4 border-bottom border-secondary border-opacity-10" style="background-color: rgba(0,0,0,0.01);">
                        <button class="btn btn-secure-chat w-100 fw-bold py-2 shadow-sm d-flex align-items-center justify-content-center" data-bs-dismiss="offcanvas">
                            <i class="bi bi-chat-text-fill me-2"></i> Return to Secure Chat
                        </button>
                    </div>

                    <div class="p-4 border-bottom border-secondary border-opacity-10">
                        <h6 class="fw-bold text-muted small mb-3 text-uppercase" style="letter-spacing: 1px;">Security Status</h6>

                        <div class="d-flex align-items-center mb-3">
                            <div class="text-success fs-4 me-3"><i class="bi bi-shield-lock-fill"></i></div>
                            <div>
                                <div class="fw-bold fs-6">End-to-End Encrypted</div>
                                <div class="text-muted small">Messages secured with AES-256</div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center">
                            <div class="text-info fs-4 me-3" style="color: #0ea5e9 !important;"><i class="bi bi-key-fill"></i></div>
                            <div class="w-100">
                                <div class="fw-bold fs-6 mb-1">Identity Verified</div>
                                <div class="input-group">
                                    <input type="text" id="panelPublicKeyInput" class="form-control text-muted bg-light border-secondary border-opacity-25" style="font-family: 'Courier New', monospace; font-size: 0.8rem;" readonly value="Generating...">
                                    <button class="btn btn-outline-secondary border-secondary border-opacity-25" type="button" onclick="copyOfficerPublicKey()" title="Copy Full Key Fingerprint">
                                        <i class="bi bi-clipboard text-muted"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-4">
                        <h6 class="fw-bold text-muted small mb-3 text-uppercase" style="letter-spacing: 1px;">Tactical Options</h6>

                        <ul class="list-unstyled mb-0">
                            <li class="mb-1">
                                <button class="btn btn-link text-body text-decoration-none d-flex align-items-center text-start tactical-btn tactical-btn-export" onclick="printEvidenceMode()">
                                    <div class="bg-secondary bg-opacity-10 rounded p-2 me-3 text-secondary"><i class="bi bi-printer-fill"></i></div>
                                    <span class="fw-semibold">Export Evidence Log</span>
                                </button>
                            </li>
                            <li class="mb-1">
                                <button class="btn btn-link text-warning text-decoration-none d-flex align-items-center text-start tactical-btn tactical-btn-wipe" onclick="secureWipeActiveChat()">
                                    <div class="bg-warning bg-opacity-10 rounded p-2 me-3 text-warning"><i class="bi bi-eraser-fill"></i></div>
                                    <span class="fw-semibold">Secure Wipe (History)</span>
                                </button>
                            </li>
                            <li>
                                <button id="dossierBlockBtn" class="btn btn-link text-danger text-decoration-none d-flex align-items-center text-start tactical-btn tactical-btn-block" onclick="blockActiveOfficer()">
                                    <div class="bg-danger bg-opacity-10 rounded p-2 me-3 text-danger"><i class="bi bi-person-x-fill"></i></div>
                                    <span id="dossierBlockText" class="fw-semibold">Restrict / Block Officer</span>
                                </button>
                            </li>
                        </ul>
                    </div>

                </div>
            </div>

            <div class="modal fade" id="filePreviewModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content bg-dark text-white shadow-lg" style="border: 1px solid var(--bs-border-color); border-radius: 15px; overflow: hidden;">
                        <div class="modal-header border-0 bg-black bg-opacity-25 pb-2">
                            <h5 class="modal-title fs-6 fw-bold text-truncate" id="filePreviewModalLabel" style="max-width: 80%;">Preview</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-0 text-center d-flex align-items-center justify-content-center bg-black" id="filePreviewModalBody" style="min-height: 300px; max-height: 75vh; overflow-y: auto;">
                        </div>
                        <div class="modal-footer border-0 bg-black bg-opacity-25 pt-2">
                            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Looks Good</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="messageActionModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-sm modal-dialog-centered">

                    <div class="modal-content shadow-lg" style="background-color: var(--bs-body-bg); border: 1px solid var(--bs-border-color); border-radius: 16px;">

                        <div class="modal-header border-0 pb-1 pt-3 px-4 d-flex justify-content-between align-items-center">
                            <div style="width: 20px;"></div>
                            <h6 class="modal-title text-body small fw-bold tracking-wide m-0" style="letter-spacing: 1px; font-size: 0.75rem;">MESSAGE ACTIONS</h6>
                            <button type="button" class="btn-close opacity-50 m-0" data-bs-dismiss="modal" aria-label="Close" style="font-size: 0.7rem;"></button>
                        </div>

                        <div class="modal-body p-2 mt-1">
                            <div class="list-group list-group-flush border-0">
                                <a href="#" class="list-group-item action-menu-item item-reply" onclick="handleMenuAction('reply')">
                                    <div class="action-icon-box"><i class="bi bi-reply-fill fs-5"></i></div> Reply
                                </a>
                                <a href="#" class="list-group-item action-menu-item item-copy" onclick="handleMenuAction('copy')">
                                    <div class="action-icon-box"><i class="bi bi-copy"></i></div> Copy
                                </a>
                                <a href="#" id="btnEditMessage" class="list-group-item action-menu-item item-edit" onclick="handleMenuAction('edit')">
                                    <div class="action-icon-box"><i class="bi bi-pencil-square fs-5"></i></div> Edit
                                </a>
                                <a href="#" class="list-group-item action-menu-item item-forward" onclick="handleMenuAction('forward')">
                                    <div class="action-icon-box"><i class="bi bi-send-fill"></i></div> Forward
                                </a>
                                <a href="#" class="list-group-item action-menu-item item-pin" onclick="handleMenuAction('pin')">
                                    <div class="action-icon-box"><i class="bi bi-pin-angle-fill"></i></div> <span id="contextMenuPinText">Pin Message</span>
                                </a>
                                <a href="#" class="list-group-item action-menu-item item-bookmark" id="contextMenuBookmarkBtn" onclick="handleMenuAction('star')">
                                    <div class="action-icon-box" id="contextMenuBookmarkIcon"><i class="bi bi-star-fill"></i></div>
                                    <span id="contextMenuBookmarkText">Bookmark</span>
                                </a>
                                <a href="#" class="list-group-item action-menu-item item-delete" onclick="handleMenuAction('delete_me')">
                                    <div class="action-icon-box"><i class="bi bi-trash-fill"></i></div> Delete for me
                                </a>
                                <a href="#" id="btnDeleteEveryone" class="list-group-item action-menu-item item-delete-all" onclick="handleMenuAction('delete_everyone')">
                                    <div class="action-icon-box"><i class="bi bi-trash3-fill"></i></div> Delete for everyone
                                </a>
                            </div>

                            <div class="mt-2 px-2 pb-1">
                                <button class="btn w-100 fw-bold py-2 cancel-btn" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="forwardModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-sm">
                    <div class="modal-content bg-dark border-secondary shadow-lg">
                        <div class="modal-header border-secondary pb-2">
                            <h6 class="modal-title text-white fw-bold"><i class="bi bi-arrow-right-circle-fill text-info me-2"></i>Forward Message</h6>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label text-white-50 small mb-1">Forward to (Username):</label>
                            <input type="text" id="forwardContactInput" class="form-control bg-secondary bg-opacity-25 text-white border-secondary mb-3" placeholder="e.g. Ali">

                            <label class="form-label text-white-50 small mb-1">Message Preview:</label>
                            <div id="forwardPreview" class="bg-black bg-opacity-25 text-white p-2 rounded small mb-3 text-truncate" style="border-left: 3px solid #0dcaf0;"></div>

                            <input type="hidden" id="forwardHiddenText" value="">
                            <input type="hidden" id="forwardHiddenImage" value="">
                            <input type="hidden" id="forwardHiddenFileName" value="">

                            <button class="btn btn-info w-100 fw-bold text-dark shadow-sm" onclick="executeForward()">
                                <i class="bi bi-send-fill me-1"></i> Send Forward
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="pinDurationModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-sm">
                    <div class="modal-content bg-dark border-secondary shadow-lg">
                        <div class="modal-header border-secondary pb-2">
                            <h6 class="modal-title text-white fw-bold"><i class="bi bi-pin-angle-fill text-primary me-2"></i>Pin Duration</h6>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-0">
                            <input type="hidden" id="pinMessageId" value="">
                            <div class="list-group list-group-flush rounded border-0">
                                <button class="list-group-item list-group-item-action bg-dark text-white border-secondary py-3" onclick="submitPin(1)">1 Hour</button>
                                <button class="list-group-item list-group-item-action bg-dark text-white border-secondary py-3" onclick="submitPin(8)">8 Hours</button>
                                <button class="list-group-item list-group-item-action bg-dark text-white border-secondary py-3" onclick="submitPin(24)">24 Hours</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="bookmarkPriorityModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-sm">
                    <div class="modal-content bg-dark border-secondary shadow-lg">
                        <div class="modal-header border-secondary pb-2">
                            <h6 class="modal-title text-white fw-bold"><i class="bi bi-bookmark-star-fill text-warning me-2"></i>Select Priority</h6>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-0">
                            <input type="hidden" id="bookmarkMessageId" value="">
                            <div class="list-group list-group-flush rounded border-0">
                                <button class="list-group-item list-group-item-action bg-dark text-danger border-secondary py-3 fw-bold" onclick="submitBookmark('High')"><i class="bi bi-circle-fill small me-2"></i>High</button>
                                <button class="list-group-item list-group-item-action bg-dark text-warning border-secondary py-3 fw-bold" onclick="submitBookmark('Medium')"><i class="bi bi-circle-fill small me-2"></i>Medium</button>
                                <button class="list-group-item list-group-item-action bg-dark text-info border-secondary py-3 fw-bold" onclick="submitBookmark('Low')"><i class="bi bi-circle-fill small me-2"></i>Low</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="copySuccessBadge" class="badge bg-success rounded-pill px-4 py-2 shadow-lg"
                style="display: none; position: fixed; bottom: 100px; left: 50%; transform: translateX(-50%); z-index: 9999; font-size: 0.95rem; opacity: 0; transition: opacity 0.3s ease;">
                <i class="bi bi-check-circle-fill me-2"></i> Message copied!
            </div>

            <div class="modal fade" id="exportEvidenceModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content" style="background-color: var(--bs-body-bg); color: var(--bs-body-color); border: 1px solid var(--bs-border-color) !important;">
                        <div class="modal-header border-bottom border-secondary border-opacity-25">
                            <h5 class="modal-title fw-bold"><i class="bi bi-printer-fill text-secondary me-2"></i> Export Evidence Log</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" style="background-color: var(--bs-secondary-bg);">
                            <p class="text-muted small mb-3">Select the media files from this chat to include in the official evidence report.</p>

                            <div id="evidenceMediaList" class="d-flex flex-column gap-2"></div>

                        </div>
                        <div class="modal-footer border-top border-secondary border-opacity-25 d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary btn-sm fw-bold" onclick="toggleAllEvidence()">
                                <i class="bi bi-check2-square me-1"></i> <span id="selectionCounterText">0 of 0 items selected</span>
                            </button>

                            <button type="button" class="btn btn-secure-chat text-white fw-bold shadow-sm" style="border-radius: 8px; border: none;" onclick="generateEvidenceLog()">
                                <i class="bi bi-file-earmark-pdf-fill me-1"></i> Generate Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

        <script>
            // --- 1. UI NAVIGATION & MENU ---
            function activateMenu(selectedId, textLabel) {
                const links = document.querySelectorAll('.menu-link');
                links.forEach(link => {
                    link.classList.remove('active');
                });

                const activeLink = document.getElementById(selectedId);
                if (activeLink) {
                    activeLink.classList.add('active');
                }

                if (selectedId !== 'menu-chats') {
                    document.querySelectorAll('.contact-item').forEach(item => item.classList.remove('active'));
                }

                const headerLabel = document.getElementById('current-section-name');
                if (headerLabel && textLabel) {
                    headerLabel.innerText = textLabel;
                }

                // Handle Interface Switching (hide everything else)
                const profilePage = document.getElementById('profileInterface');
                const settingsPage = document.getElementById('settingsInterface');
                const contactsPage = document.getElementById('contactsInterface');
                const emptyChat = document.getElementById('emptyChatInterface');
                const activeChat = document.getElementById('activeChatInterface');
                const savedDashboard = document.getElementById('savedMessagesInterface'); // NEW

                if (selectedId === 'menu-profile') {
                    if (settingsPage) settingsPage.style.display = 'none';
                    if (contactsPage) contactsPage.style.display = 'none';
                    if (emptyChat) emptyChat.style.display = 'none';
                    if (activeChat) {
                        activeChat.style.display = 'none';
                        activeChat.classList.add('d-none');
                    }
                    if (savedDashboard) savedDashboard.classList.add('d-none'); // FIXED: Hide Saved Messages
                    if (profilePage) profilePage.style.display = 'flex';
                } else if (selectedId === 'menu-settings') {
                    if (profilePage) profilePage.style.display = 'none';
                    if (contactsPage) contactsPage.style.display = 'none';
                    if (emptyChat) emptyChat.style.display = 'none';
                    if (activeChat) {
                        activeChat.style.display = 'none';
                        activeChat.classList.add('d-none');
                    }
                    if (savedDashboard) savedDashboard.classList.add('d-none'); // FIXED: Hide Saved Messages
                    if (settingsPage) settingsPage.style.display = 'flex';
                }

                const offcanvasEl = document.getElementById('appMenu');
                if (offcanvasEl) {
                    const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
                    if (offcanvas) {
                        offcanvas.hide();
                    }
                }
            }

            // --- 2. OPEN CONTACTS FUNCTION ---
            function openContacts() {
                document.getElementById('activeChatInterface').style.display = 'none';
                document.getElementById('emptyChatInterface').style.display = 'none';

                const profilePage = document.getElementById('profileInterface');
                if (profilePage) profilePage.style.display = 'none';

                const settingsPage = document.getElementById('settingsInterface');
                if (settingsPage) settingsPage.style.display = 'none';

                // FIXED: Hide Saved Messages
                const savedDashboard = document.getElementById('savedMessagesInterface');
                if (savedDashboard) savedDashboard.classList.add('d-none');

                // Show Contacts interface
                document.getElementById('contactsInterface').style.display = 'flex';

                // Clear highlighted chats
                const allContacts = document.querySelectorAll('.contact-item');
                allContacts.forEach(item => {
                    item.classList.remove('active');
                });

                activateMenu('menu-contacts', 'Contacts');

                const offcanvasEl = document.getElementById('appMenu');
                if (offcanvasEl) {
                    const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
                    if (offcanvas) {
                        offcanvas.hide();
                    }
                }
            }

            // Global variable to hold our auto-refresh timer
            let chatPollInterval = null;

            /**
             * Allows pressing "Enter" to send a message.
             */
            function handleEnter(e) {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            }

            /**
             * Reads the selected file and builds a beautiful visual preview.
             */
            function showAttachmentPreview() {
                const fileInput = document.getElementById('attachmentInput');
                const previewContainer = document.getElementById('attachmentPreview');
                const nameEl = document.getElementById('attachmentName');
                const sizeEl = document.getElementById('attachmentSize');
                const thumbEl = document.getElementById('previewThumbnail');

                if (fileInput.files.length > 0) {
                    const file = fileInput.files[0];

                    // 1. Set File Name
                    nameEl.innerText = file.name;

                    // 2. Calculate and format File Size
                    let sizeText = (file.size / 1024).toFixed(1) + ' KB';
                    if (file.size > 1024 * 1024) {
                        sizeText = (file.size / (1024 * 1024)).toFixed(1) + ' MB';
                    }
                    sizeEl.innerText = sizeText;

                    // 3. Generate Visual Thumbnail based on File Type
                    if (file.type.startsWith('image/')) {
                        // Create a temporary live URL for the image
                        const imgUrl = URL.createObjectURL(file);
                        thumbEl.innerHTML = `<img src="${imgUrl}" style="width: 100%; height: 100%; object-fit: cover;">`;
                    } else if (file.type.startsWith('video/')) {
                        // Show a sleek video icon
                        thumbEl.innerHTML = `<div class="bg-dark text-white w-100 h-100 d-flex align-items-center justify-content-center"><i class="bi bi-play-btn-fill fs-4"></i></div>`;
                    } else {
                        // Show a generic document icon
                        thumbEl.innerHTML = `<i class="bi bi-file-earmark-text-fill text-primary fs-3"></i>`;
                    }

                    // Show the preview box!
                    previewContainer.classList.remove('d-none');
                }
            }

            /**
             * Opens the full-screen modal to preview the file before sending.
             * UPGRADED: Now works for both Main Chat and Saved Messages!
             */
            function openPreviewModal() {
                const mainInput = document.getElementById('attachmentInput');
                const savedInput = document.getElementById('savedAttachmentInput');

                let file = null;

                // Check which input actually has a file in it right now
                if (mainInput && mainInput.files.length > 0) {
                    file = mainInput.files[0];
                } else if (savedInput && savedInput.files.length > 0) {
                    file = savedInput.files[0];
                }

                // If neither has a file, do nothing
                if (!file) return;

                const fileUrl = URL.createObjectURL(file); // Create a secure temporary link

                const modalBody = document.getElementById('filePreviewModalBody');
                const modalTitle = document.getElementById('filePreviewModalLabel');

                // Set the title to the file name
                modalTitle.innerText = file.name;

                // Inject the correct viewer based on file type
                if (file.type.startsWith('image/')) {
                    modalBody.innerHTML = `<img src="${fileUrl}" class="img-fluid" style="max-height: 75vh; object-fit: contain;">`;
                } else if (file.type.startsWith('video/')) {
                    modalBody.innerHTML = `<video src="${fileUrl}" controls autoplay class="w-100" style="max-height: 75vh; outline: none;"></video>`;
                } else if (file.type === 'application/pdf') {
                    modalBody.innerHTML = `<iframe src="${fileUrl}#toolbar=0&navpanes=0&scrollbar=0" class="w-100" style="height: 75vh;" frameborder="0"></iframe>`;
                } else {
                    modalBody.innerHTML = `
                    <div class="p-5 text-muted d-flex flex-column align-items-center">
                        <i class="bi bi-file-earmark-fill display-1 text-primary mb-3"></i>
                        <h5>Ready to Send</h5>
                        <p class="small">Live preview is not available for this specific file type.</p>
                    </div>`;
                }

                // Trigger the Bootstrap Modal
                const previewModal = new bootstrap.Modal(document.getElementById('filePreviewModal'));
                previewModal.show();
            }

            /**
             * Clears the file input and hides the preview box.
             */
            function clearAttachment() {
                document.getElementById('attachmentInput').value = '';
                document.getElementById('attachmentPreview').classList.add('d-none');
                document.getElementById('previewThumbnail').innerHTML = ''; // Clear memory
            }

            // NEW: Helper function to convert files into text strings for encryption
            function readFileAsBase64(file) {
                return new Promise((resolve, reject) => {
                    const reader = new FileReader();
                    reader.onload = () => resolve(reader.result);
                    reader.onerror = error => reject(error);
                    reader.readAsDataURL(file);
                });
            }

            /**
             * Sends a message to the database.
             */
            async function sendMessage() {
                const currentName = document.getElementById('chatUserName').innerText.trim();
                const inputEl = document.getElementById('messageInput');
                const fileInput = document.getElementById('attachmentInput');

                const rawText = inputEl.value.trim();
                const hasFile = fileInput.files.length > 0;

                if ((!rawText && !hasFile) || currentName === "Select a chat" || currentName === "") return;

                inputEl.value = ''; // Clear input instantly for fast UI

                let encryptedForReceiver = "";
                let encryptedForSender = "";
                let adminCiphertext = ""; // FIXED: Declare Admin's text up here!

                if (rawText) {
                    try {
                        // 1. Fetch the Contact's Public Padlock
                        const res = await fetch('get_public_key.php?username=' + encodeURIComponent(currentName));
                        const data = await res.json();

                        if (!data.success) {
                            alert("Security Error: " + data.error);
                            return;
                        }

                        // 2. TRUE DUAL-ENCRYPTION
                        encryptedForReceiver = await encryptMessage(rawText, data.public_key); // For Abu
                        encryptedForSender = await encryptMessage(rawText, MY_PUBLIC_KEY); // For Ali

                        // FIXED: Now we lock the audit log using the Head of Security's Master Padlock!
                        adminCiphertext = await encryptMessage(rawText, HOS_PUBLIC_KEY);

                    } catch (e) {
                        console.error("Encryption failed:", e);
                        alert("Encryption Engine Failure. Message aborted.");
                        return;
                    }
                }

                // 3. Create the data backpack
                const formData = new FormData();
                formData.append('receiver_username', currentName);
                formData.append('message', encryptedForReceiver);
                formData.append('sender_message', encryptedForSender);

                // Grab the reply ID if they are replying to something
                const replyId = document.getElementById('replyMessageId').value;
                if (replyId) {
                    formData.append('reply_to_id', replyId);
                }

                // FIXED: Attach Admin's copy AFTER the backpack is created!
                if (adminCiphertext) {
                    formData.append('ciphertext_admin', adminCiphertext);
                }

                // Grab the edit ID if they are editing a message
                const editIdElement = document.getElementById('editMessageId');
                if (editIdElement && editIdElement.value !== "") {
                    formData.append('edit_message_id', editIdElement.value);
                }

                // Attach the self-destruct timer
                const timerValue = document.getElementById('destructTimer').value;
                formData.append('destruct_timer', timerValue);

                // If the user attached a file/image
                if (hasFile) {
                    try {
                        // 1. Convert the image to a Base64 Text String
                        const base64File = await readFileAsBase64(fileInput.files[0]);

                        // 2. Encrypt the massive string for the Head of Security!
                        const adminFileCiphertext = await encryptLargeMessage(base64File, HOS_PUBLIC_KEY);

                        // FIXED: Just put the raw string directly into a text Blob! No corruption!
                        const encryptedBlob = new Blob([adminFileCiphertext], {
                            type: 'text/plain'
                        });

                        formData.append('attachment_admin_file', encryptedBlob, 'encrypted_blob.txt');

                        // (Ensure you have your standard file attachment for the other user here)
                        formData.append('attachment', fileInput.files[0]);

                    } catch (e) {
                        console.error("File encryption failed:", e);
                        alert("Security Error: Failed to encrypt the attachment!");
                        return;
                    }
                }

                // 4. Send to server
                fetch('send_message.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {

                            // ==========================================
                            // INSTANT OPTIMISTIC UI UPDATE (For Edits)
                            // ==========================================
                            const editIdElement = document.getElementById('editMessageId');
                            if (editIdElement && editIdElement.value !== "") {
                                const editId = editIdElement.value;
                                const msgRow = document.querySelector(`[data-id="${editId}"]`);

                                if (msgRow) {
                                    // 1. Instantly swap the text on the screen!
                                    const textBody = msgRow.querySelector('.message-text-body');
                                    if (textBody) {
                                        // Replace newlines with <br> to keep formatting nice
                                        textBody.innerHTML = rawText.replace(/\n/g, '<br>');
                                    }

                                    // 2. Instantly inject the (edited) tag next to the time!
                                    const timeContainer = msgRow.querySelector('.msg-time-container');
                                    if (timeContainer && !timeContainer.innerHTML.includes('(edited)')) {
                                        timeContainer.insertAdjacentHTML('afterbegin', '<span class="me-1 fst-italic" style="opacity: 0.7;">(Edited)</span>');
                                    }

                                    // 3. Update the hidden cipher so the background auto-refresh doesn't get confused
                                    msgRow.setAttribute('data-cipher', encryptedForSender);
                                }
                            }
                            // ==========================================

                            if (typeof clearAttachment === "function") clearAttachment();

                            // Force the screen to jump to the bottom!
                            loadMessages(currentName, true);

                            // Close the UI boxes!
                            if (typeof cancelReply === "function") cancelReply();
                            if (typeof cancelEdit === "function") cancelEdit();

                            // Move the contact instantly to the top upon confirmed success!
                            moveContactToTop(currentName);

                            // Instantly update your dashboard stats!
                            updateLiveStats();
                        } else {
                            alert("Transmission Error: " + (data.error || "Unknown error"));
                        }
                    })
                    .catch(err => console.error("Send failed:", err));
            }

            /**
             * Helper: Converts seconds into a readable Tactical Badge (e.g., 3600 -> "1 Hour")
             */
            function formatDestructTimer(seconds) {
                if (!seconds || seconds == 0) return "";
                if (seconds == -1) return "Burn on Read"; // NEW: Catch the -1 timer!
                if (seconds >= 86400) return (seconds / 86400) + " Day" + (seconds >= 172800 ? "s" : "");
                if (seconds >= 3600) return (seconds / 3600) + " Hour" + (seconds >= 7200 ? "s" : "");
                if (seconds >= 60) return (seconds / 60) + " Min" + (seconds >= 120 ? "s" : "");
                return seconds + " Sec";
            }

            function revealAndBurn(msgId, timerVal) {
                // 1. Show the secret text visually
                document.getElementById('reveal-btn-' + msgId).classList.add('d-none');
                document.getElementById('text-content-' + msgId).classList.remove('d-none');

                // 2. If it's a "Burn on Read" (-1) message, trigger the silent shredder!
                if (timerVal == -1) {
                    // Tag the bubble so our auto-refresh knows NOT to hide it while we are reading
                    const msgRow = document.querySelector(`[data-id="${msgId}"]`);
                    if (msgRow) msgRow.classList.add('is-reading-burn');

                    // Tell the database to destroy the encrypted payload immediately
                    const formData = new FormData();
                    formData.append('message_id', msgId);
                    fetch('burn_on_read.php', {
                        method: 'POST',
                        body: formData
                    });
                }
            }

            // ==========================================
            // LIVE COUNTDOWN ENGINE
            // ==========================================
            function formatTimeRemaining(endTimeStr) {
                // Cross-browser safe date parsing (replaces SQL space with 'T')
                const end = new Date(endTimeStr.replace(' ', 'T')).getTime();
                const now = new Date().getTime();
                const diff = Math.floor((end - now) / 1000);

                if (diff <= 0) return "Burning...";

                if (diff >= 86400) return Math.floor(diff / 86400) + "d " + Math.floor((diff % 86400) / 3600) + "h";
                if (diff >= 3600) return Math.floor(diff / 3600) + "h " + Math.floor((diff % 3600) / 60) + "m";
                if (diff >= 60) return Math.floor(diff / 60) + "m " + (diff % 60) + "s";
                return diff + "s";
            }

            // Check the clocks every 1 second!
            setInterval(() => {
                document.querySelectorAll('.live-timer-text').forEach(el => {
                    const expiresAt = el.getAttribute('data-expires-at');
                    if (expiresAt && expiresAt !== 'null') {
                        el.innerText = formatTimeRemaining(expiresAt);
                    }
                });
            }, 1000);

            /**
             * Fetches chat history and smartly APPENDS new messages without interrupting videos!
             * Includes Exact Screenshot Styling for Self-Destruct Bubbles
             */
            function loadMessages(username, forceScroll = false) {
                fetch('load_messages.php?username=' + encodeURIComponent(username))
                    .then(res => res.json())
                    .then(async data => {

                        if (data.error) return;

                        const chatBox = document.getElementById('chatMessages');

                        // Check if the chat box is totally empty right now
                        const isInitialLoad = (chatBox.innerHTML.trim() === '' || chatBox.innerHTML.includes('Secure connection established'));

                        // Check if the user is scrolled to the bottom so we know if we should auto-scroll later
                        const isScrolledToBottom = chatBox.scrollHeight - chatBox.clientHeight <= chatBox.scrollTop + 50;

                        let newHtmlToAppend = '';
                        let activePins = [];

                        // Remember the last date we drew on the screen so we don't draw duplicates
                        let lastDateString = '';
                        // NEW: Memory cache to remember decrypted messages for replies!
                        const tempDecryptedCache = {};
                        if (!isInitialLoad) {
                            const existingDates = chatBox.querySelectorAll('.date-badge-text');
                            if (existingDates.length > 0) {
                                lastDateString = existingDates[existingDates.length - 1].innerText.trim();
                            }
                        } else {
                            chatBox.innerHTML = ''; // Wipe the initial secure connection pill
                        }

                        for (const msg of data) {
                            const isReceived = (msg.sender_username === username);

                            // ==========================================
                            // 1. PIN BANNER TRACKER
                            // ==========================================
                            if (msg.is_pinned_for_me == 1) {
                                let previewText = "";
                                const existingMsgCheck = document.querySelector(`[data-id="${msg.message_id}"]`);

                                if (existingMsgCheck) {
                                    const textDiv = existingMsgCheck.querySelector('div[style*="word-break"]');
                                    if (textDiv) previewText = textDiv.innerText.replace(/\n/g, " ").trim();
                                    else if (existingMsgCheck.querySelector('img')) previewText = "<i class='bi bi-image me-1'></i> Photo";
                                    else if (existingMsgCheck.querySelector('video')) previewText = "<i class='bi bi-camera-video me-1'></i> Video";
                                    else previewText = "<i class='bi bi-file-earmark-fill me-1'></i> Document";
                                } else {
                                    let plainTextForPin = "";
                                    try {
                                        const cipher = isReceived ? msg.message : msg.sender_message;
                                        if (cipher && cipher.length > 200 && !cipher.includes(' ')) plainTextForPin = await decryptMessage(cipher);
                                        else plainTextForPin = cipher;
                                    } catch (e) {}

                                    previewText = plainTextForPin ? plainTextForPin.replace(/\n/g, " ").trim() : "";
                                    if (!previewText && msg.file_path) {
                                        previewText = "<i class='bi bi-file-earmark-fill me-1'></i> Document";
                                        if (msg.file_type && msg.file_type.startsWith('image/')) previewText = "<i class='bi bi-image me-1'></i> Photo";
                                        if (msg.file_type && msg.file_type.startsWith('video/')) previewText = "<i class='bi bi-camera-video me-1'></i> Video";
                                    }
                                }

                                let expirationTime = isReceived ? msg.pinned_by_receiver_until : msg.pinned_by_sender_until;
                                activePins.push({
                                    id: msg.message_id,
                                    text: previewText,
                                    expiresAt: new Date(expirationTime).getTime()
                                });
                            }

                            // ==========================================
                            // 2. DOM DIFFING: DOES IT ALREADY EXIST?
                            // ==========================================
                            const existingMsgRow = document.querySelector(`[data-id="${msg.message_id}"]`);

                            if (existingMsgRow) {
                                // Update 1: Was it just deleted for everyone?
                                if (msg.is_deleted_everyone == 1 && !existingMsgRow.innerText.includes('This message was deleted')) {
                                    existingMsgRow.innerHTML = `
                                        <div class="text-white-50 shadow-sm user-select-none" style="background-color: rgba(0, 0, 0, 0.65); padding: 10px 15px; max-width: 75%; border-radius: 20px; font-style: italic; border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(4px);">
                                            <i class="bi bi-ban me-1"></i> This message was deleted.
                                        </div>
                                    `;
                                    existingMsgRow.className = isReceived ? 'd-flex mb-3' : 'd-flex justify-content-end mb-3';
                                }

                                // ==========================================
                                // --> NEW: THE LIVE BURN SYNC <--
                                // ==========================================
                                const checkCipher = isReceived ? msg.message : msg.sender_message;
                                if (checkCipher === '[BURNED]' && !existingMsgRow.innerText.includes('self-destructed')) {
                                    // Make sure we aren't the ones actively reading it!
                                    if (!existingMsgRow.classList.contains('is-reading-burn')) {
                                        existingMsgRow.innerHTML = `
                                            <div class="shadow-sm user-select-none d-flex align-items-center" style="background-color: rgba(0, 0, 0, 0.4); padding: 10px 15px; max-width: 75%; border-radius: 16px; font-style: italic; border: 1px dashed rgba(245, 158, 11, 0.3); backdrop-filter: blur(4px);">
                                                <i class="bi bi-fire me-2" style="color: #f59e0b; font-size: 1.1rem; opacity: 0.8;"></i>
                                                <span style="font-size: 0.85rem; color: rgba(255,255,255,0.5);">This message self-destructed</span>
                                            </div>
                                        `;
                                        existingMsgRow.className = isReceived ? 'd-flex mb-3' : 'd-flex justify-content-end mb-3';
                                    }
                                }
                                // ==========================================

                                // Update 3: Did the read-receipt (ticks) change?
                                if (!isReceived) {
                                    const tickSpan = existingMsgRow.querySelector('.msg-tick-status');
                                    if (tickSpan) {
                                        let tick = '<i class="bi bi-check"></i>';
                                        if (msg.status === 'delivered') tick = '<i class="bi bi-check-all"></i>';
                                        if (msg.status === 'read') tick = '<i class="bi bi-check-all text-white fw-bold"></i>';

                                        if (tickSpan.innerHTML !== tick) tickSpan.innerHTML = tick;
                                    }
                                }

                                // Update 4: Did the text get edited?
                                const newCipher = isReceived ? msg.message : msg.sender_message;
                                const oldCipher = existingMsgRow.getAttribute('data-cipher');

                                if (msg.is_edited == 1 && newCipher !== oldCipher) {
                                    // 1. Decrypt the new updated text
                                    let updatedText = "";
                                    try {
                                        if (newCipher && newCipher.length > 200 && !newCipher.includes(' ')) {
                                            updatedText = await decryptMessage(newCipher);
                                        } else {
                                            updatedText = newCipher;
                                        }
                                    } catch (e) {
                                        updatedText = "⚠️ [Message Corrupted]";
                                    }

                                    // 2. Swap the text seamlessly inside the bubble!
                                    const textBody = existingMsgRow.querySelector('.message-text-body');
                                    if (textBody) textBody.innerHTML = updatedText;

                                    // 3. Slap the (edited) tag next to the timestamp
                                    const timeContainer = existingMsgRow.querySelector('.msg-time-container');
                                    if (timeContainer && !timeContainer.innerHTML.includes('(edited)')) {
                                        timeContainer.insertAdjacentHTML('afterbegin', '<span class="me-1 fst-italic" style="opacity: 0.7;">(Edited)</span>');
                                    }

                                    // 4. Save the new cipher to the bubble's memory so it doesn't decrypt again next second!
                                    existingMsgRow.setAttribute('data-cipher', newCipher);
                                }
                                continue; // Skip building HTML
                            }

                            // ==========================================
                            // 3. BUILD BRAND NEW MESSAGES ONLY
                            // ==========================================
                            const msgDateObj = new Date(msg.created_at);
                            const time = msgDateObj.toLocaleTimeString([], {
                                hour: '2-digit',
                                minute: '2-digit'
                            });

                            let displayDate = msgDateObj.toLocaleDateString([], {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric'
                            });
                            const today = new Date();
                            const yesterday = new Date();
                            yesterday.setDate(today.getDate() - 1);

                            if (msgDateObj.toDateString() === today.toDateString()) displayDate = "Today";
                            else if (msgDateObj.toDateString() === yesterday.toDateString()) displayDate = "Yesterday";

                            if (displayDate !== lastDateString) {
                                newHtmlToAppend += `
                                    <div class="d-flex justify-content-center my-3" style="position: relative; z-index: 10;">
                                        <div class="shadow-sm date-badge-text" style="background-color: var(--bs-tertiary-bg); color: var(--bs-secondary-color); padding: 4px 14px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; border: 1px solid var(--bs-border-color);">
                                            ${displayDate}
                                        </div>
                                    </div>
                                `;
                                lastDateString = displayDate;
                            }

                            if (isReceived && msg.deleted_by_receiver == 1) continue;
                            if (!isReceived && msg.deleted_by_sender == 1) continue;

                            if (msg.is_deleted_everyone == 1) {
                                // NEW: Save to cache before skipping!
                                tempDecryptedCache[msg.message_id] = {
                                    text: "🚫 This message was deleted."
                                };

                                newHtmlToAppend += `
                                    <div class="${isReceived ? 'd-flex mb-3' : 'd-flex justify-content-end mb-3'}" data-id="${msg.message_id}" oncontextmenu="event.stopPropagation(); return false;">
                                        <div class="text-white-50 shadow-sm user-select-none" style="background-color: rgba(0, 0, 0, 0.65); padding: 10px 15px; max-width: 75%; border-radius: 20px; font-style: italic; border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(4px);">
                                            <i class="bi bi-ban me-1"></i> This message was deleted.
                                        </div>
                                    </div>
                                `;
                                continue;
                            }

                            // NEW: Catch the Burned Tombstone
                            const cipher = isReceived ? msg.message : msg.sender_message;
                            if (cipher === '[BURNED]') {
                                // NEW: Save to cache before skipping!
                                tempDecryptedCache[msg.message_id] = {
                                    text: "🔥 This message self-destructed."
                                };

                                newHtmlToAppend += `
                                    <div class="${isReceived ? 'd-flex mb-3' : 'd-flex justify-content-end mb-3'}" data-id="${msg.message_id}" oncontextmenu="event.stopPropagation(); return false;">
                                        <div class="shadow-sm user-select-none d-flex align-items-center" style="background-color: rgba(0, 0, 0, 0.4); padding: 10px 15px; max-width: 75%; border-radius: 16px; font-style: italic; border: 1px dashed rgba(245, 158, 11, 0.3); backdrop-filter: blur(4px);">
                                            <i class="bi bi-fire me-2" style="color: #f59e0b; font-size: 1.1rem; opacity: 0.8;"></i>
                                            <span style="font-size: 0.85rem; color: rgba(255,255,255,0.5);">This message self-destructed</span>
                                        </div>
                                    </div>
                                `;
                                continue; // Skip the decryption and standard rendering below!
                            }

                            // DECRYPT TEXT
                            let plainText = "";
                            try {
                                const cipher = isReceived ? msg.message : msg.sender_message;
                                if (cipher && cipher.length > 200 && !cipher.includes(' ')) plainText = await decryptMessage(cipher);
                                else plainText = cipher;
                            } catch (unlockError) {
                                plainText = "⚠️ [Message Corrupted]";
                            }

                            // NEW: Save the decrypted text and file info to the memory cache!
                            tempDecryptedCache[msg.message_id] = {
                                text: plainText,
                                img: msg.file_type?.startsWith('image/'),
                                vid: msg.file_type?.startsWith('video/'),
                                doc: msg.file_path && !msg.file_type?.startsWith('image/') && !msg.file_type?.startsWith('video/')
                            };

                            // MEDIA & REPLIES & FORWARDS
                            let mediaHtml = '';
                            if (msg.file_path) {
                                if (msg.file_type?.startsWith('image/')) mediaHtml = `<div class="mb-1"><img src="${msg.file_path}" class="img-fluid rounded" style="max-height: 200px; cursor: pointer;" onclick="window.open('${msg.file_path}', '_blank')"></div>`;
                                else if (msg.file_type?.startsWith('video/')) mediaHtml = `<div class="mb-1"><video src="${msg.file_path}" controls preload="metadata" class="img-fluid rounded shadow-sm" style="max-height: 250px; max-width: 100%;"></video></div>`;
                                else mediaHtml = `<a href="${msg.file_path}" target="_blank" class="mb-1 p-2 bg-light rounded d-flex align-items-center text-decoration-none shadow-sm" style="font-size: 0.8rem; display: inline-flex !important;"><i class="bi bi-file-earmark-fill fs-4 text-primary me-2"></i><span class="text-dark text-truncate" style="max-width: 150px;">${msg.file_name}</span></a>`;
                            }

                            let replyUI = '';
                            if (msg.reply_to_id) {
                                let originalPreview = "View Original Message";
                                let foundText = "";
                                let foundIcon = "";

                                // 1. Check our memory cache (for messages loaded simultaneously)
                                if (tempDecryptedCache[msg.reply_to_id]) {
                                    const cacheData = tempDecryptedCache[msg.reply_to_id];
                                    foundText = cacheData.text;
                                    if (cacheData.img) foundIcon = "<i class='bi bi-image me-1'></i> Photo ";
                                    if (cacheData.vid) foundIcon = "<i class='bi bi-camera-video me-1'></i> Video ";
                                    if (cacheData.doc) foundIcon = "<i class='bi bi-file-earmark-fill me-1'></i> Document ";
                                }
                                // 2. Fallback: Check the actual screen (for older messages already rendered)
                                else {
                                    const origMsgEl = document.querySelector(`[data-id="${msg.reply_to_id}"]`);
                                    if (origMsgEl) {
                                        let clone = origMsgEl.cloneNode(true);
                                        // Destroy junk data from the clone
                                        clone.querySelectorAll('small, sub, .text-muted, .text-white-50, .burn-timer-badge').forEach(el => el.remove());
                                        // Destroy nested replies so they don't bloat the preview
                                        clone.querySelectorAll('div[style*="border-left: 3px solid"]').forEach(el => el.remove());

                                        foundText = clone.innerText.trim().replace(/\n/g, " ");

                                        if (clone.querySelector('img')) foundIcon = "<i class='bi bi-image me-1'></i> Photo ";
                                        else if (clone.querySelector('video')) foundIcon = "<i class='bi bi-camera-video me-1'></i> Video ";
                                        else if (clone.querySelector('a[href*="uploads/"]')) foundIcon = "<i class='bi bi-file-earmark-fill me-1'></i> Document ";
                                    }
                                }

                                // 3. Build the final text preview
                                if (foundText || foundIcon) {
                                    if (foundText.length > 45) foundText = foundText.substring(0, 45) + '...';
                                    originalPreview = foundIcon + foundText;
                                    if (originalPreview.trim() === "") originalPreview = "Media Attachment";
                                }

                                replyUI = `
                                <div class="p-2 mb-2 rounded shadow-sm w-100" style="background-color: rgba(0,0,0,0.15); border-left: 3px solid #0dcaf0; cursor: pointer; font-size: 0.8rem;" onclick="jumpToMessageById('${msg.reply_to_id}')">
                                    <div class="text-info fw-bold mb-1" style="font-size: 0.7rem;"><i class="bi bi-reply-fill"></i> Reply</div>
                                    <div class="text-white text-truncate">${originalPreview}</div>
                                </div>`;
                            }
                            let forwardUI = msg.is_forwarded == 1 ? `<div class="mb-1 text-white-50" style="font-size: 0.75rem; font-style: italic;"><i class="bi bi-forward-fill me-1"></i>Forwarded</div>` : '';

                            let isStarred = false;
                            if (isReceived && msg.starred_by_receiver !== null && msg.starred_by_receiver !== "0" && msg.starred_by_receiver !== "") isStarred = true;
                            if (!isReceived && msg.starred_by_sender !== null && msg.starred_by_sender !== "0" && msg.starred_by_sender !== "") isStarred = true;
                            const starIcon = isStarred ? `<i class="bi bi-star-fill text-warning ms-1" style="font-size: 0.65rem;"></i>` : '';
                            const isPinnedData = (msg.is_pinned_for_me == 1) ? 'true' : 'false';
                            const pinBadge = (msg.is_pinned_for_me == 1) ? `<div class="position-absolute" style="top: -6px; right: -6px; background-color: #212529; border-radius: 50%; padding: 3px 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.3); z-index: 5; border: 1px solid #0dcaf0;"><i class="bi bi-pin-angle-fill text-info" style="font-size: 0.70rem;"></i></div>` : '';

                            // ==========================================
                            // 4. TACTICAL BUBBLE CONTENT LOGIC
                            // ==========================================
                            // Use your database destruct_timer (e.g., 3600 or -1 for burn on read)
                            const isBurn = (msg.destruct_timer !== null && (msg.destruct_timer > 0 || msg.destruct_timer == -1));

                            let finalBubbleContent = "";

                            if (isBurn) {
                                // Convert integer to default text (e.g., "1 Hour")
                                const burnDurationText = formatDestructTimer(msg.destruct_timer);

                                // NEW: Check if the countdown has officially started in the database!
                                let burnBadgeHtml = '';
                                if (msg.expires_at && msg.expires_at !== null) {
                                    // The timer is ticking! Inject the live span element.
                                    burnBadgeHtml = `<span class="live-timer-text" data-expires-at="${msg.expires_at}">Counting...</span>`;
                                } else {
                                    // The timer hasn't started yet (Receiver hasn't opened it / clicked reveal)
                                    burnBadgeHtml = `<span>${burnDurationText}</span>`;
                                }

                                const hiddenBg = isReceived ? "rgba(255,255,255,0.08)" : "rgba(255,255,255,0.15)";
                                const hiddenBorder = isReceived ? "1px dashed rgba(255,255,255,0.3)" : "1px dashed rgba(255,255,255,0.5)";

                                finalBubbleContent = `
                                    <div class="d-flex flex-column w-100" style="min-width: 180px;">
                                        
                                        <div id="reveal-btn-${msg.message_id}" class="text-center py-4 px-4" 
                                             style="cursor: pointer; background: ${hiddenBg}; border-radius: 12px; border: ${hiddenBorder};" 
                                             onclick="revealAndBurn('${msg.message_id}', ${msg.destruct_timer})">
                                            
                                            <i class="bi bi-eye fs-3 d-block mb-1" style="opacity: 0.9;"></i>
                                            <span style="font-size: 0.85rem; font-weight: 600; letter-spacing: 0.5px;">Click to reveal</span>
                                        </div>

                                        <div id="text-content-${msg.message_id}" class="d-none">
                                            ${forwardUI} 
                                            ${mediaHtml}
                                            ${replyUI}
                                            <div style="word-break: break-word; font-size: 0.95rem;">${plainText}</div>
                                        </div>

                                        <hr style="opacity: 0.2; margin: 10px 0;">

                                        <div class="d-flex justify-content-between align-items-center" style="font-size: 0.7rem;">
                                            <span style="opacity: 0.9;">${time}</span>
                                            
                                            <div class="burn-timer-badge" style="background-color: #f59e0b; color: white; padding: 2px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: bold; display: flex; align-items: center; gap: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.15);">
                                                <i class="bi bi-fire"></i> ${burnBadgeHtml} </div>
                                            
                                            <i class="bi bi-eye ms-1" style="opacity: 0.7;"></i>
                                        </div>
                                    </div>
                                `;
                            } else {
                                // STANDARD UI
                                let tick = (msg.status === 'read') ? '<i class="bi bi-check-all text-white fw-bold"></i>' : (msg.status === 'delivered' ? '<i class="bi bi-check-all"></i>' : '<i class="bi bi-check"></i>');
                                let tickHtml = !isReceived ? '<span class="ms-1 msg-tick-status" style="font-size: 0.8rem;">' + tick + '</span>' : '';

                                // NEW: Generate the edited tag if it exists in the database
                                const editedTag = (msg.is_edited == 1) ? '<span class="me-1 fst-italic" style="opacity: 0.7;">(Edited)</span>' : '';

                                finalBubbleContent = `
                                    <div class="d-flex flex-column w-100">
                                        ${forwardUI} ${mediaHtml}
                                        ${replyUI}
                                        <div class="message-text-body" style="word-break: break-word;">${plainText}</div>
                                    </div>
                                    <small class="text-white-50 align-self-end d-flex align-items-center mt-1 msg-time-container" style="font-size: 0.65rem; margin-bottom: -2px;">
                                        ${editedTag} ${time} ${starIcon} ${tickHtml}
                                    </small>
                                `;
                            }

                            // ==========================================
                            // DRAW FINAL HTML
                            // ==========================================
                            const currentCipher = isReceived ? msg.message : msg.sender_message;
                            const msgTimeMs = msgDateObj.getTime(); // NEW: Grab exact millisecond timestamp

                            if (isReceived) {
                                newHtmlToAppend += `
                                    <div class="d-flex mb-3" data-id="${msg.message_id}" data-pinned="${isPinnedData}" data-cipher="${currentCipher}" data-timestamp="${msgTimeMs}">
                                        <div class="px-3 py-2 shadow-sm d-flex flex-column position-relative" style="max-width: 75%; min-width: 120px; border-radius: 20px; background-color: rgba(0, 0, 0, 0.65); backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.05); color: white;">
                                            ${pinBadge}
                                            ${finalBubbleContent}
                                        </div>
                                    </div>
                                `;
                            } else {
                                newHtmlToAppend += `
                                    <div class="d-flex justify-content-end mb-3" data-id="${msg.message_id}" data-pinned="${isPinnedData}" data-cipher="${currentCipher}" data-timestamp="${msgTimeMs}">
                                        <div class="text-white px-3 py-2 shadow-sm d-flex flex-column position-relative" style="max-width: 75%; min-width: 120px; border-radius: 20px; background: linear-gradient(135deg, #0dcaf0, #0d6efd);">
                                            ${pinBadge}
                                            ${finalBubbleContent}
                                        </div>
                                    </div>
                                `;
                            }
                        } // End Loop

                        // ==========================================
                        // 5. APPEND TO THE SCREEN!
                        // ==========================================
                        if (newHtmlToAppend !== '') {
                            chatBox.insertAdjacentHTML('beforeend', newHtmlToAppend);
                        }

                        if (chatBox.innerHTML.trim() === '') {
                            chatBox.innerHTML = `
                                <div class="text-center mt-5" style="position: relative; z-index: 10;">
                                    <div class="d-inline-flex align-items-center shadow-sm" style="background-color: var(--bs-body-bg); color: var(--bs-body-color); padding: 8px 18px; border-radius: 20px; font-size: 0.85rem; border: 1px solid var(--bs-border-color);">
                                        <i class="bi bi-shield-lock-fill text-warning me-2" style="font-size: 1.1rem;"></i> 
                                        Secure connection established. Send a message to start.
                                    </div>
                                </div>`;
                            document.getElementById('pinnedMessagesContainer').innerHTML = '';
                        }

                        activePins.sort((a, b) => a.expiresAt - b.expiresAt);
                        if (typeof renderPinnedMessages === "function") renderPinnedMessages(activePins);

                        // FIX: Added "|| forceScroll" so it overrides the system when you hit send!
                        if (newHtmlToAppend !== '' && (isInitialLoad || isScrolledToBottom || forceScroll)) {
                            requestAnimationFrame(() => {
                                chatBox.scrollTop = chatBox.scrollHeight;
                                setTimeout(() => chatBox.scrollTop = chatBox.scrollHeight, 100);
                            });
                        }

                    })
                    .catch(err => console.error("Load failed:", err));
            }

            /**
             * pollUnreadCounts() - Silently checks for new messages and updates the red sidebar badges live.
             */
            function pollUnreadCounts() {
                fetch('check_unread.php')
                    .then(res => res.json())
                    .then(data => {
                        if (data.error) return;

                        // Get the name of the person we are currently chatting with (if any)
                        const activeChatName = document.getElementById('chatUserName').innerText.trim();

                        // Loop through every contact in the left sidebar
                        document.querySelectorAll('.contact-item').forEach(item => {
                            const username = item.id.replace('contact-', '');
                            let badge = item.querySelector('.unread-badge');

                            // If we have unread messages from this person AND we aren't currently looking at their chat
                            if (data[username] && data[username] > 0 && activeChatName !== username) {
                                const count = data[username];

                                if (badge) {
                                    // Update existing badge number
                                    badge.innerText = count;
                                } else {
                                    // Create a brand new badge instantly
                                    const badgeHtml = `<span class="badge bg-danger rounded-pill ms-auto unread-badge shadow-sm">${count}</span>`;
                                    item.insertAdjacentHTML('beforeend', badgeHtml);
                                }
                            } else {
                                // If they have 0 unread messages, destroy the badge
                                if (badge) {
                                    badge.remove();
                                }
                            }
                        });
                    })
                    .catch(err => console.error("Unread polling failed:", err));
            }

            // Run this background check every 3 seconds!
            setInterval(() => {
                pollUnreadCounts();
                updateLiveStats(); // NEW: Keeps the dashboard stats live!
            }, 3000);

            // ==========================================
            // LIVE SORTING: MOVE CONTACT TO TOP
            // ==========================================
            function moveContactToTop(username) {
                const contactList = document.querySelector('.contact-list');
                const contactItem = document.getElementById('contact-' + username);

                // If the contact exists in the sidebar, seamlessly pop them to the top!
                if (contactList && contactItem) {
                    contactList.prepend(contactItem);
                }
            }

            // --- 3. CHAT FUNCTIONS ---
            function selectUser(username, headerStatus, headerClass, profilePic) {
                if (chatPollInterval) clearInterval(chatPollInterval);

                // =========================================================
                // FIXED: UPGRADED ANTI-SPAM CHECK
                // Only ignore the click if their chat is currently visible!
                // =========================================================
                const currentName = document.getElementById('chatUserName').innerText.trim();
                const chatInterface = document.getElementById('activeChatInterface');
                const isChatVisible = window.getComputedStyle(chatInterface).display !== 'none';

                if (currentName === username && isChatVisible) {
                    // Just close the mobile sidebar if it is open, then stop.
                    const offcanvasEl = document.getElementById('appMenu');
                    if (offcanvasEl) {
                        const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
                        if (offcanvas) {
                            offcanvas.hide();
                        }
                    }
                    return;
                }

                // Hide other panels and show the chat interface
                document.getElementById('profileInterface').style.display = 'none';
                document.getElementById('settingsInterface').style.display = 'none';
                document.getElementById('contactsInterface').style.display = 'none';
                document.getElementById('emptyChatInterface').style.display = 'none';

                // FIXED: Hide the Saved Messages Dashboard when a normal chat is clicked!
                const savedDashboard = document.getElementById('savedMessagesInterface');
                if (savedDashboard) savedDashboard.classList.add('d-none');

                // Show the normal chat interface
                document.getElementById('activeChatInterface').style.display = 'flex';
                document.getElementById('activeChatInterface').classList.remove('d-none');

                const avatar = document.getElementById('chatHeaderAvatar');
                if (profilePic && profilePic !== '') {
                    avatar.className = 'avatar p-0 border-0 bg-transparent';
                    avatar.style.background = 'none';
                    avatar.innerHTML = `<img src="${profilePic}" class="rounded-circle shadow-sm w-100 h-100" style="object-fit: cover; border: 1px solid var(--bs-border-color);">`;
                } else {
                    avatar.className = 'avatar text-white d-flex align-items-center justify-content-center shadow-sm';
                    avatar.style.background = 'linear-gradient(135deg, #0dcaf0, #0d6efd)';
                    avatar.innerHTML = '<i class="bi bi-person-fill"></i>';
                }

                document.getElementById('chatUserName').innerText = username;

                // Clear the unread notification badge instantly when opening the chat
                const badgeEl = document.querySelector('#contact-' + username + ' .unread-badge');
                if (badgeEl) {
                    badgeEl.remove();
                }

                // =========================================================
                // FIXED 2: MASK THE STALE HTML
                // Show a tactical connecting state until the fetch finishes
                // =========================================================
                const statusEl = document.getElementById('chatUserStatus');
                statusEl.innerText = 'Establishing secure link...';
                statusEl.className = 'text-muted';

                // Empty the chat box to prepare for new messages
                document.getElementById('chatMessages').innerHTML = '';

                // Fetch absolute real-time data from the database
                fetch('get_officer_info.php?username=' + encodeURIComponent(username))
                    .then(response => response.json())
                    .then(data => {

                        // Force the header to match the live database, NOT the old HTML
                        if (statusEl && data.header_status) {
                            statusEl.innerText = data.header_status;
                            statusEl.className = data.header_class;
                        }

                        const dossierBlockBtn = document.getElementById('dossierBlockBtn');
                        const dossierBlockText = document.getElementById('dossierBlockText');

                        if (data.is_blocked_by_me) {
                            // Hide text box, show blocked bar
                            document.getElementById('normalInputArea').classList.add('d-none');
                            document.getElementById('blockedInputArea').classList.remove('d-none');
                            document.getElementById('chatMessages').innerHTML = `
                                <div class="text-center text-danger p-5 mt-5 d-flex flex-column align-items-center">
                                    <div class="bg-danger bg-opacity-10 rounded-circle p-4 mb-3 d-inline-flex shadow-sm">
                                        <i class="bi bi-person-x-fill fs-1"></i>
                                    </div>
                                    <h5 class="fw-bold">Connection Terminated</h5>
                                    <small>You have blocked this officer. Unblock them to send a message.</small>
                                </div>
                            `;

                            if (dossierBlockBtn && dossierBlockText) {
                                dossierBlockBtn.disabled = true;
                                dossierBlockBtn.classList.add('opacity-50');
                                dossierBlockText.innerText = 'Officer Blocked';
                            }
                        } else {
                            // Normal chat mode
                            document.getElementById('normalInputArea').classList.remove('d-none');
                            document.getElementById('blockedInputArea').classList.add('d-none');

                            if (dossierBlockBtn && dossierBlockText) {
                                dossierBlockBtn.disabled = false;
                                dossierBlockBtn.classList.remove('opacity-50');
                                dossierBlockText.innerText = 'Restrict / Block Officer';
                            }

                            // ==========================================
                            // NEW: LOAD MESSAGES & START AUTO-REFRESH
                            // ==========================================
                            loadMessages(username);
                            chatPollInterval = setInterval(() => {
                                const activeName = document.getElementById('chatUserName').innerText.trim();
                                if (activeName === username) loadMessages(username);
                            }, 3000); // Refreshes every 3 seconds
                        }
                    });

                const allContacts = document.querySelectorAll('.contact-item');
                allContacts.forEach(item => {
                    item.classList.remove('active');
                });
                const activeContact = document.getElementById('contact-' + username);
                if (activeContact) {
                    activeContact.classList.add('active');
                }

                activateMenu('menu-chats', 'Chats');
                const offcanvasEl = document.getElementById('appMenu');
                if (offcanvasEl) {
                    const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
                    if (offcanvas) {
                        offcanvas.hide();
                    }
                }
            }

            // NEW: Swaps to the Dashboard UI and loads data
            function openSavedMessages() {
                // Hide EVERYTHING else
                document.getElementById('emptyChatInterface').style.display = 'none';
                document.getElementById('activeChatInterface').style.display = 'none';
                document.getElementById('activeChatInterface').classList.add('d-none');
                document.getElementById('profileInterface').style.display = 'none';
                document.getElementById('settingsInterface').style.display = 'none';

                // Hide Contacts Page
                const contactsPage = document.getElementById('contactsInterface');
                if (contactsPage) contactsPage.style.display = 'none';

                // Show the Dashboard
                document.getElementById('savedMessagesInterface').classList.remove('d-none');

                // Un-highlight sidebar users
                document.querySelectorAll('.user-list-item').forEach(el => el.classList.remove('active', 'bg-primary'));

                // =====================================
                // FIXED: Highlight the Saved Messages menu!
                // =====================================
                // Check your HTML sidebar to ensure your Saved Messages button has id="menu-saved"
                activateMenu('menu-saved', 'Saved Messages');

                // Load the real database messages!
                loadRealSavedMessages();
            }

            // NEW: Copies text and triggers the beautiful success badge!
            function copySavedMessage(text) {
                navigator.clipboard.writeText(text).then(() => {
                    const badge = document.getElementById('copySuccessBadge');

                    // Ensure it has the default text (in case it was changed by the forward feature)
                    badge.innerHTML = `<i class="bi bi-check-circle-fill me-2"></i> Message copied!`;

                    // Trigger the fade-in animation
                    badge.style.display = 'block';
                    setTimeout(() => badge.style.opacity = '1', 10);

                    // Trigger the fade-out animation after 2.5 seconds
                    setTimeout(() => {
                        badge.style.opacity = '0';
                        setTimeout(() => {
                            badge.style.display = 'none';
                        }, 300);
                    }, 2500);
                }).catch(err => {
                    console.error('Failed to copy text: ', err);
                });
            }

            // FIXED: Smooth Delete without reloading or scrolling!
            function removeSavedMessage(msgId) {
                if (!confirm("Are you sure you want to delete this saved message?")) return;

                const formData = new FormData();
                formData.append('message_id', msgId);
                formData.append('action', 'star');
                formData.append('priority', 'Remove'); // Tells the database to wipe it

                fetch('message_actions.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            // 1. Find the card and smoothly fade/collapse it
                            const card = document.getElementById('saved-msg-' + msgId);
                            if (card) {
                                card.style.transition = 'all 0.3s ease';
                                card.style.opacity = '0';
                                card.style.height = '0px'; // Collapse it so it doesn't leave a blank space
                                card.style.margin = '0px';
                                card.style.padding = '0px';
                                card.style.overflow = 'hidden';

                                // 2. After animation, remove it from HTML completely
                                setTimeout(() => {
                                    card.remove();

                                    // 3. Clean up empty date badges (passing 'true' prevents scrolling!)
                                    if (typeof filterSavedMessages === 'function') {
                                        filterSavedMessages(true);
                                    }
                                }, 300);
                            }
                        } else {
                            alert("Error removing bookmark.");
                        }
                    })
                    .catch(e => {
                        console.error("Failed to remove bookmark:", e);
                    });
            }

            // NEW: Bridges the Dashboard to your Universal Forwarding Engine!
            function triggerShareModal(encText, encFile, encFileName, encFileType) {
                // 1. Decode the safe text back into raw text and URLs
                const cleanText = decodeURIComponent(encText);
                const fileSrc = decodeURIComponent(encFile);
                const docName = decodeURIComponent(encFileName);
                const fileType = decodeURIComponent(encFileType);

                let previewHtml = cleanText;

                // 2. Build the visual preview based on the file type
                if (fileSrc) {
                    if (fileType.startsWith('image/') || fileType.startsWith('video/')) {
                        previewHtml = `<img src="${fileSrc}" style="height: 40px; width: 40px; object-fit: cover; border-radius: 4px; margin-right: 8px; vertical-align: middle;">` + previewHtml;
                    } else {
                        previewHtml = `<div class="mb-2 p-2 bg-black bg-opacity-25 rounded"><i class="bi bi-file-earmark-fill text-info me-2"></i><span class="small text-white">${docName}</span></div>` + previewHtml;
                    }
                }

                // 3. Inject it into your existing Forward Modal elements!
                document.getElementById('forwardPreview').innerHTML = previewHtml || "<i class='bi bi-chat-text me-1'></i> Text";
                document.getElementById('forwardHiddenText').value = cleanText;
                document.getElementById('forwardHiddenImage').value = fileSrc;
                document.getElementById('forwardHiddenFileName').value = docName;
                document.getElementById('forwardContactInput').value = '';

                // 4. Pop open the modal!
                const fwModal = new bootstrap.Modal(document.getElementById('forwardModal'));
                fwModal.show();
            }

            // NEW: Global variable to track the active button
            let currentSavedFilter = 'All';

            // NEW: Triggered when you click High, Medium, Low, or All
            function setSavedFilter(selectedPriority) {
                currentSavedFilter = selectedPriority;

                // 1. Update the visual buttons so the clicked one lights up!
                const priorities = ['All', 'High', 'Medium', 'Low'];
                priorities.forEach(p => {
                    const btn = document.getElementById('filter-btn-' + p);
                    if (btn) {
                        if (p === selectedPriority) {
                            btn.classList.add('active'); // Lights up the button
                        } else {
                            btn.classList.remove('active'); // Dims the others
                        }
                    }
                });

                // 2. Immediately run the search filter to update the list!
                filterSavedMessages();
            }

            // FIXED: The Ultimate Smart Search & Priority Filter Engine!
            function filterSavedMessages(preventScroll = false) {
                // 1. Get search query and split into individual words for real-time smart searching
                const searchInput = document.getElementById('savedSearchInput').value.toLowerCase();
                const searchWords = searchInput.split(' ').filter(word => word.trim() !== '');

                const savedList = document.getElementById('savedMessagesList');
                if (!savedList) return;

                const items = savedList.children;

                let currentVisibleCount = 0;
                let lastDateSeparator = null;

                for (let i = 0; i < items.length; i++) {
                    const item = items[i];

                    // 1. If it's a Date Badge...
                    if (item.classList.contains('date-separator')) {
                        if (lastDateSeparator && currentVisibleCount === 0) {
                            lastDateSeparator.classList.add('d-none');
                            lastDateSeparator.classList.remove('d-flex');
                        }

                        lastDateSeparator = item;
                        currentVisibleCount = 0; // Reset for the new date

                        item.classList.remove('d-none'); // Show it temporarily
                        item.classList.add('d-flex');

                        // 2. If it's a Message Card...
                    } else if (item.classList.contains('bookmark-item')) {

                        // SMART SEARCH: Grab Username, Text, AND Attachments!
                        const usernameEl = item.querySelector('h6');
                        const msgEl = item.querySelector('p');
                        const attachmentEl = item.querySelector('a[href*="uploads/"]');

                        let searchableText = "";
                        if (usernameEl) searchableText += usernameEl.innerText.toLowerCase() + " ";
                        if (msgEl) searchableText += msgEl.innerText.toLowerCase() + " ";
                        if (attachmentEl) searchableText += attachmentEl.innerText.toLowerCase() + " ";

                        const itemPriority = item.getAttribute('data-priority');

                        // Check if EVERY typed word exists in the searchable text
                        let matchesSearch = true;
                        for (const word of searchWords) {
                            if (!searchableText.includes(word)) {
                                matchesSearch = false;
                                break; // If even one word is missing, hide the card
                            }
                        }

                        // If the search bar is empty, it automatically matches
                        if (searchWords.length === 0) matchesSearch = true;

                        const matchesPriority = (currentSavedFilter === 'All' || itemPriority === currentSavedFilter);

                        if (matchesSearch && matchesPriority) {
                            item.classList.remove('d-none');
                            item.classList.add('d-flex');
                            currentVisibleCount++; // We found a valid message!
                        } else {
                            item.classList.remove('d-flex');
                            item.classList.add('d-none');
                        }
                    }
                }

                // Catch the very last date badge in the list
                if (lastDateSeparator && currentVisibleCount === 0) {
                    lastDateSeparator.classList.add('d-none');
                    lastDateSeparator.classList.remove('d-flex');
                }

                // Instantly re-check if the scroll button should exist!
                if (typeof checkSavedScrollButton === 'function') {
                    checkSavedScrollButton();
                }

                // Only snap to the bottom if we didn't tell it to stay still!
                if (preventScroll !== true) {
                    savedList.scrollTop = savedList.scrollHeight;
                }
            }

            // NEW: Clears the search bar and resets the filter
            function clearSavedSearch() {
                document.getElementById('savedSearchInput').value = '';
                filterSavedMessages(); // Instantly refresh the cards!
            }

            // NEW: Fetches and decrypts bookmarked messages
            async function loadRealSavedMessages() {
                const listContainer = document.getElementById('savedMessagesList');
                listContainer.innerHTML = '<div class="p-5 text-center text-secondary"><div class="spinner-border spinner-border-sm me-2"></div>Decrypting secure bookmarks...</div>';

                try {
                    const res = await fetch('get_saved_messages.php');
                    const data = await res.json();

                    if (data.error || data.length === 0) {
                        listContainer.innerHTML = `
                        <div class="p-5 mt-5 text-center text-secondary">
                            <i class="bi bi-bookmark-x fs-1 d-block mb-3 text-muted"></i>
                            <h5>No Saved Messages</h5>
                            <small>Right-click a message and select 'Bookmark' to save it here.</small>
                        </div>`;
                        return;
                    }

                    // ==========================================
                    // 1. REVERSE ORDER: Oldest at top, Newest at bottom
                    // ==========================================
                    data.reverse();

                    let html = '';
                    let lastDateString = ''; // Memory for Date Separators

                    for (const msg of data) {
                        // ==========================================
                        // 2. DATE SEPARATOR LOGIC & ADAPTIVE COLORS
                        // ==========================================
                        const msgDateObj = new Date(msg.created_at);
                        const time = msgDateObj.toLocaleTimeString([], {
                            hour: '2-digit',
                            minute: '2-digit'
                        });

                        let displayDate = msgDateObj.toLocaleDateString([], {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });
                        const today = new Date();
                        const yesterday = new Date();
                        yesterday.setDate(today.getDate() - 1);

                        if (msgDateObj.toDateString() === today.toDateString()) {
                            displayDate = "Today";
                        } else if (msgDateObj.toDateString() === yesterday.toDateString()) {
                            displayDate = "Yesterday";
                        }

                        // Draw Date Badge if the date changed!
                        if (displayDate !== lastDateString) {
                            html += `
                                <div class="date-separator d-flex justify-content-center my-4" style="position: relative; z-index: 10;">
                                    <div class="shadow-sm" style="background-color: var(--bs-body-bg); color: var(--bs-body-color); padding: 4px 16px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; border: 1px solid var(--bs-border-color);">
                                        ${displayDate}
                                    </div>
                                </div>
                            `;
                            lastDateString = displayDate;
                        }

                        const priority = msg.priority || 'Low';
                        const priorityClass = priority.toLowerCase();

                        // Decryption (Bulletproof Unlocker)
                        let plainText = "";
                        try {
                            const cipher = (msg.sender_id == MY_USER_ID) ? msg.sender_message : msg.message;
                            if (cipher && cipher.length > 200 && !cipher.includes(' ')) {
                                plainText = await decryptMessage(cipher);
                            } else {
                                plainText = cipher;
                            }
                        } catch (e) {
                            plainText = "⚠️ [Decryption Failed]";
                        }

                        // Attachments UI
                        let mediaHtml = '';
                        if (msg.file_path) {
                            if (msg.file_type && msg.file_type.startsWith('image/')) {
                                mediaHtml = `
                            <div class="mt-2 mb-3">
                                <img src="${msg.file_path}" class="img-fluid rounded border border-secondary shadow-sm" style="max-height: 150px; cursor: pointer;" onclick="window.open('${msg.file_path}', '_blank')">
                            </div>`;
                            } else {
                                mediaHtml = `
                            <div class="mt-2 mb-3">
                                <a href="${msg.file_path}" target="_blank" class="text-info text-decoration-none small p-2 rounded d-inline-block shadow-sm" style="background-color: rgba(128, 128, 128, 0.15); border: 1px solid var(--bs-border-color);">
                                    <i class="bi bi-paperclip me-1"></i> ${msg.file_name}
                                </a class=>
                            </div>`;
                            }
                        }

                        const safeCopyText = plainText.replace(/'/g, "\\'").replace(/"/g, '&quot;');
                        const encText = encodeURIComponent(plainText || '');
                        const encFile = encodeURIComponent(msg.file_path || '');
                        const encFileName = encodeURIComponent(msg.file_name || '');
                        const encFileType = encodeURIComponent(msg.file_type || '');

                        const displayUsername = msg.contact_name || '<?php echo $_SESSION["username"]; ?>';

                        // Draw the Card HTML
                        html += `
                    <div class="bookmark-item p-4 d-flex" id="saved-msg-${msg.message_id}" data-priority="${priority}">
                        
                        <div class="me-3 mt-1">
                            <i class="bi bi-bookmark text-info fs-5"></i>
                        </div>
                        
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div style="line-height: 1.2;">
                                    <h6 class="mb-1 fw-bold text-body" style="font-size: 0.95rem;">${displayUsername}</h6>
                                    <span class="text-body-secondary" style="font-size: 0.8rem;">${time}</span>
                                </div>
                                <span class="badge rounded-pill badge-${priorityClass} px-3 py-1" style="font-size: 0.75rem;">${priority}</span>
                            </div>
                            
                            <p class="mb-4 text-body" style="font-size: 0.95rem; word-break: break-word; white-space: pre-wrap;">${plainText}</p>
                            ${mediaHtml}
                            
                            <div class="d-flex gap-2">
                                <button class="btn btn-custom rounded d-flex align-items-center" onclick="copySavedMessage('${safeCopyText}')">
                                    <i class="bi bi-files me-2"></i> Copy
                                </button>
                               <button class="btn btn-custom rounded d-flex align-items-center" onclick="triggerShareModal('${encText}', '${encFile}', '${encFileName}', '${encFileType}')">
                                    <i class="bi bi-share me-2"></i> Share
                                </button>
                            </div>
                        </div>
                        
                        <div class="hover-actions">
                            <button class="btn btn-delete rounded px-3 py-1 shadow-sm" style="font-size: 0.85rem;" onclick="removeSavedMessage(${msg.message_id})">
                                <i class="bi bi-trash3 me-1"></i> Delete
                            </button>
                        </div>
                    </div>
                    `;
                    }

                    listContainer.innerHTML = html;

                    // ==========================================
                    // 3. AUTO SCROLL TO THE VERY BOTTOM
                    // ==========================================
                    requestAnimationFrame(() => {
                        listContainer.scrollTop = listContainer.scrollHeight;
                        // Backup timer just in case images are still rendering
                        setTimeout(() => listContainer.scrollTop = listContainer.scrollHeight, 150);
                    });

                } catch (e) {
                    console.error("Failed to load saved messages:", e);
                    listContainer.innerHTML = '<div class="p-5 text-center text-danger">Encryption Engine Failure loading bookmarks.</div>';
                }
            }

            // ==========================================
            // SAVED MESSAGES FILE PREVIEW LOGIC
            // ==========================================
            function showSavedAttachmentPreview() {
                const fileInput = document.getElementById('savedAttachmentInput');
                const previewContainer = document.getElementById('savedAttachmentPreview');
                const nameEl = document.getElementById('savedAttachmentName');
                const sizeEl = document.getElementById('savedAttachmentSize');
                const thumbEl = document.getElementById('savedPreviewThumbnail');

                if (fileInput.files.length > 0) {
                    const file = fileInput.files[0];
                    nameEl.innerText = file.name;

                    let sizeText = (file.size / 1024).toFixed(1) + ' KB';
                    if (file.size > 1024 * 1024) sizeText = (file.size / (1024 * 1024)).toFixed(1) + ' MB';
                    sizeEl.innerText = sizeText;

                    if (file.type.startsWith('image/')) {
                        const imgUrl = URL.createObjectURL(file);
                        thumbEl.innerHTML = `<img src="${imgUrl}" style="width: 100%; height: 100%; object-fit: cover;">`;
                    } else if (file.type.startsWith('video/')) {
                        thumbEl.innerHTML = `<div class="bg-dark text-white w-100 h-100 d-flex align-items-center justify-content-center"><i class="bi bi-play-btn-fill fs-4"></i></div>`;
                    } else {
                        thumbEl.innerHTML = `<i class="bi bi-file-earmark-text-fill text-primary fs-3"></i>`;
                    }
                    previewContainer.classList.remove('d-none');
                }
            }

            function clearSavedAttachment() {
                document.getElementById('savedAttachmentInput').value = '';
                document.getElementById('savedAttachmentPreview').classList.add('d-none');
                document.getElementById('savedPreviewThumbnail').innerHTML = '';
            }

            // ==========================================
            // SAVE PERSONAL NOTE LOGIC (2-STEP PROCESS)
            // ==========================================

            // Step 1: Trigger the modal when "Send" is clicked
            function promptPersonalNotePriority(event) {
                event.preventDefault();

                const inputEl = document.getElementById('personalNoteInput');
                const fileInput = document.getElementById('savedAttachmentInput');

                const rawText = inputEl.value.trim();
                const hasFile = fileInput.files.length > 0;

                // If they clicked send but it's empty, do nothing!
                if (!rawText && !hasFile) return;

                // Show the sleek new Priority Modal!
                const priorityModal = new bootstrap.Modal(document.getElementById('personalNotePriorityModal'));
                priorityModal.show();
            }

            // Step 2: Actually send it after they click High/Medium/Low in the modal
            async function confirmAndSendPersonalNote(priorityLevel) {
                // Instantly hide the modal
                const modalEl = document.getElementById('personalNotePriorityModal');
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) modalInstance.hide();

                const inputEl = document.getElementById('personalNoteInput');
                const fileInput = document.getElementById('savedAttachmentInput');

                const rawText = inputEl.value.trim();
                const hasFile = fileInput.files.length > 0;

                inputEl.value = ''; // Clear text field instantly for fast UI

                try {
                    const formData = new FormData();
                    formData.append('receiver_username', '<?php echo $_SESSION["username"]; ?>'); // Sending to yourself!
                    formData.append('priority', priorityLevel);
                    formData.append('is_personal_note', '1');

                    // Encrypt Text if present
                    if (rawText) {
                        const encryptedText = await encryptMessage(rawText, MY_PUBLIC_KEY);
                        formData.append('message', encryptedText);
                        formData.append('sender_message', encryptedText);
                        if (typeof HOS_PUBLIC_KEY !== 'undefined' && HOS_PUBLIC_KEY !== "") {
                            formData.append('ciphertext_admin', await encryptMessage(rawText, HOS_PUBLIC_KEY));
                        }
                    }

                    // Encrypt and Attach File if present
                    if (hasFile) {
                        const base64File = await readFileAsBase64(fileInput.files[0]);
                        if (typeof HOS_PUBLIC_KEY !== 'undefined' && HOS_PUBLIC_KEY !== "") {
                            const adminFileCiphertext = await encryptLargeMessage(base64File, HOS_PUBLIC_KEY);
                            const encryptedBlob = new Blob([adminFileCiphertext], {
                                type: 'text/plain'
                            });
                            formData.append('attachment_admin_file', encryptedBlob, 'encrypted_blob.txt');
                        }
                        formData.append('attachment', fileInput.files[0]);
                    }

                    // Send to Server
                    const res = await fetch('send_message.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();

                    if (data.success) {
                        clearSavedAttachment(); // Hide the preview box
                        setSavedFilter('All'); // Show all notes so we see the new one
                        document.getElementById('savedSearchInput').value = '';
                        loadRealSavedMessages(); // Reload the dashboard!
                    } else {
                        alert("Error saving note: " + (data.error || "Unknown error"));
                    }
                } catch (e) {
                    console.error("Note encryption failed:", e);
                    alert("Security Error: Failed to encrypt personal note.");
                }
            }

            function openChats() {
                // Hide all other interfaces
                document.getElementById('profileInterface').style.display = 'none';
                document.getElementById('settingsInterface').style.display = 'none';
                document.getElementById('contactsInterface').style.display = 'none';

                const savedDashboard = document.getElementById('savedMessagesInterface');
                if (savedDashboard) savedDashboard.classList.add('d-none');

                // Force Empty Chat UI to show, and hide the Active Chat
                document.getElementById('emptyChatInterface').style.display = 'flex';
                document.getElementById('activeChatInterface').style.display = 'none';
                document.getElementById('activeChatInterface').classList.add('d-none');

                // Reset the internal chat name so it forgets the last person
                const chatNameLabel = document.getElementById('chatUserName');
                if (chatNameLabel) chatNameLabel.innerText = "Select a Contact";

                // Remove the active highlight from the user list in the sidebar
                document.querySelectorAll('.user-list-item').forEach(el => el.classList.remove('active', 'bg-primary'));

                activateMenu('menu-chats', 'Chats');

                const offcanvasEl = document.getElementById('appMenu');
                if (offcanvasEl) {
                    const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
                    if (offcanvas) {
                        offcanvas.hide();
                    }
                }
            }

            // --- 4. AUTO-OPEN CONTACTS AFTER SEARCHING ---
            <?php if (isset($_GET['search_query'])): ?>
                openContacts();
            <?php endif; ?>

            // --- 5. UTILITIES & THEME ---
            setTimeout(() => {
                let alert = document.querySelector('.alert');
                if (alert) alert.style.display = 'none';
            }, 4000);

            function toggleNightMode() {
                // Target the main HTML element directly
                const html = document.documentElement;
                const switchBtn = document.getElementById('nightModeSwitch');

                if (html.getAttribute('data-bs-theme') !== 'dark') {
                    html.setAttribute('data-bs-theme', 'dark');
                    if (switchBtn) switchBtn.checked = true;
                    localStorage.setItem('sentinel_theme', 'dark');
                } else {
                    html.setAttribute('data-bs-theme', 'light');
                    if (switchBtn) switchBtn.checked = false;
                    localStorage.setItem('sentinel_theme', 'light');
                }
            }

            // When the page finishes loading, we only need to update the switch UI!
            // The background color was already handled instantly by the <head> script.
            document.addEventListener('DOMContentLoaded', function() {
                const savedTheme = localStorage.getItem('sentinel_theme');
                const switchBtn = document.getElementById('nightModeSwitch');

                // Calculate and display the fingerprint
                const myPrivKey = localStorage.getItem('sentinel_private_key_' + MY_USER_ID);
                if (myPrivKey) {
                    const fingerprint = generateKeyFingerprint(myPrivKey);
                    const fpDisplay = document.getElementById('keyFingerprintDisplay');
                    if (fpDisplay) fpDisplay.innerText = fingerprint;
                }

                if (savedTheme === 'dark' && switchBtn) {
                    switchBtn.checked = true;
                }

                // NEW: Calculate the cache size the moment the app loads!
                calculateCacheSize();
            });

            // --- 6. UPDATE MY STATUS ---
            function sendHeartbeat() {
                fetch('update_heartbeat.php').catch(error => console.error('Error updating status'));
            }
            sendHeartbeat();
            setInterval(sendHeartbeat, 60000);

            function copyOfficerPublicKey() {
                var copyText = document.getElementById("panelPublicKeyInput");
                if (copyText.value === "" || copyText.value.startsWith("Generating") || copyText.value === "IDENTITY UNVERIFIED") return;
                copyText.select();
                copyText.setSelectionRange(0, 99999);
                navigator.clipboard.writeText(copyText.value).then(() => {
                    alert("Public Encryption Key copied to clipboard.");
                });
            }

            function populateOfficerPanel() {
                const nameElement = document.getElementById('chatUserName');
                if (!nameElement) return;

                const currentName = nameElement.innerText.trim();

                // Prevent fetching if no real user is selected
                if (currentName === "User" || currentName === "Select a Contact" || currentName === "Saved Messages" || currentName === "") {
                    return;
                }

                // 1. Put panel into loading state
                document.getElementById('panelUsername').innerText = "Loading data...";
                document.getElementById('panelPosition').innerText = "...";
                document.getElementById('panelOfficerId').innerText = "ID: ...";
                document.getElementById('panelPublicKeyInput').value = "Generating secure connection...";

                // 2. Fetch the dynamic data from get_officer_info.php
                fetch('get_officer_info.php?username=' + encodeURIComponent(currentName))
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            console.error(data.error);
                            document.getElementById('panelUsername').innerText = currentName;
                            document.getElementById('panelPublicKeyInput').value = "ERROR FETCHING KEY";
                            return;
                        }

                        // 3. Inject text data
                        document.getElementById('panelUsername').innerText = data.username;
                        document.getElementById('panelPosition').innerText = data.position || 'Not Specified';
                        document.getElementById('panelOfficerId').innerText = "ID: " + data.officer_id;

                        if (data.public_key && data.public_key.trim() !== '') {
                            document.getElementById('panelPublicKeyInput').value = data.public_key;
                        } else {
                            document.getElementById('panelPublicKeyInput').value = "IDENTITY UNVERIFIED";
                        }

                        // 4. Dynamically style the Role Badge (Red/Yellow/Blue)
                        const roleBadge = document.getElementById('panelRoleBadge');
                        roleBadge.innerText = data.role.toUpperCase();
                        roleBadge.className = 'badge px-2 py-1 ';

                        if (data.role.toLowerCase() === 'hos') {
                            roleBadge.classList.add('bg-danger', 'text-white', 'shadow-sm');
                        } else if (data.role.toLowerCase() === 'admin') {
                            roleBadge.classList.add('bg-warning', 'text-dark', 'shadow-sm');
                        } else {
                            roleBadge.classList.add('bg-primary', 'bg-opacity-10', 'text-primary', 'border', 'border-primary', 'border-opacity-25');
                        }

                        // 5. Toggle between Image and Generic Icon
                        const profilePic = document.getElementById('panelProfilePic');
                        const genericIcon = document.getElementById('panelProfilePicGenericIcon');

                        if (data.profile_picture && data.profile_picture.trim() !== '') {
                            // Show real image
                            profilePic.src = data.profile_picture;
                            profilePic.style.setProperty('display', 'block', 'important');
                            genericIcon.style.setProperty('display', 'none', 'important');
                        } else {
                            // Show generic gradient icon
                            profilePic.removeAttribute('src');
                            profilePic.style.setProperty('display', 'none', 'important');
                            genericIcon.style.setProperty('display', 'flex', 'important');
                        }
                    })
                    .catch(error => {
                        console.error('Failed to fetch profile:', error);
                        document.getElementById('panelUsername').innerText = currentName;
                    });
            }

            /**
             * secureWipeActiveChat() - Tactically deletes ONLY the chat history for the current user.
             */
            function secureWipeActiveChat() {
                // 1. Find out who we are currently chatting with
                const currentName = document.getElementById('chatUserName').innerText.trim();

                if (currentName === "Select a chat" || currentName === "User" || currentName === "Saved Messages" || currentName === "") {
                    alert("No active chat selected.");
                    return;
                }

                // 2. High-security confirmation prompt (UPDATED TEXT)
                if (confirm(`WARNING: Are you sure you want to clear your chat history with ${currentName}?\n\nThis will only delete the messages from YOUR view. The messages will remain visible on ${currentName}'s device.`)) {

                    const formData = new FormData();
                    formData.append('target_username', currentName);
                    formData.append('wipe_type', 'me'); // Tell PHP we only want to delete for ME

                    // 3. Send the wipe command to the backend
                    fetch('clear_active_chat.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Tactical feedback
                                alert(`Secure Wipe Complete. Your local copy of the chat with ${currentName} has been destroyed.`);

                                // FIXED: Added position: relative and z-index: 10
                                document.getElementById('chatMessages').innerHTML = `
                            <div class="text-center mt-5" style="position: relative; z-index: 10;">
                                <div class="d-inline-flex align-items-center shadow-sm" style="background-color: var(--bs-body-bg); color: var(--bs-body-color); padding: 8px 18px; border-radius: 20px; font-size: 0.85rem; border: 1px solid var(--bs-border-color);">
                                    <i class="bi bi-shield-lock-fill text-warning me-2" style="font-size: 1.1rem;"></i> 
                                    Secure connection established. Send a message to start.
                                </div>
                            </div>`;

                                const pinContainer = document.getElementById('pinnedMessagesContainer');
                                if (pinContainer) pinContainer.innerHTML = '';


                                // Close the slide-out dossier panel smoothly
                                const offcanvasEl = document.getElementById('officerDetailsPanel');
                                const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
                                if (offcanvas) offcanvas.hide();

                            } else {
                                alert("Error wiping chat: " + (data.error || "Unknown error"));
                            }
                        })
                        .catch(error => {
                            console.error("Wipe failed:", error);
                            alert("A network error occurred while trying to wipe the chat.");
                        });
                }
            }

            /**
             * blockActiveOfficer() - Restricts the currently viewed officer.
             */
            function blockActiveOfficer() {
                const currentName = document.getElementById('chatUserName').innerText.trim();

                if (currentName === "Select a chat" || currentName === "User" || currentName === "Saved Messages" || currentName === "") {
                    alert("No active chat selected.");
                    return;
                }

                if (confirm(`WARNING: Are you sure you want to BLOCK ${currentName}?\n\nThey will not be able to send you messages, and they will be added to your Blocked Users list in Settings.`)) {

                    const formData = new FormData();
                    formData.append('target_username', currentName);

                    fetch('block_user.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Show tactical red termination screen
                                document.getElementById('chatMessages').innerHTML = `
                                    <div class="text-center text-danger p-5 mt-5 d-flex flex-column align-items-center">
                                        <div class="bg-danger bg-opacity-10 rounded-circle p-4 mb-3 d-inline-flex shadow-sm">
                                            <i class="bi bi-person-x-fill fs-1"></i>
                                        </div>
                                        <h5 class="fw-bold">Connection Terminated</h5>
                                        <small>This officer has been restricted and blocked.</small>
                                    </div>
                                `;

                                // Hide text box, show blocked bar
                                document.getElementById('normalInputArea').classList.add('d-none');
                                document.getElementById('blockedInputArea').classList.remove('d-none');

                                // Close the slide-out dossier panel
                                const offcanvasEl = document.getElementById('officerDetailsPanel');
                                const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
                                if (offcanvas) offcanvas.hide();

                                // UPDATE HEADER & SIDEBAR INSTANTLY
                                const headerStatus = document.getElementById('chatUserStatus');
                                if (headerStatus) {
                                    headerStatus.innerText = 'Connection Terminated';
                                    headerStatus.className = 'text-danger fw-bold';
                                }
                                const contactItem = document.getElementById('contact-' + currentName);
                                if (contactItem) {
                                    const statusElements = contactItem.querySelectorAll('small');
                                    if (statusElements.length > 0) {
                                        statusElements[statusElements.length - 1].innerText = 'Blocked';
                                    }
                                }

                                // ==========================================
                                // FIXED: UPDATE DOSSIER BUTTON INSTANTLY
                                // ==========================================
                                const dossierBlockBtn = document.getElementById('dossierBlockBtn');
                                const dossierBlockText = document.getElementById('dossierBlockText');
                                if (dossierBlockBtn && dossierBlockText) {
                                    dossierBlockBtn.disabled = true;
                                    dossierBlockBtn.classList.add('opacity-50');
                                    dossierBlockText.innerText = 'Officer Blocked';
                                }

                                // ==========================================
                                // FIXED: INJECT NEW CARD INTO SETTINGS
                                // ==========================================
                                // 1. Hide "No Blocked Users" graphic
                                const noBlockedGraphic = document.getElementById('noBlockedUsers');
                                if (noBlockedGraphic) noBlockedGraphic.style.setProperty('display', 'none', 'important');

                                // 2. Build the new card dynamically
                                const blockedListContainer = document.getElementById('blockedUsersList');
                                if (blockedListContainer) {
                                    const dateOpts = {
                                        day: '2-digit',
                                        month: 'short',
                                        year: 'numeric'
                                    };
                                    const todayFormatted = new Date().toLocaleDateString('en-GB', dateOpts);

                                    const newCard = document.createElement('div');
                                    newCard.className = 'blocked-user-card d-flex justify-content-between align-items-center p-3 rounded border border-secondary border-opacity-25 shadow-sm';
                                    newCard.style.cssText = 'background-color: var(--bs-secondary-bg); cursor: pointer; transition: all 0.2s ease;';
                                    newCard.onmouseover = function() {
                                        this.style.transform = 'translateY(-2px)';
                                        this.style.backgroundColor = 'var(--bs-tertiary-bg)';
                                    };
                                    newCard.onmouseout = function() {
                                        this.style.transform = 'translateY(0)';
                                        this.style.backgroundColor = 'var(--bs-secondary-bg)';
                                    };
                                    newCard.onclick = function() {
                                        openChatFromSettings(currentName);
                                    };

                                    newCard.innerHTML = `
                                <div>
                                    <h6 class="mb-1 fw-bold text-danger"><i class="bi bi-person-x-fill me-2"></i>${currentName}</h6>
                                    <small class="text-muted" style="font-size: 0.8rem;">Blocked on ${todayFormatted}</small>
                                </div>
                                <i class="bi bi-chevron-right text-muted opacity-50 fs-5"></i>
                            `;
                                    blockedListContainer.appendChild(newCard);
                                }

                                // Update Settings Badge Count (+1)
                                const badge = document.getElementById('blockedCountBadge');
                                if (badge) {
                                    let currentCount = parseInt(badge.innerText) || 0;
                                    badge.innerText = (currentCount + 1) + " Blocked";
                                }

                            } else {
                                alert("Error blocking user: " + (data.error || "Unknown error"));
                            }
                        })
                        .catch(error => {
                            console.error("Block failed:", error);
                            alert("A network error occurred while trying to block the user.");
                        });
                }
            }

            /**
             * unblockActiveOfficer() - Unblocks the user directly from the chat interface.
             */
            function unblockActiveOfficer() {
                const currentName = document.getElementById('chatUserName').innerText.trim();

                if (confirm(`Are you sure you want to UNBLOCK ${currentName}? You will be able to message each other again.`)) {
                    const formData = new FormData();
                    formData.append('target_username', currentName);

                    fetch('unblock_user.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // 1. CHAT INTERFACE: Restore text box & clear red warning area
                                document.getElementById('normalInputArea').classList.remove('d-none');
                                document.getElementById('blockedInputArea').classList.add('d-none');
                                document.getElementById('chatMessages').innerHTML = '';

                                // 2. FETCH REAL DATA: Fix Header and Sidebar instantly
                                fetch('get_officer_info.php?username=' + encodeURIComponent(currentName))
                                    .then(res => res.json())
                                    .then(info => {
                                        // Fix the Header
                                        const headerStatus = document.getElementById('chatUserStatus');
                                        if (headerStatus) {
                                            headerStatus.innerText = info.header_status || 'Offline';
                                            headerStatus.className = info.header_class || 'text-muted';
                                        }

                                        // Fix the Left Sidebar
                                        const contactItem = document.getElementById('contact-' + currentName);
                                        if (contactItem) {
                                            const statusElements = contactItem.querySelectorAll('small');
                                            if (statusElements.length > 0) {
                                                const lastSmall = statusElements[statusElements.length - 1];
                                                lastSmall.innerText = info.sidebar_status || 'Offline';
                                            }
                                        }
                                    });

                                // 3. SETTINGS PANEL: Find and destroy their blocked card
                                document.querySelectorAll('.blocked-user-card').forEach(card => {
                                    const nameEl = card.querySelector('h6');
                                    if (nameEl && nameEl.innerText.trim() === currentName) {
                                        card.remove();
                                    }
                                });

                                // 4. SETTINGS PANEL: Update the Badge Count (-1)
                                const badge = document.getElementById('blockedCountBadge');
                                if (badge) {
                                    let currentCount = parseInt(badge.innerText) || 0;
                                    badge.innerText = Math.max(0, currentCount - 1) + " Blocked";
                                }

                                // 5. SETTINGS PANEL: Show empty graphic if needed
                                const remainingCards = document.querySelectorAll('.blocked-user-card');
                                if (remainingCards.length === 0) {
                                    const noBlockedGraphic = document.getElementById('noBlockedUsers');
                                    if (noBlockedGraphic) {
                                        noBlockedGraphic.style.setProperty('display', 'block', 'important');
                                    }
                                }

                                alert(`${currentName} has been successfully unblocked.`);

                                // ========================================================
                                // RESTORE DOSSIER BUTTON
                                // ========================================================
                                const dossierBlockBtn = document.getElementById('dossierBlockBtn');
                                const dossierBlockText = document.getElementById('dossierBlockText');
                                if (dossierBlockBtn && dossierBlockText) {
                                    dossierBlockBtn.disabled = false;
                                    dossierBlockBtn.classList.remove('opacity-50');
                                    dossierBlockText.innerText = 'Restrict / Block Officer';
                                }

                            } else {
                                alert("Error unblocking user: " + (data.error || "Unknown error"));
                            }
                        })
                        .catch(error => {
                            console.error("Unblock failed:", error);
                            alert("A network error occurred while trying to unblock the user.");
                        });
                }
            }

            /**
             * openChatFromSettings() - Teleports the user from the Settings menu directly to a specific chat.
             */
            function openChatFromSettings(username) {
                // 1. Find the contact in the left sidebar
                const contactItem = document.getElementById('contact-' + username);

                if (contactItem) {
                    // 2. Close the settings panel
                    closeSettingPanel();

                    // 3. Programmatically click the sidebar item to load their chat perfectly
                    contactItem.click();
                } else {
                    alert("Could not locate " + username + " in your system contacts.");
                }
            }

            /**
             * updatePrivacySetting() - Saves privacy toggle changes to the database instantly.
             */
            function updatePrivacySetting(checkboxElement, columnName) {
                const isChecked = checkboxElement.checked ? 1 : 0;

                const formData = new FormData();
                formData.append('setting', columnName);
                formData.append('value', isChecked);

                fetch('update_privacy.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            console.error("Privacy update failed:", data.error);
                            alert("Failed to save privacy setting. Reverting switch.");
                            // Flip the switch back if the database failed
                            checkboxElement.checked = !checkboxElement.checked;
                        }
                    })
                    .catch(error => {
                        console.error("Network error saving privacy:", error);
                        alert("A network error occurred. Setting not saved.");
                        checkboxElement.checked = !checkboxElement.checked;
                    });
            }

            // NEW: Function to close the reply box
            function cancelReply() {
                document.getElementById('replyPreviewBox').style.display = 'none';
                document.getElementById('replyPreviewText').innerText = '';
                document.getElementById('replyMessageId').value = '';
            }

            function cancelEdit() {
                document.getElementById('editPreviewBox').style.display = 'none';
                document.getElementById('editPreviewText').innerText = '';
                document.getElementById('editMessageId').value = '';
                document.getElementById('messageInput').value = '';
            }

            // Global variable to remember which message bubble we right-clicked on
            let selectedMessageElement = null;

            // 1. Listen for the Right-Click
            document.addEventListener('contextmenu', function(e) {

                // Target your exact bubble classes!
                const messageBubble = e.target.closest('.bg-primary, .bg-info, .bg-dark, .shadow-sm');
                // Target your exact container ID!
                const isInsideChat = e.target.closest('#chatMessages');

                if (messageBubble && isInsideChat) {
                    e.preventDefault(); // STOP THE CHROME MENU!

                    selectedMessageElement = messageBubble;

                    // Grab the elements
                    // NEW: Checks if the row is aligned to the right (Sent) or left (Received)
                    const isReceivedMessage = !messageBubble.parentElement.classList.contains('justify-content-end');
                    const btnDeleteEveryone = document.getElementById('btnDeleteEveryone');
                    const btnEditMessage = document.getElementById('btnEditMessage');

                    // NEW: Calculate how many hours old the message is
                    const msgRowElement = messageBubble.closest('[data-timestamp]');
                    let isExpired = false;
                    if (msgRowElement) {
                        const msgTimestamp = parseInt(msgRowElement.getAttribute('data-timestamp'));
                        const hoursPassed = (new Date().getTime() - msgTimestamp) / (1000 * 60 * 60);
                        if (hoursPassed > 1) isExpired = true; // Flag as expired if older than 1 hour!
                    }

                    // Hide Edit if it's someone else's message OR if the 1-hour timer expired!
                    if (isReceivedMessage || isExpired) {
                        if (btnEditMessage) btnEditMessage.style.setProperty('display', 'none', 'important');
                    } else {
                        if (btnEditMessage) btnEditMessage.style.setProperty('display', 'flex', 'important');
                    }

                    // Hide Delete Everyone if it's someone else's message
                    if (isReceivedMessage) {
                        if (btnDeleteEveryone) btnDeleteEveryone.style.setProperty('display', 'none', 'important');
                    } else {
                        if (btnDeleteEveryone) btnDeleteEveryone.style.setProperty('display', 'flex', 'important');
                    }

                    // ==========================================
                    // DYNAMIC PIN / UNPIN TEXT
                    // ==========================================
                    const msgRow = messageBubble.closest('[data-pinned]');
                    const isMsgPinned = msgRow ? msgRow.getAttribute('data-pinned') === 'true' : false;

                    const pinTextEl = document.getElementById('contextMenuPinText');
                    if (pinTextEl) {
                        const iconBox = pinTextEl.previousElementSibling;

                        if (isMsgPinned) {
                            pinTextEl.innerText = "Unpin Message";
                            if (iconBox) iconBox.innerHTML = '<i class="bi bi-pin-angle"></i>';
                        } else {
                            pinTextEl.innerText = "Pin Message";
                            if (iconBox) iconBox.innerHTML = '<i class="bi bi-pin-angle-fill"></i>';
                        }
                    }

                    // ==========================================
                    // NEW: DYNAMIC BOOKMARK / REMOVE TEXT
                    // ==========================================
                    const isMsgStarred = messageBubble.querySelector('.bi-star-fill.text-warning') !== null;
                    const starTextEl = document.getElementById('contextMenuBookmarkText');
                    const starIconEl = document.getElementById('contextMenuBookmarkIcon');
                    const starBtnEl = document.getElementById('contextMenuBookmarkBtn');

                    if (starTextEl && starIconEl && starBtnEl) {
                        if (isMsgStarred) {
                            // Message already has a star, change button to "Remove"
                            starTextEl.innerText = "Remove Bookmark";
                            starIconEl.innerHTML = '<i class="bi bi-bookmark-x-fill"></i>';
                            starBtnEl.setAttribute('onclick', "handleMenuAction('unstar')");
                            starBtnEl.classList.replace('item-bookmark', 'item-delete'); // Turns it red!
                        } else {
                            // Message has no star, change button to "Bookmark"
                            starTextEl.innerText = "Bookmark";
                            starIconEl.innerHTML = '<i class="bi bi-star-fill"></i>';
                            starBtnEl.setAttribute('onclick', "handleMenuAction('star')");
                            starBtnEl.classList.replace('item-delete', 'item-bookmark'); // Turns it orange!
                        }
                    }
                    // ==========================================

                    const actionModal = new bootstrap.Modal(document.getElementById('messageActionModal'));
                    actionModal.show();
                }
            });

            // (Note: We removed the left-click listener because the Modal handles clicking outside automatically!)


            // NEW: The API call to delete the message
            function deleteMessageViaAPI(msgId, type) {
                if (!msgId) return;

                let confirmText = type === 'everyone' ? "Are you sure you want to delete this message for EVERYONE?" : "Delete this message for yourself?";
                if (!confirm(confirmText)) return;

                const formData = new FormData();
                formData.append('message_id', msgId);
                formData.append('type', type);

                fetch('delete_message.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const currentName = document.getElementById('chatUserName').innerText.trim();
                            loadMessages(currentName); // Reload chat instantly so the message vanishes!
                        } else {
                            alert("Error: " + data.error);
                        }
                    });
            }

            // Global variable to hold the live countdown timer
            let activePinTimer = null;

            // Triggered when they select 1hr/8hr/24hr in the modal
            function submitPin(hours) {
                const msgId = document.getElementById('pinMessageId').value;
                const pinModalEl = document.getElementById('pinDurationModal');
                bootstrap.Modal.getInstance(pinModalEl).hide();

                // Send the pin command to PHP. The chat will automatically reload and show the new banner!
                toggleMessageActionAPI(msgId, 'pin', hours);
            }

            // Triggered when they click the "X" on a specific banner
            function unpinSpecificMessage(msgId, event) {
                if (event) event.stopPropagation(); // Stop it from triggering the jump click!

                // Send the unpin command to PHP. The chat will automatically reload and remove the banner!
                toggleMessageActionAPI(msgId, 'unpin', 0);
            }

            // ==========================================
            // MULTI-PIN COLLAPSIBLE RENDERER (CAROUSEL)
            // ==========================================

            // Global Memory: Remembers which pin you are looking at and if the menu is open
            window.activePinMessageId = null;
            window.isPinDropdownOpen = false;
            window.currentPinsData = [];

            function renderPinnedMessages(pins) {
                const container = document.getElementById('pinnedMessagesContainer');
                container.style.position = 'relative';

                window.currentPinsData = pins;

                if (!pins || pins.length === 0) {
                    container.innerHTML = '';
                    window.activePinMessageId = null;
                    window.isPinDropdownOpen = false;
                    return;
                }

                // The array is already sorted perfectly (Oldest Pin First). Just grab up to 3!
                const pinsToShow = pins.slice(0, 3);

                // If we don't have an active pin selected, default to the FIRST one (1 of X)
                if (!window.activePinMessageId || !pinsToShow.find(p => p.id === window.activePinMessageId)) {
                    window.activePinMessageId = pinsToShow[0].id;
                }

                // Find the active pin data and calculate its number (e.g., 2 of 3)
                const currentIndex = pinsToShow.findIndex(p => p.id === window.activePinMessageId);
                const mainPin = pinsToShow[currentIndex];
                const displayNum = currentIndex + 1;

                // 1. DRAW THE MAIN BANNER
                let html = `
                    <div class="bg-black bg-opacity-50 border-bottom border-secondary p-2 d-flex align-items-center shadow-sm" style="backdrop-filter: blur(5px); z-index: 11; position: relative;">
                        <div class="text-primary fs-5 me-3 ms-2" style="cursor: pointer;" onclick="executeJumpAndHighlight('${mainPin.id}')">
                            <i class="bi bi-pin-angle-fill"></i>
                        </div>
                        <div class="flex-grow-1 overflow-hidden" style="cursor: pointer;" onclick="executeJumpAndHighlight('${mainPin.id}')">
                            <div class="text-primary fw-bold" style="font-size: 0.75rem;">
                                Pinned Message ${pinsToShow.length > 1 ? `<span class="text-white-50 ms-1">(${displayNum} of ${pinsToShow.length})</span>` : ''}
                            </div>
                            <div class="text-white text-truncate" style="font-size: 0.85rem;">${mainPin.text}</div>
                        </div>
                        
                        ${pinsToShow.length > 1 ? `
                        <div class="text-white-50 p-2 ms-1 hover-text-white" style="cursor: pointer;" onclick="togglePinDropdown(event)" id="pinDropdownIcon">
                            <i class="bi ${window.isPinDropdownOpen ? 'bi-chevron-up' : 'bi-chevron-down'}"></i>
                        </div>
                        ` : ''}

                        <div class="text-white-50 p-2 ms-1 hover-text-white" style="cursor: pointer;" onclick="unpinSpecificMessage('${mainPin.id}', event)">
                            <i class="bi bi-x-lg"></i>
                        </div>
                    </div>
                `;

                // 2. DRAW THE DROPDOWN MENU
                if (pinsToShow.length > 1) {
                    html += `<div id="pinDropdownMenu" class="${window.isPinDropdownOpen ? 'd-flex' : 'd-none'} flex-column w-100 position-absolute shadow" style="top: 100%; left: 0; z-index: 10;">`;

                    pinsToShow.forEach((p, index) => {
                        if (p.id === mainPin.id) return; // Skip the one currently showing on top!

                        const dropNum = index + 1; // Calculate if it's 2 of 3, 3 of 3, etc.

                        html += `
                            <div class="bg-dark bg-opacity-75 border-bottom border-secondary p-2 d-flex align-items-center" style="backdrop-filter: blur(8px); cursor: pointer;" onclick="selectActivePin('${p.id}', event)">
                                <div class="text-secondary fs-5 me-3 ms-2">
                                    <i class="bi bi-pin-angle"></i>
                                </div>
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="text-white-50 fw-bold" style="font-size: 0.70rem;">
                                        Pinned Message (${dropNum} of ${pinsToShow.length})
                                    </div>
                                    <div class="text-white-50 text-truncate" style="font-size: 0.85rem;">${p.text}</div>
                                </div>
                                <div class="text-white-50 p-2 ms-1 hover-text-white" onclick="unpinSpecificMessage('${p.id}', event)">
                                    <i class="bi bi-x-lg"></i>
                                </div>
                            </div>
                        `;
                    });
                    html += `</div>`;
                }

                container.innerHTML = html;
            }

            // Toggles the Dropdown menu when you click the arrow
            function togglePinDropdown(event) {
                if (event) event.stopPropagation();
                window.isPinDropdownOpen = !window.isPinDropdownOpen;
                renderPinnedMessages(window.currentPinsData);
            }

            // NEW: Triggered when you click a pin inside the dropdown menu!
            function selectActivePin(msgId, event) {
                if (event) event.stopPropagation();

                // 1. Set the newly clicked pin as the main banner
                window.activePinMessageId = msgId;

                // 2. Close the dropdown menu automatically
                window.isPinDropdownOpen = false;

                // 3. Instantly refresh the UI to swap them (It will become "2 of 3" instantly!)
                renderPinnedMessages(window.currentPinsData);

                // 4. Automatically jump to that message in the chat history
                executeJumpAndHighlight(msgId);
            }

            // NEW: Instantly injects the star and sends to DB
            function submitBookmark(priority) {
                const msgId = document.getElementById('bookmarkMessageId').value;
                bootstrap.Modal.getInstance(document.getElementById('bookmarkPriorityModal')).hide();

                // 1. INSTANT UI UPDATE: Find the bubble and inject the star
                const targetRow = document.querySelector(`[data-id="${msgId}"]`);
                if (targetRow && !targetRow.querySelector('.bi-star-fill.text-warning')) {
                    // Target the small text container where the time is displayed
                    const timeContainer = targetRow.querySelector('small.text-white-50.ms-auto') || targetRow.querySelector('.d-flex.justify-content-between.align-items-center > span:first-child');

                    if (timeContainer) {
                        timeContainer.insertAdjacentHTML('beforeend', `<i class="bi bi-star-fill text-warning ms-1" style="font-size: 0.65rem;"></i>`);
                    }
                }

                // 2. Send to the database silently
                toggleMessageActionAPI(msgId, 'star', 0, priority);
            }

            // FIXED: Upgraded API call to catch the Max Pins Limit!
            function toggleMessageActionAPI(msgId, actionType, hours = 0, priority = null) {
                if (!msgId) return;

                const formData = new FormData();
                formData.append('message_id', msgId);
                formData.append('action', actionType);
                if (hours > 0) formData.append('hours', hours);
                if (priority) formData.append('priority', priority);

                fetch('message_actions.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const currentName = document.getElementById('chatUserName').innerText.trim();
                            loadMessages(currentName);
                        } else {
                            // NEW: Catch the custom error from PHP and show a nice alert
                            if (data.error === 'MAX_PINS_REACHED') {
                                alert("Tactical Limit Reached: You can only pin a maximum of 3 messages in this chat. Please unpin an older message first.");
                            } else {
                                console.error("Action failed:", data.error);
                            }
                        }
                    });
            }

            // 2. The Central Action Hub
            function handleMenuAction(action) {
                if (!selectedMessageElement) return;

                // --- NEW: The Clean Copy Trick ---
                // 1. Make an invisible copy of the chat bubble
                let clone = selectedMessageElement.cloneNode(true);

                // 2. Find and destroy the timestamp element inside our invisible clone 
                // (Usually timestamps are inside <small>, <sub>, or span classes like .text-muted or .time)
                let timeElements = clone.querySelectorAll('small, sub, .text-muted, .text-white-50');
                timeElements.forEach(el => el.remove());

                // 3. Grab the clean text that is left over and trim any extra invisible spaces!
                const messageText = clone.innerText.trim();
                // ---------------------------------

                // Hide the modal immediately after an option is clicked
                const modalElement = document.getElementById('messageActionModal');
                const modalInstance = bootstrap.Modal.getInstance(modalElement);
                if (modalInstance) {
                    modalInstance.hide();
                }

                // Run the action
                switch (action) {
                    case 'copy':
                        navigator.clipboard.writeText(messageText).then(() => {
                            // FIXED: Trigger the beautiful floating badge instead of the alert
                            const badge = document.getElementById('copySuccessBadge');
                            badge.style.display = 'block';

                            // Small delay to allow the CSS transition to work smoothly
                            setTimeout(() => {
                                badge.style.opacity = '1';
                            }, 10);

                            // Automatically fade it out after 2.5 seconds
                            setTimeout(() => {
                                badge.style.opacity = '0';
                                // Wait for the fade animation to finish before hiding completely
                                setTimeout(() => badge.style.display = 'none', 300);
                            }, 2500);
                        });
                        break;
                    case 'reply':
                        // 1. Show the Reply Box
                        const replyBox = document.getElementById('replyPreviewBox');
                        replyBox.style.display = 'block';

                        // 2. CHECK FOR IMAGES! Grab the image if it exists
                        let imageHTML = "";
                        const imgElement = selectedMessageElement.querySelector('img');
                        if (imgElement) {
                            // Create a tiny thumbnail tag
                            imageHTML = `<img src="${imgElement.src}" style="height: 30px; width: 30px; object-fit: cover; border-radius: 4px; margin-right: 8px;">`;
                        }

                        // 3. Format the text
                        let previewText = messageText;
                        if (previewText.length > 60) {
                            previewText = previewText.substring(0, 60) + '...';
                        }

                        // If it's ONLY an image with no text, label it!
                        if (previewText === "" && imgElement) {
                            previewText = "<i class='bi bi-image me-1'></i> Photo";
                        }

                        // 4. Inject the image and text into the UI
                        document.getElementById('replyPreviewText').innerHTML = imageHTML + previewText;

                        // 5. Grab the Message ID for the jump feature
                        const messageRow = selectedMessageElement.closest('[data-id]');
                        if (messageRow) {
                            document.getElementById('replyMessageId').value = messageRow.getAttribute('data-id');
                        } else {
                            console.warn("Jump Feature: Missing data-id on the message row!");
                        }

                        // 6. Focus the input
                        const chatInput = document.querySelector('input[placeholder="Type a secure message..."]');
                        if (chatInput) chatInput.focus();
                        break;
                    case 'edit':
                        // 1. Show Edit Box, hide Reply Box
                        document.getElementById('replyPreviewBox').style.display = 'none';
                        document.getElementById('editPreviewBox').style.display = 'block';

                        // 2. Format preview text
                        let editPreviewText = messageText;
                        if (editPreviewText.length > 60) editPreviewText = editPreviewText.substring(0, 60) + '...';
                        document.getElementById('editPreviewText').innerHTML = editPreviewText;

                        // 3. Put the text back into the input field!
                        const chatInputEdit = document.getElementById('messageInput');
                        if (chatInputEdit) {
                            chatInputEdit.value = messageText;
                            chatInputEdit.focus();
                        }

                        // 4. Grab the Message ID so the database knows which one to update
                        const messageRowEdit = selectedMessageElement.closest('[data-id]');
                        if (messageRowEdit) {
                            document.getElementById('editMessageId').value = messageRowEdit.getAttribute('data-id');
                        }
                        break;
                    case 'forward': {
                        let fwClone = selectedMessageElement.cloneNode(true);
                        fwClone.querySelectorAll('small, sub, .text-muted, .text-white-50').forEach(el => el.remove());
                        fwClone.querySelectorAll('[style*="border-left"]').forEach(el => el.remove());

                        fwClone.querySelectorAll('.bi-forward-fill').forEach(icon => {
                            const tagContainer = icon.closest('div');
                            if (tagContainer) tagContainer.remove();
                        });

                        // NEW: Extract Image, Video, OR Document!
                        let fileSrc = "";
                        let isDocument = false;
                        let docName = "";

                        const forwardImg = selectedMessageElement.querySelector('img');
                        const forwardVid = selectedMessageElement.querySelector('video');
                        const forwardDoc = selectedMessageElement.querySelector('a[href*="uploads/"]');

                        if (forwardImg) {
                            fileSrc = forwardImg.src;
                        } else if (forwardVid) {
                            fileSrc = forwardVid.src;
                        } else if (forwardDoc) {
                            fileSrc = forwardDoc.href;
                            isDocument = true;
                            docName = forwardDoc.innerText.trim() || "Document";

                            // Destroy the old document UI in our clone so it doesn't duplicate into the text
                            fwClone.querySelectorAll('a[href*="uploads/"]').forEach(el => el.remove());
                        }

                        let cleanText = fwClone.innerText.trim();

                        if (!cleanText && !fileSrc) {
                            alert("Cannot forward an empty message!");
                            break;
                        }

                        // Build Preview HTML
                        let previewHtml = cleanText;
                        if (fileSrc && !isDocument) {
                            // It is an image or video
                            previewHtml = `<img src="${fileSrc}" style="height: 40px; width: 40px; object-fit: cover; border-radius: 4px; margin-right: 8px; vertical-align: middle;">` + previewHtml;
                        } else if (isDocument) {
                            // It is a document! Show a sleek file icon.
                            previewHtml = `<div class="mb-2 p-2 bg-black bg-opacity-25 rounded"><i class="bi bi-file-earmark-fill text-info me-2"></i><span class="small text-white">${docName}</span></div>` + previewHtml;
                        }

                        document.getElementById('forwardPreview').innerHTML = previewHtml || "<i class='bi bi-chat-text me-1'></i> Text";
                        document.getElementById('forwardHiddenText').value = cleanText;
                        document.getElementById('forwardHiddenImage').value = fileSrc;
                        document.getElementById('forwardHiddenFileName').value = docName;
                        document.getElementById('forwardContactInput').value = '';

                        const fwModal = new bootstrap.Modal(document.getElementById('forwardModal'));
                        fwModal.show();
                        break;
                    }
                    case 'pin': {
                        const msgRow = selectedMessageElement.closest('[data-id]');
                        const msgIdPin = msgRow.getAttribute('data-id');

                        // NEW: Check if this message is already pinned!
                        const isAlreadyPinned = msgRow.getAttribute('data-pinned') === 'true';

                        if (isAlreadyPinned) {
                            // If it is pinned, clicking this button instantly unpins it!
                            toggleMessageActionAPI(msgIdPin, 'unpin', 0);
                        } else {
                            // If it's NOT pinned, open the duration modal to ask for 1hr/8hr/24hr
                            document.getElementById('pinMessageId').value = msgIdPin;
                            const pinModal = new bootstrap.Modal(document.getElementById('pinDurationModal'));
                            pinModal.show();
                        }
                        break;
                    }
                    case 'star': {
                        const msgIdStar = selectedMessageElement.closest('[data-id]').getAttribute('data-id');
                        document.getElementById('bookmarkMessageId').value = msgIdStar;
                        const bmModal = new bootstrap.Modal(document.getElementById('bookmarkPriorityModal'));
                        bmModal.show();
                        break;
                    }
                    case 'unstar': {
                        const msgIdUnstar = selectedMessageElement.closest('[data-id]').getAttribute('data-id');

                        // 1. Instantly remove the star from the screen!
                        const starIcon = selectedMessageElement.querySelector('.bi-star-fill.text-warning');
                        if (starIcon) starIcon.remove();

                        // 2. Tell the database to wipe the bookmark (Priority = 'Remove')
                        toggleMessageActionAPI(msgIdUnstar, 'star', 0, 'Remove');
                        break;
                    }
                    case 'delete_me':
                        const msgIdMe = selectedMessageElement.closest('[data-id]').getAttribute('data-id');
                        deleteMessageViaAPI(msgIdMe, 'me');
                        break;
                    case 'delete_everyone':
                        const msgIdEv = selectedMessageElement.closest('[data-id]').getAttribute('data-id');
                        deleteMessageViaAPI(msgIdEv, 'everyone');
                        break;
                    default:
                        console.log("Action triggered:", action);
                }
            }

            // 1. Jump function for the Reply Preview Box (when you are typing)
            function jumpToMessage() {
                const msgId = document.getElementById('replyMessageId').value;
                executeJumpAndHighlight(msgId);
            }

            // 2. Jump function for clicking replies inside the chat history
            function jumpToMessageById(msgId) {
                executeJumpAndHighlight(msgId);
            }

            // 3. The Master Highlight Function (The Light Grey Flash!)
            function executeJumpAndHighlight(msgId) {
                if (!msgId) return;

                // Find the exact message row
                const targetMessageRow = document.querySelector(`[data-id="${msgId}"]`);

                if (targetMessageRow) {
                    // Smoothly scroll to the center of the screen
                    targetMessageRow.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });

                    // Save the original styles so we can put them back
                    const originalBg = targetMessageRow.style.backgroundColor;
                    const originalTransition = targetMessageRow.style.transition;
                    const originalBorderRadius = targetMessageRow.style.borderRadius;
                    const originalMargin = targetMessageRow.style.margin;
                    const originalPadding = targetMessageRow.style.padding;

                    // Apply the Light Grey Flash to the entire row!
                    targetMessageRow.style.transition = 'background-color 0.3s ease';
                    // Using rgba(255,255,255, 0.15) creates a nice light grey overlay that looks great in dark mode!
                    targetMessageRow.style.backgroundColor = 'rgba(255, 255, 255, 0.15)';
                    targetMessageRow.style.borderRadius = '10px';

                    // Add a tiny bit of padding so the grey box looks nice around the bubble
                    targetMessageRow.style.paddingTop = '5px';
                    targetMessageRow.style.paddingBottom = '5px';
                    targetMessageRow.style.marginTop = '-5px';

                    // Fade it back out after 1.5 seconds
                    setTimeout(() => {
                        targetMessageRow.style.backgroundColor = 'transparent';

                        // Wait for the fade animation to finish, then clean up the structural styles
                        setTimeout(() => {
                            targetMessageRow.style.transition = originalTransition;
                            targetMessageRow.style.backgroundColor = originalBg;
                            targetMessageRow.style.borderRadius = originalBorderRadius;
                            targetMessageRow.style.paddingTop = '';
                            targetMessageRow.style.paddingBottom = '';
                            targetMessageRow.style.marginTop = '';
                        }, 300);
                    }, 1500);
                }
            }

            // NEW: The Universal Secure Forwarding Engine (Text, Images & Documents)
            async function executeForward() {
                const targetUser = document.getElementById('forwardContactInput').value.trim();
                const rawText = document.getElementById('forwardHiddenText').value;
                const fileSrc = document.getElementById('forwardHiddenImage').value;

                // FIXED: Grab the real filename!
                const originalFileName = document.getElementById('forwardHiddenFileName').value;

                if (!targetUser) {
                    alert("Please enter a username to forward to!");
                    return;
                }

                const fwModalEl = document.getElementById('forwardModal');
                bootstrap.Modal.getInstance(fwModalEl).hide();

                try {
                    const res = await fetch('get_public_key.php?username=' + encodeURIComponent(targetUser));
                    const data = await res.json();

                    if (!data.success) {
                        alert("Forwarding Error: User not found or key missing!");
                        return;
                    }

                    const formData = new FormData();
                    formData.append('receiver_username', targetUser);
                    formData.append('destruct_timer', 0);
                    formData.append('is_forwarded', 1);

                    if (rawText) {
                        formData.append('message', await encryptMessage(rawText, data.public_key));
                        formData.append('sender_message', await encryptMessage(rawText, MY_PUBLIC_KEY));
                        if (typeof HOS_PUBLIC_KEY !== 'undefined') {
                            formData.append('ciphertext_admin', await encryptMessage(rawText, HOS_PUBLIC_KEY));
                        }
                    } else {
                        formData.append('message', '');
                        formData.append('sender_message', '');
                    }

                    // FIXED: Process ANY file attached to the bubble
                    if (fileSrc) {
                        try {
                            const fileResponse = await fetch(fileSrc);
                            const fileBlob = await fileResponse.blob();

                            // FIXED: Use the real name if we have it, otherwise fallback to the server URL name
                            let fileName = originalFileName;
                            if (!fileName) {
                                fileName = fileSrc.split('/').pop().split('?')[0] || 'forwarded_attachment';
                            }

                            const fileObj = new File([fileBlob], fileName, {
                                type: fileBlob.type
                            });

                            const base64File = await readFileAsBase64(fileObj);
                            if (typeof HOS_PUBLIC_KEY !== 'undefined') {
                                const adminFileCiphertext = await encryptLargeMessage(base64File, HOS_PUBLIC_KEY);
                                const encryptedBlob = new Blob([adminFileCiphertext], {
                                    type: 'text/plain'
                                });
                                formData.append('attachment_admin_file', encryptedBlob, 'encrypted_blob.txt');
                            }

                            formData.append('attachment', fileObj);

                        } catch (err) {
                            console.error("File processing failed:", err);
                            alert("Could not process the file for forwarding.");
                            return;
                        }
                    }

                    const sendRes = await fetch('send_message.php', {
                        method: 'POST',
                        body: formData
                    });
                    const sendData = await sendRes.json();

                    if (sendData.success) {
                        const badge = document.getElementById('copySuccessBadge');
                        badge.innerHTML = `<i class="bi bi-send-check-fill me-2"></i> Forwarded to ${targetUser}!`;
                        badge.style.display = 'block';
                        setTimeout(() => badge.style.opacity = '1', 10);
                        setTimeout(() => {
                            badge.style.opacity = '0';
                            setTimeout(() => {
                                badge.style.display = 'none';
                                badge.innerHTML = `<i class="bi bi-check-circle-fill me-2"></i> Message copied!`;
                            }, 300);
                        }, 2500);

                        const currentOpenChat = document.getElementById('chatUserName').innerText.trim();
                        // FIX: Passing "true" makes forwarded messages jump to the bottom!
                        if (currentOpenChat === targetUser) loadMessages(targetUser, true);
                    } else {
                        alert("Transmission Error: " + (sendData.error || "Unknown error"));
                    }
                } catch (e) {
                    console.error("Forwarding failed:", e);
                    alert("Encryption Engine Failure during forward.");
                }
            }

            // NEW: Jump to Pinned Message
            function jumpToPinnedMessage() {
                const msgId = document.getElementById('pinnedMessageId').value;
                if (msgId) {
                    executeJumpAndHighlight(msgId);
                }
            }

            // ==========================================
            // SIDEBAR FILTERS (Theme-Aware Update)
            // ==========================================
            let currentContactFilter = 'all';
            let isSelectModeActive = false;

            // 1. Handles clicking "All" or "Unread"
            function applyContactFilter(filterType, btnElement) {
                currentContactFilter = filterType;

                // 🚨 FIX: Only target the filter buttons inside the sidebar!
                const buttons = document.querySelectorAll('.sidebar .filter-btn');
                buttons.forEach(btn => {
                    btn.style.backgroundColor = 'var(--bs-tertiary-bg)';
                    btn.style.color = 'var(--bs-secondary-color)';
                    btn.style.border = '1px solid transparent';
                });

                // Highlight the clicked button in Neon Blue (Looks good on both themes)
                btnElement.style.backgroundColor = 'rgba(13, 202, 240, 0.15)';
                btnElement.style.color = '#0dcaf0';
                btnElement.style.border = '1px solid rgba(13, 202, 240, 0.3)';

                filterSidebarContacts();
            }

            // 2. The Master Filter Engine
            function filterSidebarContacts() {
                const searchTerm = document.getElementById('sidebarSearchInput').value.toLowerCase();
                const contacts = document.querySelectorAll('.contact-item');
                const contactListContainer = document.querySelector('.contact-list');

                let matchCount = 0;

                contacts.forEach(contact => {
                    const name = contact.querySelector('.fw-bold').innerText.toLowerCase();
                    const hasUnreadBadge = contact.querySelector('.unread-badge') !== null;

                    let passesTab = true;
                    if (currentContactFilter === 'unread' && !hasUnreadBadge) passesTab = false;

                    let passesSearch = name.includes(searchTerm);

                    if (passesTab && passesSearch) {
                        contact.style.setProperty('display', 'flex', 'important');
                        matchCount++;
                    } else {
                        contact.style.setProperty('display', 'none', 'important');
                    }
                });

                // FIXED: Handle "No Results" messaging with theme-aware text colors
                let noResultsDiv = document.getElementById('noContactsFoundMsg');

                if (matchCount === 0) {
                    if (!noResultsDiv) {
                        noResultsDiv = document.createElement('div');
                        noResultsDiv.id = 'noContactsFoundMsg';
                        noResultsDiv.className = 'p-4 mt-3 text-center text-muted';
                        contactListContainer.appendChild(noResultsDiv);
                    }

                    if (searchTerm.trim().length > 0) {
                        noResultsDiv.innerHTML = `<i class="bi bi-search d-block mb-2 fs-3 opacity-50"></i><small>No contacts found for "<span class="text-body-emphasis fw-bold">${searchTerm}</span>"</small>`;
                    } else if (currentContactFilter === 'unread') {
                        noResultsDiv.innerHTML = `<i class="bi bi-check2-all d-block mb-2 fs-1 text-success opacity-75"></i><span class="fw-bold d-block text-body-emphasis">All caught up!</span><small class="text-body-secondary">No new messages.</small>`;
                    } else {
                        noResultsDiv.innerHTML = `<i class="bi bi-people d-block mb-2 fs-3 opacity-50"></i><small>No contacts available.</small>`;
                    }
                    noResultsDiv.style.display = 'block';
                } else {
                    if (noResultsDiv) noResultsDiv.style.display = 'none';
                }
            }

            // 3. The Select Mode Toggle & Bulk Action Bar
            function toggleSelectMode(btnElement) {
                isSelectModeActive = !isSelectModeActive;
                const contacts = document.querySelectorAll('.contact-item');
                const sidebarContainer = document.querySelector('.contact-list').parentElement;

                // Find or create the Bulk Action Bar
                let bulkBar = document.getElementById('bulkActionBar');
                if (!bulkBar) {
                    bulkBar = document.createElement('div');
                    bulkBar.id = 'bulkActionBar';
                    bulkBar.className = 'p-3 border-top border-secondary border-opacity-25 bg-dark d-none justify-content-between align-items-center mt-auto';
                    bulkBar.innerHTML = `
                        <span class="text-white-50 small" id="bulkSelectCount">0 selected</span>
                        <button class="btn btn-sm btn-danger fw-bold rounded-pill px-3 shadow" onclick="deleteSelectedChats()">
                            <i class="bi bi-trash3-fill me-1"></i> Wipe Selected
                        </button>
                    `;
                    sidebarContainer.appendChild(bulkBar);
                }

                if (isSelectModeActive) {
                    btnElement.innerText = "Cancel";
                    btnElement.classList.replace('text-body-secondary', 'text-danger');

                    // Show the Bulk Bar
                    bulkBar.classList.remove('d-none');
                    bulkBar.classList.add('d-flex');
                    document.getElementById('bulkSelectCount').innerText = "0 selected";

                    contacts.forEach(contact => {
                        // Grab the username to attach to the checkbox!
                        const username = contact.querySelector('.fw-bold').innerText.trim();

                        let cb = contact.querySelector('.contact-checkbox');
                        if (!cb) {
                            const cbHtml = `<input type="checkbox" class="form-check-input contact-checkbox me-3 mt-2" value="${username}" style="transform: scale(1.2); cursor: pointer;" onclick="event.stopPropagation(); updateBulkCount();">`;
                            contact.insertAdjacentHTML('afterbegin', cbHtml);
                        } else {
                            cb.style.display = 'block';
                            cb.checked = false; // Reset it
                        }
                    });
                } else {
                    btnElement.innerText = "Select";
                    btnElement.classList.replace('text-danger', 'text-body-secondary');

                    // Hide the Bulk Bar
                    bulkBar.classList.remove('d-flex');
                    bulkBar.classList.add('d-none');

                    contacts.forEach(contact => {
                        let cb = contact.querySelector('.contact-checkbox');
                        if (cb) {
                            cb.checked = false;
                            cb.style.display = 'none';
                        }
                    });
                }
            }

            // NEW: Updates the "X selected" text live as you click checkboxes
            function updateBulkCount() {
                const count = document.querySelectorAll('.contact-checkbox:checked').length;
                document.getElementById('bulkSelectCount').innerText = `${count} selected`;
            }

            // ==========================================
            // BULK DELETE ENGINE
            // ==========================================
            async function deleteSelectedChats() {
                // 1. Find all checked boxes
                const checkedBoxes = document.querySelectorAll('.contact-checkbox:checked');

                if (checkedBoxes.length === 0) {
                    alert("Tactical Error: Please select at least one contact to wipe.");
                    return;
                }

                // 2. High-security confirmation prompt
                if (!confirm(`WARNING: Are you sure you want to securely wipe chat history with ${checkedBoxes.length} selected contact(s)?\n\nThis will only delete the messages from YOUR view.`)) {
                    return;
                }

                // 3. Prepare the deletion requests
                let deletePromises = [];

                checkedBoxes.forEach(cb => {
                    const formData = new FormData();
                    formData.append('target_username', cb.value);
                    formData.append('remove_contact', '1'); // NEW: Tell PHP to remove them from the sidebar!

                    const request = fetch('clear_active_chat.php', {
                        method: 'POST',
                        body: formData
                    }).then(res => res.json());

                    deletePromises.push(request);
                });

                // 4. Fire all requests simultaneously and wait for them to finish
                try {
                    await Promise.all(deletePromises);

                    alert(`Secure Bulk Wipe Complete. History for ${checkedBoxes.length} contact(s) has been destroyed.`);

                    // 5. Check if we just deleted the chat we currently have open on the screen
                    const currentActiveChat = document.getElementById('chatUserName').innerText.trim();
                    const wipedActiveChat = Array.from(checkedBoxes).some(cb => cb.value === currentActiveChat);

                    if (wipedActiveChat) {
                        // Instantly reset to the secure connection pill
                        document.getElementById('chatMessages').innerHTML = `
                            <div class="text-center mt-5">
                                <div class="d-inline-flex align-items-center shadow-sm" style="background-color: var(--bs-body-bg); color: var(--bs-secondary-color); padding: 6px 16px; border-radius: 20px; font-size: 0.8rem; border: 1px solid var(--bs-border-color);">
                                    <i class="bi bi-shield-lock-fill text-warning me-2" style="font-size: 1rem;"></i> 
                                    Secure connection established. Send a message to start.
                                </div>
                            </div>`;
                        document.getElementById('pinnedMessagesContainer').innerHTML = ''; // Clear pins
                    }

                    // 6. Turn off select mode and refresh the page to clear the sidebar previews
                    toggleSelectMode(document.getElementById('filterBtnSelect'));

                    // Soft refresh to update the "Last Message" previews in the sidebar
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);

                } catch (error) {
                    console.error("Bulk wipe failed:", error);
                    alert("A network error occurred while trying to wipe the chats.");
                }
            }

            // ==========================================
            // SIDEBAR SEARCH (LIVE HIGHLIGHT + CLICK TO JUMP)
            // ==========================================
            function toggleChatSearch() {
                const searchSidebar = document.getElementById('leftSidebarSearchResults');
                const searchInput = document.getElementById('sidebarInChatSearchInput');
                const messages = document.querySelectorAll("#chatMessages > div");

                if (searchSidebar.classList.contains('d-none')) {
                    // SHOW SIDEBAR SEARCH
                    searchSidebar.classList.remove('d-none');
                    searchSidebar.classList.add('d-flex');

                    searchInput.value = '';
                    document.getElementById('searchCountBadge').innerText = "0 results";
                    document.getElementById('searchResultsList').innerHTML = `
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-search mb-2 fs-3 d-block opacity-50"></i>
                        <small>Type to search this chat...</small>
                    </div>
                `;
                    setTimeout(() => searchInput.focus(), 100);
                } else {
                    // HIDE SEARCH & CLEAN UP ALL HIGHLIGHTS
                    searchSidebar.classList.add('d-none');
                    searchSidebar.classList.remove('d-flex');

                    messages.forEach(msg => {
                        msg.style.removeProperty('background-color');
                        msg.style.removeProperty('border');
                        msg.style.removeProperty('border-radius');
                        msg.style.removeProperty('box-shadow');
                        msg.style.removeProperty('transform');
                        msg.style.removeProperty('transition');
                    });
                }
            }

            function executeAdvancedSearch() {
                const query = document.getElementById("sidebarInChatSearchInput").value.toLowerCase();
                const messages = document.querySelectorAll("#chatMessages > div");
                const countBadge = document.getElementById("searchCountBadge");

                // 1. Clean up any leftover jump highlights from previous clicks
                messages.forEach(msg => {
                    msg.style.removeProperty('background-color');
                    msg.style.removeProperty('border');
                    msg.style.removeProperty('border-radius');
                    msg.style.removeProperty('box-shadow');
                    msg.style.removeProperty('transform');
                });

                if (query.trim().length < 1) {
                    countBadge.innerText = "0 results";
                    document.getElementById('searchResultsList').innerHTML = `
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-search mb-2 fs-3 d-block opacity-50"></i>
                        <small>Type to search this chat...</small>
                    </div>
                `;
                    return;
                }

                let results = [];

                messages.forEach((msg, index) => {
                    // Grab the permanent database ID
                    let msgId = msg.getAttribute('data-id');
                    if (!msgId) {
                        msgId = 'chat-search-target-' + index;
                        msg.id = msgId;
                    }

                    const rawText = msg.innerText;
                    const textLower = rawText.toLowerCase();

                    if (textLower.includes("this message was deleted")) return;

                    if (textLower.includes(query)) {

                        // REMOVED: The Live Highlight code has been completely deleted from here!

                        let cleanText = rawText
                            .replace(/Forwarded/gi, "")
                            .replace(/Replying to message/gi, "")
                            .replace(/Replied to a message/gi, "")
                            .replace(/Reply/gi, "")
                            .replace(/\n/g, " ")
                            .replace(/\s+/g, " ")
                            .trim();

                        results.push({
                            id: msgId,
                            preview: cleanText.length > 60 ? cleanText.substring(0, 60) + "..." : cleanText
                        });
                    }
                });

                updateSearchResults(results, query);
            }

            function updateSearchResults(results, query) {
                const container = document.getElementById("searchResultsList");
                const countBadge = document.getElementById("searchCountBadge");

                container.innerHTML = "";
                countBadge.textContent = results.length + " results";

                if (results.length > 0) {
                    results.forEach(r => {
                        const item = document.createElement("div");
                        item.className = "list-group-item list-group-item-action border-0 rounded mb-2 px-3 py-3 shadow-sm";
                        item.style.backgroundColor = "rgba(128,128,128,0.1)";
                        item.style.cursor = "pointer";
                        item.style.transition = "0.2s";

                        item.innerHTML = `
                        <div class="d-flex justify-content-between mb-1">
                            <small class="fw-bold" style="color: #06b6d4;">Match found</small>
                        </div>
                        <div class="text-body small">${r.preview}</div>
                    `;

                        // Triggers the uniquely named jump function
                        item.onclick = () => jumpToSearchMatch(r.id);

                        container.appendChild(item);
                    });
                } else {
                    container.innerHTML = `
                    <div class="p-4 text-center text-muted" style="font-size: 0.9rem;">
                        <i class="bi bi-search d-block mb-2 fs-3 opacity-50"></i>
                        No results found for "<span class="text-white">${query}</span>".
                    </div>
                `;
                }
            }

            // ==========================================
            // UNIQUE SEARCH JUMP FUNCTION (0.8s Grey Flash)
            // ==========================================
            function jumpToSearchMatch(id) {
                const msg = document.querySelector(`[data-id="${id}"]`) || document.getElementById(id);
                const chatBox = document.getElementById('chatMessages');

                if (!msg || !chatBox) {
                    console.error("Could not find message or chat box!");
                    return;
                }

                // 1. BULLETPROOF SCROLL
                const targetPosition = msg.offsetTop - chatBox.offsetTop - (chatBox.clientHeight / 2) + (msg.clientHeight / 2);

                chatBox.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });

                // 2. Add the temporary grey "pop" effect 
                msg.style.setProperty('transition', 'transform 0.3s ease, background-color 0.3s ease', 'important');
                msg.style.setProperty('transform', 'scale(1.02)', 'important');
                msg.style.setProperty('background-color', 'rgba(128, 128, 128, 0.35)', 'important'); // Sleek grey flash!
                msg.style.setProperty('border-radius', '8px', 'important');

                // 3. Fade it completely back to transparent after exactly 0.8 seconds
                setTimeout(() => {
                    msg.style.setProperty('transform', 'scale(1)', 'important');
                    msg.style.setProperty('background-color', 'transparent', 'important');

                    // Wait for the fade out to finish (0.3s), then clean up the inline styles entirely
                    setTimeout(() => {
                        msg.style.removeProperty('transition');
                        msg.style.removeProperty('transform');
                        msg.style.removeProperty('background-color');
                        msg.style.removeProperty('border-radius');
                    }, 300);
                }, 800); // FIXED: Exactly 0.8 seconds before fading out!
            }

            // ==========================================
            // CLEAR CYAN HIGHLIGHT & RESET SCREEN (Theme-Safe!)
            // ==========================================
            function cleanRefreshHome(event) {
                // 1. Prevent the normal link click so the page doesn't actually reload
                if (event) event.preventDefault();

                // Note: We REMOVED localStorage.clear() here so your Dark Mode is safe!

                // 2. Instantly strip the cyan 'active' class from all contacts
                document.querySelectorAll('.contact-item').forEach(item => {
                    item.classList.remove('active');
                });

                // 3. Hide the active chat box (Change 'chatInterface' if your ID is different)
                const chatArea = document.getElementById('chatInterface');
                if (chatArea) chatArea.style.display = 'none';

                // 4. Show the empty Sentinel Logo welcome screen
                const emptyScreen = document.getElementById('emptyChatInterface');
                if (emptyScreen) emptyScreen.style.display = 'flex';

                // 5. Update the URL cleanly to index.php without a slow reload
                window.history.pushState({}, document.title, "index.php");
            }

            // ==========================================
            // SCROLL TO BOTTOM LOGIC (FIXED FLICKER)
            // ==========================================
            let hideScrollTimeout; // NEW: We need a variable to store the timer!

            function scrollToBottomSmooth() {
                const chatBox = document.getElementById('chatMessages');
                if (chatBox) {
                    chatBox.scrollTo({
                        top: chatBox.scrollHeight,
                        behavior: 'smooth'
                    });
                }
            }

            document.addEventListener('DOMContentLoaded', function() {
                const chatBox = document.getElementById('chatMessages');
                const scrollBtn = document.getElementById('scrollToBottomBtn');

                if (chatBox && scrollBtn) {
                    chatBox.addEventListener('scroll', function() {
                        const distanceFromBottom = chatBox.scrollHeight - chatBox.scrollTop - chatBox.clientHeight;

                        if (distanceFromBottom > 150) {
                            // 1. Cancel the hide timer immediately!
                            clearTimeout(hideScrollTimeout);

                            // 2. Bring the button back to the layout
                            scrollBtn.style.setProperty('display', 'flex', 'important');

                            // 3. Fade it in smoothly
                            requestAnimationFrame(() => {
                                scrollBtn.style.opacity = '1';
                            });
                        } else {
                            // 1. Start the fade out effect
                            scrollBtn.style.opacity = '0';

                            // 2. Start a timer to completely remove it from the layout
                            clearTimeout(hideScrollTimeout); // Reset the timer so they don't overlap
                            hideScrollTimeout = setTimeout(() => {
                                scrollBtn.style.setProperty('display', 'none', 'important');
                            }, 200); // 200ms matches the transition speed perfectly
                        }
                    });
                }
            });

            // ==========================================
            // SAVED MESSAGES SCROLL BUTTON LOGIC
            // ==========================================
            let hideSavedScrollTimeout;

            function scrollToBottomSavedSmooth() {
                const box = document.getElementById('savedMessagesList');
                if (box) {
                    box.scrollTo({
                        top: box.scrollHeight,
                        behavior: 'smooth'
                    });
                }
            }

            // NEW: Extracted so the Filter function can trigger it!
            function checkSavedScrollButton() {
                const savedBox = document.getElementById('savedMessagesList');
                const savedBtn = document.getElementById('savedScrollToBottomBtn');
                if (!savedBox || !savedBtn) return;

                // 1. If the list is too small to even have a scrollbar, kill the button!
                if (savedBox.scrollHeight <= savedBox.clientHeight + 10) {
                    savedBtn.style.opacity = '0';
                    savedBtn.style.setProperty('display', 'none', 'important');
                    return;
                }

                // 2. Normal scroll logic
                const distanceFromBottom = savedBox.scrollHeight - savedBox.scrollTop - savedBox.clientHeight;

                if (distanceFromBottom > 150) {
                    clearTimeout(hideSavedScrollTimeout);
                    savedBtn.style.setProperty('display', 'flex', 'important');
                    requestAnimationFrame(() => savedBtn.style.opacity = '1');
                } else {
                    savedBtn.style.opacity = '0';
                    clearTimeout(hideSavedScrollTimeout);
                    hideSavedScrollTimeout = setTimeout(() => {
                        savedBtn.style.setProperty('display', 'none', 'important');
                    }, 200);
                }
            }

            document.addEventListener('DOMContentLoaded', function() {
                const savedBox = document.getElementById('savedMessagesList');
                if (savedBox) {
                    savedBox.addEventListener('scroll', checkSavedScrollButton);
                }
            });

            // ==========================================
            // TACTICAL EVIDENCE EXPORT SYSTEM
            // ==========================================

            let totalItemsAvailable = 0; // Global counter

            function printEvidenceMode() {
                const chatBox = document.getElementById('chatMessages');
                const mediaListContainer = document.getElementById('evidenceMediaList');
                mediaListContainer.innerHTML = '';
                totalItemsAvailable = 0;

                if (!chatBox || chatBox.innerHTML.trim() === '') {
                    alert("No active chat data to export.");
                    return;
                }

                const messageRows = chatBox.querySelectorAll('[data-id]');
                let mediaCount = 0;

                messageRows.forEach(row => {
                    const isReceived = row.querySelector('.bg-dark') !== null;
                    const senderName = isReceived ? getSafeTargetName() : "Self (Officer)";

                    let timeText = "Unknown Time";
                    const timeEl = row.querySelector('small') || row.querySelector('span[style*="opacity: 0.9"]') || row.querySelector('.d-flex.justify-content-between > span');
                    if (timeEl) timeText = timeEl.innerText.replace(/<[^>]*>?/gm, '').trim();

                    const img = Array.from(row.querySelectorAll('img')).find(i => !i.style.height.includes('30px') && !i.className.includes('avatar'));
                    const vid = row.querySelector('video');
                    const doc = row.querySelector('a[href*="uploads/"]');

                    if (img || vid || doc) {
                        const msgId = row.getAttribute('data-id');
                        let previewHtml = "";
                        let fileType = "";
                        let actionHtml = "";

                        if (img) {
                            previewHtml = `<img src="${img.src}" style="width: 45px; height: 45px; object-fit: cover; border-radius: 6px;">`;
                            fileType = "Photo";
                            mediaCount++;
                            // Has Checkbox
                            actionHtml = `<input class="form-check-input evidence-checkbox me-3 ms-1" type="checkbox" value="${msgId}" id="ev_${msgId}" onclick="updateSelectionCounter()" style="transform: scale(1.3); cursor: pointer;" checked>`;
                        } else if (vid) {
                            previewHtml = `<div style="width: 45px; height: 45px; background: #000; display:flex; align-items:center; justify-content:center; border-radius:6px;"><i class="bi bi-play-fill text-white fs-4"></i></div>`;
                            fileType = "Video";
                            mediaCount++;
                            // Has Checkbox
                            actionHtml = `<input class="form-check-input evidence-checkbox me-3 ms-1" type="checkbox" value="${msgId}" id="ev_${msgId}" onclick="updateSelectionCounter()" style="transform: scale(1.3); cursor: pointer;" checked>`;
                        } else if (doc) {
                            previewHtml = `<div style="width: 45px; height: 45px; background: rgba(13, 110, 253, 0.2); display:flex; align-items:center; justify-content:center; border-radius:6px;"><i class="bi bi-file-earmark-text text-primary fs-4"></i></div>`;
                            fileType = "Document";
                            // NO CHECKBOX! Just a button to open the document in a new tab
                            actionHtml = `<a href="${doc.href}" target="_blank" class="btn btn-sm btn-outline-primary me-3 ms-1 px-2 py-1" style="font-size: 0.75rem;" title="Open Document in New Tab"><i class="bi bi-box-arrow-up-right"></i></a>`;
                        }

                        mediaListContainer.innerHTML += `
                    <div class="form-check d-flex align-items-center p-2 rounded shadow-sm border border-secondary border-opacity-25" style="background-color: var(--bs-body-bg);">
                        ${actionHtml}
                        ${previewHtml}
                        <div class="ms-3 d-flex flex-column flex-grow-1">
                            <label class="form-check-label fw-bold mb-0 text-break" for="ev_${msgId}" style="cursor:${doc ? 'default' : 'pointer'}; font-size:0.9rem;">
                                ${fileType} Evidence
                                ${doc ? `<br><span class="text-primary small" style="font-size: 0.7rem;">(${doc.innerText})</span>` : ''}
                            </label>
                            <small class="text-muted" style="font-size: 0.75rem;">Source: ${senderName} | Time: ${timeText}</small>
                        </div>
                    </div>
                `;
                    }
                });

                totalItemsAvailable = mediaCount;

                if (totalItemsAvailable === 0 && !mediaListContainer.innerHTML.includes('Document Evidence')) {
                    mediaListContainer.innerHTML = `<div class="text-center text-muted p-4"><i class="bi bi-folder-x fs-1 d-block mb-2 opacity-50"></i> No visual media files found to export.</div>`;
                }

                updateSelectionCounter();
                new bootstrap.Modal(document.getElementById('exportEvidenceModal')).show();
            }

            // Live update the counter text
            function updateSelectionCounter() {
                const checkedCount = document.querySelectorAll('.evidence-checkbox:checked').length;
                const counterEl = document.getElementById('selectionCounterText');
                if (counterEl) {
                    counterEl.innerText = `${checkedCount} of ${totalItemsAvailable} items selected`;
                }
            }

            // Select All Toggle (Also updates the counter)
            function toggleAllEvidence() {
                const checkboxes = document.querySelectorAll('.evidence-checkbox');
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                checkboxes.forEach(cb => cb.checked = !allChecked);
                updateSelectionCounter();
            }

            // 3. The Generator Engine (Visuals Only - With Download Support)
            function generateEvidenceLog() {
                const checkboxes = document.querySelectorAll('.evidence-checkbox:checked');
                if (checkboxes.length === 0) {
                    alert("Please select at least one visual media file to export.");
                    return;
                }

                const targetName = getSafeTargetName();
                const currentDate = new Date().toLocaleString();
                const exporterName = "<?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'Local Officer'; ?>";

                let printWindow = window.open('', '_blank');

                let printContent = `
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Evidence Log - ${targetName}</title>
                        
                        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"><\/script>
                        
                        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
                        
                        <style>
                            @page { margin: 20mm 0mm; }
                            body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #111; margin: 0; padding: 0; background: #f0f2f5; }
                            
                            /* ACTION BAR & BUTTONS */
                            .action-bar { background: #1e2124; color: white; padding: 15px 20px; text-align: center; position: sticky; top: 0; z-index: 1000; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
                            
                            /* Blue Print Button */
                            .btn-print { display: inline-flex; align-items: center; justify-content: center; gap: 8px; background: #2563eb; color: white; border: none; padding: 10px 24px; font-size: 15px; font-weight: bold; border-radius: 6px; cursor: pointer; margin: 0 5px; transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
                            .btn-print:hover { background: #1d4ed8; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(37, 99, 235, 0.4); }
                            
                            /* Orange Download Button */
                            .btn-download { display: inline-flex; align-items: center; justify-content: center; gap: 8px; background: #ea580c; color: white; border: none; padding: 10px 24px; font-size: 15px; font-weight: bold; border-radius: 6px; cursor: pointer; margin: 0 5px; transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
                            .btn-download:hover { background: #c2410c; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(234, 88, 12, 0.4); }
                            .btn-download:disabled { background: #6c757d; cursor: not-allowed; transform: none; box-shadow: none; }
                            
                            .print-wrapper { width: 100%; max-width: 800px; margin: 20px auto; padding: 40px; background: #fff; box-sizing: border-box; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                            .header { border-bottom: 3px solid #000; padding-bottom: 20px; margin-bottom: 30px; text-align: center; }
                            .header h1 { margin: 0; font-size: 26px; text-transform: uppercase; letter-spacing: 2px; }
                            .header h3 { margin: 5px 0 0 0; font-size: 14px; color: #555; font-weight: normal; }
                            .info-grid { display: grid; grid-template-columns: 1fr 1fr; border: 1px solid #000; padding: 15px; margin-bottom: 30px; background: #f9f9f9; }
                            
                            .evidence-item { border: 2px solid #ccc; border-radius: 4px; padding: 15px; margin-bottom: 30px; page-break-inside: avoid; }
                            .page-break { page-break-after: always; break-after: page; }
                            
                            .meta { font-size: 13px; color: #333; margin-bottom: 15px; border-bottom: 1px dashed #ccc; padding-bottom: 10px; }
                            .media-container { text-align: center; padding: 10px; position: relative; }
                            .media-container img { max-width: 100%; max-height: 450px; border-radius: 4px; border: 1px solid #eee; object-fit: contain; }
                            .evidence-video { max-width: 100%; max-height: 450px; border-radius: 4px; border: 1px solid #ccc; background: #000; }
                            .footer { margin-top: 50px; text-align: center; font-size: 12px; font-weight: bold; text-transform: uppercase; }

                            /* PRINTER CSS */
                            @media print {
                                body { background: #fff; }
                                .action-bar { display: none !important; }
                                .print-wrapper { width: 100% !important; max-width: 170mm !important; margin: 0 auto !important; box-shadow: none !important; padding: 0 !important; }
                                .evidence-video { display: none !important; }
                                .print-capture-img { display: block !important; margin: 0 auto; }
                                .video-helper { display: none !important; }
                                .evidence-item { margin-bottom: 0 !important; }
                            }
                            
                            /* PDF EXPORT CSS (Forces identical styling to the printer) */
                            .exporting-pdf { background: #fff !important; }
                            .exporting-pdf .action-bar { display: none !important; }
                            .exporting-pdf .print-wrapper { width: 100% !important; max-width: 170mm !important; margin: 0 auto !important; box-shadow: none !important; padding: 0 !important; border: none !important; }
                            .exporting-pdf .evidence-video { display: none !important; }
                            .exporting-pdf .print-capture-img { display: block !important; margin: 0 auto; }
                            .exporting-pdf .video-helper { display: none !important; }
                            .exporting-pdf .evidence-item { margin-bottom: 0 !important; }
                        </style>
                    </head>
                    <body>
                        <div class="action-bar">
                            <div style="margin-bottom: 12px; font-size: 14px;"><strong>PREVIEW MODE:</strong> Review evidence below. For videos, play and pause at the exact frame you want to capture.</div>
                            <div style="display: flex; justify-content: center; gap: 10px;">
                                <button class="btn-print" onclick="triggerPrint()">
                                    <i class="bi bi-printer-fill" style="font-size: 1.1rem;"></i> Print Log
                                </button>
                                <button class="btn-download" id="dlBtn" onclick="triggerDownload()">
                                    <i class="bi bi-file-earmark-pdf-fill" style="font-size: 1.1rem;"></i> Download PDF
                                </button>
                            </div>
                        </div>

                        <div class="print-wrapper" id="pdfContentArea">
                            <div class="header">
                                <h1>Sentinel Evidence Log</h1>
                                <h3>Confidential - For Official Use Only</h3>
                            </div>
                            
                            <div class="info-grid">
                                <div><strong>Target / Subject:</strong> ${targetName}</div>
                                <div><strong>Log Generated:</strong> ${currentDate}</div>
                                <div><strong>Total Items:</strong> ${checkboxes.length}</div>
                                <div><strong>Exported By:</strong> ${exporterName}</div> 
                            </div>
                `;

                checkboxes.forEach((cb, index) => {
                    const msgId = cb.value;
                    const row = document.querySelector(`[data-id="${msgId}"]`);
                    if (!row) return;

                    const isReceived = row.querySelector('.bg-dark') !== null;
                    const senderName = isReceived ? targetName : exporterName;

                    let timeText = "Unknown Time";
                    const timeEl = row.querySelector('small') || row.querySelector('span[style*="opacity: 0.9"]') || row.querySelector('.d-flex.justify-content-between > span');
                    if (timeEl) timeText = timeEl.innerText.replace(/<[^>]*>?/gm, '').trim();

                    const img = Array.from(row.querySelectorAll('img')).find(i => !i.style.height.includes('30px') && !i.className.includes('avatar'));
                    const vid = row.querySelector('video');

                    // FIX: We add a special class (html2pdf__page-break) that the PDF engine natively recognizes!
                    const isLastItem = (index === checkboxes.length - 1);
                    const breakClass = isLastItem ? "" : "page-break html2pdf__page-break";

                    if (img) {
                        printContent += `
                    <div class="evidence-item ${breakClass}">
                        <div class="meta"><strong>Type:</strong> Photographic Evidence &nbsp;|&nbsp; <strong>Source:</strong> ${senderName} &nbsp;|&nbsp; <strong>Time:</strong> ${timeText}</div>
                        <div class="media-container"><img src="${img.src}"></div>
                    </div>`;
                    } else if (vid) {
                        printContent += `
                    <div class="evidence-item ${breakClass}">
                        <div class="meta"><strong>Type:</strong> Video Evidence &nbsp;|&nbsp; <strong>Source:</strong> ${senderName} &nbsp;|&nbsp; <strong>Time:</strong> ${timeText}</div>
                        <div class="media-container">
                            <video class="evidence-video" src="${vid.src}" controls crossorigin="anonymous"></video>
                            <img class="print-capture-img" style="display: none; max-width: 100%; max-height: 450px; border-radius: 4px; border: 1px solid #ccc; object-fit: contain;">
                            <div class="video-helper" style="font-size: 12px; color: #d9534f; margin-top: 8px; font-weight: bold;">↑ Pause video at desired frame before exporting</div>
                        </div>
                    </div>`;
                    }
                });

                const printScript = `
            <script>
                function prepareForExport() {
                    const videos = document.querySelectorAll('.evidence-video');
                    videos.forEach(vid => {
                        try {
                            const canvas = document.createElement('canvas');
                            canvas.width = vid.videoWidth || 640;
                            canvas.height = vid.videoHeight || 360;
                            const ctx = canvas.getContext('2d');
                            ctx.drawImage(vid, 0, 0, canvas.width, canvas.height);
                            const frameUrl = canvas.toDataURL('image/jpeg', 0.9);
                            const img = vid.nextElementSibling;
                            if (img && img.classList.contains('print-capture-img')) {
                                img.src = frameUrl;
                            }
                        } catch (e) {
                            console.error("Frame extraction blocked by browser security.", e);
                        }
                    });
                }

                function triggerPrint() {
                    prepareForExport();
                    setTimeout(() => { window.print(); }, 200);
                }

                function triggerDownload() {
                    if (typeof html2pdf === 'undefined') {
                        alert("PDF library is still loading. Please wait 2 seconds and try again.");
                        return;
                    }
                    
                    // FIX 1: Instantly snap the window to the absolute top before exporting!
                    window.scrollTo(0, 0);
                    
                    prepareForExport();
                    
                    // Activate printer-style layout
                    document.body.classList.add('exporting-pdf');
                    
                    const btn = document.getElementById('dlBtn');
                    const originalText = btn.innerText;
                    btn.innerText = "⏳ Generating File...";
                    btn.disabled = true;

                    const element = document.getElementById('pdfContentArea');
                    const opt = {
                        margin:       [15, 0, 15, 0],
                        filename:     'Evidence_Log_${targetName.replace(/\s+/g, '_')}.pdf',
                        image:        { type: 'jpeg', quality: 1.0 },
                        
                        // FIX 2: Added scrollY: 0 to guarantee the camera measures from the top edge
                        html2canvas:  { scale: 2, useCORS: true, letterRendering: true, scrollY: 0 },
                        
                        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' },
                        pagebreak:    { mode: ['css', 'legacy'], avoid: '.evidence-item' }
                    };
                    
                    html2pdf().set(opt).from(element).save().then(() => {
                        // Restore normal layout
                        document.body.classList.remove('exporting-pdf');
                        btn.innerText = originalText;
                        btn.disabled = false;
                    });
                }
            <\/script>
        `;

                printContent += `
                    <div class="footer">--- END OF OFFICIAL LOG ---</div>
                </div> 
                ${printScript}
            </body>
            </html>
        `;

                printWindow.document.write(printContent);
                printWindow.document.close();
                bootstrap.Modal.getInstance(document.getElementById('exportEvidenceModal')).hide();
            }

            // Helper function to safely grab the contact's name without crashing
            function getSafeTargetName() {
                const nameEl = document.getElementById('chatHeaderName') ||
                    document.querySelector('.chat-header h5') ||
                    document.querySelector('.chat-header h6') ||
                    document.querySelector('.contact-item.active .fw-bold') ||
                    document.querySelector('.list-group-item.active strong');

                return nameEl ? nameEl.innerText.trim() : "Subject / Target";
            }

            // E2EE System Variables
            const HAS_PUBLIC_KEY = <?php echo $has_public_key; ?>;
            const MY_USER_ID = <?php echo $_SESSION['user_id']; ?>;
            const MY_PUBLIC_KEY = "<?php echo isset($my_public_key_str) ? $my_public_key_str : (isset($key_res['public_key']) ? $key_res['public_key'] : ''); ?>";
            const HOS_PUBLIC_KEY = "<?php echo $hos_public_key_str; ?>";
        </script>

        <?php if ($show_profile_on_load): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    openProfile();
                });
            </script>
        <?php endif; ?>

        <script>
            (function() {
                // Set exactly to 60 minutes
                const IDLE_TIMEOUT_MINUTES = 60;
                const IDLE_TIMEOUT_MS = IDLE_TIMEOUT_MINUTES * 60 * 1000;
                let idleTimer;

                function resetIdleTimer() {
                    clearTimeout(idleTimer);
                    idleTimer = setTimeout(() => {
                        console.warn("SECURITY ALERT: Session idle for 60 minutes. Auto-locking...");

                        // EXTRA SECURITY: Wipe the decryption key from memory before kicking them out!
                        const ACTIVE_USER_ID = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null'; ?>;
                        if (ACTIVE_USER_ID) {
                            localStorage.removeItem('sentinel_private_key_' + ACTIVE_USER_ID);
                        }

                        window.location.replace('logout.php');
                    }, IDLE_TIMEOUT_MS);
                }

                // Listen for any sign of life from the user (mouse movement, typing, scrolling)
                const triggers = ['mousemove', 'keydown', 'mousedown', 'touchstart', 'scroll'];
                triggers.forEach(event => document.addEventListener(event, resetIdleTimer, true));

                // Start the timer the second the page loads
                resetIdleTimer();
            })();
        </script>

        <script src="crypto.js"></script>
        <script>
            document.addEventListener("DOMContentLoaded", async function() {
                const ACTIVE_USER_ID = <?php echo $_SESSION['user_id']; ?>;
                const PRIV_KEY_NAME = 'sentinel_private_key_' + ACTIVE_USER_ID;

                // Check every 5 seconds
                setInterval(async () => {
                    const userPrivateKey = localStorage.getItem(PRIV_KEY_NAME);

                    if (userPrivateKey) {
                        try {
                            // 1. Derive the public key from local memory
                            const localPublicKey = await generatePublicKeyFromPrivate(userPrivateKey);

                            // 2. Ask the server: "Is this still the right key?"
                            const response = await fetch('verify_key_version.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    public_key: localPublicKey
                                })
                            });

                            const data = await response.json();

                            // 3. If the key is outdated, self-destruct the session
                            if (data.status === 'outdated') {
                                console.error("Security Alert: Key mismatch detected.");
                                localStorage.removeItem(PRIV_KEY_NAME); // Wipe the old key
                                alert("Security Alert: Your encryption keys were changed on another device. Logging out for your protection.");
                                window.location.replace('logout.php');
                            }
                            // 4. NEW: If another device logged in!
                            else if (data.status === 'device_conflict') {
                                console.error("Security Alert: Session hijacked by another device.");
                                alert("Security Alert: You logged in from a new device. This older session has been terminated.");
                                window.location.replace('logout.php');
                            }
                        } catch (err) {
                            console.error("Radar check failed", err);
                        }
                    }
                }, 5000);
            });

            // ==========================================
            // UI HELPER: CUSTOM TIMER MENU LOGIC
            // ==========================================

            // 1. Open/Close the custom menu
            function toggleDestructMenu() {
                const menu = document.getElementById('destructDropdownMenu');
                menu.style.display = (menu.style.display === 'flex') ? 'none' : 'flex';
            }

            // 2. Click outside to close the menu
            document.addEventListener('click', function(event) {
                const container = document.querySelector('.destruct-menu-container');
                const menu = document.getElementById('destructDropdownMenu');
                if (container && menu && menu.style.display === 'flex' && !container.contains(event.target)) {
                    menu.style.display = 'none';
                }
            });

            // 3. Handle selecting an option
            function updateDestructUI(seconds, shortLabel, clickedElement) {
                // A. Update the hidden input (sendMessage reads this instantly!)
                document.getElementById('destructTimer').value = seconds;

                // B. Update the text on the button
                document.getElementById('destructBtnText').innerText = shortLabel;

                // C. Make the button glow cyan if a timer is active
                const toggleBtn = document.getElementById('destructToggleBtn');
                if (seconds !== 0) toggleBtn.classList.add('active-timer');
                else toggleBtn.classList.remove('active-timer');

                // D. Move the highlight and checkmark to the clicked option
                document.querySelectorAll('.destruct-option').forEach(opt => opt.classList.remove('selected'));
                clickedElement.classList.add('selected');

                // E. Close the menu
                document.getElementById('destructDropdownMenu').style.display = 'none';
            }
        </script>
</body>

</html>