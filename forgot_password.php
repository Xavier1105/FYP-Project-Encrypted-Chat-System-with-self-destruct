<?php
session_start();
require_once 'db_connect.php';

$msg = '';
$msg_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login_input = trim($_POST['username']);
    $reason = trim($_POST['reason']);

    if (empty($login_input) || empty($reason)) {
        $msg = "Please fill in all fields.";
        $msg_type = "danger";
    } else {
        // 1. Check if the user exists and if they already requested a reset
        $stmt = $conn->prepare("SELECT user_id, reset_requested FROM users WHERE officer_id = ? OR username = ?");
        $stmt->bind_param("ss", $login_input, $login_input);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $user = $res->fetch_assoc();
            
            if ($user['reset_requested'] == 1) {
                $msg = "You already have a pending reset request. Please wait for the Admin to review it.";
                $msg_type = "warning";
            } else {
                // 2. Update their row in the users table with the request!
                $update = $conn->prepare("UPDATE users SET reset_requested = 1, reset_reason = ? WHERE user_id = ?");
                $update->bind_param("si", $reason, $user['user_id']);
                if ($update->execute()) {
                    $msg = "Your request has been securely sent to the Admin or Head of Security. Please await approval.";
                    $msg_type = "success";
                } else {
                    $msg = "A database error occurred. Please try again.";
                    $msg_type = "danger";
                }
                $update->close();
            }
        } else {
            $msg = "Officer ID or Username not found in the system.";
            $msg_type = "danger";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sentinel - Password Reset Request</title>
    <link rel="icon" type="image/png" href="Sentinel logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-image: url('Unimas4.webp');
            background-size: cover; background-position: center; background-repeat: no-repeat; background-attachment: fixed; background-color: #f0f2f5; height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0;
        }
        .info-card { width: 100%; max-width: 500px; padding: 2.5rem; border: none; border-radius: 12px; box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08); background-color: rgba(255, 255, 255, 0.95); }
        .icon-circle { width: 70px; height: 70px; background-color: rgba(79, 70, 229, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem auto; color: #4f46e5; font-size: 2rem; border: 1px solid rgba(79, 70, 229, 0.2); }
        h3 { font-weight: 700; color: #1a2e44; margin-bottom: 0.5rem; text-align: center; }
        .subtitle { color: #6c757d; font-size: 0.95rem; text-align: center; margin-bottom: 2rem; }
        .form-label { font-weight: 600; color: #343a40; font-size: 0.9rem; }
        .btn-primary { background: linear-gradient(135deg, #4f46e5, #3730a3); color: white; border: none; padding: 0.8rem; font-weight: 600; border-radius: 8px; transition: all 0.2s ease-in-out; width: 100%; }
        .btn-primary:hover { background: linear-gradient(135deg, #6366f1, #4338ca); transform: translateY(-2px); box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4); }
        .back-link { display: block; text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: #6c757d; text-decoration: none; font-weight: 600; transition: color 0.2s; }
        .back-link:hover { color: #4f46e5; }
    </style>
</head>
<body>
    <div class="info-card">
        <div class="icon-circle"><i class="bi bi-shield-lock"></i></div>
        <h3>Request Password Reset</h3>
        <p class="subtitle">Submit a request to the Head of Security or Admin.</p>

        <?php if ($msg): ?>
            <div class="alert alert-<?php echo $msg_type; ?> shadow-sm" style="font-size: 0.9rem;">
                <i class="bi <?php echo $msg_type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?> me-2"></i>
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <form action="forgot_password.php" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Officer ID / Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Enter your ID or username" required>
                </div>
            </div>
            <div class="mb-4">
                <label for="reason" class="form-label">Reason for Reset</label>
                <textarea class="form-control" id="reason" name="reason" rows="3" placeholder="Explain why you are requesting a reset..." required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Submit Request</button>
        </form>
        <a href="login.php" class="back-link"><i class="bi bi-arrow-left me-1"></i> Back to Login</a>
    </div>
</body>
</html>