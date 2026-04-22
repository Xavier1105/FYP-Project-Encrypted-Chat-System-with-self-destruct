<style>
    /* Custom Modal Buttons (Profile Edit) */
    .btn-modal-save {
        background: linear-gradient(135deg, #0dcaf0, #0d6efd);
        color: white;
        border: none;
        border-radius: 8px;
        transition: all 0.2s ease-in-out;
    }

    .btn-modal-save:hover {
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(13, 202, 240, 0.4) !important;
        background: linear-gradient(135deg, #0cbee3, #0b5ed7);
    }

    .btn-modal-save:active {
        transform: translateY(0);
        box-shadow: 0 2px 5px rgba(13, 202, 240, 0.4) !important;
    }

    .btn-modal-cancel {
        background-color: #e2e8f0;
        color: #475569;
        border: none;
        border-radius: 8px;
        transition: all 0.2s ease-in-out;
    }

    .btn-modal-cancel:hover {
        background-color: #cbd5e1;
        color: #475569;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(71, 85, 105, 0.2) !important;
    }

    .btn-modal-cancel:active {
        transform: translateY(0);
        box-shadow: 0 2px 5px rgba(71, 85, 105, 0.2) !important;
    }

    /* The Edit Profile Button */
    .btn-edit-profile {
        background-color: rgba(13, 202, 240, 0.1);
        color: #0dcaf0;
        border: 1px solid rgba(13, 202, 240, 0.3);
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .btn-edit-profile:hover {
        background-color: #0dcaf0;
        color: white;
        transform: translateY(-2px);
        /* Floats the button up slightly */
        box-shadow: 0 4px 10px rgba(13, 202, 240, 0.3);
        /* Adds a soft cyan glow */
    }
</style>

<div id="profileInterface" style="display: none; flex-direction: column; height: 100%; width: 100%; overflow-y: auto; background-color: transparent;">
    <div class="border-bottom shadow-sm p-4 w-100 flex-shrink-0" style="background-color: var(--bs-body-bg); backdrop-filter: blur(10px); position: sticky; top: 0; z-index: 10;">
        <div class="container-fluid p-0">

            <div class="d-flex align-items-center mb-2">

                <div class="d-flex align-items-center justify-content-center shadow-sm rounded-3 me-3"
                    style="width: 48px; height: 48px; background-color: rgba(13, 110, 253, 0.1); border: 1px solid rgba(13, 110, 253, 0.25);">
                    <i class="bi bi-person-badge fs-4" style="color: #0d6efd;"></i>
                </div>

                <h3 class="fw-bold mb-0 text-body" style="letter-spacing: -0.5px;">My Profile</h3>
            </div>

            <div class="text-body-secondary small mt-1">Account Information & Security</div>

        </div>
    </div>

    <div class="p-4 d-flex flex-column align-items-center">
        <div style="width: 100%; max-width: 650px;">

            <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; background-color: var(--bs-body-bg); border: 1px solid var(--bs-border-color) !important;">
                <div class="card-body p-4 position-relative">

                    <button class="btn btn-sm position-absolute top-0 end-0 m-3 px-3 py-2 btn-edit-profile" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                        <i class="bi bi-pencil-square me-1"></i> Edit
                    </button>

                    <div class="d-flex align-items-center">
                        <div class="me-4">
                            <?php if (!empty($my_info['profile_picture'])): ?>
                                <img src="<?php echo htmlspecialchars($my_info['profile_picture']); ?>" class="rounded-circle shadow-sm object-fit-cover" style="width: 90px; height: 90px; border: 2px solid var(--bs-border-color);">
                            <?php else: ?>
                                <div class="rounded-circle text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 90px; height: 90px; font-size: 2.5rem; background: linear-gradient(135deg, #0dcaf0, #0d6efd);">
                                    <i class="bi bi-person"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div>
                            <h3 class="fw-bold mb-1"><?php echo htmlspecialchars($my_info['username']); ?></h3>
                            <div class="text-muted mb-3" style="font-size: 1.1rem;"><?php echo htmlspecialchars($my_info['position'] ?? 'Not Specified'); ?></div>

                            <div class="d-flex gap-2">
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill">
                                    <i class="bi bi-shield-check me-1"></i> <?php echo strtoupper($my_info['role']); ?>
                                </span>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 rounded-pill">
                                    Officer ID: <?php echo htmlspecialchars(preg_replace('/^(HOS|AD)/i', '', $my_info['officer_id'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm" style="border-radius: 12px; background-color: var(--bs-body-bg); border: 1px solid var(--bs-border-color) !important;">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4">Security & Account Details</h5>

                    <div class="d-flex align-items-start mb-4">
                        <div class="text-info fs-4 me-3 mt-1" style="color: #0ea5e9 !important;"><i class="bi bi-calendar3"></i></div>
                        <div>
                            <div class="text-muted small mb-1">Account Created</div>
                            <div class="fw-semibold fs-5"><?php echo date('d M Y, h:i A', strtotime($my_info['created_at'])); ?></div>
                        </div>
                    </div>

                    <div class="d-flex align-items-start">
                        <div class="text-info fs-4 me-3 mt-1" style="color: #0ea5e9 !important;"><i class="bi bi-key-fill"></i></div>
                        <div class="w-100">
                            <div class="text-muted small mb-1">Public Encryption Key</div>

                            <div class="input-group mt-1">
                                <input type="text" class="form-control bg-secondary bg-opacity-10 text-body border-secondary border-opacity-25" value="<?php echo htmlspecialchars($my_info['public_key']); ?>" readonly id="myPublicKey">
                                <button class="btn btn-outline-secondary border-secondary border-opacity-25" onclick="copyPublicKey()" title="Copy Key">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>

                            <small class="text-muted mt-2 d-block" style="font-size: 0.8rem;">This public key is visible to other officers to encrypt messages sent to you.</small>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="editProfileModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #0dcaf0, #0d6efd); border-bottom: none;">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Profile</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="index.php" enctype="multipart/form-data">
                <div class="modal-body">

                    <div class="mb-4">
                        <label class="form-label fw-bold d-block text-center">Profile Picture</label>

                        <?php
                        // Extract just the file name if they already have a picture saved
                        $savedProfilePic = 'No file chosen';
                        if (!empty($my_info['profile_picture'])) {
                            $savedProfilePic = basename($my_info['profile_picture']);
                        }
                        ?>

                        <div class="input-group shadow-sm">
                            <input type="file" id="profilePictureInput" name="profile_picture" class="d-none" accept="image/png, image/jpeg, image/gif" onchange="updateProfileFileName(this)">

                            <label for="profilePictureInput" class="btn btn-dark border-secondary border-opacity-25 mb-0 px-3" style="cursor: pointer;">
                                <i class="bi bi-image me-2"></i> Choose File
                            </label>

                            <span id="profilePicFileName" class="input-group-text bg-light text-muted border-secondary border-opacity-25 text-truncate flex-grow-1">
                                <?php echo htmlspecialchars($savedProfilePic); ?>
                            </span>
                        </div>
                        <small class="text-muted d-block text-center mt-2">Leave empty to keep your current picture.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Username</label>
                        <input type="text" class="form-control" name="new_username" value="<?php echo htmlspecialchars($my_info['username']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Position / Title</label>
                        <input type="text" class="form-control" name="new_position" value="<?php echo htmlspecialchars($my_info['position'] ?? ''); ?>" placeholder="e.g. Senior Investigator" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted">Officer ID</label>
                        <input type="text" class="form-control bg-secondary bg-opacity-10 text-body border-secondary border-opacity-25" value="<?php echo htmlspecialchars($my_info['officer_id']); ?>" readonly>
                    </div>

                    <input type="hidden" name="update_profile" value="1">
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-modal-cancel fw-bold px-4 py-2 shadow-sm" data-bs-dismiss="modal">Cancel</button>

                    <button type="submit" class="btn btn-modal-save fw-bold px-4 py-2 shadow-sm">
                        <i class="bi bi-floppy me-2"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // --- PROFILE JAVASCRIPT ---
    function openProfile() {
        // Hide Chats, Empty State, AND Settings
        document.getElementById('activeChatInterface').style.display = 'none';
        document.getElementById('emptyChatInterface').style.display = 'none';

        // NEW: Make sure Settings closes if it was open!
        const settingsPage = document.getElementById('settingsInterface');
        if (settingsPage) settingsPage.style.display = 'none';

        // Show Profile
        document.getElementById('profileInterface').style.display = 'flex';

        const allContacts = document.querySelectorAll('.contact-item');
        allContacts.forEach(item => {
            item.classList.remove('active');
        });

        activateMenu('menu-profile', 'My Profile');

        const offcanvasEl = document.getElementById('appMenu');
        if (offcanvasEl) {
            const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
            if (offcanvas) {
                offcanvas.hide();
            }
        }
    }

    // --- FUNCTION TO COPY PUBLIC KEY ---
    function copyPublicKey() {
        const copyText = document.getElementById("myPublicKey");
        copyText.select();
        copyText.setSelectionRange(0, 99999); // For mobile devices
        navigator.clipboard.writeText(copyText.value);

        // Optional: Show a quick visual confirmation
        const btn = event.currentTarget;
        const originalIcon = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check2 text-success"></i>';
        setTimeout(() => {
            btn.innerHTML = originalIcon;
        }, 2000);
    }

    function updateProfileFileName(inputElement) {
        const fileNameSpan = document.getElementById('profilePicFileName');
        if (inputElement.files && inputElement.files.length > 0) {
            fileNameSpan.textContent = inputElement.files[0].name;
            fileNameSpan.classList.remove('text-muted');
            fileNameSpan.classList.add('text-body', 'fw-bold');
        } else {
            fileNameSpan.textContent = 'No file chosen';
            fileNameSpan.classList.add('text-muted');
            fileNameSpan.classList.remove('text-body', 'fw-bold');
        }
    }
</script>