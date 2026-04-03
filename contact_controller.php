<?php
// contact_controller.php

// --- 1. SAFEGUARD FOR AJAX CALLS ---
// If Javascript calls this file directly, it needs to boot up the session and database!
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($conn)) {
    require_once 'db_connect.php'; // Ensure your database connects!
}

// Security Check: Make sure they are logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// 2. SET TIMEZONE
date_default_timezone_set('Asia/Kuala_Lumpur');
$my_id = $_SESSION['user_id'];
$my_username = $_SESSION['username'];
$alert_msg = '';

// ==========================================
// NEW: CANCEL OUTGOING FRIEND REQUEST (AJAX)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'cancel_request') {
    header('Content-Type: application/json');

    // SCENARIO 1: JavaScript sent the 'request_id'
    if (isset($_POST['request_id']) && intval($_POST['request_id']) > 0) {
        $req_id = intval($_POST['request_id']);

        // Delete using the specific request ID
        $cancel_stmt = $conn->prepare("DELETE FROM friend_requests WHERE request_id = ? AND sender_id = ?");
        $cancel_stmt->bind_param("ii", $req_id, $my_id);

        if ($cancel_stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
        $cancel_stmt->close();
    }
    // SCENARIO 2: JavaScript sent the 'contact_id' (user_id of the receiver)
    else if (isset($_POST['contact_id']) && intval($_POST['contact_id']) > 0) {
        $target_id = intval($_POST['contact_id']);

        // Delete using the sender and receiver IDs
        $cancel_stmt = $conn->prepare("DELETE FROM friend_requests WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'");
        $cancel_stmt->bind_param("ii", $my_id, $target_id);

        if ($cancel_stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
        $cancel_stmt->close();
    }
    // SCENARIO 3: JavaScript didn't send either one properly
    else {
        // This will print out exactly what JS sent so we can easily debug it!
        echo json_encode(['success' => false, 'error' => 'Missing ID. Data received: ' . json_encode($_POST)]);
    }

    exit; // CRITICAL: Stop the script here!
}

// --- A. AJAX SEARCH HANDLER (NEW BLOCK) ---
// This listens for JavaScript requests. If detected, it returns HTML and STOPS.
if (isset($_GET['ajax_search'])) {
    $search_term = "%" . $_GET['ajax_search'] . "%";

    // 1. Get my existing contact IDs
    $my_contact_ids = [];
    $contacts_sql = "SELECT contact_id FROM contacts WHERE user_id = ?";
    $stmt = $conn->prepare($contacts_sql);
    $stmt->bind_param("i", $my_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $my_contact_ids[] = $row['contact_id'];
    }

    // ==========================================
    // 1b. NEW: Get pending request target IDs (both sent and received)
    // ==========================================
    $pending_request_user_ids = [];
    $pending_sql = "SELECT sender_id, receiver_id 
                    FROM friend_requests 
                    WHERE (sender_id = ? OR receiver_id = ?) 
                    AND status = 'pending'";
    $stmt_p = $conn->prepare($pending_sql);
    $stmt_p->bind_param("ii", $my_id, $my_id);
    $stmt_p->execute();
    $res_p = $stmt_p->get_result();
    while ($p_row = $res_p->fetch_assoc()) {
        if ($p_row['sender_id'] == $my_id) {
            $pending_request_user_ids[] = $p_row['receiver_id'];
        } else {
            $pending_request_user_ids[] = $p_row['sender_id'];
        }
    }
    $stmt_p->close();
    // ==========================================

    // 2. Perform Search
    // FIXED: Added "AND is_discoverable = 1" so hidden users don't show up!
    $sql = "SELECT user_id, username, role, profile_picture
            FROM users 
            WHERE (username LIKE ?) 
            AND user_id != ? 
            AND is_approved = 1 
            AND is_discoverable = 1
            LIMIT 5";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $search_term, $my_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // 3. Output HTML (Upgraded to match Friends Tab styling)
    if ($result->num_rows > 0) {
        // FIXED: Using a flex column with gap-3 instead of list-group
        echo '<div class="d-flex flex-column gap-3">';

        while ($user = $result->fetch_assoc()) {
            $is_added = in_array($user['user_id'], $my_contact_ids);
            $is_pending = in_array($user['user_id'], $pending_request_user_ids);

            // FIXED: Card matches the exact border, padding, and background of the Friends tab!
            echo '<div class="d-flex justify-content-between align-items-center p-3 rounded shadow-sm" style="background-color: var(--bs-body-bg); border: 1px solid var(--bs-border-color) !important;">';

            echo '  <div class="d-flex align-items-center">';

            // Profile Picture Wrapper
            echo '      <div class="position-relative">';
            if (!empty($user['profile_picture'])) {
                $pic = htmlspecialchars($user['profile_picture']);
                echo '          <img src="' . $pic . '" class="rounded-circle me-3 shadow-sm" style="width: 48px; height: 48px; object-fit: cover;">';
            } else {
                echo '          <div class="avatar me-3 shadow-sm" style="width: 48px; height: 48px; background-color: var(--bs-secondary-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center;">';
                echo '              <i class="bi bi-person-fill fs-4 text-secondary"></i>';
                echo '          </div>';
            }
            echo '      </div>';

            // Name and Role
            echo '      <div>';
            echo '          <h6 class="mb-0 fw-bold text-body" style="font-size: 1rem;">' . htmlspecialchars($user['username']) . '</h6>';

            // Format role exactly like the Friends tab
            $role_display = strtoupper($user['role']) === 'HOS' ? 'HOS' : ucfirst($user['role']);
            echo '          <small class="text-muted">' . $role_display . '</small>';
            echo '      </div>';
            echo '  </div>';

            // DYNAMIC ACTION BUTTONS (Added, Pending, Add)
            echo '  <div>';
            if ($is_added) {
                // Scenario 1: User is already connected
                echo '      <span class="text-success fw-bold small"><i class="bi bi-check-circle-fill"></i> Added</span>';
            } else if ($is_pending) {
                // Scenario 2: User has a pending request
                echo '      <button class="btn btn-sm btn-secondary rounded-pill px-3 fw-bold" style="opacity: 0.6; cursor: default;" disabled><i class="bi bi-clock me-1"></i> Pending</button>';
            } else {
                // Scenario 3: Regular Add Friend button
                echo '      <form method="POST" action="index.php" class="m-0">';
                echo '          <input type="hidden" name="send_request_id" value="' . $user['user_id'] . '">';
                echo '          <button type="submit" class="btn btn-sm btn-secure-chat rounded-pill px-4 fw-bold shadow-sm"><i class="bi bi-person-plus me-1"></i> Add</button>';
                echo '      </form>';
            }
            echo '  </div>';

            echo '</div>'; // End of Card
        }
        echo '  <div class="p-3 text-center text-muted small"><i class="bi bi-info-circle me-1"></i> End of results</div>';
        echo '</div>';
    } else {
        // FIXED: Added solid card background to match the default Discover tab!
        echo '<div class="text-center text-muted py-5 rounded-4 shadow-sm mt-3" style="background-color: var(--bs-body-bg); border: 1px solid var(--bs-border-color) !important;">';
        echo '  <i class="bi bi-search display-1 opacity-25 mb-3 d-block"></i>';
        echo '  <h5 class="fw-bold text-body">No officers found</h5>';
        echo '  <p class="mb-0">Please check your spelling and try again.</p>';
        echo '</div>';
    }

    // CRITICAL: Stop the script here so the rest of the dashboard doesn't load!
    exit;
}

// --- B. REGULAR PHP LOGIC BELOW (Existing code) ---
// ... keep your existing handle Post Actions, Incoming Requests, etc. here ...
// ==========================================
// 1. SEND FRIEND REQUEST (With Instant Restore)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_request_id'])) {
    $target_id = intval($_POST['send_request_id']);

    // A. Check if they are ALREADY in your contacts
    $check_me = $conn->prepare("SELECT contact_id FROM contacts WHERE user_id = ? AND contact_id = ?");
    $check_me->bind_param("ii", $my_id, $target_id);
    $check_me->execute();
    $in_my_contacts = $check_me->get_result()->num_rows > 0;
    $check_me->close();

    if ($in_my_contacts) {
        $alert_msg = "<div class='alert alert-warning'>You are already connected.</div>";
    } else {
        // B. Check if you are in THEIR contacts (Meaning you deleted them, but they kept you)
        $check_them = $conn->prepare("SELECT contact_id FROM contacts WHERE user_id = ? AND contact_id = ?");
        $check_them->bind_param("ii", $target_id, $my_id);
        $check_them->execute();
        $in_their_contacts = $check_them->get_result()->num_rows > 0;
        $check_them->close();

        if ($in_their_contacts) {
            // INSTANT RESTORE: They didn't delete you, so just silently restore them to your list!
            $restore = $conn->prepare("INSERT INTO contacts (user_id, contact_id) VALUES (?, ?)");
            $restore->bind_param("ii", $my_id, $target_id);
            $restore->execute();
            $restore->close();

            $_SESSION['flash_msg'] = 'accepted'; // Triggers the green "Contact Added" popup
            header("Location: index.php");
            exit();
        } else {
            // C. Neither of you has each other. Check if a request is already PENDING.
            $check_req = $conn->prepare("SELECT request_id FROM friend_requests WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) AND status = 'pending'");
            $check_req->bind_param("iiii", $my_id, $target_id, $target_id, $my_id);
            $check_req->execute();

            if ($check_req->get_result()->num_rows > 0) {
                $alert_msg = "<div class='alert alert-warning'>A friend request is already pending.</div>";
            } else {
                // D. Clean up any ancient accepted/rejected requests from the past
                $wipe_old = $conn->prepare("DELETE FROM friend_requests WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
                $wipe_old->bind_param("iiii", $my_id, $target_id, $target_id, $my_id);
                $wipe_old->execute();
                $wipe_old->close();

                // E. Send a fresh new request!
                $req = $conn->prepare("INSERT INTO friend_requests (sender_id, receiver_id, status) VALUES (?, ?, 'pending')");
                $req->bind_param("ii", $my_id, $target_id);
                if ($req->execute()) {
                    $_SESSION['flash_msg'] = 'sent';
                    header("Location: index.php");
                    exit();
                }
            }
            $check_req->close();
        }
    }
}

// 2. ACCEPT REQUEST
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accept_request_id'])) {
    $req_id = intval($_POST['accept_request_id']);
    $sender_id = intval($_POST['sender_id']);

    $update = $conn->prepare("UPDATE friend_requests SET status = 'accepted' WHERE request_id = ?");
    $update->bind_param("i", $req_id);

    if ($update->execute()) {
        $conn->query("INSERT INTO contacts (user_id, contact_id) VALUES ($my_id, $sender_id)");
        $conn->query("INSERT INTO contacts (user_id, contact_id) VALUES ($sender_id, $my_id)");

        // NEW: Save message to session, then redirect to clean URL
        $_SESSION['flash_msg'] = 'accepted';
        header("Location: index.php");
        exit();
    }
    $update->close();
}

// 3. REJECT REQUEST
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reject_request_id'])) {
    $req_id = intval($_POST['reject_request_id']);
    $conn->query("DELETE FROM friend_requests WHERE request_id = $req_id");

    // NEW: Save message to session, then redirect to clean URL
    $_SESSION['flash_msg'] = 'rejected';
    header("Location: index.php");
    exit();
}

// ==========================================
// NEW: REMOVE FRIEND (ONE-SIDED MESSAGE HIDE)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'delete_contact') {
    header('Content-Type: application/json');

    $my_id = intval($_SESSION['user_id']);
    $target_id = intval($_POST['contact_id']);

    // 1. Delete YOUR side of the connection in the contacts table.
    $del_contact = $conn->prepare("DELETE FROM contacts WHERE user_id = ? AND contact_id = ?");
    $del_contact->bind_param("ii", $my_id, $target_id);
    $contact_success = $del_contact->execute();
    $del_contact->close();

    // 2. Hide messages you SENT to this person
    $hide_sent = $conn->prepare("UPDATE messages SET deleted_by_sender = 1 WHERE sender_id = ? AND receiver_id = ?");
    $hide_sent->bind_param("ii", $my_id, $target_id);
    $hide_sent->execute();
    $hide_sent->close();

    // 3. Hide messages you RECEIVED from this person
    $hide_received = $conn->prepare("UPDATE messages SET deleted_by_receiver = 1 WHERE sender_id = ? AND receiver_id = ?");
    $hide_received->bind_param("ii", $target_id, $my_id);
    $hide_received->execute();
    $hide_received->close();

    if ($contact_success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database update failed.']);
    }

    exit;
}

// --- B. FETCH DATA ---

// 1. INCOMING REQUESTS
// 1. INCOMING REQUESTS
$req_sql = "SELECT r.request_id, r.sender_id, u.username, u.role, u.profile_picture FROM friend_requests r JOIN users u ON r.sender_id = u.user_id WHERE r.receiver_id = ? AND r.status = 'pending'";
$stmt = $conn->prepare($req_sql);
$stmt->bind_param("i", $my_id);
$stmt->execute();
$incoming_requests = $stmt->get_result();

// 2. OUTGOING REQUESTS
$out_sql = "SELECT r.request_id, u.username, u.role, u.profile_picture FROM friend_requests r JOIN users u ON r.receiver_id = u.user_id WHERE r.sender_id = ? AND r.status = 'pending'";
$stmt_out = $conn->prepare($out_sql);
$stmt_out->bind_param("i", $my_id);
$stmt_out->execute();
$outgoing_requests = $stmt_out->get_result();

// 3. MY CONTACTS
$contacts_sql = "SELECT u.user_id, u.username, u.role, u.last_seen, u.is_active_session, u.profile_picture, u.show_last_seen,
                 IF(b.block_id IS NOT NULL, 1, 0) AS is_blocked_by_me,
                 (SELECT COUNT(*) FROM messages m WHERE m.sender_id = u.user_id AND m.receiver_id = c.user_id AND m.status != 'read') AS unread_count,
                 
                 /* 1. Find the exact time of the last NON-DELETED message */
                 (SELECT MAX(created_at) 
                  FROM messages m 
                  WHERE ((m.sender_id = c.user_id AND m.receiver_id = u.user_id AND m.deleted_by_receiver = 0) 
                     OR (m.sender_id = u.user_id AND m.receiver_id = c.user_id AND m.deleted_by_sender = 0))
                 ) AS last_interaction_time,
                 
                 /* 2. Grab the exact time you became friends (using your added_at column!) */
                 c.added_at AS friendship_started_at

                 FROM contacts c 
                 JOIN users u ON c.contact_id = u.user_id 
                 LEFT JOIN blocked_users b ON b.blocker_id = c.user_id AND b.blocked_id = u.user_id AND b.is_active = 1
                 WHERE c.user_id = ? AND c.is_hidden = 0
                 
                 /* 3. THE MAGIC: Sort by whichever is newer (the latest message OR the time you added them!) */
                 ORDER BY GREATEST(
                    COALESCE(last_interaction_time, '2000-01-01'), 
                    COALESCE(c.added_at, '2000-01-01')
                 ) DESC, u.username ASC";

$stmt2 = $conn->prepare($contacts_sql);
$stmt2->bind_param("i", $my_id);
$stmt2->execute();
$my_contacts = $stmt2->get_result();

$contact_data = [];

while ($row = $my_contacts->fetch_assoc()) {
    $last_seen_time = strtotime($row['last_seen']);
    $current_time = time();
    $time_diff = $current_time - $last_seen_time;

    // --- UPDATED STATUS LOGIC ---

    // 1. IS THIS USER BLOCKED?
    if ($row['is_blocked_by_me'] == 1) {
        $row['status'] = 'blocked';
        $row['status_text'] = 'Blocked';
        $row['header_status'] = 'Connection Terminated';
        $row['header_class'] = 'text-danger fw-bold';
    }
    // ========================================================
    // 2. NEW: PRIVACY GUARD
    // ========================================================
    else if (isset($row['show_last_seen']) && $row['show_last_seen'] == 0) {
        $row['status'] = 'offline';
        $row['status_text'] = 'Offline'; // Forces sidebar to say Offline
        $row['header_status'] = 'Status Hidden'; // Forces header to say Status Hidden
        $row['header_class'] = 'text-muted';
    }
    // ========================================================
    // 3. CHECK: ARE THEY ONLINE?
    else if ($row['is_active_session'] == 1 && $row['last_seen'] && $time_diff >= 0 && $time_diff < 1800) {
        $row['status'] = 'online';
        $row['status_text'] = 'Online';
        $row['header_status'] = '● Online';
        $row['header_class'] = 'text-success fw-bold';
    }
    // 4. OTHERWISE: THEY ARE OFFLINE WITH PUBLIC TIMESTAMPS
    else {
        $row['status'] = 'offline';

        if ($row['last_seen']) {
            $mins = abs(round($time_diff / 60));
            if ($mins < 60) $row['status_text'] = $mins . " m ago";
            elseif ($mins < 1440) $row['status_text'] = round($mins / 60) . " h ago";
            else $row['status_text'] = date('d M', $last_seen_time);

            if (date('Y-m-d') == date('Y-m-d', $last_seen_time)) {
                $row['header_status'] = 'Last seen at ' . date('H:i', $last_seen_time);
            } else {
                $row['header_status'] = 'Last seen ' . date('d M, H:i', $last_seen_time);
            }
        } else {
            $row['status_text'] = 'Offline';
            $row['header_status'] = 'Offline';
        }
        $row['header_class'] = 'text-muted';
    }

    $contact_data[] = $row;
}

// 4. HANDLE SUCCESS MESSAGES (Using Sessions for clean URLs)
if (isset($_SESSION['flash_msg'])) {
    if ($_SESSION['flash_msg'] == 'sent') {
        $alert_msg = "<div class='alert alert-success alert-dismissible fade show shadow-sm text-center mb-0 py-2' role='alert'><i class='bi bi-check-circle-fill me-2'></i>Friend request sent!<button type='button' class='btn-close btn-sm pt-2' data-bs-dismiss='alert'></button></div>";
    }
    if ($_SESSION['flash_msg'] == 'accepted') {
        $alert_msg = "<div class='alert alert-success alert-dismissible fade show shadow-sm text-center mb-0 py-2' role='alert'><i class='bi bi-person-check-fill me-2'></i>New contact added!<button type='button' class='btn-close btn-sm pt-2' data-bs-dismiss='alert'></button></div>";
    }
    if ($_SESSION['flash_msg'] == 'rejected') {
        $alert_msg = "<div class='alert alert-secondary alert-dismissible fade show shadow-sm text-center mb-0 py-2' role='alert'><i class='bi bi-x-circle-fill me-2'></i>Request removed.<button type='button' class='btn-close btn-sm pt-2' data-bs-dismiss='alert'></button></div>";
    }

    // CRITICAL: Clear the message so it doesn't show up again if they refresh the page!
    unset($_SESSION['flash_msg']);
}
