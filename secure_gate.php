<?php
session_start();
require_once 'db_connect.php'; // Make sure to include your DB connection!

// 1. SECURITY CHECK
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. CHECK IF BRAND NEW USER
$stmt = $conn->prepare("SELECT public_key FROM users WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

// If the public_key column is empty, they are a brand new user!
$is_brand_new_user = empty($user_data['public_key']) ? 'true' : 'false';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sentinel - Security Gate</title>
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
            background-color: #1a2e44;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
        }

        .security-card {
            background-color: rgba(33, 37, 41, 0.95) !important;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .logout-link {
            color: #adb5bd;
            font-size: 0.85rem;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease-in-out;
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 6px;
        }

        .logout-link:hover {
            color: #ff6b6b;
            background-color: rgba(255, 107, 107, 0.1);
            transform: translateY(-2px);
        }

        .btn-unlock {
            background: linear-gradient(135deg, #0dcaf0, #0d6efd);
            color: white;
            border: none;
            transition: all 0.2s ease-in-out;
        }

        .btn-unlock:hover {
            background: linear-gradient(135deg, #22d3f4, #2563eb);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(13, 202, 240, 0.5);
        }
    </style>
</head>

<body class="d-flex align-items-center justify-content-center" style="height: 100vh;">

    <div id="loadingUi" class="text-center text-info">
        <div class="spinner-border mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
        <h5 class="fw-bold">Verifying Device Security...</h5>
    </div>

    <div id="uploadUi" class="card security-card border-info shadow-lg d-none" style="width: 100%; max-width: 450px; border-width: 2px; border-radius: 15px;">
        <div class="card-body p-5 text-center">

            <div class="mb-4">
                <i class="bi bi-shield-lock-fill text-info display-1" style="text-shadow: 0 0 15px rgba(13, 202, 240, 0.5);"></i>
            </div>

            <h3 class="text-white fw-bold">Device Not Recognized</h3>
            <p class="text-white-50 small mb-4">
                You are logging in from an Incognito tab or a new device. To unlock your End-to-End Encrypted chat history, please upload your Security Backup file.
            </p>

            <input type="file" id="keyFileInput" class="form-control bg-secondary bg-opacity-25 text-white border-secondary mb-3" accept=".json,.txt">

            <button class="btn btn-unlock w-100 fw-bold mb-4 shadow-sm" onclick="importKeys()" style="border-radius: 8px;">
                <i class="bi bi-unlock-fill me-2"></i> Unlock Sentinel
            </button>

            <hr class="border-secondary my-4 opacity-25">

            <p class="text-white-50 small mb-2">Lost your backup file?</p>
            <button class="btn btn-outline-danger btn-sm w-100 mb-3" style="border-radius: 8px;" data-bs-toggle="modal" data-bs-target="#keyResetModal">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> Generate New Keys (Wipes Old History)
            </button>

            <div class="mt-3">
                <a href="logout.php" class="logout-link">
                    <i class="bi bi-box-arrow-left me-2"></i> Cancel and Logout
                </a>
            </div>
        </div>
    </div>

    <div class="modal fade" id="keyResetModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg border-0" style="background-color: #1a1d21; border-radius: 20px; overflow: hidden;">
                <div class="p-1" style="background: linear-gradient(90deg, #dc3545, #ff6b6b);"></div>
                <div class="modal-body p-5 text-center">
                    <div class="mb-4">
                        <i class="bi bi-exclamation-octagon-fill text-danger" style="font-size: 4rem; filter: drop-shadow(0 0 10px rgba(220, 53, 69, 0.4));"></i>
                    </div>
                    <h3 class="text-white fw-bold mb-3">Irreversible Action</h3>

                    <p class="text-white-50 mb-4">
                        Generating a new key set will permanently break the link to all previously encrypted data.

                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'hos'): ?>
                    <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-start mt-3">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <strong>HOS NOTICE:</strong> All existing **Audit Logs** were encrypted for your OLD key. If you continue, you will <span class="text-decoration-underline text-white fw-bold">NEVER</span> be able to decrypt those logs again.
                    </div>
                <?php else: ?>
                    <br><br>
                    <span class="text-danger fw-bold">NOTICE:</span> Any personal messages encrypted with your old key will be lost forever.
                <?php endif; ?>
                </p>

                <div class="d-grid gap-3">
                    <button type="button" class="btn btn-danger btn-lg fw-bold py-3 shadow-sm d-flex align-items-center justify-content-center" id="confirmResetBtn" onclick="executeEmergencyReset()">
                        Confirm Key Rotation
                    </button>
                    <button type="button" class="btn btn-link text-white-50 text-decoration-none" data-bs-dismiss="modal">
                        Cancel and Go Back to Upload JSON
                    </button>
                </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="crypto.js"></script>

    <script>
        const MY_USER_ID = <?php echo $_SESSION['user_id']; ?>;
        const MY_USERNAME = "<?php echo htmlspecialchars($_SESSION['username']); ?>"; // <-- Add this line
        const PRIV_KEY_NAME = 'sentinel_private_key_' + MY_USER_ID;
        const IS_BRAND_NEW_USER = <?php echo $is_brand_new_user; ?>;

        // 1. SMART GATEKEEPER CHECK
        const storedPrivateKey = localStorage.getItem(PRIV_KEY_NAME);

        if (storedPrivateKey) {
            // SCENARIO A: Normal User. Key exists. Let them in instantly!
            window.location.replace('index.php');

        } else if (IS_BRAND_NEW_USER) {
            // SCENARIO B: Brand New User! 
            // Show a welcoming UI instead of the scary upload screen.
            document.getElementById('loadingUi').innerHTML = `
                <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
                <h5 class="fw-bold text-primary">Initializing Secure Vault...</h5>
                <p class="text-white-50 small">Generating your unique End-to-End Encryption keys.</p>
            `;

            // Automatically generate their first set of keys!
            generateAndStoreKeys().then(() => {
                // Once generated, send them to the dashboard!
                setTimeout(() => {
                    window.location.replace('index.php');
                }, 1000);
            });

        } else {
            // SCENARIO C: Existing User on a New Device or Incognito!
            // Show the strict Upload UI.
            document.getElementById('loadingUi').classList.add('d-none');
            document.getElementById('uploadUi').classList.remove('d-none');
        }

        function importKeys() {
            const fileInput = document.getElementById('keyFileInput');
            if (fileInput.files.length === 0) {
                alert("Tactical Error: Please select your backup file first.");
                return;
            }

            const file = fileInput.files[0];
            const reader = new FileReader();

            reader.onload = async function(e) {
                try {
                    const keyData = JSON.parse(e.target.result);
                    if (keyData.private_key && keyData.public_key) {
                        const uploadedFingerprint = generateKeyFingerprint(keyData.private_key);
                        localStorage.setItem(PRIV_KEY_NAME, keyData.private_key);

                        await fetch('save_public_key.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                public_key: keyData.public_key
                            })
                        });

                        document.getElementById('uploadUi').innerHTML = `
                            <div class="text-center p-5">
                                <i class="bi bi-check-circle-fill text-success display-1 mb-3"></i>
                                <h3 class="text-success fw-bold">Keys Verified & Synced</h3>
                                <div class="badge bg-dark border border-success text-success fs-5 mb-3 px-3 py-2">ID: ${uploadedFingerprint}</div>
                                <p class="text-white-50">Unlocking Sentinel...</p>
                            </div>`;

                        setTimeout(() => {
                            window.location.replace('index.php');
                        }, 1500);
                    }
                } catch (err) {
                    alert("Security Error: Failed to read file.");
                }
            };
            reader.readAsText(file);
        }

        // --- NEW EMERGENCY RESET LOGIC ---
        async function executeEmergencyReset() {
            const btn = document.getElementById('confirmResetBtn');
            btn.disabled = true;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span> Wiping History...`;

            try {
                // 🚨 STEP 1: WIPE CHAT HISTORIES ONLY
                const wipeResponse = await fetch('clear_all_history.php', {
                    method: 'POST'
                });
                const wipeResult = await wipeResponse.json();

                if (!wipeResult.success) {
                    throw new Error("History wipe failed");
                }

                // STEP 2: Generate the brand new keys
                await generateAndStoreKeys();

                const newPrivKey = localStorage.getItem(PRIV_KEY_NAME);
                const newPubKey = await generatePublicKeyFromPrivate(newPrivKey);

                // STEP 3: Sync New Public Key to DB
                await fetch('save_public_key.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        public_key: newPubKey
                    })
                });

                // STEP 4: DOWNLOAD BACKUP
                const backupData = JSON.stringify({
                    private_key: newPrivKey,
                    public_key: newPubKey,
                    generated_at: new Date().toISOString()
                });

                const blob = new Blob([backupData], {
                    type: 'application/json'
                });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `Sentinel_Reset_Backup_${MY_USERNAME}.json`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);

                // SUCCESS UI
                btn.classList.replace('btn-danger', 'btn-success');
                btn.innerHTML = `<i class="bi bi-shield-check me-2"></i> History Cleared & Keys Rotated`;

                setTimeout(() => {
                    window.location.replace('index.php');
                }, 1500);

            } catch (e) {
                console.error("Reset Failed:", e);
                alert("Critical System Error: " + e.message);
                location.reload();
            }
        }
    </script>
</body>

</html>