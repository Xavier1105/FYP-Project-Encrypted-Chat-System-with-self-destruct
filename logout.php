<?php
// logout.php
session_start();
require_once 'db_connect.php';

// 1. Set Timezone
date_default_timezone_set('Asia/Kuala_Lumpur');

// 2. UPDATE: Set Last Seen to NOW() AND turn off Active Session
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];

    $stmt = $conn->prepare("UPDATE users SET last_seen = NOW(), is_active_session = 0 WHERE user_id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $stmt->close();
}

// =========================================================================
// 3. SAFE SESSION DESTRUCTION (Fixes XAMPP "Permission Denied" Errors)
// =========================================================================

// A. Empty the session array completely
$_SESSION = array();

// B. Destroy the session cookie on the user's browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// C. Destroy the session file on the server safely
session_destroy();

// =========================================================================

// 4. Redirect back to login
header("Location: login.php");
exit();
