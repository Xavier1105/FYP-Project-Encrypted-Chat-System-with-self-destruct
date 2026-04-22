<?php
session_start();
require_once 'db_connect.php';

$error = '';
$success = '';
$entered_officer_id = '';
$entered_username = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $officer_id = trim($_POST['officer_id']);
    $username = trim($_POST['username']);
    $position = trim($_POST['position']); // NEW: Capture Position

    $entered_officer_id = $officer_id;
    $entered_username = $username;

    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $public_key = '';

    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif (!preg_match("/[a-z]/i", $password)) {
        $error = "Password must contain at least one letter.";
    } elseif (!preg_match("/[0-9]/", $password)) {
        $error = "Password must contain at least one number.";
    } elseif (!preg_match("/[\W_]/", $password)) {
        $error = "Password must contain at least one special character.";
    } else {
        // CHECK IF OFFICER ID OR USERNAME EXISTS
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE officer_id = ? OR username = ?");
        $stmt->bind_param("ss", $officer_id, $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Officer ID or Username already taken.";
        } else {
            // --- 1. DETERMINE ROLE & CLEAN THE OFFICER ID ---
            $is_hos = (stripos($officer_id, 'HOS') === 0);
            $is_admin = (stripos($officer_id, 'AD') === 0);

            if ($is_hos) {
                $clean_officer_id = substr($officer_id, 3); // Cuts off the first 3 letters ('HOS')
                $role = 'hos';
                $is_approved = 1;
            } elseif ($is_admin) {
                $clean_officer_id = substr($officer_id, 2); // Cuts off the first 2 letters ('AD')
                $role = 'admin';
                $is_approved = 1;
            } else {
                $clean_officer_id = $officer_id; // Keeps normal ID unchanged
                $role = 'officer';
                $is_approved = 0;
            }

            // --- 2. CHECK FOR MULTIPLE HOS ---
            $hos_already_exists = false;
            if ($role === 'hos') {
                $check_hos = $conn->query("SELECT user_id FROM users WHERE role = 'hos'");
                if ($check_hos->num_rows > 0) {
                    $hos_already_exists = true;
                }
            }

            if ($hos_already_exists) {
                $error = "A Head of Security account already exists. Only one is permitted.";
            } else {
                // --- 3. CHECK IF ID OR USERNAME EXISTS ---
                // Notice we use $clean_officer_id here so it only checks the numbers!
                $stmt = $conn->prepare("SELECT user_id FROM users WHERE officer_id = ? OR username = ?");
                $stmt->bind_param("ss", $clean_officer_id, $username);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    $error = "Officer ID or Username already taken.";
                } else {
                    $password_hash = password_hash($password, PASSWORD_BCRYPT);

                    // --- 4. INSERT INTO DATABASE ---
                    $insert_stmt = $conn->prepare("INSERT INTO users (officer_id, username, password_hash, public_key, role, is_approved, position) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    // We save $clean_officer_id instead of the raw input
                    $insert_stmt->bind_param("sssssis", $clean_officer_id, $username, $password_hash, $public_key, $role, $is_approved, $position);

                    if ($insert_stmt->execute()) {
                        if ($role === 'hos') {
                            $success = "<strong>Head of Security Account Created!</strong> You may login now.";
                        } elseif ($role === 'admin') {
                            $success = "<strong>Admin Account Created!</strong> You may login now.";
                        } else {
                            $success = "Registration successful! Please wait for Admin approval before logging in.";
                        }
                        // Clear inputs on success
                        $entered_officer_id = '';
                        $entered_username = '';
                        $entered_position = '';
                    } else {
                        $error = "Error: " . $conn->error;
                    }
                    $insert_stmt->close();
                }
                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sentinel - Register</title>
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

        .register-card {
            width: 100%;
            max-width: 600px;
            padding: 2.5rem;
            border: none;
            border-radius: 12px;
            background-color: rgba(255, 255, 255, 0.95);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        .logo-placeholder {
            width: 70px;
            height: 70px;
            background-color: #e6f4ea;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            color: #108a55;
            font-weight: bold;
            font-size: 0.8rem;
        }

        .register-header h3 {
            font-weight: 700;
            color: #1a2e44;
            margin-bottom: 0.2rem;
        }

        .register-header p {
            color: #6c757d;
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
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
            border-color: #108a55;
        }

        .toggle-password {
            border-left: none;
            border-right: 1px solid #ced4da;
            cursor: pointer;
        }

        .password-field {
            border-right: none;
        }

        .btn-success {
            background-color: #108a55;
            border: none;
            padding: 0.7rem;
            font-weight: 600;
            border-radius: 6px;
            width: 100%;
        }

        .btn-success:hover {
            background-color: #0c6b42;
        }

        .login-link {
            display: block;
            margin-top: 1rem;
            color: #2563eb;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .login-link:hover {
            text-decoration: underline;
        }

        .fade-out {
            opacity: 0;
            transition: opacity 0.5s ease-out;
        }

        .password-requirements {
            font-size: 0.75rem;
            color: #6c757d;
            text-align: left;
            margin-top: 0.25rem;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>

    <div class="register-card">
        <div class="logo-placeholder">
            <i class="bi bi-person-plus-fill" style="font-size: 1.5rem;"></i>
        </div>

        <div class="register-header">
            <h3>Create Account</h3>
            <p>Join the Sentinel secure network</p>
        </div>

        <?php if ($error): ?>
            <div id="alertBox" class="alert alert-danger p-2 mb-4" style="font-size: 0.9rem;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div id="alertBox" class="alert alert-success p-2 mb-4" style="font-size: 0.9rem;">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form id="registerForm" action="register.php" method="POST">
            <input type="hidden" id="public_key" name="public_key">

            <div class="mb-3 text-start">
                <label for="officer_id" class="form-label">Officer ID</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person-vcard"></i></span>
                    <input type="text"
                        class="form-control"
                        id="officer_id"
                        name="officer_id"
                        placeholder="e.g. 12345"
                        value="<?php echo htmlspecialchars($entered_officer_id); ?>"
                        required>
                </div>
            </div>

            <div class="mb-3 text-start">
                <label for="username" class="form-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text"
                        class="form-control"
                        id="username"
                        name="username"
                        placeholder="e.g. AgentSmith"
                        value="<?php echo htmlspecialchars($entered_username); ?>"
                        required>
                </div>
            </div>

            <div class="mb-3 text-start">
                <label for="position" class="form-label">Position / Title</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-briefcase"></i></span>
                    <input type="text"
                        class="form-control"
                        id="position"
                        name="position"
                        placeholder="e.g. Field Officer"
                        required>
                </div>
            </div>

            <div class="mb-0 text-start">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control password-field" id="password" name="password" placeholder="Create a password" required>
                    <span class="input-group-text toggle-password" onclick="togglePass('password', this)">
                        <i class="bi bi-eye"></i>
                    </span>
                </div>
            </div>

            <div class="password-requirements">
                Password must have at least 6 characters with letters, numbers, and special characters.
            </div>

            <div class="mb-3 text-start">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                    <input type="password" class="form-control password-field" id="confirm_password" name="confirm_password" placeholder="Confirm password" required>
                    <span class="input-group-text toggle-password" onclick="togglePass('confirm_password', this)">
                        <i class="bi bi-eye"></i>
                    </span>
                </div>
            </div>

            <button type="submit" class="btn btn-success mt-2">Register</button>
            <a href="login.php" class="login-link">Already have an account? Login here</a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function togglePass(fieldId, iconSpan) {
            const field = document.getElementById(fieldId);
            const icon = iconSpan.querySelector('i');

            if (field.type === "password") {
                field.type = "text";
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                field.type = "password";
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }

        const alertBox = document.getElementById('alertBox');
        if (alertBox) {
            setTimeout(function() {
                alertBox.classList.add('fade-out');
                setTimeout(function() {
                    alertBox.style.display = 'none';
                }, 500);
            }, 5000);
        }
    </script>
</body>

</html>