<style>
    .bookmark-item {
        background-color: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: 12px;
        margin: 15px 20px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: all 0.2s ease-in-out;
        position: relative;
    }

    .bookmark-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }

    .hover-actions {
        opacity: 0;
        transition: opacity 0.2s ease;
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
    }

    .bookmark-item:hover .hover-actions {
        opacity: 1;
    }

    .btn-custom {
        background-color: rgba(128, 128, 128, 0.2);
        color: var(--bs-body-color);
        border: none;
        font-size: 0.85rem;
        padding: 5px 12px;
    }

    .btn-custom:hover {
        background-color: rgba(128, 128, 128, 0.4);
        color: var(--bs-body-color);
    }

    .btn-delete {
        background-color: rgba(239, 68, 68, 0.1);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.2);
    }

    .btn-delete:hover {
        background-color: #ef4444;
        color: white;
    }

    .badge-high {
        background-color: rgba(239, 68, 68, 0.15);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .badge-medium {
        background-color: rgba(245, 158, 11, 0.15);
        color: #f59e0b;
        border: 1px solid rgba(245, 158, 11, 0.3);
    }

    .badge-low {
        background-color: rgba(59, 130, 246, 0.15);
        color: #3b82f6;
        border: 1px solid rgba(59, 130, 246, 0.3);
    }

    /* ========================================= */
    /* FILTER BUTTONS (Base Style)               */
    /* ========================================= */
    .filter-btn {
        background-color: transparent;
        transition: all 0.2s ease-in-out;
        font-weight: 600;
    }

    /* ========================================= */
    /* 1. "ALL" BUTTON (Gray)                    */
    /* ========================================= */
    .filter-btn-all {
        color: #9ca3af !important;
        border: 1px solid #374151;
    }

    .filter-btn-all:hover {
        background-color: rgba(156, 163, 175, 0.1);
        color: #d1d5db !important;
    }

    .filter-btn-all.active {
        background-color: rgba(156, 163, 175, 0.2);
        color: #ffffff !important;
        border-color: #9ca3af;
        box-shadow: 0 0 10px rgba(156, 163, 175, 0.3);
        /* Soft glowing shadow */
    }

    /* ========================================= */
    /* 2. "HIGH" BUTTON (Red)                    */
    /* ========================================= */
    .filter-btn-high {
        color: #ef4444 !important;
        border: 1px solid rgba(239, 68, 68, 0.4);
    }

    .filter-btn-high:hover {
        background-color: rgba(239, 68, 68, 0.1);
    }

    .filter-btn-high.active {
        background-color: rgba(239, 68, 68, 0.2);
        border-color: #ef4444;
        box-shadow: 0 0 10px rgba(239, 68, 68, 0.4);
        /* Red glowing shadow */
    }

    /* ========================================= */
    /* 3. "MEDIUM" BUTTON (Amber)                */
    /* ========================================= */
    .filter-btn-medium {
        color: #f59e0b !important;
        border: 1px solid rgba(245, 158, 11, 0.4);
    }

    .filter-btn-medium:hover {
        background-color: rgba(245, 158, 11, 0.1);
    }

    .filter-btn-medium.active {
        background-color: rgba(245, 158, 11, 0.2);
        border-color: #f59e0b;
        box-shadow: 0 0 10px rgba(245, 158, 11, 0.4);
        /* Amber glowing shadow */
    }

    /* ========================================= */
    /* 4. "LOW" BUTTON (Blue)                    */
    /* ========================================= */
    .filter-btn-low {
        color: #3b82f6 !important;
        border: 1px solid rgba(59, 130, 246, 0.4);
    }

    .filter-btn-low:hover {
        background-color: rgba(59, 130, 246, 0.1);
    }

    .filter-btn-low.active {
        background-color: rgba(59, 130, 246, 0.2);
        border-color: #3b82f6;
        box-shadow: 0 0 10px rgba(59, 130, 246, 0.4);
        /* Blue glowing shadow */
    }

    /* ========================================= */
    /* CIRCULAR SEND BUTTON (Hover & Glow)       */
    /* ========================================= */
    .btn-send-circular {
        width: 42px;
        height: 42px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #0dcaf0, #0d6efd);
        color: white;
        border: none;
        border-radius: 50%;
        transition: all 0.2s ease;
        box-shadow: 0 2px 5px rgba(13, 110, 253, 0.4);
    }

    .btn-send-circular:hover {
        transform: scale(1.05) translateY(-2px);
        /* Slightly enlarges and floats up */
        box-shadow: 0 6px 15px rgba(13, 110, 253, 0.6);
        /* Brighter blue glow */
        color: white;
    }

    .btn-send-circular:active {
        transform: scale(1) translateY(0);
        /* Snaps back when clicked */
        box-shadow: 0 2px 5px rgba(13, 110, 253, 0.4);
    }
</style>

<div id="savedMessagesInterface" class="d-none w-100 h-100 bg-transparent">
    <div class="d-flex flex-column w-100 h-100">

        <div class="p-4 border-bottom flex-shrink-0 z-3" style="background-color: var(--bs-body-bg); backdrop-filter: blur(10px);">
            <div class="container-fluid p-0">

                <div class="mb-4">
                    <div class="d-flex align-items-center mb-1">
                        <div class="d-flex align-items-center justify-content-center shadow-sm rounded-3 me-3"
                            style="width: 42px; height: 42px; background-color: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.25);">
                            <i class="bi bi-bookmark-star-fill fs-4" style="color: #f59e0b;"></i>
                        </div>
                        <h3 class="fw-bold mb-0 text-body" style="letter-spacing: -0.5px;">Saved Messages</h3>
                    </div>
                    <div class="text-body-secondary small mt-1">Review and manage your important messages</div>
                </div>

                <div class="d-flex align-items-center">
                    <div class="input-group border rounded overflow-hidden flex-grow-1 me-3">
                        <span class="input-group-text bg-transparent border-0 text-body-secondary"><i class="bi bi-search"></i></span>
                        <input type="text" id="savedSearchInput" class="form-control bg-transparent border-0 text-body shadow-none" placeholder="Search messages..." oninput="filterSavedMessages()">
                        <button class="btn bg-transparent border-0 text-body-secondary shadow-none hover-text-white" type="button" onclick="clearSavedSearch()">
                            <i class="bi bi-x-lg" style="font-size: 0.85rem;"></i>
                        </button>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button class="btn btn-sm rounded-pill px-3 filter-btn filter-btn-all active" id="filter-btn-All" onclick="setSavedFilter('All')">All</button>
                    <button class="btn btn-sm rounded-pill px-3 filter-btn filter-btn-high" id="filter-btn-High" onclick="setSavedFilter('High')">High</button>
                    <button class="btn btn-sm rounded-pill px-3 filter-btn filter-btn-medium" id="filter-btn-Medium" onclick="setSavedFilter('Medium')">Medium</button>
                    <button class="btn btn-sm rounded-pill px-3 filter-btn filter-btn-low" id="filter-btn-Low" onclick="setSavedFilter('Low')">Low</button>
                </div>
            </div>
        </div>

        <div class="container-fluid p-0 bg-transparent flex-grow-1 overflow-auto pb-4 position-relative" id="savedMessagesList">
        </div>

        <button id="savedScrollToBottomBtn" class="btn shadow-sm align-items-center justify-content-center rounded-circle"
            style="display: none !important; position: absolute; bottom: 100px; left: 50%; transform: translateX(-50%); z-index: 1000; width: 40px; height: 40px; transition: opacity 0.2s; opacity: 0; cursor: pointer; background: linear-gradient(135deg, #0dcaf0, #0d6efd); border: none;"
            onclick="scrollToBottomSavedSmooth()">
            <i class="bi bi-chevron-down text-white fs-5" style="text-shadow: 0 1px 2px rgba(0,0,0,0.3);"></i>
        </button>

        <div id="savedAttachmentPreview" class="d-none px-4 py-3 w-100 border-top position-relative" style="background-color: var(--bs-tertiary-bg); z-index: 5;">
            <div class="d-flex align-items-center bg-body p-2 shadow-sm" style="max-width: fit-content; border-radius: 12px; border: 1px solid var(--bs-border-color);">
                <div id="savedPreviewThumbnail" class="me-3 d-flex align-items-center justify-content-center overflow-hidden bg-light" style="width: 50px; height: 50px; border-radius: 8px; cursor: pointer;" onclick="openPreviewModal()" title="Click to view full size"></div>
                <div class="d-flex flex-column pe-4">
                    <span id="savedAttachmentName" class="fw-bold text-truncate" style="max-width: 180px; font-size: 0.85rem;">filename.jpg</span>
                    <small id="savedAttachmentSize" class="text-muted" style="font-size: 0.7rem;">0 KB</small>
                </div>
                <button type="button" class="btn btn-sm text-danger ms-auto p-1" onclick="clearSavedAttachment()" title="Remove file">
                    <i class="bi bi-x-circle-fill fs-5"></i>
                </button>
            </div>
        </div>

        <div class="w-100 d-flex align-items-center px-4 py-3 flex-shrink-0" style="background-color: var(--bs-body-bg); border-top: 1px solid var(--bs-border-color); z-index: 10;">
            <form onsubmit="promptPersonalNotePriority(event)" class="d-flex align-items-center w-100 m-0">

                <input type="file" id="savedAttachmentInput" class="d-none" onchange="showSavedAttachmentPreview()">

                <button type="button" class="btn btn-link text-muted me-2 px-0 shadow-none" onclick="document.getElementById('savedAttachmentInput').click()">
                    <i class="bi bi-paperclip fs-5"></i>
                </button>

                <input type="text" id="personalNoteInput" class="form-control bg-secondary bg-opacity-10 border-0 shadow-none px-4 flex-grow-1" style="border-radius: 20px; height: 42px; color: var(--bs-body-color);" placeholder="Type a secure note to yourself..." autocomplete="off">

                <button type="submit" class="btn p-0 ms-3 flex-shrink-0 btn-send-circular">
                    <i class="bi bi-send-fill fs-5"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="personalNotePriorityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content shadow-lg" style="background-color: #212529; border: 1px solid #495057; border-radius: 12px;">

            <div class="modal-header border-secondary border-opacity-50 pb-2 pt-3 px-4">
                <h6 class="modal-title text-white fw-bold d-flex align-items-center">
                    <i class="bi bi-bookmark-fill text-warning me-2"></i>Select Priority
                </h6>
                <button type="button" class="btn-close btn-close-white opacity-50 shadow-none" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-0">
                <div class="list-group list-group-flush border-0" style="border-radius: 12px;">
                    <button class="list-group-item list-group-item-action border-secondary border-opacity-50 py-3 px-4 d-flex align-items-center" style="background-color: transparent; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='rgba(255,255,255,0.05)'" onmouseout="this.style.backgroundColor='transparent'" onclick="confirmAndSendPersonalNote('High')">
                        <div class="rounded-circle me-3" style="width: 12px; height: 12px; background-color: #ef4444;"></div>
                        <span class="fw-bold" style="color: #ef4444;">High</span>
                    </button>
                    <button class="list-group-item list-group-item-action border-secondary border-opacity-50 py-3 px-4 d-flex align-items-center" style="background-color: transparent; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='rgba(255,255,255,0.05)'" onmouseout="this.style.backgroundColor='transparent'" onclick="confirmAndSendPersonalNote('Medium')">
                        <div class="rounded-circle me-3" style="width: 12px; height: 12px; background-color: #facc15;"></div>
                        <span class="fw-bold" style="color: #facc15;">Medium</span>
                    </button>
                    <button class="list-group-item list-group-item-action border-0 py-3 px-4 d-flex align-items-center" style="background-color: transparent; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='rgba(255,255,255,0.05)'" onmouseout="this.style.backgroundColor='transparent'" onclick="confirmAndSendPersonalNote('Low')">
                        <div class="rounded-circle me-3" style="width: 12px; height: 12px; background-color: #0dcaf0;"></div>
                        <span class="fw-bold" style="color: #0dcaf0;">Low</span>
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>