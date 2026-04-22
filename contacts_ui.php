<style>
    /* The Message Button Hover */
    .btn-contact-msg {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        background-color: rgba(13, 202, 240, 0.1);
        color: #0dcaf0;
        border: none;
        transition: all 0.2s ease;
    }

    .btn-contact-msg:hover {
        background-color: rgba(13, 202, 240, 0.25);
        transform: translateY(-2px);
        /* Floats the button up slightly */
        color: #0dcaf0;
    }

    /* The Remove Button Hover */
    .btn-contact-remove {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        background-color: rgba(220, 53, 69, 0.1);
        color: #dc3545;
        border: none;
        transition: all 0.2s ease;
    }

    .btn-contact-remove:hover {
        background-color: rgba(220, 53, 69, 0.25);
        transform: translateY(-2px);
        /* Floats the button up slightly */
        color: #dc3545;
    }

    /* The Add Friend Button Hover */
    .btn-add-friend {
        background-color: #bbf7d0;
        color: #166534;
        border: none;
        border-radius: 8px;
        transition: all 0.2s ease;
    }

    .btn-add-friend:hover {
        background-color: #86efac;
        color: #166534;
        transform: translateY(-2px);
        /* Floats the button up slightly */
        box-shadow: 0 4px 10px rgba(22, 101, 52, 0.15) !important;
        /* Adds a soft green glow shadow */
    }

    /* Custom styling for the Find Friends button */
    .btn-find-friends {
        background-color: #bbf7d0;
        color: #166534;
        transition: all 0.2s ease-in-out;
    }

    .btn-find-friends:hover {
        background-color: #86efac;
        /* Slightly darker green */
        color: #14532d;
        /* Darker text */
        transform: translateY(-2px);
        /* Slight lift */
        box-shadow: 0 4px 10px rgba(22, 101, 52, 0.2);
        /* Soft green shadow */
    }

    .btn-find-friends:active {
        transform: translateY(0);
        box-shadow: 0 2px 4px rgba(22, 101, 52, 0.1);
    }
</style>

<div id="contactsInterface" style="display: none; flex-direction: column; height: 100%; width: 100%; overflow: hidden; background-color: transparent;">

    <div class="border-bottom flex-shrink-0" style="background-color: var(--bs-body-bg);">
        <div class="p-4 pb-0">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <div class="d-flex align-items-center mb-1">

                        <div class="d-flex align-items-center justify-content-center shadow-sm rounded-3 me-3"
                            style="width: 42px; height: 42px; background-color: rgba(99, 102, 241, 0.1); border: 1px solid rgba(99, 102, 241, 0.25);">
                            <i class="bi bi-people fs-4" style="color: #6366f1;"></i>
                        </div>

                        <h3 class="fw-bold mb-0 text-body" style="letter-spacing: -0.5px;">Contacts</h3>
                    </div>

                    <div class="text-muted small mt-1">Manage your network and connections</div>
                </div>

                <button class="btn btn-sm fw-bold px-3 py-2 shadow-sm btn-add-friend"
                    onclick="switchContactTab('discover'); document.getElementById('searchInput').focus();">
                    <i class="bi bi-person-plus-fill me-1"></i> Add Friend
                </button>
            </div>

            <form id="ajaxSearchForm" class="position-relative mb-4" onsubmit="return false;">
                <i class="bi bi-search position-absolute top-50 translate-middle-y text-muted" style="left: 15px;"></i>
                <input type="text" id="searchInput" class="form-control form-control-lg text-body"
                    style="padding-left: 45px; padding-right: 45px; border: 1px solid var(--bs-border-color); border-radius: 10px; font-size: 1rem; background-color: var(--bs-tertiary-bg);"
                    placeholder="Search current tab..." autocomplete="off">
                <i class="bi bi-x-lg position-absolute top-50 translate-middle-y text-muted" id="clearSearchBtn"
                    style="right: 15px; cursor: pointer; display: none; padding: 5px;"
                    onclick="clearSearchInput()" title="Clear search"></i>
            </form>

            <?php
            $friend_count = isset($contact_data) ? count($contact_data) : 0;
            $req_count = (isset($incoming_requests) ? $incoming_requests->num_rows : 0) + (isset($outgoing_requests) ? $outgoing_requests->num_rows : 0);
            ?>
            <div class="d-flex gap-1" style="overflow-x: auto; scrollbar-width: none;">
                <button class="btn btn-sm px-4 py-2 fw-bold contact-tab-btn active" id="btn-tab-friends" onclick="switchContactTab('friends')"
                    style="border-radius: 10px 10px 0 0; border: none; background-color: rgba(13, 202, 240, 0.15); color: #0dcaf0; border-bottom: 2px solid #0dcaf0;">
                    Friends <span class="badge bg-secondary bg-opacity-25 text-info rounded-pill ms-1"><?php echo $friend_count; ?></span>
                </button>
                <button class="btn btn-sm px-4 py-2 fw-bold contact-tab-btn" id="btn-tab-requests" onclick="switchContactTab('requests')"
                    style="border-radius: 10px 10px 0 0; border: none; background-color: transparent; color: var(--bs-secondary-color); border-bottom: 2px solid transparent;">
                    Requests <?php if ($req_count > 0): ?><span class="badge bg-info text-dark rounded-pill ms-1"><?php echo $req_count; ?></span><?php endif; ?>
                </button>
                <button class="btn btn-sm px-4 py-2 fw-bold contact-tab-btn" id="btn-tab-discover" onclick="switchContactTab('discover')"
                    style="border-radius: 10px 10px 0 0; border: none; background-color: transparent; color: var(--bs-secondary-color); border-bottom: 2px solid transparent;">
                    Discover
                </button>
            </div>
        </div>
    </div>

    <div class="p-4" style="flex-grow: 1; overflow-y: auto; background-color: transparent;">
        <div style="width: 100%; max-width: 800px; margin: 0 auto;">

            <div id="tab-friends">
                <?php if (isset($contact_data) && !empty($contact_data)): ?>
                    <div class="d-flex flex-column gap-3" id="friends-list-container">
                        <?php foreach ($contact_data as $row): ?>
                            <?php $js_pic = !empty($row['profile_picture']) ? htmlspecialchars($row['profile_picture']) : ''; ?>

                            <div id="contact-<?php echo htmlspecialchars($row['username']); ?>" class="d-flex justify-content-between align-items-center p-3 rounded shadow-sm filterable-friend" style="background-color: var(--bs-body-bg); border: 1px solid var(--bs-border-color) !important;">
                                <div class="d-flex align-items-center">
                                    <div class="position-relative">
                                        <?php if (!empty($row['profile_picture'])): ?>
                                            <img src="<?php echo htmlspecialchars($row['profile_picture']); ?>" class="rounded-circle me-3 shadow-sm" style="width: 48px; height: 48px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="avatar me-3 shadow-sm" style="width: 48px; height: 48px; background-color: var(--bs-secondary-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                <i class="bi bi-person-fill fs-4 text-secondary"></i>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($row['status'] === 'online'): ?>
                                            <span class="position-absolute bottom-0 end-0 p-1 bg-success border border-2 border-dark rounded-circle" style="margin-right: 12px; margin-bottom: 2px;"></span>
                                        <?php endif; ?>
                                    </div>

                                    <div>
                                        <h6 class="mb-0 fw-bold text-body search-name" style="font-size: 1rem;"><?php echo htmlspecialchars($row['username']); ?></h6>
                                        <small class="text-muted">
                                            <?php echo strtoupper($row['role']) === 'HOS' ? 'HOS' : ucfirst($row['role']); ?>
                                        </small>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm d-flex align-items-center justify-content-center shadow-sm btn-contact-msg"
                                        onclick="selectUser('<?php echo htmlspecialchars($row['username']); ?>', '<?php echo htmlspecialchars($row['header_status']); ?>', '<?php echo htmlspecialchars($row['header_class']); ?>', '<?php echo $js_pic; ?>')" title="Send Message">
                                        <i class="bi bi-chat-left-text-fill"></i>
                                    </button>

                                    <button class="btn btn-sm d-flex align-items-center justify-content-center shadow-sm btn-contact-remove"
                                        onclick="confirmDeleteContact('<?php echo $row['user_id']; ?>', '<?php echo htmlspecialchars($row['username']); ?>')" title="Remove Friend">
                                        <i class="bi bi-person-x-fill"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div id="friends-empty-search" style="display: none; background-color: var(--bs-body-bg); border: 1px solid var(--bs-border-color);" class="text-center py-5 rounded-4 shadow-sm mt-3">
                        <i class="bi bi-people display-1 text-muted opacity-25 mb-3 d-block"></i>
                        <h5 class="fw-bold text-body">No friends found</h5>
                        <p class="text-muted mb-0">Try a different search term.</p>
                    </div>

                <?php else: ?>
                    <div class="text-center text-muted py-5 rounded-4 shadow-sm" style="background-color: var(--bs-body-bg); border: 1px solid var(--bs-border-color);">
                        <i class="bi bi-person-check display-1 opacity-25"></i>
                        <p class="mt-3 fs-5 fw-bold">No active contacts yet.</p>

                        <button class="btn fw-bold px-4 btn-find-friends" onclick="switchContactTab('discover'); document.getElementById('searchInput').focus();">Find Friends</button>

                    </div>
                <?php endif; ?>
            </div>

            <div id="tab-requests" style="display: none;">

                <?php if ($req_count > 0): ?>
                    <div class="d-flex flex-column gap-3 mb-4" id="requests-list-container">

                        <?php if (isset($incoming_requests) && $incoming_requests->num_rows > 0): ?>
                            <?php while ($req = $incoming_requests->fetch_assoc()): ?>
                                <div class="d-flex justify-content-between align-items-center p-3 rounded shadow-sm filterable-request" style="background-color: var(--bs-body-bg); border: 1px solid var(--bs-border-color) !important;">
                                    <div class="d-flex align-items-center">
                                        <div class="position-relative">
                                            <?php if (!empty($req['profile_picture'])): ?>
                                                <img src="<?php echo htmlspecialchars($req['profile_picture']); ?>" class="rounded-circle me-3 shadow-sm" style="width: 48px; height: 48px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="avatar me-3 shadow-sm" style="width: 48px; height: 48px; background-color: var(--bs-secondary-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                    <i class="bi bi-person-fill fs-4 text-secondary"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold text-body search-name" style="font-size: 1rem;"><?php echo htmlspecialchars($req['username']); ?></h6>
                                            <small class="text-muted">Sent you a request</small>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <form method="POST" class="m-0">
                                            <input type="hidden" name="accept_request_id" value="<?php echo $req['request_id']; ?>">
                                            <input type="hidden" name="sender_id" value="<?php echo $req['sender_id']; ?>">
                                            <button class="btn btn-success btn-sm d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px; border-radius: 8px; border: none;">
                                                <i class="bi bi-person-check-fill fs-5"></i>
                                            </button>
                                        </form>
                                        <form method="POST" class="m-0">
                                            <input type="hidden" name="reject_request_id" value="<?php echo $req['request_id']; ?>">
                                            <button class="btn btn-danger btn-sm d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px; border-radius: 8px; border: none;">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php endif; ?>

                        <?php if (isset($outgoing_requests) && $outgoing_requests->num_rows > 0): ?>
                            <?php while ($out = $outgoing_requests->fetch_assoc()): ?>
                                <div class="d-flex justify-content-between align-items-center p-3 rounded shadow-sm filterable-request" data-id="request_<?php echo $out['request_id']; ?>" style="background-color: var(--bs-body-bg); border: 1px solid var(--bs-border-color) !important;">
                                    <div class="d-flex align-items-center">

                                        <div class="position-relative">
                                            <?php if (!empty($out['profile_picture'])): ?>
                                                <img src="<?php echo htmlspecialchars($out['profile_picture']); ?>" class="rounded-circle me-3 shadow-sm" style="width: 48px; height: 48px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="avatar me-3 shadow-sm" style="width: 48px; height: 48px; background-color: var(--bs-secondary-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                    <i class="bi bi-person-fill fs-4 text-secondary"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div>
                                            <h6 class="mb-0 fw-bold text-body search-name" style="font-size: 1rem;"><?php echo htmlspecialchars($out['username']); ?></h6>
                                            <small class="text-muted">Request pending</small>
                                        </div>
                                    </div>
                                    <div>
                                        <button class="btn btn-sm btn-outline-danger fw-bold rounded-pill px-3 shadow-sm"
                                            onclick="confirmCancelRequest('<?php echo $out['request_id']; ?>', '<?php echo htmlspecialchars($out['username']); ?>')">
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>

                    <div id="requests-empty-search" style="display: none; background-color: var(--bs-body-bg); border: 1px solid var(--bs-border-color);" class="text-center py-5 rounded-4 shadow-sm mt-3">
                        <i class="bi bi-inbox display-1 text-muted opacity-25 mb-3 d-block"></i>
                        <h5 class="fw-bold text-body">No requests found</h5>
                        <p class="text-muted mb-0">You're all caught up.</p>
                    </div>

                <?php else: ?>
                    <div class="text-center text-muted py-5 rounded-4 shadow-sm" style="background-color: var(--bs-body-bg); border: 1px solid var(--bs-border-color);">
                        <i class="bi bi-inbox display-1 opacity-25"></i>
                        <p class="mt-3 fs-5 fw-bold">No pending requests.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div id="tab-discover" style="display: none;">
                <div id="searchResultsArea">
                    <div class="text-center text-muted py-5 rounded-4 shadow-sm" style="background-color: var(--bs-body-bg); border: 1px solid var(--bs-border-color);">
                        <i class="bi bi-search display-1 opacity-25 mb-3 d-block"></i>
                        <h5 class="fw-bold text-body">Discover Contacts</h5>
                        <p class="mb-0">Type in the search bar above to find officers.</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    // --- CONTACTS INTERFACE JAVASCRIPT ---

    let currentContactTab = 'friends';

    function switchContactTab(tabName) {
        currentContactTab = tabName;

        document.getElementById('tab-friends').style.display = 'none';
        document.getElementById('tab-requests').style.display = 'none';
        document.getElementById('tab-discover').style.display = 'none';

        const buttons = document.querySelectorAll('.contact-tab-btn');
        buttons.forEach(btn => {
            btn.style.backgroundColor = 'transparent';
            btn.style.color = 'var(--bs-secondary-color)';
            btn.style.borderBottom = '2px solid transparent';
            btn.classList.remove('active');
        });

        document.getElementById('tab-' + tabName).style.display = 'block';
        const activeBtn = document.getElementById('btn-tab-' + tabName);
        activeBtn.style.backgroundColor = 'rgba(13, 202, 240, 0.15)';
        activeBtn.style.color = '#0dcaf0';
        activeBtn.style.borderBottom = '2px solid #0dcaf0';
        activeBtn.classList.add('active');

        // Only run search automatically if returning to Friends/Requests.
        // If switching to discover, do nothing until they press Enter.
        if (currentContactTab !== 'discover') {
            performSearch();
        }
    }

    // FUNCTION TO CLEAR THE SEARCH INPUT
    function clearSearchInput() {
        const searchInput = document.getElementById('searchInput');
        searchInput.value = '';
        document.getElementById('clearSearchBtn').style.display = 'none';
        searchInput.focus();
        performSearch(); // Instantly reset the lists
    }

    let typingTimer;

    // 1. FILTER AS THEY TYPE (For all tabs)
    document.getElementById('searchInput').addEventListener('input', function() {
        // Toggle the Clear (X) icon visibility
        document.getElementById('clearSearchBtn').style.display = this.value.length > 0 ? 'block' : 'none';

        // Wait 300ms so we don't spam the server while they are actively hitting keys
        clearTimeout(typingTimer);
        typingTimer = setTimeout(performSearch, 300);
    });

    // 2. SEARCH WHEN THEY PRESS ENTER (Close keyboard)
    document.getElementById('searchInput').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(typingTimer);
            this.blur(); // Close keyboard
            performSearch(); // Force search
        }
    });

    function performSearch() {
        const query = document.getElementById('searchInput').value.toLowerCase().trim();

        // SCENARIO 1: Friends Tab
        if (currentContactTab === 'friends') {
            let visibleCount = 0;
            const friends = document.querySelectorAll('.filterable-friend');

            friends.forEach(item => {
                const name = item.querySelector('.search-name').innerText.toLowerCase();
                if (name.includes(query) || query === '') {
                    item.style.setProperty('display', 'flex', 'important');
                    visibleCount++;
                } else {
                    item.style.setProperty('display', 'none', 'important');
                }
            });

            const emptyMsg = document.getElementById('friends-empty-search');
            if (emptyMsg) {
                emptyMsg.style.display = (visibleCount === 0 && friends.length > 0) ? 'block' : 'none';
            }
        }

        // SCENARIO 2: Requests Tab
        else if (currentContactTab === 'requests') {
            let visibleCount = 0;
            const requests = document.querySelectorAll('.filterable-request');

            requests.forEach(item => {
                const name = item.querySelector('.search-name').innerText.toLowerCase();
                if (name.includes(query) || query === '') {
                    item.style.setProperty('display', 'flex', 'important');
                    visibleCount++;
                } else {
                    item.style.setProperty('display', 'none', 'important');
                }
            });

            const emptyMsg = document.getElementById('requests-empty-search');
            if (emptyMsg) {
                emptyMsg.style.display = (visibleCount === 0 && requests.length > 0) ? 'block' : 'none';
            }
        }

        // SCENARIO 3: Discover Tab (INSTANT AJAX SEARCH)
        else if (currentContactTab === 'discover') {
            const resultsArea = document.getElementById('searchResultsArea');

            // If empty, force the default placeholder back
            if (query === '') {
                resultsArea.innerHTML = `
                    <div class="text-center text-muted py-5 rounded-4 shadow-sm" style="background-color: var(--bs-body-bg); border: 1px solid var(--bs-border-color);">
                        <i class="bi bi-search display-1 opacity-25 mb-3 d-block"></i>
                        <h5 class="fw-bold text-body">Discover Contacts</h5>
                        <p class="mb-0">Type in the search bar above to find officers.</p>
                    </div>`;
                return;
            }

            // Fetch the results instantly
            fetch('index.php?ajax_search=' + encodeURIComponent(query))
                .then(response => response.text())
                .then(html => {
                    // Prevent replacing if the user cleared the box before the server replied
                    if (document.getElementById('searchInput').value.toLowerCase().trim() === query) {
                        resultsArea.innerHTML = `<h6 class="text-body fw-bold small mb-3">SEARCH RESULTS</h6>` + html;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
    }

    // ==========================================
    // CANCEL PENDING FRIEND REQUEST LOGIC
    // ==========================================
    function confirmCancelRequest(requestId, username) {
        if (!requestId) return;

        if (!confirm(`Are you sure you want to cancel your friend request to ${username}?`)) {
            return;
        }
        cancelFriendRequest(requestId);
    }

    async function cancelFriendRequest(requestId) {
        const formData = new FormData();
        formData.append('action', 'cancel_request'); // The command for PHP
        formData.append('request_id', requestId);

        try {
            const res = await fetch('contact_controller.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                // 1. Remove the row from the screen smoothly
                const row = document.querySelector(`[data-id="request_${requestId}"]`);
                if (row) row.remove();

                // 2. Dynamically lower the number on the "Requests" tab badge!
                const badge = document.querySelector('#btn-tab-requests .badge');
                if (badge) {
                    let currentCount = parseInt(badge.innerText);
                    if (currentCount > 1) {
                        badge.innerText = currentCount - 1;
                    } else {
                        badge.remove(); // Destroy the badge if count hits 0
                    }
                }

                // 3. Show the "No requests found" message if the list is now empty (BULLETPROOF)
                const container = document.getElementById('requests-list-container');
                if (container && container.querySelectorAll('.filterable-request').length === 0) {

                    const emptyMsg = document.getElementById('requests-empty-search');
                    if (emptyMsg) {
                        emptyMsg.style.display = 'block';

                        // Smart Search: Find whatever heading tag you used (h5, h6, or a bold div)
                        const titleEl = emptyMsg.querySelector('h5, h6, .fw-bold');
                        if (titleEl) {
                            titleEl.innerText = "No pending requests.";
                        }
                    }
                }

            } else {
                alert("Cancellation failed: " + (data.error || "Unknown error"));
            }
        } catch (e) {
            console.error("Cancellation crash:", e);
            alert("A network error occurred.");
        }
    }

    // ==========================================
    // REMOVE FRIEND LOGIC
    // ==========================================
    function confirmDeleteContact(contactId, username) {
        if (!contactId) return;

        if (confirm(`WARNING: Are you sure you want to remove ${username} from your friends list?`)) {
            const formData = new FormData();
            formData.append('action', 'delete_contact');
            formData.append('contact_id', contactId);

            // FIXED: Sent to index.php so the database connection and session are loaded correctly!
            fetch('index.php', {
                    method: 'POST',
                    body: formData
                })
                .then(async res => {
                    // Added a failsafe to check exactly what the server sends back
                    if (!res.ok) throw new Error("Server returned " + res.status);
                    const text = await res.text();
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error("Raw Server Response:", text);
                        throw new Error("Invalid JSON response from server. Check console.");
                    }
                })
                .then(data => {
                    if (data.success) {
                        // Refresh the page so the contact list is perfectly updated
                        window.location.reload();
                    } else {
                        alert("Error removing contact: " + (data.error || "Unknown error"));
                    }
                })
                .catch(err => {
                    console.error("Delete failed:", err);
                    alert("A network error occurred. Check the browser console for exact details.");
                });
        }
    }

    // ==========================================
    // LIVE SORTING: MOVE CONTACT TO TOP
    // ==========================================
    function moveContactToTop(username) {
        const contactList = document.getElementById('friends-list-container');
        const contactItem = document.getElementById('contact-' + username);

        // If the list and the contact both exist on the screen, move them!
        if (contactList && contactItem) {
            // 'prepend' instantly moves the HTML element to the very top
            contactList.prepend(contactItem);
        }
    }
</script>