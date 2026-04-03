<?php
session_start();
require_once 'db_connect.php';
date_default_timezone_set('Asia/Kuala_Lumpur');

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Unauthorized access']));
}

$my_user_id = $_SESSION['user_id'];
$target_username = isset($_GET['username']) ? trim($_GET['username']) : '';

if (empty($target_username)) {
    die(json_encode(['error' => 'No username provided']));
}

// 1. Fetch user data (NEW: Now fetching 'show_last_seen' from the database!)
$stmt = $conn->prepare("SELECT user_id, officer_id, username, role, position, profile_picture, public_key, last_seen, is_active_session, show_last_seen FROM users WHERE username = ?");
$stmt->bind_param("s", $target_username);
$stmt->execute();
$result = $stmt->get_result();
$officer_info = $result->fetch_assoc();
$stmt->close();

if ($officer_info) {
    // 2. Check if blocked
    $is_blocked = false;
    $block_check = $conn->prepare("SELECT is_active FROM blocked_users WHERE blocker_id = ? AND blocked_id = ? AND is_active = 1");
    $block_check->bind_param("ii", $my_user_id, $officer_info['user_id']);
    $block_check->execute();
    if ($block_check->get_result()->num_rows > 0) {
        $is_blocked = true;
    }
    $block_check->close();

    $officer_info['is_blocked_by_me'] = $is_blocked;

    // 3. STATUS CALCULATION (WITH NEW PRIVACY GUARD)
    if ($is_blocked) {
        $officer_info['header_status'] = 'Connection Terminated';
        $officer_info['header_class'] = 'text-danger fw-bold';
        $officer_info['sidebar_status'] = 'Blocked';
    }
    // ========================================================
    // NEW: PRIVACY GUARD
    // If they turned off the switch, hide their exact time!
    // ========================================================
    else if (isset($officer_info['show_last_seen']) && $officer_info['show_last_seen'] == 0) {
        $officer_info['header_status'] = 'Status Hidden';
        $officer_info['header_class'] = 'text-muted';
        $officer_info['sidebar_status'] = 'Offline';
    }
    // ========================================================
    else {
        // Normal time math for users who allow it
        $last_seen_time = strtotime($officer_info['last_seen']);
        $current_time = time();
        $time_diff = $current_time - $last_seen_time;

        if ($officer_info['is_active_session'] == 1 && $officer_info['last_seen'] && $time_diff >= 0 && $time_diff < 1800) {
            $officer_info['header_status'] = '● Online';
            $officer_info['header_class'] = 'text-success fw-bold';
            $officer_info['sidebar_status'] = 'Online';
        } else {
            $officer_info['header_class'] = 'text-muted';
            if ($officer_info['last_seen']) {
                $mins = abs(round($time_diff / 60));
                if ($mins < 60) $officer_info['sidebar_status'] = $mins . " m ago";
                elseif ($mins < 1440) $officer_info['sidebar_status'] = round($mins / 60) . " h ago";
                else $officer_info['sidebar_status'] = date('d M', $last_seen_time);

                if (date('Y-m-d') == date('Y-m-d', $last_seen_time)) {
                    $officer_info['header_status'] = 'Last seen at ' . date('H:i', $last_seen_time);
                } else {
                    $officer_info['header_status'] = 'Last seen ' . date('d M, H:i', $last_seen_time);
                }
            } else {
                $officer_info['sidebar_status'] = 'Offline';
                $officer_info['header_status'] = 'Offline';
            }
        }
    }

    echo json_encode($officer_info);
} else {
    echo json_encode(['error' => 'Officer not found']);
}
