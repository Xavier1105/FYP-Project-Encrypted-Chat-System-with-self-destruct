<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sentinel - Forgot Password</title>
    <link rel="icon" type="image/png" href="Sentinel logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body {
            /* BACKGROUND IMAGE CONFIGURATION */
            background-image: url('Unimas4.webp');
            /* Ensure this file exists in your folder */
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;

            /* Fallback color if image is missing */
            background-color: #f0f2f5;

            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
        }

        .info-card {
            width: 100%;
            max-width: 600px;
            padding: 2.5rem;
            border: none;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            background-color: white;
            text-align: center;
        }

        .icon-circle {
            width: 70px;
            height: 70px;
            /* NEW: A very light tint of the Security Indigo */
            background-color: rgba(79, 70, 229, 0.1);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            /* NEW: Matches the primary Security Indigo from the button gradient */
            color: #4f46e5;
            font-size: 2rem;
            /* Added a border to match the circle style in the screenshot */
            border: 1px solid rgba(79, 70, 229, 0.2);
        }

        h3 {
            font-weight: 700;
            color: #1a2e44;
            margin-bottom: 1rem;
        }

        p {
            color: #6c757d;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .admin-contact {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            border: 1px dashed #dee2e6;
            margin-bottom: 2rem;
            font-weight: 600;
            color: #495057;
        }

        .btn-primary {
            /* A rich, deep indigo gradient */
            background: linear-gradient(135deg, #4f46e5, #3730a3);
            color: white;
            border: none;
            padding: 0.8rem;
            /* Slightly taller for a premium feel */
            font-weight: 600;
            border-radius: 8px;
            /* Slightly rounder to match modern UI */
            transition: all 0.2s ease-in-out;
            /* Smooth animation */
            display: block;
            /* Ensures the <a> tag behaves like a full button */
            text-decoration: none;
            /* Removes any default underline */
        }

        .btn-primary:hover {
            /* Brightens up on hover */
            background: linear-gradient(135deg, #6366f1, #4338ca);
            color: white;
            transform: translateY(-2px);
            /* Floats upward */
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
            /* Indigo glowing shadow */
        }
    </style>
</head>

<body>

    <div class="info-card">
        <div class="icon-circle">
            <i class="bi bi-shield-lock"></i>
        </div>

        <h3>Forgot Password?</h3>

        <p>
            For security reasons, self-service password reset is disabled for the Sentinel System.
            Please contact the <strong>Head of Security</strong> or the <strong>System Administrator</strong> to request a manual password reset.
        </p>

        <div class="admin-contact">
            <i class="bi bi-telephone-fill me-2"></i> IT Support Ext: 3044
        </div>

        <a href="login.php" class="btn btn-primary w-100">Back to Login</a>
    </div>

</body>

</html>