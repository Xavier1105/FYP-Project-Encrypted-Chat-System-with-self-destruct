<style>
    /* Custom Styles for Settings Dashboard Cards */
    .setting-card {
        background-color: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: 12px;
        transition: all 0.3s ease;
        cursor: pointer;
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .setting-card:hover {
        border-color: #0dcaf0;
        /* The nice blue/teal hover border */
        transform: translateY(-3px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
    }

    .setting-card.border-danger:hover {
        border-color: #dc3545 !important;
        box-shadow: 0 8px 15px rgba(220, 53, 69, 0.15);
    }

    .setting-icon-box {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
    }

    /* Panel Animation */
    .settings-panel {
        display: none;
        animation: fadeIn 0.3s ease-in-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Preset color hover */
    .bg-preset-card {
        cursor: pointer;
        border: 2px solid transparent;
        transition: transform 0.2s ease-in-out;
    }

    .bg-preset-card:hover {
        transform: scale(1.15);
        border-color: rgba(255, 255, 255, 0.5);
    }

    /* Custom Settings Save Button */
    .btn-settings-save {
        background-color: #d1fae5;
        color: #059669;
        border: none;
        border-radius: 8px;
        transition: all 0.2s ease-in-out;
    }

    .btn-settings-save:hover {
        background-color: #bbf7d0;
        color: #059669;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2) !important;
    }

    .btn-settings-save:active {
        transform: translateY(0);
        box-shadow: 0 2px 5px rgba(5, 150, 105, 0.2) !important;
    }

    /* Custom Settings Cancel Button */
    .btn-settings-cancel {
        background-color: #e2e8f0;
        color: #475569;
        border: none;
        border-radius: 8px;
        transition: all 0.2s ease-in-out;
    }

    .btn-settings-cancel:hover {
        background-color: #cbd5e1;
        color: #475569;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(71, 85, 105, 0.2) !important;
    }

    .btn-settings-cancel:active {
        transform: translateY(0);
        box-shadow: 0 2px 5px rgba(71, 85, 105, 0.2) !important;
    }

    /* Custom Save Background Button (Appearance Panel) */
    .btn-settings-save-bg {
        background-color: rgba(13, 202, 240, 0.15);
        color: #0dcaf0;
        border: none;
        border-radius: 8px;
        transition: all 0.2s ease-in-out;
    }

    .btn-settings-save-bg:hover {
        background-color: rgba(13, 202, 240, 0.25);
        color: #0dcaf0;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(13, 202, 240, 0.2) !important;
    }

    .btn-settings-save-bg:active {
        transform: translateY(0);
        box-shadow: 0 2px 5px rgba(13, 202, 240, 0.2) !important;
    }

    /* Custom Settings Clear Buttons (Data Management) */
    .btn-settings-clear {
        background-color: rgba(220, 53, 69, 0.15);
        color: #e57373;
        border: none;
        border-radius: 8px;
        transition: all 0.2s ease-in-out;
    }

    .btn-settings-clear:hover {
        background-color: rgba(220, 53, 69, 0.25);
        color: #e57373;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2) !important;
    }

    .btn-settings-clear:active {
        transform: translateY(0);
        box-shadow: 0 2px 5px rgba(220, 53, 69, 0.2) !important;
    }

    /* Custom Unblock Button (Blocked Users Panel) */
    .btn-settings-unblock {
        background-color: #2b3a55;
        color: white;
        border: none;
        border-radius: 6px;
        transition: all 0.2s ease-in-out;
    }

    .btn-settings-unblock:hover {
        background-color: #3b4d6b;
        /* Slightly lighter navy */
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(43, 58, 85, 0.3) !important;
    }

    .btn-settings-unblock:active {
        transform: translateY(0);
        box-shadow: 0 2px 5px rgba(43, 58, 85, 0.3) !important;
    }

    /* Custom Settings Panel Buttons */
    .btn-gradient-purple {
        background: linear-gradient(135deg, #a855f7, #7c3aed);
        color: white;
        transition: all 0.2s ease-in-out;
    }

    .btn-gradient-purple:hover {
        background: linear-gradient(135deg, #b673f8, #8b5cf6);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(168, 85, 247, 0.4) !important;
    }

    .btn-gradient-indigo {
        background: linear-gradient(135deg, #6366f1, #4f46e5);
        color: white;
        transition: all 0.2s ease-in-out;
    }

    .btn-gradient-indigo:hover {
        background: linear-gradient(135deg, #818cf8, #6366f1);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4) !important;
    }

    .btn-gradient-emerald {
        background: linear-gradient(135deg, #10b981, #047857);
        color: white;
        transition: all 0.2s ease-in-out;
    }

    .btn-gradient-emerald:hover {
        background: linear-gradient(135deg, #34d399, #059669);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4) !important;
    }
</style>

<div id="settingsInterface" style="display: none; flex-direction: column; height: 100%; width: 100%; overflow-y: auto; background-color: transparent;">
    <?php
    // FETCH TOTAL BLOCKED USERS COUNT
    $blocked_count = 0;
    $current_uid = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

    if ($current_uid > 0 && isset($conn)) {
        $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM blocked_users WHERE blocker_id = ? AND is_active = 1");
        if ($count_stmt) {
            $count_stmt->bind_param("i", $current_uid);
            $count_stmt->execute();
            $count_res = $count_stmt->get_result()->fetch_assoc();
            $blocked_count = $count_res['total'];
            $count_stmt->close();
        }
    }
    ?>

    <div class="border-bottom shadow-sm p-4 w-100 flex-shrink-0" style="background-color: var(--bs-body-bg); backdrop-filter: blur(10px); position: sticky; top: 0; z-index: 10;">
        <div class="container-fluid p-0">

            <div class="d-flex align-items-center mb-2">
                <div class="d-flex align-items-center justify-content-center shadow-sm rounded-3 me-3"
                    style="width: 48px; height: 48px; background-color: rgba(100, 116, 139, 0.1); border: 1px solid rgba(100, 116, 139, 0.25);">
                    <i class="bi bi-gear-fill fs-4" style="color: #64748b;"></i>
                </div>

                <h3 class="fw-bold mb-0 text-body" style="letter-spacing: -0.5px;">System Settings</h3>
            </div>

            <div class="text-body-secondary small mt-1" id="settingsSubtitle">Dashboard Overview</div>

        </div>
    </div>

    <div id="settingsGridArea" class="p-4">
        <div class="row g-4">

            <div class="col-lg-4 col-md-6">
                <div class="setting-card p-4" onclick="openSettingPanel('appearancePanel')">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="setting-icon-box bg-info bg-opacity-10 text-info"><i class="bi bi-palette-fill fs-5"></i></div>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill">Active</span>
                    </div>
                    <h6 class="fw-bold mb-2">Appearance</h6>
                    <small class="text-muted d-block mb-4">App theme and chat background customization.</small>
                    <button class="btn btn-sm btn-outline-secondary w-100 mt-auto border-secondary border-opacity-25">Configure</button>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="setting-card p-4" onclick="openSettingPanel('securityPanel')">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="setting-icon-box bg-success bg-opacity-10 text-success"><i class="bi bi-shield-lock-fill fs-5"></i></div>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill">Available</span>
                    </div>
                    <h6 class="fw-bold mb-2">Security</h6>
                    <small class="text-muted d-block mb-4">Update your authentication credentials.</small>
                    <button class="btn btn-sm btn-outline-secondary w-100 mt-auto border-secondary border-opacity-25">Configure</button>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="setting-card p-4" onclick="openSettingPanel('privacyPanel')">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="setting-icon-box bg-primary bg-opacity-10 text-primary"><i class="bi bi-eye-slash-fill fs-5"></i></div>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill">Active</span>
                    </div>
                    <h6 class="fw-bold mb-2">Privacy</h6>
                    <small class="text-muted d-block mb-4">Online status, discoverability, and receipts.</small>
                    <button class="btn btn-sm btn-outline-secondary w-100 mt-auto border-secondary border-opacity-25">Configure</button>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="setting-card p-4" onclick="openSettingPanel('dataPanel')">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="setting-icon-box bg-warning bg-opacity-10 text-warning"><i class="bi bi-database-fill-gear fs-5"></i></div>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill">Available</span>
                    </div>
                    <h6 class="fw-bold mb-2">Data Management</h6>
                    <small class="text-muted d-block mb-4">Manage local cache and chat histories.</small>
                    <button class="btn btn-sm btn-outline-secondary w-100 mt-auto border-secondary border-opacity-25">Configure</button>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="setting-card p-4" onclick="openSettingPanel('blockedPanel')">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="setting-icon-box bg-secondary bg-opacity-10 text-secondary"><i class="bi bi-person-x-fill fs-5"></i></div>
                        <span id="blockedCountBadge" class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill"><?php echo $blocked_count; ?> Blocked</span>
                    </div>
                    <h6 class="fw-bold mb-2">Blocked Users</h6>
                    <small class="text-muted d-block mb-4">Manage restricted officers and users.</small>
                    <button class="btn btn-sm btn-outline-secondary w-100 mt-auto border-secondary border-opacity-25">Configure</button>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="setting-card p-4" onclick="openSettingPanel('backupPanel')">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="setting-icon-box" style="background-color: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                            <i class="bi bi-database-fill fs-5"></i>
                        </div>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill">Available</span>
                    </div>
                    <h6 class="fw-bold mb-2">Chat Backups</h6>
                    <small class="text-muted d-block mb-4">Export and manage secure backups of your conversations.</small>
                    <button class="btn btn-sm btn-outline-secondary w-100 mt-auto border-secondary border-opacity-25">Manage</button>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="setting-card border-danger p-4" style="background-color: rgba(220,53,69,0.03);" onclick="openSettingPanel('panicPanel')">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="setting-icon-box bg-danger text-white shadow-sm"><i class="bi bi-exclamation-triangle-fill fs-5"></i></div>
                        <span class="badge bg-danger text-white rounded-pill shadow-sm">CRITICAL</span>
                    </div>
                    <h6 class="fw-bold text-danger mb-2">Emergency Wipe</h6>
                    <small class="text-danger opacity-75 d-block mb-4">Instantly destroy session and local memory.</small>
                    <button class="btn btn-sm btn-danger w-100 mt-auto shadow-sm">Access Override</button>
                </div>
            </div>

        </div>
    </div>

    <div id="settingsPanelsArea" class="p-4" style="display: none; max-width: 800px; margin: 0 auto; width: 100%;">

        <button class="btn btn-secure-chat fw-bold px-4 py-2 mb-4 d-inline-flex align-items-center shadow-sm" style="border-radius: 8px; transition: all 0.2s;" onclick="closeSettingPanel()">
            <i class="bi bi-arrow-left-circle-fill fs-5 me-2"></i> Back to Dashboard
        </button>

        <div id="appearancePanel" class="settings-panel card border-0 shadow-sm p-4" style="border-radius: 12px; background-color: var(--bs-body-bg); border: 1px solid var(--bs-border-color) !important;">
            <h5 class="fw-bold mb-4"><i class="bi bi-palette-fill text-info me-2"></i> Appearance</h5>

            <?php
            // SETUP: Determine the saved styles and filename for the UI
            $previewStyle = 'background-color: #1e293b;'; // Default fallback
            $savedFileName = 'No file chosen';

            // FIXED: If background is empty, set the saved color to the default #1e293b
            $savedBgColor = empty($my_info['chat_background']) ? '#1e293b' : '';

            if (!empty($my_info['chat_background'])) {
                $bg = $my_info['chat_background'];
                if (strpos($bg, '#') === 0) {
                    $previewStyle = "background-color: " . htmlspecialchars($bg) . ";";
                    $savedBgColor = htmlspecialchars($bg);
                } else {
                    // It's an image
                    $previewStyle = "background-image: url('" . htmlspecialchars($bg) . "'); background-size: cover; background-position: center; background-repeat: no-repeat; background-blend-mode: overlay; background-color: rgba(0,0,0,0.6);";
                    $savedFileName = basename($bg);
                }
            }
            ?>

            <h6 class="fw-bold mb-3">Chat Background</h6>
            <div class="d-flex gap-4 mb-4 flex-wrap">
                <div class="text-center">
                    <div class="rounded-circle bg-preset-card shadow-sm mx-auto mb-2 <?php echo ($savedBgColor === '#1e293b') ? 'border border-info border-2' : ''; ?>" style="width: 45px; height: 45px; background-color: #1e293b;" onclick="selectPreset(this, '#1e293b')"></div>
                    <small class="text-muted fw-bold d-block" style="font-size: 0.75rem;">Default</small>
                </div>
                <div class="text-center">
                    <div class="rounded-circle bg-preset-card shadow-sm mx-auto mb-2 <?php echo ($savedBgColor === '#0284c7') ? 'border border-info border-2' : ''; ?>" style="width: 45px; height: 45px; background-color: #0284c7;" onclick="selectPreset(this, '#0284c7')"></div>
                    <small class="text-muted fw-bold d-block" style="font-size: 0.75rem;">Ocean</small>
                </div>
                <div class="text-center">
                    <div class="rounded-circle bg-preset-card shadow-sm mx-auto mb-2 <?php echo ($savedBgColor === '#16a34a') ? 'border border-info border-2' : ''; ?>" style="width: 45px; height: 45px; background-color: #16a34a;" onclick="selectPreset(this, '#16a34a')"></div>
                    <small class="text-muted fw-bold d-block" style="font-size: 0.75rem;">Forest</small>
                </div>
                <div class="text-center">
                    <div class="rounded-circle bg-preset-card shadow-sm mx-auto mb-2 <?php echo ($savedBgColor === '#1e1b4b') ? 'border border-info border-2' : ''; ?>" style="width: 45px; height: 45px; background-color: #1e1b4b;" onclick="selectPreset(this, '#1e1b4b')"></div>
                    <small class="text-muted fw-bold d-block" style="font-size: 0.75rem;">Midnight</small>
                </div>
                <div class="text-center">
                    <div class="rounded-circle bg-preset-card shadow-sm mx-auto mb-2 <?php echo ($savedBgColor === '#d97706') ? 'border border-info border-2' : ''; ?>" style="width: 45px; height: 45px; background-color: #d97706;" onclick="selectPreset(this, '#d97706')"></div>
                    <small class="text-muted fw-bold d-block" style="font-size: 0.75rem;">Sunset</small>
                </div>
            </div>

            <div class="input-group shadow-sm mb-4">
                <input type="file" id="chatBackgroundInput" class="d-none" accept="image/*" onchange="previewCustomImage(this)">
                <label for="chatBackgroundInput" class="btn btn-dark border-secondary border-opacity-25 mb-0 px-3" style="cursor: pointer;"><i class="bi bi-image me-2"></i> Custom File</label>

                <span id="chatBackgroundFileName" class="input-group-text bg-light text-muted border-secondary border-opacity-25 text-truncate flex-grow-1">
                    <?php echo htmlspecialchars($savedFileName); ?>
                </span>
            </div>

            <div class="mb-4 p-3 rounded" style="background-color: var(--bs-secondary-bg); border: 1px solid var(--bs-border-color);">
                <h6 class="fw-bold mb-3 fs-6">Preview</h6>

                <?php
                // This PHP makes sure the preview box loads with your CURRENT saved background
                $previewStyle = 'background-color: #1e293b;'; // Default fallback
                if (!empty($my_info['chat_background'])) {
                    $bg = $my_info['chat_background'];
                    if (strpos($bg, '#') === 0) {
                        $previewStyle = "background-color: " . htmlspecialchars($bg) . ";";
                    } else {
                        $previewStyle = "background-image: url('" . htmlspecialchars($bg) . "'); background-size: cover; background-position: center; background-repeat: no-repeat; background-blend-mode: overlay; background-color: rgba(0,0,0,0.6);";
                    }
                }
                ?>
                <div id="bgPreviewArea" class="rounded d-flex align-items-center justify-content-center text-white shadow-sm" style="height: 400px; <?php echo $previewStyle; ?> text-shadow: 0 1px 3px rgba(0,0,0,0.8); transition: all 0.3s ease; border: 1px solid rgba(255,255,255,0.1);">
                    <span class="fw-bold">This is how your chat background will look</span>
                </div>
            </div>

            <div class="text-end mt-2">
                <button class="btn btn-secure-chat fw-bold px-4 py-2 d-inline-flex justify-content-center align-items-center shadow-sm" style="border-radius: 8px; border: none;" onclick="saveBackground()">
                    <i class="bi bi-floppy me-2 fs-5"></i> Save Background
                </button>
            </div>
        </div>

        <div id="securityPanel" class="settings-panel card border-0 shadow-sm p-4" style="border-radius: 12px; background-color: var(--bs-body-bg); border: 1px solid var(--bs-border-color) !important;">
            <h5 class="fw-bold mb-4"><i class="bi bi-shield-lock-fill text-success me-2"></i> Security</h5>

            <form id="passwordUpdateForm" onsubmit="submitPasswordChange(event)">

                <div id="passwordBadgeContainer"></div>

                <input type="hidden" name="action" value="change_password">

                <div class="mb-3">
                    <label class="form-label fw-bold mb-1" style="font-size: 0.85rem;">Current Password</label>
                    <div class="position-relative">
                        <input type="password" class="form-control bg-light border-secondary border-opacity-25 pe-5 py-2" id="current_password" name="current_password" required>
                        <i class="bi bi-eye text-muted position-absolute top-50 end-0 translate-middle-y me-3" style="cursor: pointer; z-index: 10;" onclick="togglePasswordVisibility('current_password', this)"></i>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold mb-1" style="font-size: 0.85rem;">New Password</label>
                    <div class="position-relative">
                        <input type="password" class="form-control bg-light border-secondary border-opacity-25 pe-5 py-2" id="new_password" name="new_password" required>
                        <i class="bi bi-eye text-muted position-absolute top-50 end-0 translate-middle-y me-3" style="cursor: pointer; z-index: 10;" onclick="togglePasswordVisibility('new_password', this)"></i>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold mb-1" style="font-size: 0.85rem;">Confirm Password</label>
                    <div class="position-relative">
                        <input type="password" class="form-control bg-light border-secondary border-opacity-25 pe-5 py-2" id="confirm_password" name="confirm_password" required>
                        <i class="bi bi-eye text-muted position-absolute top-50 end-0 translate-middle-y me-3" style="cursor: pointer; z-index: 10;" onclick="togglePasswordVisibility('confirm_password', this)"></i>
                    </div>
                </div>

                <div class="row g-3 mt-3">
                    <div class="col-6">
                        <button type="submit" class="btn btn-gradient-emerald text-white fw-bold w-100 py-2 d-flex justify-content-center align-items-center shadow-sm" style="border-radius: 8px; border: none;">
                            <i class="bi bi-floppy me-2 fs-5"></i> Save
                        </button>
                    </div>
                    <div class="col-6">
                        <button type="button" class="btn btn-settings-cancel fw-bold w-100 py-2 d-flex justify-content-center align-items-center shadow-sm" onclick="closeSettingPanel()">
                            <i class="bi bi-x-lg me-2 fs-5"></i> Cancel
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div id="privacyPanel" class="settings-panel card border-0 shadow-sm p-4" style="border-radius: 12px; background-color: var(--bs-body-bg); border: 1px solid var(--bs-border-color) !important;">
            <h5 class="fw-bold mb-4"><i class="bi bi-eye-slash-fill text-primary me-2"></i> Privacy</h5>

            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-secondary border-opacity-10">
                <div>
                    <h6 class="mb-0 fw-bold fs-6">Show "Last Seen" Status</h6>
                    <small class="text-muted">Allow other officers to see when you were last online.</small>
                </div>
                <div class="form-check form-switch fs-4 mb-0">
                    <input class="form-check-input" type="checkbox" id="toggleLastSeen" style="cursor: pointer;"
                        onchange="updatePrivacySetting(this, 'show_last_seen')"
                        <?php echo (!isset($my_info['show_last_seen']) || $my_info['show_last_seen'] == 1) ? 'checked' : ''; ?>>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-secondary border-opacity-10">
                <div>
                    <h6 class="mb-0 fw-bold fs-6">Discoverable in Search</h6>
                    <small class="text-muted">Allow others to find you by searching your username.</small>
                </div>
                <div class="form-check form-switch fs-4 mb-0">
                    <input class="form-check-input" type="checkbox" id="toggleDiscoverable" style="cursor: pointer;"
                        onchange="updatePrivacySetting(this, 'is_discoverable')"
                        <?php echo (!isset($my_info['is_discoverable']) || $my_info['is_discoverable'] == 1) ? 'checked' : ''; ?>>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center">
                <label for="read_receipts" class="flex-grow-1" style="cursor: pointer; margin: 0;">
                    <h6 class="mb-0 fw-bold fs-6">Read Receipts</h6>
                    <small class="text-muted">Send 'Read' status to others when you view their messages.</small>
                </label>
                <div class="form-check form-switch fs-4 mb-0">
                    <input class="form-check-input" type="checkbox" style="cursor: pointer;" role="switch" id="read_receipts"
                        onchange="updatePrivacySetting(this, 'read_receipts')"
                        <?php echo (!isset($my_info['read_receipts']) || $my_info['read_receipts'] == 1) ? 'checked' : ''; ?>>
                </div>
            </div>
        </div>

        <div id="dataPanel" class="settings-panel card border-0 shadow-sm p-4" style="border-radius: 12px; background-color: var(--bs-body-bg); border: 1px solid var(--bs-border-color) !important;">
            <h5 class="fw-bold mb-4"><i class="bi bi-database-fill-gear text-warning me-2"></i> Data Management</h5>

            <?php
            // --- FETCH REAL CHAT DATA FROM DATABASE (SAFE VERSION) ---
            $total_messages = "0";
            $total_conversations = "0";

            // Ensure we have the user ID before trying to search the database
            $current_uid = isset($user_id) ? $user_id : (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0);

            if ($current_uid > 0 && isset($conn)) {
                try {
                    // FIXED: Only count messages that haven't been wiped by the user!
                    $msg_stmt = $conn->prepare("
                        SELECT COUNT(*) as msg_count 
                        FROM messages 
                        WHERE (sender_id = ? AND deleted_by_sender = 0) 
                           OR (receiver_id = ? AND deleted_by_receiver = 0)
                    ");
                    if ($msg_stmt) {
                        $msg_stmt->bind_param("ii", $current_uid, $current_uid);
                        $msg_stmt->execute();
                        $msg_result = $msg_stmt->get_result()->fetch_assoc();
                        $total_messages = number_format($msg_result['msg_count']);
                        $msg_stmt->close();
                    }

                    // FIXED: Only count active conversations where messages haven't been wiped!
                    $conv_stmt = $conn->prepare("
                        SELECT COUNT(DISTINCT CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END) as conv_count 
                        FROM messages 
                        WHERE (sender_id = ? AND deleted_by_sender = 0) 
                           OR (receiver_id = ? AND deleted_by_receiver = 0)
                    ");
                    if ($conv_stmt) {
                        $conv_stmt->bind_param("iii", $current_uid, $current_uid, $current_uid);
                        $conv_stmt->execute();
                        $conv_result = $conv_stmt->get_result()->fetch_assoc();
                        $total_conversations = number_format($conv_result['conv_count']);
                        $conv_stmt->close();
                    }
                } catch (Exception $e) {
                    // Silently handle errors so the page doesn't break
                }
            }
            ?>

            <div class="d-flex flex-column gap-3">

                <div class="d-flex justify-content-between align-items-center p-3 rounded border border-secondary border-opacity-25 shadow-sm" style="background-color: var(--bs-secondary-bg);">
                    <div>
                        <h6 class="mb-1 fw-bold">Cache Size</h6>
                        <small class="text-muted" style="font-size: 0.85rem;">Current: <span id="cacheSizeDisplay">Calculating...</span></small>
                    </div>
                    <button class="btn btn-settings-clear btn-sm fw-bold px-3 py-2" onclick="clearLocalCache()">
                        <i class="bi bi-trash3 me-1"></i> Clear Cache
                    </button>
                </div>

                <div class="d-flex justify-content-between align-items-center p-3 rounded border border-secondary border-opacity-25 shadow-sm mt-3" style="background-color: var(--bs-secondary-bg);">
                    <div>
                        <h6 class="mb-1 fw-bold">Chat History</h6>
                        <small class="text-muted" style="font-size: 0.85rem;">Messages: <span id="msgCountDisplay"><?php echo $total_messages; ?></span> | Conversations: <span id="convCountDisplay"><?php echo $total_conversations; ?></span></small>
                    </div>
                    <button class="btn btn-settings-clear btn-sm fw-bold px-3 py-2" onclick="clearChatHistory()">
                        <i class="bi bi-trash3 me-1"></i> Clear History
                    </button>
                </div>

                <div class="p-3 mt-2 rounded shadow-sm d-flex align-items-center" style="background-color: rgba(30, 33, 36, 0.6); border-left: 4px solid #f59e0b;">
                    <span class="fw-bold" style="color: #f59e0b; font-size: 0.85rem;">Clearing data cannot be undone. This will permanently delete all stored information.</span>
                </div>

            </div>
        </div>

        <div id="blockedPanel" class="settings-panel card border-0 shadow-sm p-4" style="border-radius: 12px; background-color: var(--bs-body-bg); border: 1px solid var(--bs-border-color) !important;">
            <h5 class="fw-bold mb-4"><i class="bi bi-person-x-fill text-secondary me-2"></i> Blocked Users</h5>

            <div id="blockedUsersList" class="d-flex flex-column gap-3">

                <?php
                // FETCH REAL BLOCKED USERS FROM DATABASE
                $blocked_users = [];

                // FIXED: Grab the ID directly from the secure session!
                $current_uid = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

                if ($current_uid > 0 && isset($conn)) {
                    // FIXED: Added "AND b.is_active = 1" so historical unblocks are hidden
                    $block_query = "SELECT u.username, DATE_FORMAT(b.created_at, '%d %b %Y') as date 
                                        FROM blocked_users b 
                                        JOIN users u ON b.blocked_id = u.user_id 
                                        WHERE b.blocker_id = ? AND b.is_active = 1";
                    $b_stmt = $conn->prepare($block_query);
                    if ($b_stmt) {
                        $b_stmt->bind_param("i", $current_uid);
                        $b_stmt->execute();
                        $b_result = $b_stmt->get_result();
                        while ($row = $b_result->fetch_assoc()) {
                            $blocked_users[] = $row;
                        }
                        $b_stmt->close();
                    }
                }
                ?>

                <div id="noBlockedUsers" class="text-center p-5 rounded border border-secondary border-opacity-25 shadow-sm" style="background-color: var(--bs-secondary-bg); <?php echo empty($blocked_users) ? '' : 'display: none !important;'; ?>">
                    <i class="bi bi-shield-check fs-1 text-muted opacity-50 mb-3 d-block"></i>
                    <h6 class="text-muted mb-1 fw-bold">No Blocked Users</h6>
                    <small class="text-muted" style="font-size: 0.85rem;">When you restrict an officer's access, they will appear here.</small>
                </div>

                <?php foreach ($blocked_users as $blocked): ?>
                    <div class="blocked-user-card d-flex justify-content-between align-items-center p-3 rounded border border-secondary border-opacity-25 shadow-sm"
                        style="background-color: var(--bs-secondary-bg); cursor: pointer; transition: all 0.2s ease;"
                        onmouseover="this.style.transform='translateY(-2px)'; this.style.backgroundColor='var(--bs-tertiary-bg)';"
                        onmouseout="this.style.transform='translateY(0)'; this.style.backgroundColor='var(--bs-secondary-bg)';"
                        onclick="openChatFromSettings('<?php echo htmlspecialchars($blocked['username']); ?>')">

                        <div>
                            <h6 class="mb-1 fw-bold text-danger"><i class="bi bi-person-x-fill me-2"></i><?php echo htmlspecialchars($blocked['username']); ?></h6>
                            <small class="text-muted" style="font-size: 0.8rem;">Blocked on <?php echo htmlspecialchars($blocked['date']); ?></small>
                        </div>

                        <i class="bi bi-chevron-right text-muted opacity-50 fs-5"></i>
                    </div>
                <?php endforeach; ?>

            </div>
        </div>

        <div id="backupPanel" class="settings-panel card border-0 shadow-sm p-4" style="border-radius: 12px; background-color: var(--bs-body-bg); border: 1px solid var(--bs-border-color) !important; display: none;">

            <div class="d-flex align-items-center mb-2">
                <i class="bi bi-database-fill fs-4 me-3" style="color: #8b5cf6;"></i>
                <h4 class="fw-bold mb-0 text-body">Chat Backups</h4>
            </div>
            <p class="text-muted mb-4 pb-2" style="font-size: 0.95rem;">Create and manage secure backups of your chat conversations. Export your message history, media files, and conversation data for archival or migration purposes.</p>

            <div class="d-flex gap-3 mb-4 flex-wrap">
                <button class="btn btn-gradient-purple flex-grow-1 fw-bold py-3 shadow-sm" style="border-radius: 8px; border: none; font-size: 0.95rem;" onclick="createBackup()">
                    <i class="bi bi-download me-2"></i> Create New Backup
                </button>

                <button class="btn btn-gradient-indigo flex-grow-1 fw-bold py-3 shadow-sm" style="border-radius: 8px; border: none; font-size: 0.95rem;" onclick="exportAllChats()">
                    <i class="bi bi-archive me-2"></i> Export All Chats
                </button>

                <button class="btn btn-gradient-emerald flex-grow-1 fw-bold py-3 shadow-sm" style="border-radius: 8px; border: none; font-size: 0.95rem;" onclick="document.getElementById('importBackupInput').click()">
                    <i class="bi bi-cloud-upload me-2"></i> Restore
                </button>
            </div>

            <input type="file" id="importBackupInput" class="d-none" accept=".json" onchange="importBackup(this)">

            <div class="p-4 rounded shadow-sm mb-5" style="border: 1px solid #0dcaf0; background-color: rgba(13, 202, 240, 0.05);">
                <h6 class="fw-bold text-info mb-2"><i class="bi bi-shield-key-fill me-2"></i>Device Security Key</h6>
                <p class="text-muted small mb-3">Download your unique cryptographic identity key. <strong>You will need this to unlock your encrypted messages if you log in from an Incognito tab or a new computer.</strong></p>

                <button class="btn btn-outline-info w-100 fw-bold shadow-sm" style="border-radius: 8px; padding: 10px;" onclick="exportSecurityKeys()">
                    <i class="bi bi-key-fill me-2"></i> Download Security Key (.json)
                </button>
            </div>
            <h6 class="fw-bold text-body mb-3 d-flex align-items-center"><i class="bi bi-clock-history me-2"></i> Backup History</h6>

            <div id="backupHistoryList" class="d-flex flex-column gap-2 mb-4">
                <div class="text-center p-4 rounded shadow-sm" style="background-color: var(--bs-secondary-bg); border: 1px dashed var(--bs-border-color);">
                    <i class="bi bi-inbox text-muted fs-3 mb-2 d-block"></i>
                    <span class="text-muted" style="font-size: 0.9rem;">No backups found. Create your first backup above!</span>
                </div>
            </div>

            <div class="p-4 rounded shadow-sm" style="background-color: var(--bs-tertiary-bg); border: 1px solid var(--bs-border-color);">
                <h6 class="fw-bold text-body mb-2" style="font-size: 0.9rem;">Backup Information</h6>
                <p class="text-muted mb-0" style="font-size: 0.85rem; line-height: 1.6;">Backups include all message text, timestamps, sender information, and associated media files. Data is exported in JSON format with optional media archive. Backups are encrypted and can be restored to any compatible chat system.</p>
            </div>

        </div>

        <div id="panicPanel" class="settings-panel card border-0 shadow-lg border-danger p-5" style="border-radius: 12px; background-color: rgba(220, 53, 69, 0.02);">
            <div class="text-center mb-4">
                <i class="bi bi-shield-slash-fill text-danger" style="font-size: 4rem;"></i>
                <h4 class="fw-bold text-danger mt-2">EMERGENCY PROTOCOLS</h4>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-5 p-4 rounded shadow-sm" style="border: 1px solid rgba(220, 53, 69, 0.3); background-color: rgba(220, 53, 69, 0.05);">
                <div class="me-3">
                    <h6 class="mb-1 fw-bold text-danger fs-5"><i class="bi bi-fingerprint me-2"></i>Dead Man's Switch</h6>
                    <small class="text-danger opacity-75">Automatically wipe device keys and lock this account if an unauthorized user fails to login 5 times.</small>
                </div>
                <div class="form-check form-switch fs-3 mb-0">
                    <input class="form-check-input bg-danger border-danger" type="checkbox" role="switch" id="panic_mode_toggle"
                        <?php echo (!empty($my_info['panic_mode']) && $my_info['panic_mode'] == 1) ? 'checked' : ''; ?>
                        onchange="togglePanicMode(this.checked)" style="cursor: pointer;">
                </div>
            </div>

            <div class="text-center">
                <p class="text-danger opacity-75 mb-4 px-md-5">WARNING: The manual override will immediately drop all AES session keys, clear the DOM of all messages, wipe local storage, and forcefully terminate your server connection. <br><strong>This action cannot be undone.</strong></p>
                <button class="btn btn-danger fw-bold py-3 px-5 shadow" style="font-size: 1.2rem; letter-spacing: 3px;" onclick="triggerPanicWipe()">
                    INITIATE INSTANT WIPE
                </button>
            </div>
        </div>

    </div>
</div>

<script>
    // --- UI NAVIGATION (Grid vs Panels) ---
    function openSettingPanel(panelId) {
        // Hide Grid, Show Panels Area
        document.getElementById('settingsGridArea').style.display = 'none';
        document.getElementById('settingsPanelsArea').style.display = 'block';

        // Hide all specific panels
        document.querySelectorAll('.settings-panel').forEach(panel => {
            panel.style.display = 'none';
        });

        // Show the specific panel requested
        document.getElementById(panelId).style.display = 'block';

        // Trigger specific logic when certain panels open
        if (panelId === 'dataPanel') {
            calculateCacheSize();
        } else if (panelId === 'backupPanel') {
            loadBackupHistory(); // <-- NEW: Load the history exactly when the panel opens!
        }

        // Update Header Subtitle
        const titles = {
            'appearancePanel': 'Appearance Configuration',
            'securityPanel': 'Authentication Settings',
            'privacyPanel': 'Privacy Controls',
            'dataPanel': 'Storage & Data',
            'blockedPanel': 'Access Restrictions',
            'backupPanel': 'Data Archival & Backup', // <-- FIXED THIS LINE
            'panicPanel': 'Emergency Protocols'
        };
        document.getElementById('settingsSubtitle').innerText = titles[panelId] || 'Dashboard Overview';
    }

    function closeSettingPanel() {
        document.getElementById('settingsPanelsArea').style.display = 'none';
        document.getElementById('settingsGridArea').style.display = 'block';
        document.getElementById('settingsSubtitle').innerText = 'Dashboard Overview';

        // --- Reset Appearance Unsaved Changes ---

        // 1. Reset the preview box back to the actual saved database style
        const previewArea = document.getElementById('bgPreviewArea');
        if (previewArea) {
            previewArea.style.cssText = `height: 400px; <?php echo $previewStyle; ?> text-shadow: 0 1px 3px rgba(0,0,0,0.8); transition: all 0.3s ease; border: 1px solid rgba(255,255,255,0.1);`;
        }

        // 2. Reset the filename text and clear the file input
        document.getElementById('chatBackgroundFileName').textContent = '<?php echo addslashes($savedFileName); ?>';
        document.getElementById('chatBackgroundInput').value = '';

        // 3. Reset the tracking variables so they don't accidentally save old clicks
        selectedBgType = null;
        selectedBgValue = null;

        // 4. Remove all blue borders from colors, then re-apply it ONLY to the saved color
        document.querySelectorAll('.bg-preset-card').forEach(el => el.classList.remove('border', 'border-info', 'border-2'));
        const savedColor = '<?php echo $savedBgColor; ?>';
        if (savedColor) {
            const activePreset = document.querySelector(`.bg-preset-card[onclick*="${savedColor}"]`);
            if (activePreset) activePreset.classList.add('border', 'border-info', 'border-2');
        }
    }

    // --- 1. PRIVACY AUTO-SAVE ---
    function autoSavePrivacy(settingName, isChecked) {
        let formData = new FormData();
        formData.append('action', 'toggle_privacy');
        formData.append('setting_name', settingName);
        formData.append('setting_value', isChecked ? 1 : 0);
        fetch('update_settings.php', {
            method: 'POST',
            body: formData
        }).catch(e => console.error(e));
    }

    // --- 2. APPEARANCE (BACKGROUNDS) ---
    let selectedBgType = null;
    let selectedBgValue = null;

    function selectPreset(element, hexColor) {
        // Clear active borders from presets
        document.querySelectorAll('.bg-preset-card').forEach(el => el.classList.remove('border', 'border-info', 'border-2'));
        element.classList.add('border', 'border-info', 'border-2'); // Add active border

        document.getElementById('chatBackgroundInput').value = '';
        document.getElementById('chatBackgroundFileName').textContent = 'No file chosen';
        selectedBgType = 'color';
        selectedBgValue = hexColor;

        // Instantly update the Preview Box color
        const previewArea = document.getElementById('bgPreviewArea');
        if (previewArea) {
            previewArea.style.backgroundImage = 'none';
            previewArea.style.backgroundColor = hexColor;
        }

        // Visual feedback (pop effect)
        element.style.transform = 'scale(1.2)';
        setTimeout(() => {
            element.style.transform = 'scale(1)';
        }, 200);
    }

    function previewCustomImage(inputElement) {
        if (typeof updateFileName === 'function') {
            updateFileName(inputElement, 'chatBackgroundFileName');
        }
        if (inputElement.files.length > 0) {
            document.querySelectorAll('.bg-preset-card').forEach(el => el.classList.remove('border', 'border-info', 'border-2'));
            selectedBgType = 'image';
            selectedBgValue = inputElement.files[0];

            // Instantly update the Preview Box with the uploaded image
            const file = inputElement.files[0];
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewArea = document.getElementById('bgPreviewArea');
                if (previewArea) {
                    previewArea.style.backgroundColor = 'transparent';
                    previewArea.style.backgroundImage = `url('${e.target.result}')`;
                    previewArea.style.backgroundSize = 'cover';
                    previewArea.style.backgroundPosition = 'center';
                    previewArea.style.backgroundBlendMode = 'overlay';
                    previewArea.style.backgroundColor = 'rgba(0,0,0,0.6)'; // Keeps text readable over bright images
                }
            }
            reader.readAsDataURL(file);
        }
    }

    function saveBackground() {
        if (!selectedBgType) {
            alert("Select a color or upload an image first.");
            return;
        }
        let formData = new FormData();
        if (selectedBgType === 'color') {
            formData.append('action', 'update_background_color');
            formData.append('color', selectedBgValue);
        } else if (selectedBgType === 'image') {
            formData.append('action', 'update_background');
            formData.append('chat_background', selectedBgValue);
        }

        fetch('update_settings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const rightArea = document.getElementById('mainRightArea');
                    if (rightArea) {
                        if (selectedBgType === 'color') {
                            rightArea.style.backgroundImage = 'none';
                            rightArea.style.backgroundColor = data.filepath;
                        } else {
                            rightArea.style.backgroundImage = `url('${data.filepath}')`;
                            rightArea.style.backgroundSize = 'cover';
                            rightArea.style.backgroundPosition = 'center';
                            rightArea.style.backgroundBlendMode = 'overlay';
                            rightArea.style.backgroundColor = 'rgba(0,0,0,0.6)';
                        }
                    }
                    alert('Background applied!');
                }
            });
    }

    // --- 3. PASSWORD VISIBILITY TOGGLE ---
    function togglePasswordVisibility(inputId, iconElement) {
        const inputField = document.getElementById(inputId);

        if (inputField.type === "password") {
            inputField.type = "text";
            iconElement.classList.remove("bi-eye");
            iconElement.classList.add("bi-eye-slash");
        } else {
            inputField.type = "password";
            iconElement.classList.remove("bi-eye-slash");
            iconElement.classList.add("bi-eye");
        }
    }

    // --- PASSWORD SUBMISSION VIA AJAX ---
    function submitPasswordChange(event) {
        event.preventDefault(); // Stop page refresh

        const form = document.getElementById('passwordUpdateForm');
        const formData = new FormData(form);
        formData.append('action', 'change_password');

        if (formData.get('new_password') !== formData.get('confirm_password')) {
            showPasswordBadge('error', 'New passwords do not match.');
            return;
        }

        fetch('update_settings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showPasswordBadge('success', data.message);
                    form.reset(); // Clear the form on success
                } else {
                    showPasswordBadge('error', data.message);
                }
            })
            .catch(error => {
                showPasswordBadge('error', 'Connection error. Please try again.');
            });
    }

    function showPasswordBadge(type, message) {
        const container = document.getElementById('passwordBadgeContainer');
        if (type === 'success') {
            container.innerHTML = `<div class="alert alert-success py-2 small fw-bold mb-3 shadow-sm border-0" style="background-color: rgba(25, 135, 84, 0.1); color: #198754;"><i class="bi bi-check-circle-fill me-2"></i>${message}</div>`;
        } else {
            container.innerHTML = `<div class="alert alert-danger py-2 small fw-bold mb-3 shadow-sm border-0" style="background-color: rgba(220, 53, 69, 0.1); color: #dc3545;"><i class="bi bi-exclamation-circle-fill me-2"></i>${message}</div>`;
        }
        setTimeout(() => {
            container.innerHTML = '';
        }, 4000);
    }

    // --- DATA MANAGEMENT FUNCTIONS ---

    function clearLocalCache() {
        if (confirm("Are you sure you want to clear your temporary browser cache? Your secure encryption keys will be kept safe.")) {

            // 1. Wipe session storage completely (this is just temporary tab data anyway)
            sessionStorage.clear();

            // 2. Selectively wipe Local Storage (The Smart Filter)
            const keysToDelete = [];

            for (let i = 0; i < localStorage.length; i++) {
                const keyName = localStorage.key(i);

                // If the item name contains 'key', 'aes', or 'theme', we protect it!
                const isProtected = keyName.includes('key') ||
                    keyName.includes('aes') ||
                    keyName.includes('sentinel_theme');

                // If it's NOT protected, add it to our hit list
                if (!isProtected) {
                    keysToDelete.push(keyName);
                }
            }

            // 3. Delete only the unprotected items from the hit list
            keysToDelete.forEach(k => localStorage.removeItem(k));

            // 4. Instantly recalculate the exact MB/KB size of what is left over
            if (typeof calculateCacheSize === "function") {
                calculateCacheSize();
            }

            alert("Temporary cache successfully cleared. Your encryption keys remain secure.");
        }
    }

    function clearChatHistory() {
        if (confirm("WARNING: This will permanently delete all your saved messages and chat history from this device. Proceed?")) {

            // Call the Secure Wipe endpoint
            fetch('soft_wipe.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        // 1. Wipe the active chat screen
                        const chatBox = document.getElementById('chatMessages');
                        if (chatBox) {
                            chatBox.innerHTML = `
                            <div class="text-center mt-5" style="position: relative; z-index: 10;">
                                <div class="d-inline-flex align-items-center shadow-sm" style="background-color: var(--bs-body-bg); color: var(--bs-body-color); padding: 8px 18px; border-radius: 20px; font-size: 0.85rem; border: 1px solid var(--bs-border-color);">
                                    <i class="bi bi-shield-lock-fill text-warning me-2" style="font-size: 1.1rem;"></i> 
                                    Secure connection established. Send a message to start.
                                </div>
                            </div>
                        `;
                        }

                        // 2. Wipe the Saved Messages dashboard visually
                        const savedBox = document.getElementById('savedMessagesList');
                        if (savedBox) {
                            savedBox.innerHTML = '<div class="p-4 text-center text-white-50"><i class="bi bi-bookmark-x fs-1 mb-2 d-block"></i>No saved messages</div>';
                        }

                        // 3. Reset the Data Management Counters
                        const msgDisplay = document.getElementById('msgCountDisplay');
                        if (msgDisplay) msgDisplay.innerText = "0";

                        const convDisplay = document.getElementById('convCountDisplay');
                        if (convDisplay) convDisplay.innerText = "0";

                        alert("Chat history and saved messages successfully wiped.");
                    } else {
                        alert("Error clearing history from server.");
                    }
                })
                .catch(err => {
                    console.error("Wipe failed:", err);
                    alert("Network error while attempting to clear history.");
                });
        }
    }

    // NEW: Fetches live counts for the Data Management dashboard
    function updateLiveStats() {
        fetch('get_chat_stats.php')
            .then(res => res.json())
            .then(data => {
                if (data.error) return;

                const msgDisplay = document.getElementById('msgCountDisplay');
                const convDisplay = document.getElementById('convCountDisplay');

                if (msgDisplay && data.messages !== undefined) {
                    msgDisplay.innerText = data.messages;
                }
                if (convDisplay && data.conversations !== undefined) {
                    convDisplay.innerText = data.conversations;
                }
            })
            .catch(err => console.error("Stats sync failed:", err));
    }

    function calculateCacheSize() {
        let totalBytes = 0;

        // Calculate Local Storage
        for (let x in localStorage) {
            if (!localStorage.hasOwnProperty(x)) continue;
            totalBytes += ((localStorage[x].length + x.length) * 2);
        }

        // Calculate Session Storage
        for (let x in sessionStorage) {
            if (!sessionStorage.hasOwnProperty(x)) continue;
            totalBytes += ((sessionStorage[x].length + x.length) * 2);
        }

        let sizeInMB = (totalBytes / (1024 * 1024)).toFixed(2);

        // If it's incredibly small, show it in KB instead!
        let displayText = "";
        if (totalBytes === 0) {
            displayText = "0.00 MB";
        } else if (sizeInMB <= 0.00) {
            let sizeInKB = (totalBytes / 1024).toFixed(2);
            displayText = sizeInKB + " KB";
        } else {
            displayText = sizeInMB + " MB";
        }

        const display = document.getElementById('cacheSizeDisplay');
        if (display) display.innerText = displayText;
    }

    // --- 4. UNBLOCK USER ---
    function unblockUser(btnElement, username) {
        if (confirm(`Are you sure you want to unblock ${username}?`)) {
            const userCard = btnElement.closest('.d-flex');
            userCard.style.transition = 'all 0.3s ease';
            userCard.style.opacity = '0';
            userCard.style.transform = 'translateX(20px)';

            setTimeout(() => {
                userCard.remove();

                const listContainer = document.getElementById('blockedUsersList');
                if (listContainer.children.length <= 1) {
                    document.getElementById('noBlockedUsers').classList.remove('d-none');
                }
            }, 300);
        }
    }

    // --- 5. CHAT BACKUP FUNCTIONS & HISTORY SYSTEM ---

    // NEW: Pagination State Variables
    let currentBackupPage = 1;
    const BACKUPS_PER_PAGE = 5;

    // 1. Generate current MYT time
    function getMalaysiaTime() {
        const now = new Date();
        const optionsDate = {
            timeZone: 'Asia/Kuala_Lumpur',
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        };
        const optionsTime = {
            timeZone: 'Asia/Kuala_Lumpur',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        };

        const parts = new Intl.DateTimeFormat('en-GB', optionsDate).formatToParts(now);
        const dateStr = `${parts.find(p => p.type === 'year').value}-${parts.find(p => p.type === 'month').value}-${parts.find(p => p.type === 'day').value}`;
        const timeStr = new Intl.DateTimeFormat('en-US', optionsTime).format(now);

        return {
            dateStr,
            timeStr,
            timestampId: now.getTime()
        };
    }

    // 2. Format database timestamp into MYT
    function formatToMYT(dateInput) {
        const date = new Date(dateInput);
        const options = {
            timeZone: 'Asia/Kuala_Lumpur',
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        };
        return date.toLocaleString('en-US', options);
    }

    // 3. Load Persistent History with PAGINATION!
    function loadBackupHistory() {
        const historyList = document.getElementById('backupHistoryList');
        if (!historyList) return;

        let backups = JSON.parse(localStorage.getItem('sentinel_backup_history')) || [];

        if (backups.length === 0) {
            historyList.innerHTML = `
                        <div class="text-center p-4 rounded shadow-sm" style="background-color: var(--bs-secondary-bg); border: 1px dashed var(--bs-border-color);">
                            <i class="bi bi-inbox text-muted fs-3 mb-2 d-block"></i>
                            <span class="text-muted" style="font-size: 0.9rem;">No backups found. Create your first backup above!</span>
                        </div>
                    `;
            return;
        }

        // Sort by newest first
        backups.sort((a, b) => b.timestampId - a.timestampId);

        // ==========================================
        // PAGINATION MATH & SLICING
        // ==========================================
        const totalPages = Math.ceil(backups.length / BACKUPS_PER_PAGE);

        // Safety catch: If you delete the last item on page 2, push you back to page 1
        if (currentBackupPage > totalPages) currentBackupPage = totalPages;
        if (currentBackupPage < 1) currentBackupPage = 1;

        // Slice the array to only get the 5 items for the current page
        const startIndex = (currentBackupPage - 1) * BACKUPS_PER_PAGE;
        const paginatedBackups = backups.slice(startIndex, startIndex + BACKUPS_PER_PAGE);

        let html = '';

        // Draw only the 5 items
        paginatedBackups.forEach(backup => {
            const iconClass = backup.isExport ? 'bi-file-earmark-text text-primary' : 'bi-check-circle text-success';
            const bgClass = backup.isExport ? 'rgba(13, 110, 253, 0.1)' : 'rgba(16, 185, 129, 0.1)';
            const borderClass = backup.isExport ? '#0d6efd' : '#10b981';

            // NEW: Explicit JSON and TXT Badges
            const badgeText = backup.isExport ? 'TXT' : 'JSON';
            const badgeColorClass = backup.isExport ? 'bg-primary text-primary' : 'bg-success text-success';

            html += `
                        <div class="d-flex align-items-center p-3 rounded shadow-sm mb-2" style="background-color: var(--bs-secondary-bg); border: 1px solid var(--bs-border-color); border-left: 4px solid ${borderClass}; transition: all 0.2s ease;">
                            <div class="rounded-circle d-flex justify-content-center align-items-center me-3 flex-shrink-0" style="width: 40px; height: 40px; background-color: ${bgClass};">
                                <i class="bi ${iconClass} fs-6"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold text-body" style="font-size: 0.95rem;">${backup.dateStr} <span class="text-muted ms-3 fw-normal" style="font-size: 0.85rem;">${backup.timeStr}</span></div>
                                <div class="text-muted mt-1" style="font-size: 0.85rem;">${backup.msgCount} messages <span class="mx-2">•</span> ${backup.sizeMB} MB</div>
                            </div>
                            <div class="ms-auto d-flex align-items-center gap-3">
                                <span class="badge ${badgeColorClass} bg-opacity-10 rounded-pill px-3 py-1">${badgeText}</span>
                                
                                <button class="btn btn-sm btn-link text-danger p-0 shadow-none hover-scale" onclick="deleteBackupRecord(${backup.timestampId})" title="Delete Record">
                                    <i class="bi bi-trash3 fs-5"></i>
                                </button>
                            </div>
                        </div>
                    `;
        });

        // Draw Pagination Controls if there is more than 1 page
        if (totalPages > 1) {
            html += `
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top border-secondary border-opacity-25 px-1">
                            <button class="btn btn-sm text-white px-3 shadow-sm" style="border-radius: 8px; background: linear-gradient(135deg, #0dcaf0, #0d6efd); border: none;" onclick="changeBackupPage(-1)" ${currentBackupPage === 1 ? 'disabled' : ''}>
                                <i class="bi bi-chevron-left me-1"></i> Prev
                            </button>
                            <span class="text-muted fw-bold" style="font-size: 0.85rem;">Page ${currentBackupPage} of ${totalPages}</span>
                            <button class="btn btn-sm text-white px-3 shadow-sm" style="border-radius: 8px; background: linear-gradient(135deg, #0dcaf0, #0d6efd); border: none;" onclick="changeBackupPage(1)" ${currentBackupPage === totalPages ? 'disabled' : ''}>
                                Next <i class="bi bi-chevron-right ms-1"></i>
                            </button>
                        </div>
                    `;
        }

        historyList.innerHTML = html;
    }

    // NEW: Change Page Function
    function changeBackupPage(direction) {
        currentBackupPage += direction;
        loadBackupHistory();
    }

    // 4. Delete a specific backup record
    function deleteBackupRecord(id) {
        if (confirm("Remove this record from your backup history? (The downloaded file will remain on your computer).")) {
            let backups = JSON.parse(localStorage.getItem('sentinel_backup_history')) || [];
            backups = backups.filter(b => b.timestampId !== id);
            localStorage.setItem('sentinel_backup_history', JSON.stringify(backups));
            loadBackupHistory(); // Refreshes and auto-fixes the page number if needed!
        }
    }

    // BUTTON 1: CREATE NEW BACKUP (App Data)
    async function createBackup() {
        const btn = event.currentTarget;
        const originalHtml = btn.innerHTML;

        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Encrypting...';
        btn.disabled = true;

        try {
            const res = await fetch('export_backup.php');
            const data = await res.json();

            if (data.error) {
                alert("Backup Failed: " + data.error);
            } else {
                data.metadata.generated_at = formatToMYT(new Date());

                const jsonString = JSON.stringify(data, null, 4);
                const blob = new Blob([jsonString], {
                    type: "application/json"
                });
                const downloadUrl = window.URL.createObjectURL(blob);

                const myt = getMalaysiaTime();

                const downloadLink = document.createElement("a");
                downloadLink.href = downloadUrl;
                downloadLink.download = `Sentinel_Encrypted_Backup_${myt.dateStr}.json`;

                document.body.appendChild(downloadLink);
                downloadLink.click();
                document.body.removeChild(downloadLink);
                window.URL.revokeObjectURL(downloadUrl);

                let rawMB = blob.size / (1024 * 1024);
                let displayMB = rawMB < 0.01 ? "0.01" : rawMB.toFixed(2);

                let backups = JSON.parse(localStorage.getItem('sentinel_backup_history')) || [];
                backups.push({
                    timestampId: myt.timestampId,
                    dateStr: myt.dateStr,
                    timeStr: myt.timeStr,
                    msgCount: data.messages.length,
                    sizeMB: displayMB,
                    isExport: false
                });
                localStorage.setItem('sentinel_backup_history', JSON.stringify(backups));

                currentBackupPage = 1; // Always jump back to Page 1 to show the new file!
                loadBackupHistory();
            }
        } catch (error) {
            console.error("Backup failed:", error);
            alert("A network error occurred.");
        }

        btn.innerHTML = originalHtml;
        btn.disabled = false;
    }

    // BUTTON 2: EXPORT ALL CHATS (Readable Text)
    async function exportAllChats() {
        const btn = event.currentTarget;
        const originalHtml = btn.innerHTML;

        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Compiling Text...';
        btn.disabled = true;

        try {
            const res = await fetch('export_backup.php');
            const data = await res.json();

            if (data.error) {
                alert("Export Failed: " + data.error);
                return;
            }

            let textContent = "=================================================\n";
            textContent += " SENTINEL CHAT EXPORT TRANSCRIPT\n";
            textContent += ` Generated: ${formatToMYT(new Date())}\n`;
            textContent += ` Account: ${data.metadata.user}\n`;
            textContent += "=================================================\n\n";

            for (const msg of data.messages) {
                const cleanTime = formatToMYT(msg.timestamp);

                let messageBody = "[Encrypted Content]";
                if (msg.attachment) {
                    messageBody += `\n   📎 Attachment: ${msg.attachment.name}`;
                }
                textContent += `[${cleanTime}] ${msg.sender}: ${messageBody}\n`;
            }

            const blob = new Blob([textContent], {
                type: "text/plain"
            });
            const downloadUrl = window.URL.createObjectURL(blob);

            const myt = getMalaysiaTime();

            const downloadLink = document.createElement("a");
            downloadLink.href = downloadUrl;
            downloadLink.download = `Sentinel_Chat_Transcript_${myt.dateStr}.txt`;

            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
            window.URL.revokeObjectURL(downloadUrl);

            let rawMB = blob.size / (1024 * 1024);
            let displayMB = rawMB < 0.01 ? "0.01" : rawMB.toFixed(2);

            let backups = JSON.parse(localStorage.getItem('sentinel_backup_history')) || [];
            backups.push({
                timestampId: myt.timestampId,
                dateStr: myt.dateStr,
                timeStr: myt.timeStr,
                msgCount: data.messages.length,
                sizeMB: displayMB,
                isExport: true
            });
            localStorage.setItem('sentinel_backup_history', JSON.stringify(backups));

            currentBackupPage = 1; // Always jump back to Page 1 to show the new file!
            loadBackupHistory();

        } catch (error) {
            console.error("Export failed:", error);
            alert("A network error occurred.");
        }

        btn.innerHTML = originalHtml;
        btn.disabled = false;
    }

    // BUTTON 3: IMPORT & RESTORE BACKUP (Upload JSON)
    async function importBackup(inputElement) {
        if (!inputElement.files || inputElement.files.length === 0) return;

        const file = inputElement.files[0];

        // Safety check to ensure they don't upload an image or text file
        if (!file.name.endsWith('.json')) {
            alert("Security Error: Please select a valid Sentinel .json encrypted backup file.");
            inputElement.value = ''; // Reset the input
            return;
        }

        if (!confirm(`Are you sure you want to restore messages from:\n${file.name}?\n\n(Existing messages will be ignored, but any missing/deleted messages will be restored safely).`)) {
            inputElement.value = '';
            return;
        }

        const formData = new FormData();
        formData.append('backup_file', file);

        // Show a loading status to the user
        alert("Uploading and decrypting backup... Please click OK and wait.");

        try {
            const res = await fetch('import_backup.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.error) {
                alert("Restore Failed: " + data.error);
            } else {
                alert(`✅ Restore Complete!\n\nSuccessfully scanned the archive and restored ${data.restored_count} missing messages into your account.`);
                // Reload the page so the newly restored messages appear instantly in the chat!
                location.reload();
            }
        } catch (error) {
            console.error("Import error:", error);
            alert("A network error occurred while uploading the backup.");
        }

        inputElement.value = ''; // Clear the input so they can use it again later
    }

    // --- 6. BLOCKED USERS ---
    function openBlockedUsersModal() {
        alert("Blocked Users modal will open here.");
    }

    // --- 7. PANIC BUTTON & DEAD MAN'S SWITCH LOGIC ---

    // NEW: Toggles the 5-strike trap on and off securely via AJAX
    function togglePanicMode(isChecked) {
        let formData = new FormData();
        formData.append('action', 'toggle_panic_mode');
        formData.append('setting_value', isChecked ? 1 : 0);

        fetch('update_settings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    console.log("Dead Man's Switch: " + (isChecked ? "ARMED" : "DISARMED"));
                } else {
                    console.error("Failed to update panic mode.");
                }
            })
            .catch(e => console.error("Error connecting to server to toggle panic mode: ", e));
    }

    async function triggerPanicWipe() {
        if (confirm("CRITICAL: This will permanently hide your history and destroy keys. Proceed?")) {

            // 1. Tell the server to "Hide" these messages for me forever
            await fetch('soft_wipe.php');

            // 2. Nuke the keys so I can't decrypt even if I tried
            localStorage.clear();

            // 3. Logout
            window.location.replace('logout.php');
        }
    }

    function exportSecurityKeys() {
        // 1. Grab the Private Key (The Unlocker)
        const privKey = localStorage.getItem('sentinel_private_key_' + MY_USER_ID);

        // 2. Grab the Public Key (The Padlock) 
        // IMPORTANT: Grab it from the actual crypto.js memory, not just the PHP variable
        const pubKey = MY_PUBLIC_KEY;

        // Grab the username directly from the PHP session!
        const myUsername = "<?php echo htmlspecialchars($_SESSION['username']); ?>";

        if (!privKey) {
            alert("Security Error: Private Key not found in this browser.");
            return;
        }

        const keyData = {
            private_key: privKey,
            public_key: pubKey,
            user_id: MY_USER_ID,
            exported_at: new Date().toISOString()
        };

        const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(keyData));
        const downloadAnchorNode = document.createElement('a');
        downloadAnchorNode.setAttribute("href", dataStr);

        // Use the new username variable for the filename!
        downloadAnchorNode.setAttribute("download", `Sentinel_Key_${myUsername}.json`);

        document.body.appendChild(downloadAnchorNode);
        downloadAnchorNode.click();
        downloadAnchorNode.remove();
    }
</script>