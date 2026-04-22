<?php
session_start();
// SECURITY KICK-OUT: Only HOS allowed!
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'hos') {
    die("<h2 style='color:red; text-align:center; margin-top:50px;'>Access Denied.</h2>");
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Vault Reset</title>
    <link rel="icon" type="image/png" href="Sentinel logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* Add custom styling for the body to handle the background image perfectly */
        body {
            height: 100vh;
            /* Linear-gradient adds a 75% dark tint over the image so the UI stands out */
            background-image: url('Unimas4.webp');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }
    </style>
</head>
<body class="bg-dark text-white d-flex align-items-center justify-content-center" style="height: 100vh;">

    <div class="text-center p-5 border border-danger border-opacity-50 rounded-4 shadow-lg" style="background-color: #1a1d20; max-width: 500px;">
        <i class="bi bi-exclamation-triangle-fill text-danger display-1 mb-3"></i>
        <h2 class="fw-bold text-danger">Emergency Key Reset</h2>
        <p class="text-white-50 small mb-4">You are about to destroy the old Master Padlock and generate a new one. All messages sent after this exact moment will be encrypted with your new key.</p>
        
        <button id="genBtn" onclick="generateMasterKeys()" class="btn btn-danger btn-lg w-100 fw-bold shadow">
            <i class="bi bi-fire me-2"></i> Generate New Master Keys
        </button>

        <a href="admin_dashboard.php" class="btn btn-outline-secondary w-100 mt-3">Cancel & Return</a>
    </div>

    <script>
        // Helper: Formats Base64 into standard PEM format
        function formatPEM(base64Str, type) {
            let pem = `-----BEGIN ${type}-----\n`;
            for (let i = 0; i < base64Str.length; i += 64) {
                pem += base64Str.substring(i, i + 64) + '\n';
            }
            pem += `-----END ${type}-----`;
            return pem;
        }

        async function generateMasterKeys() {
            const btn = document.getElementById('genBtn');
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Generating 4096-bit Keys...';
            btn.disabled = true;

            try {
                // 1. Generate upgraded keys
                const keyPair = await window.crypto.subtle.generateKey(
                    { name: "RSA-OAEP", modulusLength: 4096, publicExponent: new Uint8Array([1, 0, 1]), hash: "SHA-256" },
                    true, ["encrypt", "decrypt"]
                );

                const pubExp = await window.crypto.subtle.exportKey("spki", keyPair.publicKey);
                const privExp = await window.crypto.subtle.exportKey("pkcs8", keyPair.privateKey);

                const pubBase64 = btoa(String.fromCharCode(...new Uint8Array(pubExp)));
                const privBase64 = btoa(String.fromCharCode(...new Uint8Array(privExp)));

                const pemPublic = formatPEM(pubBase64, "PUBLIC KEY");
                const pemPrivate = formatPEM(privBase64, "PRIVATE KEY");

                // 2. Send padlock to server
                await fetch('save_master_public.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ public_key: pemPublic })
                });

                // 3. Download New Private Key
                const backupData = JSON.stringify({
                    system: "Sentinel Master Audit Vault",
                    generated_at: new Date().toISOString(),
                    private_key_pem: pemPrivate
                }, null, 4);

                const blob = new Blob([backupData], { type: 'application/json' });
                const a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = `MASTER_AUDIT_PRIVATE_KEY_NEW.json`;
                a.click();
                
                alert("SUCCESS! The system is now using your new keys.\n\nReturning to dashboard...");
                window.location.href = "admin_dashboard.php";

            } catch (error) {
                alert("Critical Error: Key generation failed.");
                btn.innerHTML = '<i class="bi bi-fire me-2"></i> Generate New Master Keys';
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>