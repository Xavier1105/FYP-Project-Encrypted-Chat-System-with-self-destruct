<?php
session_start();
require_once 'db_connect.php';

$error = '';
$login_input = ''; // Variable to hold the entered ID or Username
$trigger_panic_wipe = false; // NEW: The detonator switch

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login_input = trim($_POST['username']); // Get input (ID or Username)
    $password = $_POST['password'];

    // SQL: Check IF input matches 'officer_id' OR 'username' and get the new security columns
    $stmt = $conn->prepare("SELECT user_id, officer_id, username, password_hash, role, is_approved, failed_attempts, is_locked, panic_mode FROM users WHERE officer_id = ? OR username = ?");
    $stmt->bind_param("ss", $login_input, $login_input); // Bind same input twice
    $stmt->execute();
    $result = $stmt->get_result(); // Using get_result makes it easier to pull an array of data

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // 1. CHECK IF ACCOUNT IS ALREADY LOCKED
        if ($user['is_locked'] == 1) {
            $error = "<strong>SECURITY LOCKDOWN:</strong> Account disabled due to multiple failed attempts. Contact Admin.";

            // If they are locked AND panic mode is on, fire the JS wipe just in case they are still trying
            if ($user['panic_mode'] == 1) {
                $trigger_panic_wipe = true;
            }
        } else {
            // 2. VERIFY PASSWORD
            if (password_verify($password, $user['password_hash'])) {

                if ($user['is_approved'] == 1) {
                    // 🚨 THE CONSISTENCY FIX: Generate a unique ID for this specific login
                    $current_session_id = bin2hex(random_bytes(16));
                    $_SESSION['login_auth_id'] = $current_session_id;

                    // SUCCESS: Reset attempts, set active, AND save the new session ID
                    $reset_stmt = $conn->prepare("UPDATE users SET failed_attempts = 0, is_active_session = 1, last_login_id = ? WHERE user_id = ?");
                    $reset_stmt->bind_param("si", $current_session_id, $user['user_id']);
                    $reset_stmt->execute();
                    $reset_stmt->close();

                    // Set remaining Session Variables
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];

                    header("Location: index.php");
                    exit();
                } else {
                    $error = "Account pending approval from Administrator.";
                }
            } else {
                // FAILURE: Wrong password entered
                $new_fails = $user['failed_attempts'] + 1;

                if ($new_fails >= 5) {
                    // STRIKE 5: INITIATE SERVER LOCKDOWN
                    $lock_stmt = $conn->prepare("UPDATE users SET failed_attempts = ?, is_locked = 1 WHERE user_id = ?");
                    $lock_stmt->bind_param("ii", $new_fails, $user['user_id']);
                    $lock_stmt->execute();
                    $lock_stmt->close();

                    $error = "<strong>SECURITY LOCKDOWN:</strong> Maximum attempts exceeded. Account is now locked.";

                    // IF PANIC MODE IS ENABLED, TRIGGER THE CLIENT-SIDE WIPE!
                    if ($user['panic_mode'] == 1) {
                        $trigger_panic_wipe = true;
                    }
                } else {
                    // STRIKES 1-4: Increment counter and warn the user
                    $inc_stmt = $conn->prepare("UPDATE users SET failed_attempts = ? WHERE user_id = ?");
                    $inc_stmt->bind_param("ii", $new_fails, $user['user_id']);
                    $inc_stmt->execute();
                    $inc_stmt->close();

                    $attempts_left = 5 - $new_fails;
                    $error = "Invalid Password. You have <strong>$attempts_left</strong> attempts remaining before lockdown.";
                }
            }
        }
    } else {
        $error = "Officer ID or Username not found.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sentinel - Login</title>
    <link rel="icon" type="image/png" href="Sentinel logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body {
            background-image: url('Unimas4.webp');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-color: #f0f2f5;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
        }

        .login-card {
            width: 100%;
            max-width: 600px;
            padding: 2.5rem;
            border: none;
            border-radius: 12px;
            background-color: rgba(255, 255, 255, 0.95);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

        .logo-placeholder {
            width: 100px;
            height: 100px;
            margin-bottom: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .logo-placeholder img {
            width: 200px;
            height: 100px;
            object-fit: contain;
        }

        .login-header h3 {
            font-weight: 700;
            color: #1a2e44;
            margin-bottom: 0.2rem;
        }

        .login-header p {
            color: #6c757d;
            font-size: 0.95rem;
            margin-bottom: 2rem;
        }

        .form-label {
            font-weight: 600;
            color: #343a40;
            font-size: 0.9rem;
            margin-bottom: 0.4rem;
        }

        .input-group-text {
            background-color: white;
            border-right: none;
            color: #adb5bd;
            border-color: #ced4da;
        }

        .form-control {
            border-left: none;
            box-shadow: none !important;
            border-color: #ced4da;
            padding-left: 0;
        }

        .input-group:focus-within .input-group-text,
        .input-group:focus-within .form-control {
            border-color: #0d6efd;
        }

        #togglePassword {
            border-left: none;
            border-right: 1px solid #ced4da;
            cursor: pointer;
        }

        #password {
            border-right: none;
        }

        .btn-primary {
            background-color: #2563eb;
            color: white;
            border: none;
            padding: 0.7rem;
            font-weight: 600;
            border-radius: 6px;
            transition: all 0.2s ease-in-out;
            /* Smooth animation */
        }

        .btn-primary:hover {
            background-color: #1d4ed8;
            color: white;
            transform: translateY(-2px);
            /* Floats up */
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.4);
            /* Blue glowing shadow */
        }

        .btn-success {
            background-color: #108a55;
            color: white;
            border: none;
            padding: 0.7rem;
            font-weight: 600;
            border-radius: 6px;
            margin-top: 1rem;
            transition: all 0.2s ease-in-out;
            /* Smooth animation */
            display: block;
            /* Ensures the <a> tag behaves perfectly like a button */
        }

        .btn-success:hover {
            background-color: #0c6b42;
            color: white;
            transform: translateY(-2px);
            /* Floats up */
            box-shadow: 0 4px 15px rgba(16, 138, 85, 0.4);
            /* Green glowing shadow */
        }

        .forgot-link {
            font-size: 0.85rem;
            color: #4f46e5;
            /* Matches the Deep Security Indigo */
            text-decoration: none;
            font-weight: 600;
            /* Made slightly bolder so it stands out */
            transition: all 0.2s ease-in-out;
            /* Smooth hover animation */
        }

        .forgot-link:hover {
            color: #6366f1;
            /* Brighter indigo when you hover */
            text-decoration: underline;
        }

        .warning-text {
            color: #dc3545;
            font-size: 0.75rem;
            margin-top: 2rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-top: 1px solid #eee;
            padding-top: 1rem;
        }

        .fade-out {
            opacity: 0;
            transition: opacity 0.5s ease-out;
        }
    </style>
</head>

<body>

    <div class="login-card">
        <div class="logo-placeholder">
            <img src="Sentinel logo.png" alt="Sentinel Logo">
        </div>

        <div class="login-header">
            <h3>Welcome Back</h3>
            <p>Sign in to access your account</p>
        </div>

        <div class="alert alert-primary border-0 shadow-sm mb-4 text-start" style="background: rgba(79, 70, 229, 0.1); border-radius: 10px;">
            <div class="d-flex">
                <i class="bi bi-info-circle-fill me-2 mt-1" style="color: #4f46e5;"></i>
                <div style="font-size: 0.82rem; color: #444; line-height: 1.4;">
                    <strong>Sentinel Pro-Tip:</strong> Using an Incognito tab? Ensure you <b>download your Security Key Backup (.json)</b> in Settings before closing the session.
                </div>
            </div>
        </div>

        <?php if ($error): ?>
            <div id="errorAlert" class="alert alert-danger p-2 mb-4" style="font-size: 0.9rem;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="mb-3 text-start">
                <label for="username" class="form-label">Officer ID / Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="text"
                        class="form-control"
                        id="username"
                        name="username"
                        placeholder="Enter your ID or username"
                        value="<?php echo htmlspecialchars($login_input); ?>"
                        required>
                </div>
            </div>

            <div class="mb-3 text-start">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                    <span class="input-group-text" id="togglePassword">
                        <i class="bi bi-eye" id="toggleIcon"></i>
                    </span>
                </div>
                <div class="text-end mt-2">
                    <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100">Login</button>
            <a href="register.php" class="btn btn-success w-100">Register New Account</a>
        </form>

        <div class="warning-text">
            <strong>AUTHORIZED PERSONNEL ONLY - ALL ACTIONS LOGGED</strong>
        </div>

    </div>

    <?php if ($trigger_panic_wipe): ?>
        <script>
            console.warn("CRITICAL ALERT: MAXIMUM FAILED ATTEMPTS REACHED.");
            console.warn("INITIATING LOCAL ENCRYPTION KEY DESTRUCTION...");

            // 1. Destroy all local storage (Private Keys, Saved Settings)
            localStorage.clear();

            // 2. Destroy all session storage (AES Session Keys)
            sessionStorage.clear();

            console.error("WIPE COMPLETE: Device Scrubbed.");
        </script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');
        const toggleIcon = document.querySelector('#toggleIcon');

        togglePassword.addEventListener('click', function(e) {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);

            if (type === 'password') {
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            } else {
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            }
        });

        // Makes the error alert fade out nicely
        const errorAlert = document.getElementById('errorAlert');
        if (errorAlert) {
            setTimeout(function() {
                errorAlert.classList.add('fade-out');
                setTimeout(function() {
                    errorAlert.style.display = 'none';
                }, 500);
            }, 5000);
        }
    </script>
</body>

</html>