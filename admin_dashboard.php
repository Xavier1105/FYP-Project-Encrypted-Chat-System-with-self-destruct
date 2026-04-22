<?php
session_start();
require_once 'db_connect.php';

// 1. SECURITY CHECK (Allow both Admin and Head of Security)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'hos'])) {
    header("Location: index.php");
    exit();
}

$message = '';
$admin_id = $_SESSION['user_id'];

// 1.5 FETCH CURRENT LOGGED-IN USER DATA (For Navbar Profile Chip)
$my_info = null;
$stmt_me = $conn->prepare("SELECT profile_picture, role FROM users WHERE user_id = ?");
$stmt_me->bind_param("i", $admin_id);
$stmt_me->execute();
$my_info = $stmt_me->get_result()->fetch_assoc();
$stmt_me->close();

// 2. HANDLE ACTIONS
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && isset($_POST['target_user_id'])) {
        $target_id = intval($_POST['target_user_id']);

        if ($_POST['action'] === 'approve') {
            $stmt = $conn->prepare("UPDATE users SET is_approved = 1 WHERE user_id = ?");
            $stmt->bind_param("i", $target_id);
            if ($stmt->execute()) $message = "<div class='alert alert-success alert-dismissible fade show' role='alert' id='autoDismissAlert'>User approved successfully.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            $stmt->close();
        } elseif ($_POST['action'] === 'delete') {
            // SECURITY CHECK: Find out the role of the person we are trying to delete
            $check_role = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
            $check_role->bind_param("i", $target_id);
            $check_role->execute();
            $target_role = $check_role->get_result()->fetch_assoc()['role'];
            $check_role->close();

            // Only the HOS can revoke the HOS!
            if ($target_role === 'hos' && $_SESSION['role'] !== 'hos') {
                $message = "<div class='alert alert-danger alert-dismissible fade show' role='alert' id='autoDismissAlert'><strong>Access Denied:</strong> Only the Head of Security can authorize the revocation of this account.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            } else {
                $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->bind_param("i", $target_id);
                if ($stmt->execute()) $message = "<div class='alert alert-secondary alert-dismissible fade show' role='alert' id='autoDismissAlert'>User account successfully revoked.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
                $stmt->close();
            }
        } elseif ($_POST['action'] === 'unlock_user') {
            // NEW: Handle unlocking a locked account from the new directory
            $stmt = $conn->prepare("UPDATE users SET is_locked = 0, failed_attempts = 0 WHERE user_id = ?");
            $stmt->bind_param("i", $target_id);
            if ($stmt->execute()) $message = "<div class='alert alert-success alert-dismissible fade show' role='alert' id='autoDismissAlert'><i class='bi bi-unlock-fill me-2'></i> Officer account successfully unlocked and attempts reset.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            $stmt->close();
        }
    }

// ==========================================
    // NEW: HANDLE PASSWORD RESET REQUEST (MODAL UI)
    // ==========================================
    if (isset($_POST['reset_user_password']) && isset($_POST['target_user_id'])) {
        $target_id = intval($_POST['target_user_id']);
        
        // Generate temporary password
        $random_pin = rand(1000, 9999);
        $temp_password = "Temp-" . $random_pin . "!";
        
        // Hash it for the database
        $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
        $stmt->bind_param("si", $hashed_password, $target_id);
        
        if ($stmt->execute()) {
            // INSTEAD OF AN ALERT, SET FLAGS TO TRIGGER THE MODAL
            $show_password_modal = true;
            $generated_temp_password = $temp_password;
            
            // Set the dashboard alert banner as a background confirmation
            $message = "<div class='alert alert-warning alert-dismissible fade show' role='alert' id='autoDismissAlert'><i class='bi bi-key-fill me-2'></i> Officer's password has been temporarily reset.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        } else {
            $message = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>Error: Could not reset password.</div>";
        }
        $stmt->close();
    }
}

// 3. FETCH DATA (Admin Dashboard Stats)
$pending_query = "SELECT user_id, officer_id, username, created_at FROM users WHERE is_approved = 0 AND role != 'admin'";
$pending_result = $conn->query($pending_query);

$active_query = "SELECT user_id, officer_id, username, role, created_at, is_locked FROM users WHERE is_approved = 1 AND is_locked = 0";
$active_result = $conn->query($active_query);

// NEW: FETCH ALL USERS FOR THE DIRECTORY (Sort locked users to the very top!)
$directory_query = "SELECT user_id, officer_id, username, role, is_locked, failed_attempts, is_approved FROM users ORDER BY is_locked DESC, role ASC, username ASC";
$directory_result = $conn->query($directory_query);

// NEW: FETCH COUNT OF LOCKED OFFICERS
$locked_query = "SELECT user_id FROM users WHERE is_locked = 1";
$locked_result = $conn->query($locked_query);
$locked_count = $locked_result->num_rows;

$log_query = "
    SELECT a.log_id, a.created_at, a.ciphertext_admin, a.attachment_admin, /* FIXED: Added attachment_admin here! */
           u1.username AS sender_name, 
           u1.public_key AS sender_public_key, 
           u2.username AS receiver_name
    FROM audit_logs a
    JOIN users u1 ON a.sender_id = u1.user_id
    JOIN users u2 ON a.receiver_id = u2.user_id
    ORDER BY a.created_at DESC";
$log_result = $conn->query($log_query);

// 4. FETCH PENDING CHAT/FRIEND REQUESTS 
$pending_friends_count = 0;
$friend_req_sql = "SELECT COUNT(*) as req_count FROM friend_requests WHERE receiver_id = ? AND status = 'pending'";
$stmt_friend = $conn->prepare($friend_req_sql);
$stmt_friend->bind_param("i", $admin_id);
$stmt_friend->execute();
$friend_result = $stmt_friend->get_result();
$pending_friends_count = $friend_result->fetch_assoc()['req_count'];
$stmt_friend->close();

// 5. FETCH UNREAD MESSAGES
$unread_messages_count = 0;
$unread_stmt = $conn->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND status != 'read'");
$unread_stmt->bind_param("i", $admin_id);
$unread_stmt->execute();
$unread_stmt->bind_result($unread_messages_count);
$unread_stmt->fetch();
$unread_stmt->close();

// COMBINE THEM FOR THE RED BADGE TOTAL
$chat_req_count = $pending_friends_count + $unread_messages_count;

?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light" id="htmlTag">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sentinel - Admin Dashboard</title>
    <link rel="icon" type="image/png" href="Sentinel logo.png">
    <style>
        html[data-bs-theme="dark"],
        html[data-bs-theme="dark"] body {
            background-color: #212529 !important;
            /* Forces dark grey instantly */
            color: #dee2e6 !important;
        }
    </style>
    <script>
        (function() {
            let theme = localStorage.getItem('sentinel_theme');
            if (!theme) {
                // If no theme is saved, check the user's OS preference
                theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            // Instantly apply the theme to the HTML tag
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        /* Updated to use CSS variables for Dark Mode support */
        body {
            background-color: var(--bs-body-bg);
            color: var(--bs-body-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            transition: background-color 0.3s, color 0.3s;

            /* NEW: Forces the background to stretch all the way down! */
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar {
            background-color: #1a2e44;
        }

        .navbar-brand {
            font-weight: bold;
            color: white !important;
        }

        .accordion-item {
            border: 1px solid var(--bs-border-color);
            margin-bottom: 1rem;
            border-radius: 10px !important;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            background-color: var(--bs-body-bg);
        }

        .accordion-button {
            background-color: var(--bs-body-bg);
            color: var(--bs-body-color);
            font-weight: 700;
            padding: 1.2rem;
        }

        .accordion-button:not(.collapsed) {
            background-color: var(--bs-tertiary-bg);
            color: #0d6efd;
            box-shadow: none;
            border-bottom: 1px solid var(--bs-border-color);
        }

        .accordion-button:focus {
            box-shadow: none;
        }

        .encrypted-text {
            font-family: 'Courier New', monospace;
            color: #dc3545;
            font-size: 0.85rem;
        }

        /* ========================================= */
        /* DARK MODE TABLE FIXES                     */
        /* ========================================= */
        [data-bs-theme="dark"] .table-light {
            --bs-table-bg: #2c3034;
            --bs-table-color: #e9ecef;
            --bs-table-border-color: #373b3e;
        }

        [data-bs-theme="dark"] .table {
            --bs-table-color: #ced4da;
        }

        [data-bs-theme="dark"] .table-hover>tbody>tr:hover>* {
            --bs-table-bg-state: #22262a;
            --bs-table-color-state: #f8f9fa;
        }

        /* NEW: Locked row styling */
        .locked-row {
            background-color: rgba(220, 53, 69, 0.05);
        }

        /* ========================================= */
        /* FLOATING CHAT WIDGET STYLES               */
        /* ========================================= */
        .chat-widget-container {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1050;
        }

        .chat-widget-btn {
            width: 65px;
            height: 65px;
            background: linear-gradient(135deg, #0dcaf0, #0d6efd);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            cursor: pointer;
            box-shadow: 0 6px 15px rgba(13, 110, 253, 0.4);
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
            float: right;
            outline: none;
            position: relative;
            /* <-- THIS IS THE FIX! It locks the badge to the button */
        }

        .chat-widget-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(13, 110, 253, 0.5);
        }

        .chat-widget-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background-color: #ef4444;
            color: white;
            font-size: 0.85rem;
            font-weight: bold;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .chat-widget-popup {
            display: none;
            position: absolute;
            bottom: 85px;
            right: 0;
            width: 320px;
            background: var(--bs-body-bg);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            padding: 24px;
            text-align: left;
            border: 1px solid var(--bs-border-color);
            animation: slideUp 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .chat-widget-popup.show {
            display: block;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .chat-popup-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .chat-popup-icon {
            width: 45px;
            height: 45px;
            background-color: rgba(14, 165, 233, 0.1);
            color: #0ea5e9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            margin-right: 15px;
        }

        .chat-popup-title {
            font-weight: 700;
            color: var(--bs-heading-color);
            margin: 0;
            font-size: 1.1rem;
        }

        .chat-popup-subtitle {
            color: var(--bs-secondary-color);
            font-size: 0.85rem;
            margin: 0;
        }

        .chat-popup-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(90deg, #0dcaf0, #0d6efd);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            transition: opacity 0.2s;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .chat-popup-btn:hover {
            opacity: 0.9;
            color: white;
        }

        .chat-popup-footer {
            text-align: center;
            font-size: 0.75rem;
            color: var(--bs-secondary-color);
            margin-top: 15px;
        }

        /* ========================================= */
        /* NAVBAR LOGOUT BUTTON (Glass to Red Hover) */
        /* ========================================= */
        .btn-logout-nav {
            color: #ffffff;
            background-color: rgba(255, 255, 255, 0.15);
            /* Soft glass background */
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s ease-in-out;
            backdrop-filter: blur(4px);
        }

        .btn-logout-nav:hover {
            background-color: #dc3545;
            /* Bootstrap Danger Red */
            border-color: #dc3545;
            color: #ffffff;
            transform: translateY(-2px);
            /* Floats up slightly */
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
            /* Soft red glow */
        }

        .btn-logout-nav i {
            transition: transform 0.2s ease;
        }

        .btn-logout-nav:hover i {
            transform: translateX(3px);
            /* Pushes the arrow icon out slightly when hovered */
        }

        /* Smooth Hover Effect for Action Buttons */
        .btn-action-hover {
            transition: all 0.2s ease-in-out;
        }

        .btn-action-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }

        .btn-action-hover:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Custom Hover Effect for Master Key Button */
        .btn-master-key {
            transition: all 0.2s ease-in-out;
            box-shadow: 0 2px 4px rgba(13, 110, 253, 0.2);
        }

        .btn-master-key:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(13, 110, 253, 0.4);
            background-color: #0b5ed7;
            /* Slightly darker blue on hover */
        }

        .btn-master-key:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(13, 110, 253, 0.2);
        }

        /* Custom Action Buttons to match screenshot */
        .btn-approve-custom {
            background-color: #10b981 !important;
            /* Vibrant Emerald Green */
            color: white !important;
            border: none;
            border-radius: 6px;
        }

        .btn-approve-custom:hover {
            background-color: #059669 !important;
            /* Darker Green on hover */
        }

        .btn-reject-custom {
            background-color: #e11d48 !important;
            /* Vibrant Rose Red */
            color: white !important;
            border: none;
            border-radius: 6px;
        }

        .btn-reject-custom:hover {
            background-color: #be123c !important;
            /* Darker Red on hover */
        }

        /* Custom Teal Button */
        .btn-teal {
            background-color: #1abc9c;
            color: white;
            border: none;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        .btn-teal:hover {
            background-color: #16a085;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(26, 188, 156, 0.3);
        }

        /* Modal Custom Styling */
        .modal-header-teal {
            background-color: #1abc9c;
            color: white;
            border-top-left-radius: 0.5rem;
            border-top-right-radius: 0.5rem;
        }
        .password-display-box {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            /* This creates the soft glowing teal shadow around the box */
            box-shadow: 0 0 25px rgba(26, 188, 156, 0.25); 
            position: relative;
        }
        .warning-box {
            background-color: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 8px;
            color: #92400e;
        }

        /* Custom Crimson Button (For Revoke Access) */
        .btn-crimson {
            background-color: #ef4444; /* Modern soft red */
            color: white;
            border: none;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        .btn-crimson:hover {
            background-color: #dc2626; /* Darker red on hover */
            color: white;
            transform: translateY(-2px); /* Lifts the button up */
            box-shadow: 0 4px 8px rgba(239, 68, 68, 0.3); /* Red glowing shadow */
        }

        /* ========================================= */
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark mb-4 px-3 sticky-top shadow-sm" style="background: linear-gradient(135deg, #0dcaf0, #0d6efd); border-bottom: none; z-index: 1030;">
        <div class="container-fluid">
            <div class="navbar-brand fw-bold text-white d-flex align-items-center">
                <img src="Sentinel logo.png" alt="Sentinel Logo" style="height: 30px; width: auto; object-fit: contain;" class="me-2">
                SENTINEL ADMIN DASHBOARD
            </div>
            <div class="d-flex ms-auto align-items-center">
                <div class="d-none d-md-flex align-items-center px-3 py-2 rounded-pill shadow-sm me-4" style="background-color: rgba(255, 255, 255, 0.15); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.25);">

                    <div class="d-flex align-items-center justify-content-center bg-white rounded-circle me-2 overflow-hidden" style="width: 28px; height: 28px; color: #0d6efd; flex-shrink: 0;">
                        <?php if (isset($my_info['profile_picture']) && !empty($my_info['profile_picture'])): ?>
                            <img src="<?php echo htmlspecialchars($my_info['profile_picture']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <i class="bi bi-person-fill" style="font-size: 1rem;"></i>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex align-items-center">
                        <span class="text-white-50 me-2" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">
                            <?php
                            // 1. Get the role from whichever variable is available
                            $currentRole = '';
                            if (isset($my_info['role'])) {
                                $currentRole = $my_info['role'];
                            } elseif (isset($_SESSION['role'])) {
                                $currentRole = $_SESSION['role'];
                            }

                            // 2. Perform the check (Case-Insensitive)
                            if (strtolower($currentRole) === 'hos') {
                                echo 'Head of Security: ';
                            } else {
                                echo 'Admin: ';
                            }
                            ?>
                        </span>
                        <span class="text-white fw-bold" style="font-size: 0.9rem; letter-spacing: 0.5px;">
                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </span>
                    </div>
                </div>
                <button class="btn btn-outline-light btn-sm me-3 px-3 py-2 d-flex align-items-center justify-content-center" id="themeToggleBtn" onclick="toggleTheme()" title="Toggle Dark/Light Mode">
                    <i class="bi bi-sun-fill fs-6" id="themeIcon"></i>
                </button>

                <a href="logout.php" class="btn btn-sm btn-logout-nav d-flex align-items-center px-3 py-2" onclick="return confirm('Are you sure you want to logout?');">
                    Logout <i class="bi bi-box-arrow-right ms-2 fs-6"></i>
                </a>

            </div>
        </div>
    </nav>

    <div class="container">
        <?php if ($message) echo $message; ?>
        <?php
        // DYNAMIC LAYOUT: If HOS, make cards take up 1/4 of the screen (col-lg-3). 
        // If normal Admin, make cards take up 1/3 of the screen (col-md-4).
        $card_col_class = ($_SESSION['role'] === 'hos') ? 'col-lg-3 col-md-6 mb-3' : 'col-md-4 mb-3';
        ?>
        <div class="row mb-4">
            <div class="<?php echo $card_col_class; ?>">
                <div class="card bg-warning text-dark bg-opacity-10 border-warning h-100 shadow-sm overflow-hidden"
                    onclick="jumpToSection('collapseOne')"
                    style="cursor: pointer; transition: all 0.3s ease-in-out; position: relative;"
                    onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 20px rgba(255, 193, 7, 0.3)';"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                    <i class="bi bi-person-plus-fill text-warning position-absolute end-0 bottom-0 opacity-25 m-2" style="font-size: 3rem;"></i>
                    <div class="card-body text-center">
                        <h5 class="card-title text-muted text-uppercase small fw-bold">Pending Requests</h5>
                        <h2 class="fw-bold display-6 text-warning"><?php echo $pending_result->num_rows; ?></h2>
                    </div>
                </div>
            </div>

            <div class="<?php echo $card_col_class; ?>">
                <div class="card bg-success text-success bg-opacity-10 border-success h-100 shadow-sm overflow-hidden"
                    onclick="jumpToSection('collapseTwo')"
                    style="cursor: pointer; transition: all 0.3s ease-in-out; position: relative;"
                    onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 20px rgba(25, 135, 84, 0.3)';"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                    <i class="bi bi-people-fill text-success position-absolute end-0 bottom-0 opacity-25 m-2" style="font-size: 3rem;"></i>
                    <div class="card-body text-center">
                        <h5 class="card-title text-muted text-uppercase small fw-bold">Active Officers</h5>
                        <h2 class="fw-bold display-6 text-success"><?php echo $active_result->num_rows; ?></h2>
                    </div>
                </div>
            </div>

            <div class="<?php echo $card_col_class; ?>">
                <div class="card bg-danger text-danger bg-opacity-10 border-danger h-100 shadow-sm overflow-hidden"
                    onclick="jumpToSection('collapseDirectory')"
                    style="cursor: pointer; transition: all 0.3s ease-in-out; position: relative;"
                    onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 20px rgba(220, 53, 69, 0.3)';"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                    <i class="bi bi-shield-lock-fill text-danger position-absolute end-0 bottom-0 opacity-25 m-2" style="font-size: 3rem;"></i>
                    <div class="card-body text-center">
                        <h5 class="card-title text-muted text-uppercase small fw-bold">Locked Accounts</h5>
                        <h2 class="fw-bold display-6 text-danger"><?php echo $locked_count; ?></h2>
                    </div>
                </div>
            </div>

            <?php if ($_SESSION['role'] === 'hos'): ?>
                <div class="<?php echo $card_col_class; ?>">
                    <div class="card bg-primary text-primary bg-opacity-10 border-primary h-100 shadow-sm overflow-hidden"
                        onclick="jumpToSection('collapseThree')"
                        style="cursor: pointer; transition: all 0.3s ease-in-out; position: relative;"
                        onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 20px rgba(13, 110, 253, 0.3)';"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                        <i class="bi bi-journal-text text-primary position-absolute end-0 bottom-0 opacity-25 m-2" style="font-size: 3rem;"></i>
                        <div class="card-body text-center">
                            <h5 class="card-title text-muted text-uppercase small fw-bold">Encrypted Logs</h5>
                            <h2 class="fw-bold display-6 text-primary"><?php echo $log_result->num_rows; ?></h2>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="accordion" id="adminAccordion">
            <div class="accordion-item border-warning">
                <h2 class="accordion-header" id="headingOne">
                    <button class="accordion-button fw-bold text-warning" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" style="background-color: rgba(255, 193, 7, 0.1);">
                        <i class="bi bi-person-plus-fill me-2"></i> Registration Requests
                        <?php if ($pending_result->num_rows > 0): ?>
                            <span class="badge bg-warning text-dark ms-2"><?php echo $pending_result->num_rows; ?> New</span>
                        <?php endif; ?>
                    </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#adminAccordion">
                    <div class="accordion-body p-0">
                        <div class="p-3 border-bottom" style="background-color: rgba(255, 193, 7, 0.03);">
                            <div class="input-group input-group-sm" style="max-width: 300px;">
                                <span class="input-group-text border-warning text-warning" style="background-color: rgba(255, 193, 7, 0.1);"><i class="bi bi-search"></i></span>
                                <input type="text" id="pendingSearchInput" class="form-control border-warning border-end-0" style="background-color: rgba(255, 193, 7, 0.05);" placeholder="Search requests..." onkeyup="filterTable('pendingTable', this.value)">
                                <button class="btn btn-outline-warning border-warning" type="button" onclick="clearSearch('pendingSearchInput', 'pendingTable')" style="background-color: rgba(255, 193, 7, 0.1);">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="pendingTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4 user-select-none" style="cursor:pointer;" onclick="sortTable('pendingTable', 0, 'number')">
                                            Officer ID <i class="bi bi-arrow-down-up text-muted ms-1" style="font-size: 0.8rem;"></i>
                                        </th>
                                        <th class="user-select-none" style="cursor:pointer;" onclick="sortTable('pendingTable', 1, 'string')">
                                            Username <i class="bi bi-arrow-down-up text-muted ms-1" style="font-size: 0.8rem;"></i>
                                        </th>
                                        <th class="user-select-none" style="cursor:pointer;" onclick="sortTable('pendingTable', 2, 'date')">
                                            Registered At <i class="bi bi-arrow-down-up text-muted ms-1" style="font-size: 0.8rem;"></i>
                                        </th>
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($pending_result->num_rows > 0): ?>
                                        <?php while ($row = $pending_result->fetch_assoc()): ?>
                                            <tr>
                                                <td class="ps-4 fw-bold"><?php echo htmlspecialchars($row['officer_id']); ?></td>
                                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                                <td><?php echo date('d M Y, h:i A', strtotime($row['created_at'])); ?></td>
                                                <td class="text-end pe-4 align-middle">
                                                    <div class="d-flex justify-content-end gap-2">
                                                        <form method="POST" class="m-0">
                                                            <input type="hidden" name="target_user_id" value="<?php echo $row['user_id']; ?>">
                                                            <input type="hidden" name="action" value="approve">
                                                            <button type="submit" class="btn btn-sm fw-bold px-3 btn-approve-custom btn-action-hover">Approve</button>
                                                        </form>
                                                        <form method="POST" class="m-0" onsubmit="return confirm('Reject this user?');">
                                                            <input type="hidden" name="target_user_id" value="<?php echo $row['user_id']; ?>">
                                                            <input type="hidden" name="action" value="delete">
                                                            <button type="submit" class="btn btn-sm fw-bold px-3 btn-reject-custom btn-action-hover">Reject</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">No pending requests.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="accordion-item border-success">
                <h2 class="accordion-header" id="headingTwo">
                    <button class="accordion-button collapsed fw-bold text-success" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" style="background-color: rgba(25, 135, 84, 0.1);">
                        <i class="bi bi-people-fill me-2"></i> Active Officers Directory
                    </button>
                </h2>
                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#adminAccordion">
                    <div class="accordion-body p-0">
                        <div class="p-3 border-bottom" style="background-color: rgba(25, 135, 84, 0.03);">
                            <div class="input-group input-group-sm" style="max-width: 300px;">
                                <span class="input-group-text border-success text-success" style="background-color: rgba(25, 135, 84, 0.1);"><i class="bi bi-search"></i></span>
                                <input type="text" id="activeSearchInput" class="form-control border-success border-end-0" style="background-color: rgba(25, 135, 84, 0.05);" placeholder="Search officers..." onkeyup="filterTable('activeTable', this.value)">
                                <button class="btn btn-outline-success border-success" type="button" onclick="clearSearch('activeSearchInput', 'activeTable')" style="background-color: rgba(25, 135, 84, 0.1);">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="activeTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4 user-select-none" style="cursor:pointer;" onclick="sortTable('activeTable', 0, 'string')">
                                            Officer ID <i class="bi bi-arrow-down-up text-muted ms-1" style="font-size: 0.8rem;"></i>
                                        </th>
                                        <th class="user-select-none" style="cursor:pointer;" onclick="sortTable('activeTable', 1, 'string')">
                                            Username <i class="bi bi-arrow-down-up text-muted ms-1" style="font-size: 0.8rem;"></i>
                                        </th>
                                        <th class="user-select-none" style="cursor:pointer;" onclick="sortTable('activeTable', 2, 'string')">
                                            Role <i class="bi bi-arrow-down-up text-muted ms-1" style="font-size: 0.8rem;"></i>
                                        </th>
                                        <th class="user-select-none" style="cursor:pointer;" onclick="sortTable('activeTable', 3, 'date')">
                                            Active Since <i class="bi bi-arrow-down-up text-muted ms-1" style="font-size: 0.8rem;"></i>
                                        </th>
                                        <th class="text-end pe-4">Management</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($active_result->num_rows > 0): ?>
                                        <?php
                                        // Reset the pointer so we can loop again since we used the same result query
                                        $active_result->data_seek(0);
                                        while ($row = $active_result->fetch_assoc()):
                                        ?>
                                            <tr class="<?php echo ($row['is_locked'] == 1) ? 'locked-row' : ''; ?>">
                                                <td class="ps-4 fw-bold text-body">
                                                    <?php echo htmlspecialchars($row['officer_id']); ?>
                                                </td>
                                                <td class="fw-bold">
                                                    <?php echo htmlspecialchars($row['username']); ?>
                                                    <?php if ($row['is_locked'] == 1): ?>
                                                        <span class="badge bg-danger ms-2" style="font-size: 0.65rem; vertical-align: middle;"><i class="bi bi-lock-fill"></i> LOCKED</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (strtolower($row['role']) === 'hos'): ?>
                                                        <span class="badge bg-danger text-white shadow-sm px-2 py-1">HOS</span>
                                                    <?php elseif (strtolower($row['role']) === 'admin'): ?>
                                                        <span class="badge bg-warning text-dark shadow-sm px-2 py-1">ADMIN</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">OFFICER</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                                <td class="text-end pe-4">
                                                    <?php if ($row['user_id'] == $_SESSION['user_id']): ?>
                                                        <span class="badge bg-secondary px-3 py-2">You</span>
                                                    <?php elseif ($row['role'] === 'hos' && $_SESSION['role'] !== 'hos'): ?>
                                                        <button class="btn btn-outline-secondary btn-sm" disabled title="Requires HOS Authorization to Modify">
                                                            <i class="bi bi-lock-fill"></i> Restricted
                                                        </button>
                                                    <?php else: ?>
                                                        <form method="POST" class="d-inline" onsubmit="return confirm('Revoke access for this officer?');">
                                                            <input type="hidden" name="target_user_id" value="<?php echo $row['user_id']; ?>">
                                                            <input type="hidden" name="action" value="delete">
                                                            <button type="submit" class="btn btn-crimson btn-sm fw-bold px-3">
                                                                <i class="bi bi-person-x-fill me-1"></i> Revoke Access
                                                            </button>
                                                        </form>
                                                        
                                                        <form method="POST" action="admin_dashboard.php" class="d-inline ms-1">
                                                            <input type="hidden" name="target_user_id" value="<?php echo $row['user_id']; ?>">
                                                            <button type="submit" name="reset_user_password" class="btn btn-teal btn-sm fw-bold px-3" onclick="return confirm('Are you sure you want to reset this user\'s password?');">
                                                                <i class="bi bi-key me-1"></i> Reset Password
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">No active officers found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="accordion-item border-danger">
                <h2 class="accordion-header" id="headingDirectory">
                    <button class="accordion-button collapsed text-danger fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDirectory" style="background-color: rgba(220, 53, 69, 0.1);">
                        <i class="bi bi-shield-lock-fill me-2"></i> Security Directory & Access Control
                    </button>
                </h2>
                <div id="collapseDirectory" class="accordion-collapse collapse" aria-labelledby="headingDirectory" data-bs-parent="#adminAccordion">
                    <div class="accordion-body p-0">
                        <div class="p-3 border-bottom" style="background-color: rgba(220, 53, 69, 0.03);">
                            <div class="input-group input-group-sm" style="max-width: 300px;">
                                <span class="input-group-text border-danger text-danger" style="background-color: rgba(220, 53, 69, 0.1);"><i class="bi bi-search"></i></span>
                                <input type="text" id="directorySearchInput" class="form-control border-danger border-end-0" style="background-color: rgba(220, 53, 69, 0.05);" placeholder="Search locked accounts..." onkeyup="filterTable('directoryTable', this.value)">
                                <button class="btn btn-outline-danger border-danger" type="button" onclick="clearSearch('directorySearchInput', 'directoryTable')" style="background-color: rgba(220, 53, 69, 0.1);">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="directoryTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4 user-select-none" style="cursor:pointer;" onclick="sortTable('directoryTable', 0, 'string')">
                                            Officer ID <i class="bi bi-arrow-down-up text-muted ms-1" style="font-size: 0.8rem;"></i>
                                        </th>
                                        <th class="user-select-none" style="cursor:pointer;" onclick="sortTable('directoryTable', 1, 'string')">
                                            Username <i class="bi bi-arrow-down-up text-muted ms-1" style="font-size: 0.8rem;"></i>
                                        </th>
                                        <th class="user-select-none" style="cursor:pointer;" onclick="sortTable('directoryTable', 2, 'string')">
                                            Role <i class="bi bi-arrow-down-up text-muted ms-1" style="font-size: 0.8rem;"></i>
                                        </th>
                                        <th>Security Status</th>
                                        <th>Failed Logins</th>
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $locked_count = 0;
                                    if ($directory_result->num_rows > 0):
                                        while ($row = $directory_result->fetch_assoc()):
                                            // NEW: Only process this row if the account is actually locked!
                                            if ($row['is_locked'] == 1):
                                                $locked_count++;
                                    ?>
                                                <tr class="locked-row">
                                                    <td class="ps-4 fw-bold text-body"><?php echo htmlspecialchars($row['officer_id']); ?></td>
                                                    <td class="fw-bold"><?php echo htmlspecialchars($row['username']); ?></td>
                                                    <td>
                                                        <?php if (strtolower($row['role']) === 'hos'): ?>
                                                            <span class="badge bg-danger text-white shadow-sm px-2 py-1">HOS</span>
                                                        <?php elseif (strtolower($row['role']) === 'admin'): ?>
                                                            <span class="badge bg-warning text-dark shadow-sm px-2 py-1">ADMIN</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">OFFICER</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-danger fw-bold px-2 py-1 shadow-sm"><i class="bi bi-lock-fill me-1"></i> LOCKED</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-danger fw-bold"><i class="bi bi-exclamation-triangle-fill me-1"></i> <?php echo $row['failed_attempts']; ?></span>
                                                    </td>
                                                    <td class="text-end pe-4">
                                                        <form method="POST" class="m-0 d-inline-block">
                                                            <input type="hidden" name="action" value="unlock_user">
                                                            <input type="hidden" name="target_user_id" value="<?php echo $row['user_id']; ?>">
                                                            <button type="submit" class="btn btn-sm fw-bold px-3 btn-approve-custom btn-action-hover shadow-sm" onclick="return confirm('Are you sure you want to unlock <?php echo htmlspecialchars($row['username']); ?>?');">
                                                                <i class="bi bi-unlock-fill me-1"></i> Unlock Account
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                        <?php
                                            endif;
                                        endwhile;
                                    endif;

                                    // NEW: Show a success message if no locked accounts were found
                                    if ($locked_count === 0):
                                        ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">
                                                <i class="bi bi-shield-check display-4 opacity-25 d-block mb-3"></i>
                                                <h5 class="fw-bold text-body">All Systems Secure</h5>
                                                <p class="mb-0">There are currently no locked accounts.</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($_SESSION['role'] === 'hos'): ?>
                <div class="accordion-item border-primary">
                    <h2 class="accordion-header" id="headingThree">
                        <button class="accordion-button collapsed fw-bold text-primary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" style="background-color: rgba(13, 110, 253, 0.1);">
                            <i class="bi bi-journal-text me-2"></i> Compliance Audit Log
                            <span class="badge bg-primary ms-2 small">Encrypted</span>
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#adminAccordion">
                        <div class="accordion-body p-0">

                            <div class="p-4 text-center border-bottom border-primary" id="masterKeyUploadUI" style="background-color: var(--bs-body-bg);">
                                <i class="bi bi-safe2-fill display-4 text-primary mb-2"></i>
                                <h5 class="fw-bold" style="color: var(--bs-body-color);">Cold Storage Vault Locked</h5>
                                <p class="small mb-4" style="color: var(--bs-secondary-color);">Upload the physical Master Audit Key to decrypt historical logs.</p>
                                
                                <input type="file" id="masterKeyFileInput" class="d-none" accept=".json" onchange="loadMasterKey(event)">
                                <button class="btn btn-primary fw-bold px-4 py-2 btn-master-key mb-3" style="border-radius: 8px;" onclick="document.getElementById('masterKeyFileInput').click()">
                                    <i class="bi bi-upload me-2"></i> Insert Master Key
                                </button>

                                <div>
                                    <a href="setup_master_key.php" class="btn btn-outline-danger btn-sm fw-bold border-danger border-opacity-25 shadow-sm" onclick="return confirm('EMERGENCY RESET:\n\nGenerating a new Master Key will permanently lock all previous audit logs. Only do this if you have completely lost your original .json file.\n\nProceed to reset?');">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Lost Key? Emergency Reset
                                    </a>
                                </div>
                            </div>

                            <div class="p-2 bg-success text-white text-center border-bottom d-none" id="masterKeySuccessUI">
                                <i class="bi bi-unlock-fill me-2"></i> Vault Unlocked. Master Key loaded in temporary memory.
                            </div>

                            <div class="p-3 border-bottom" style="background-color: rgba(13, 110, 253, 0.03);">
                                <div class="input-group input-group-sm" style="max-width: 350px;">
                                    <span class="input-group-text border-primary text-primary" style="background-color: rgba(13, 110, 253, 0.1);">
                                        <i class="bi bi-search"></i>
                                    </span>

                                    <input type="text" id="auditSearchInput" class="form-control border-primary border-end-0"
                                        style="background-color: rgba(13, 110, 253, 0.05);"
                                        placeholder="Search logs by name or ID..."
                                        onkeyup="filterAuditLogs(this.value)">

                                    <button class="btn btn-outline-primary border-primary border-start-0" type="button"
                                        onclick="clearSearch('auditSearchInput', 'auditLogTableBody')"
                                        style="background-color: rgba(13, 110, 253, 0.1); border-top-right-radius: 8px; border-bottom-right-radius: 8px;">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Timestamp</th>
                                            <th>Sender</th>
                                            <th>Receiver</th>
                                            <th>Content Status</th>
                                            <th class="text-end pe-4">Integrity</th>
                                        </tr>
                                    </thead>
                                    <tbody id="auditLogTableBody">
                                        <?php if ($log_result->num_rows > 0): ?>
                                            <?php while ($row = $log_result->fetch_assoc()): ?>
                                                <tr class="audit-log-row">
                                                    <td class="ps-4 text-muted small"><?php echo $row['created_at']; ?></td>
                                                    <td class="fw-bold text-primary"><?php echo htmlspecialchars($row['sender_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['receiver_name']); ?></td>
                                                    <td>
                                                        <span class="badge bg-secondary"><i class="bi bi-lock-fill"></i> Encrypted</span>
                                                        <div class="encrypted-text text-truncate" style="max-width: 200px;">
                                                            <?php echo htmlspecialchars(substr($row['ciphertext_admin'], 0, 20)) . '...'; ?>
                                                        </div>
                                                    </td>
                                                    <td class="text-end pe-4">
                                                        <button class="btn btn-sm btn-outline-danger unlock-btn"
                                                            data-attachment="<?php echo htmlspecialchars($row['attachment_admin'] ?? ''); ?>"
                                                            onclick="unlockLog(this, '<?php echo $row['ciphertext_admin']; ?>', '<?php echo $row['sender_public_key']; ?>')">
                                                            <i class="bi bi-key-fill"></i> Decrypt
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr id="noLogsRow">
                                                <td colspan="5" class="text-center py-4 text-muted">No logs available.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-between align-items-center p-3 border-top theme-aware-pagination" id="auditPaginationWrapper" style="display: none !important;">
                            </div>

                            <div class="card-footer text-muted small border-0 border-top theme-aware-footer">
                                * Note: Message content is encrypted using AES-256. Decryption requires the specialized Private Key held by the Head of Security.
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="chat-widget-container">
        <div class="chat-widget-popup" id="chatPopup">
            <div class="chat-popup-header">
                <div class="chat-popup-icon">
                    <i class="bi bi-chat-dots"></i>
                </div>
                <div>
                    <h4 class="chat-popup-title">Open Chat</h4>
                    <p class="chat-popup-subtitle">
                        <?php
                        // UPDATED: Now says "new message" / "new messages"
                        if ($chat_req_count > 0) {
                            echo $chat_req_count . ($chat_req_count == 1 ? " new notification" : " new notifications");
                        } else {
                            echo "Secure communications";
                        }
                        ?>
                    </p>
                </div>
            </div>
            <a href="index.php" class="chat-popup-btn">
                <i class="bi bi-chat-text-fill me-2"></i> Start Conversation
            </a>
            <div class="chat-popup-footer">
                Click above to open the chat interface
            </div>
        </div>

        <button class="chat-widget-btn" onclick="toggleChatPopup()">
            <i class="bi bi-chat-fill" id="chatWidgetIcon"></i>
            <span class="chat-widget-badge" id="chatWidgetBadge" style="display: <?php echo ($chat_req_count > 0) ? 'flex' : 'none'; ?>; transition: transform 0.2s ease;">
                <?php echo $chat_req_count > 0 ? $chat_req_count : ''; ?>
            </span>
        </button>
    </div>

    <div class="modal fade" id="imageViewerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content bg-dark">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center pt-0">
                    <img id="fullSizeImage" src="" class="img-fluid rounded shadow" style="max-height: 85vh;" alt="Decrypted Evidence">
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($show_password_modal) && $show_password_modal === true): ?>
    <div class="modal fade" id="tempPasswordModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 0.5rem;">
                
                <div class="modal-header modal-header-teal border-bottom-0">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-shield-check me-2"></i> Password Reset Successful
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body px-4 py-4">
                    <p class="text-muted mb-4 text-center">Please provide the officer with their new temporary password:</p>

                    <div class="password-display-box mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-secondary fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">TEMPORARY PASSWORD</span>
                            
                            <button type="button" class="btn btn-sm btn-light border d-flex align-items-center fw-bold text-secondary" onclick="copyTempPassword()" id="copyBtn">
                                <i class="bi bi-copy me-1"></i> Copy
                            </button>
                        </div>
                        <h2 class="mb-0 fw-bold" style="color: #1abc9c; letter-spacing: 1px;" id="tempPasswordText">
                            <?php echo htmlspecialchars($generated_temp_password); ?>
                        </h2>
                    </div>

                    <div class="warning-box p-3 mb-4 d-flex align-items-start">
                        <i class="bi bi-exclamation-circle text-warning fs-5 me-3 mt-1"></i>
                        <div>
                            <strong style="color: #b45309;">Action Required</strong>
                            <p class="mb-0 mt-1" style="font-size: 0.9rem;">Remind the officer to update this password in their profile settings immediately after logging in.</p>
                        </div>
                    </div>

                    <button type="button" class="btn btn-teal w-100 fw-bold py-2 rounded-3" data-bs-dismiss="modal" style="font-size: 1.05rem;">
                        I have copied the password
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var myModal = new bootstrap.Modal(document.getElementById('tempPasswordModal'));
            myModal.show();
        });

        // Function to copy text to clipboard
        function copyTempPassword() {
            var passwordText = document.getElementById('tempPasswordText').innerText;
            navigator.clipboard.writeText(passwordText).then(function() {
                var copyBtn = document.getElementById('copyBtn');
                // Change button to show success
                copyBtn.innerHTML = '<i class="bi bi-check2 text-success me-1"></i> Copied!';
                // Change it back after 2 seconds
                setTimeout(function() {
                    copyBtn.innerHTML = '<i class="bi bi-copy me-1"></i> Copy';
                }, 2000);
            });
        }
    </script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Dismiss Alerts
        const alertBox = document.getElementById('autoDismissAlert');
        if (alertBox) {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alertBox);
                bsAlert.close();
            }, 5000);
        }

        // Toggle Floating Chat Widget
        function toggleChatPopup() {
            const popup = document.getElementById('chatPopup');
            const icon = document.getElementById('chatWidgetIcon');
            const badge = document.getElementById('chatWidgetBadge');

            popup.classList.toggle('show');

            if (popup.classList.contains('show')) {
                // 1. OPENING: Change to X icon and hide the badge
                icon.classList.remove('bi-chat-fill');
                icon.classList.add('bi-x-lg');
                if (badge) badge.style.display = 'none';

            } else {
                // 2. CLOSING: Change back to Chat icon
                icon.classList.remove('bi-x-lg');
                icon.classList.add('bi-chat-fill');

                // INSTANT FIX: If the badge has a number saved in it, show it immediately!
                if (badge && parseInt(badge.innerText) > 0) {
                    badge.style.display = 'flex';
                }

                // Force an instant background check just to be perfectly synced
                if (typeof checkLiveNotifications === 'function') {
                    checkLiveNotifications();
                }
            }
        }

        // The function that safely updates the UI
        function updateChatBadge(unreadCount) {
            const badge = document.getElementById('chatWidgetBadge');
            const popup = document.getElementById('chatPopup');
            const subtitle = document.querySelector('.chat-popup-subtitle');

            if (!badge || !popup) return;

            const count = parseInt(unreadCount) || 0;

            if (count > 0) {
                // Update the text inside the popup
                if (subtitle) {
                    subtitle.innerText = count + (count === 1 ? " new notification" : " new notifications");
                }

                // ALWAYS update the hidden number so the button remembers it when closed!
                if (badge.innerText != count && !popup.classList.contains('show')) {
                    // Only play animation if it's a new number AND the popup is closed
                    badge.style.transform = "scale(1.3)";
                    setTimeout(() => badge.style.transform = "scale(1)", 200);
                }

                // Store the number
                badge.innerText = count;

                // Ensure it is only visible if the popup is CLOSED
                if (!popup.classList.contains('show')) {
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }

            } else {
                // If 0, hide the badge entirely
                badge.style.display = 'none';
                badge.innerText = '';
                if (subtitle) subtitle.innerText = "Secure communications";
            }
        }

        // 2. NEW: The Live Polling Engine
        async function checkLiveNotifications() {
            try {
                // Silently check the database
                const res = await fetch('check_notifications.php');
                const data = await res.json();

                if (data.success) {
                    // Send the live number to the UI updater!
                    updateChatBadge(data.count);
                }
            } catch (error) {
                // Fail silently so it doesn't break the console if there's a quick network drop
            }
        }

        // 3. Run the checker every 3 seconds (3000 milliseconds)
        setInterval(checkLiveNotifications, 3000);

        // Toggle Theme Function
        function toggleTheme() {
            const html = document.getElementById('htmlTag');
            const themeIcon = document.getElementById('themeIcon');

            if (html.getAttribute('data-bs-theme') === 'light') {
                html.setAttribute('data-bs-theme', 'dark');
                themeIcon.classList.remove('bi-sun-fill');
                themeIcon.classList.add('bi-moon-stars-fill');
                localStorage.setItem('sentinel_theme', 'dark');
            } else {
                html.setAttribute('data-bs-theme', 'light');
                themeIcon.classList.remove('bi-moon-stars-fill');
                themeIcon.classList.add('bi-sun-fill');
                localStorage.setItem('sentinel_theme', 'light');
            }
        }

        // Apply saved theme on page load
        window.addEventListener('DOMContentLoaded', (event) => {
            const savedTheme = localStorage.getItem('sentinel_theme');
            if (savedTheme === 'dark') {
                document.getElementById('htmlTag').setAttribute('data-bs-theme', 'dark');
                const themeIcon = document.getElementById('themeIcon');
                if (themeIcon) {
                    themeIcon.classList.remove('bi-sun-fill');
                    themeIcon.classList.add('bi-moon-stars-fill');
                }
            }
        });

        // INSTANT TABLE SORTING FUNCTION
        function sortTable(tableId, colIndex, type) {
            const table = document.getElementById(tableId);
            let rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;

            switching = true;
            dir = "asc";

            const headers = table.getElementsByTagName("TH");
            for (let j = 0; j < headers.length - 1; j++) {
                let icon = headers[j].querySelector('i');
                if (icon) icon.className = 'bi bi-arrow-down-up text-muted ms-1';
            }

            while (switching) {
                switching = false;
                rows = table.rows;

                for (i = 1; i < (rows.length - 1); i++) {
                    shouldSwitch = false;

                    if (rows[i].getElementsByTagName("TD").length < 3) continue;

                    x = rows[i].getElementsByTagName("TD")[colIndex];
                    y = rows[i + 1].getElementsByTagName("TD")[colIndex];

                    let xVal = x.innerText.trim().toLowerCase();
                    let yVal = y.innerText.trim().toLowerCase();

                    if (type === 'number') {
                        xVal = parseFloat(xVal.replace(/[^0-9.-]+/g, "")) || 0;
                        yVal = parseFloat(yVal.replace(/[^0-9.-]+/g, "")) || 0;
                    } else if (type === 'date') {
                        xVal = new Date(xVal).getTime() || 0;
                        yVal = new Date(yVal).getTime() || 0;
                    }

                    if (dir == "asc") {
                        if (xVal > yVal) {
                            shouldSwitch = true;
                            break;
                        }
                    } else if (dir == "desc") {
                        if (xVal < yVal) {
                            shouldSwitch = true;
                            break;
                        }
                    }
                }

                if (shouldSwitch) {
                    rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                    switching = true;
                    switchcount++;
                } else {
                    if (switchcount == 0 && dir == "asc") {
                        dir = "desc";
                        switching = true;
                    }
                }
            }

            let activeIcon = headers[colIndex].querySelector('i');
            if (activeIcon) {
                activeIcon.className = dir === 'asc' ? 'bi bi-arrow-up text-primary fw-bold ms-1' : 'bi bi-arrow-down text-primary fw-bold ms-1';
            }
        }

        // NEW: Function to open the full-size image in the modal
        function viewFullImage(imageSrc) {
            document.getElementById('fullSizeImage').src = imageSrc;
            const imageModal = new bootstrap.Modal(document.getElementById('imageViewerModal'));
            imageModal.show();
        }

        // 1. Create a temporary variable to hold the Cold Storage key
        let sessionMasterKey = null;

        // 2. Function to read the uploaded Master JSON
        function loadMasterKey(event) {
            const file = event.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const keyData = JSON.parse(e.target.result);
                    // Match the new JSON format
                    if (keyData.private_key_pem && keyData.system === "Sentinel Master Audit Vault") {
                        
                        // Strip the formatting so crypto.js can read it!
                        const cleanKey = keyData.private_key_pem
                            .replace(/-----BEGIN PRIVATE KEY-----/g, '')
                            .replace(/-----END PRIVATE KEY-----/g, '')
                            .replace(/\s+/g, '');

                        sessionMasterKey = cleanKey; 

                        document.getElementById('masterKeyUploadUI').classList.add('d-none');
                        document.getElementById('masterKeySuccessUI').classList.remove('d-none');
                    } else {
                        alert("Security Error: Invalid Master Key file.");
                    }
                } catch (err) {
                    alert("Security Error: Failed to parse key file.");
                }
            };
            reader.readAsText(file);
        }

        // 3. Updated Unlock Log Function
        async function unlockLog(buttonElement, ciphertext, senderPublicKey) {
            // Check if they uploaded the Master Key into memory first!
            if (!sessionMasterKey) {
                alert("🛑 VAULT LOCKED 🛑\n\nPlease upload the MASTER_AUDIT_PRIVATE_KEY.json file at the top of the tab first.");
                return;
            }

            try {
                // Pass the sessionMasterKey into your updated crypto.js functions!
                const decryptedText = await decryptMessage(ciphertext, sessionMasterKey);

                let decryptedMediaHTML = "";
                const attachmentPath = buttonElement.getAttribute('data-attachment');

                if (attachmentPath && attachmentPath !== "null" && attachmentPath !== "") {
                    try {
                        const res = await fetch(attachmentPath);
                        const encryptedFileStr = (await res.text()).trim();
                        // Pass the master key here too!
                        const decryptedFileStr = await decryptLargeMessage(encryptedFileStr, sessionMasterKey);

                        // ... keep the rest of your exact media UI rendering code here ...
                        if (decryptedFileStr.startsWith('data:application/pdf')) {
                            // ... PDF UI ...
                        } else if (decryptedFileStr.startsWith('data:video/')) {
                            // ... Video UI ...
                        } else {
                            // ... Image UI ...
                        }
                    } catch (fileErr) {
                        console.error("File decryption failed:", fileErr);
                        decryptedMediaHTML = `<br><span class="badge bg-warning text-dark mt-2">⚠️ Attachment Decryption Failed</span>`;
                    }
                }

                // Update the UI row
                const row = buttonElement.closest('tr');
                const textBox = row.querySelector('.encrypted-text');
                const badge = row.querySelector('.badge.bg-secondary');

                textBox.innerHTML = `<span class="text-body fw-medium d-block">${decryptedText}</span>${decryptedMediaHTML}`;
                textBox.style.maxWidth = 'none';

                badge.className = "badge bg-success";
                badge.innerHTML = '<i class="bi bi-unlock-fill"></i> Decrypted';

                buttonElement.className = "btn btn-sm btn-success disabled";
                buttonElement.innerHTML = "<i class='bi bi-check-circle'></i> Unlocked";

            } catch (error) {
                console.error("Decryption failed:", error);
                alert("Security Error: Decryption failed. The key does not match this log.");
            }
        }

        // Helper function to open the decrypted PDF in a new tab
        function openSecureAttachmentFromData(decryptedDataUrl) {
            const parts = decryptedDataUrl.split(',');
            const mimeType = parts[0].match(/:(.*?);/)[1];
            const binaryString = atob(parts[1]);
            const uint8Array = new Uint8Array(binaryString.length);

            for (let i = 0; i < binaryString.length; i++) {
                uint8Array[i] = binaryString.charCodeAt(i);
            }

            const blob = new Blob([uint8Array], {
                type: mimeType
            });
            const secureBlobUrl = URL.createObjectURL(blob);
            window.open(secureBlobUrl, '_blank');

            // Cleanup memory after 60 seconds
            setTimeout(() => URL.revokeObjectURL(secureBlobUrl), 60000);
        }

        let auditSearchQuery = "";

        /**
         * Filter Audit Logs and Refresh Pagination
         */
        function filterAuditLogs(query) {
            auditSearchQuery = query.toLowerCase().trim();
            const rows = document.querySelectorAll('.audit-log-row');

            // 1. Tag rows as 'matching' or not
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(auditSearchQuery)) {
                    row.classList.add('search-match');
                } else {
                    row.classList.remove('search-match');
                }
            });

            // 2. Reset to Page 1 and Re-render using only matching rows
            renderAuditLogPage(1);
        }

        // --- AUDIT LOG PAGINATION ENGINE ---
        let currentLogPage = 1;
        const LOGS_PER_PAGE = 25;

        // NEW: Added a 'doScroll' parameter that defaults to false
        function renderAuditLogPage(pageNumber, doScroll = false) {
            const tbody = document.getElementById('auditLogTableBody');
            const wrapper = document.getElementById('auditPaginationWrapper');
            if (!tbody) return;

            // Filter logic: If there's a search, only use rows with 'search-match'. Otherwise, use all.
            const allRows = Array.from(tbody.getElementsByClassName('audit-log-row'));
            const visibleRows = auditSearchQuery === "" ? allRows : allRows.filter(r => r.classList.contains('search-match'));

            const totalVisible = visibleRows.length;

            // CLEAR THE TBODY and handle "No Results"
            // We don't want to lose our original rows, so we just hide them (already done above)
            // But we need to handle the visual feedback
            const existingNoResults = tbody.querySelector('.no-results-row');
            if (existingNoResults) existingNoResults.remove();

            if (totalVisible === 0) {
                const noResultsRow = document.createElement('tr');
                noResultsRow.className = 'no-results-row';
                noResultsRow.innerHTML = `
                    <td colspan="5" class="text-center py-5 text-muted">
                        <i class="bi bi-shield-exclamation display-4 opacity-25 d-block mb-3"></i>
                        <h5 class="fw-bold">No Logs Found</h5>
                        <p class="mb-0 small">No encrypted records match your search criteria.</p>
                    </td>
                `;
                tbody.appendChild(noResultsRow);
            }
            const totalPages = Math.ceil(totalVisible / LOGS_PER_PAGE);

            // Ensure wrapper is visible even if 0 results (to show "0 of 0")
            if (wrapper) wrapper.style.setProperty('display', 'flex', 'important');

            // Boundary check for page number
            if (pageNumber < 1) pageNumber = 1;
            if (pageNumber > totalPages && totalPages > 0) pageNumber = totalPages;
            currentLogPage = pageNumber;

            // Hide ALL rows first
            allRows.forEach(r => r.style.display = 'none');

            // Show only the slice for the current page
            const startIndex = (currentLogPage - 1) * LOGS_PER_PAGE;
            const endIndex = startIndex + LOGS_PER_PAGE;

            visibleRows.forEach((row, index) => {
                if (index >= startIndex && index < endIndex) {
                    row.style.display = '';
                }
            });

            // Update Pagination Text (e.g., "Showing 0 to 0 of 0" or "1 to 25 of 97")
            const actualEnd = Math.min(endIndex, totalVisible);
            const startNum = totalVisible === 0 ? 0 : startIndex + 1;
            const infoText = `Showing ${startNum} to ${actualEnd} of ${totalVisible} entries`;

            const displayPage = totalPages === 0 ? 0 : currentLogPage;
            const pageStatusText = `Page ${displayPage} of ${totalPages}`;

            // Update the UI Wrapper
            wrapper.innerHTML = `
                <span class="text-muted small fw-bold">${infoText}</span>
                
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-sm text-white px-3 shadow-sm d-flex align-items-center gap-1" 
                        style="border-radius: 8px; background: linear-gradient(135deg, #0dcaf0, #0d6efd); border: none; opacity: ${currentLogPage <= 1 ? '0.5' : '1'};" 
                        onclick="renderAuditLogPage(${currentLogPage - 1}, true)" 
                        ${currentLogPage <= 1 ? 'disabled' : ''}>
                        <i class="bi bi-chevron-left"></i> Prev
                    </button>
                    
                    <span class="text-muted fw-bold" style="font-size: 0.9rem;">${pageStatusText}</span>
                    
                    <button class="btn btn-sm text-white px-3 shadow-sm d-flex align-items-center gap-1" 
                        style="border-radius: 8px; background: linear-gradient(135deg, #0dcaf0, #0d6efd); border: none; opacity: ${currentLogPage >= totalPages ? '0.5' : '1'};" 
                        onclick="renderAuditLogPage(${currentLogPage + 1}, true)" 
                        ${currentLogPage >= totalPages ? 'disabled' : ''}>
                        Next <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            `;

            if (doScroll) {
                const header = document.getElementById('headingThree');
                if (header) header.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }

        // Run the slicer when the page loads, but leave 'doScroll' as false so it doesn't jump!
        document.addEventListener('DOMContentLoaded', () => {
            // --- AUTO-RESET TABS WHEN CLOSED ---
            const adminAccordion = document.getElementById('adminAccordion');
            if (adminAccordion) {
                adminAccordion.addEventListener('hidden.bs.collapse', function(event) {
                    const closedSection = event.target; // The tab that just closed

                    // 1. Reset Pending Requests
                    if (closedSection.id === 'collapseOne') {
                        const input = document.getElementById('pendingSearchInput');
                        if (input && input.value !== '') {
                            input.value = '';
                            filterTable('pendingTable', '');
                        }
                    }
                    // 2. Reset Active Officers
                    else if (closedSection.id === 'collapseTwo') {
                        const input = document.getElementById('activeSearchInput');
                        if (input && input.value !== '') {
                            input.value = '';
                            filterTable('activeTable', '');
                        }
                    }
                    // 3. Reset Security Directory (Locked Accounts)
                    else if (closedSection.id === 'collapseDirectory') {
                        const input = document.getElementById('directorySearchInput');
                        if (input && input.value !== '') {
                            input.value = '';
                            filterTable('directoryTable', '');
                        }
                    }
                    // 4. Reset Audit Log & Pagination
                    else if (closedSection.id === 'collapseThree') {
                        const input = document.getElementById('auditSearchInput');
                        if (input && input.value !== '') {
                            input.value = '';
                            filterAuditLogs('');
                        }
                    }
                });
            }
            renderAuditLogPage(1);
        });

        function jumpToSection(sectionId) {
            const section = document.getElementById(sectionId);
            if (!section) return;

            // 1. If the accordion is collapsed, trigger the Bootstrap show method
            const bsCollapse = new bootstrap.Collapse(section, {
                toggle: false
            });
            bsCollapse.show();

            // 2. Smoothly scroll to the parent accordion item WITH an offset!
            const accordionItem = section.closest('.accordion-item');
            if (accordionItem) {
                setTimeout(() => {
                    // Calculate the position of the item, then subtract 80 pixels for breathing room
                    const yOffset = -80;
                    const y = accordionItem.getBoundingClientRect().top + window.scrollY + yOffset;

                    window.scrollTo({
                        top: y,
                        behavior: 'smooth'
                    });
                }, 300); // Small delay to allow accordion opening animation to start
            }
        }

        function filterTable(tableId, query) {
            const searchTerm = query.toLowerCase().trim();
            const table = document.getElementById(tableId);
            const tbody = table.getElementsByTagName('tbody')[0];

            // 1. CRITICAL FIX: Remove the "No Results" row completely BEFORE scanning!
            // This stops the word "different" from causing ghost matches.
            const existingNoResults = tbody.querySelector('.no-results-row');
            if (existingNoResults) existingNoResults.remove();

            // 2. NOW gather the rows
            const rows = Array.from(tbody.getElementsByTagName('tr'));
            let matchCount = 0;

            rows.forEach(row => {
                // SAFETY CHECK: Ignore PHP's empty database messages (they use a single colspan cell)
                const cells = row.getElementsByTagName('td');
                if (cells.length === 1 && cells[0].hasAttribute('colspan')) {
                    // Hide the PHP message if searching, show it if the search box is empty
                    row.style.display = searchTerm === "" ? "" : "none";
                    return;
                }

                // Standard Text Search
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = "";
                    matchCount++;
                } else {
                    row.style.display = "none";
                }
            });

            // 3. Inject "No Matches" ONLY if the search failed
            if (searchTerm !== "" && matchCount === 0) {
                const colCount = table.getElementsByTagName('th').length;
                const noResultsRow = document.createElement('tr');
                noResultsRow.className = 'no-results-row';
                noResultsRow.innerHTML = `
                    <td colspan="${colCount}" class="text-center py-5 text-muted">
                        <i class="bi bi-search display-4 opacity-25 d-block mb-3"></i>
                        <h5 class="fw-bold">No matches found</h5>
                        <p class="mb-0 small">Try searching for a different ID or Name.</p>
                    </td>
                `;
                tbody.appendChild(noResultsRow);
            }
        }

        function clearSearch(inputId, tableId) {
            const input = document.getElementById(inputId);
            if (!input) return;

            input.value = "";

            if (inputId === 'auditSearchInput') {
                filterAuditLogs("");
            } else {
                filterTable(tableId, "");
            }

            input.focus();
        }

        const MY_USER_ID = <?php echo $_SESSION['user_id']; ?>;
    </script>

    <script src="crypto.js"></script>

</body>

</html>