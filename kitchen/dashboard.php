<?php
/**
 * Restaurant Management System
 * Kitchen Display System (KDS) - Interactive Real-Time Board
 */

declare(strict_types=1);

$pageTitle = 'Kitchen Display System (KDS)';
require_once __DIR__ . '/../includes/header.php';
requireRole(['admin', 'manager', 'kitchen']);

require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h2 class="h4 fw-bold text-white mb-0">Kitchen Display Station (KDS)</h2>
            <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25" id="kdsLiveIndicator">
                <i class="fa-solid fa-circle fa-2xs me-1 text-success animate-pulse"></i> LIVE STREAM
            </span>
        </div>
        <p class="text-muted mb-0">Tickets update in real-time without browser refresh. Audible chime alerts on new orders.</p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <button class="btn btn-secondary-saas btn-sm" onclick="kds.poll()">
            <i class="fa-solid fa-rotate me-1"></i> Force Sync
        </button>
        <button class="btn btn-secondary-saas btn-sm" onclick="playNotificationChime('alert')">
            <i class="fa-solid fa-volume-high me-1 text-warning"></i> Test Chime
        </button>
    </div>
</div>

<!-- KDS 3-Column Kanban Board -->
<div class="kds-board">
    <!-- 1. Pending Column -->
    <div class="kds-column" style="border-top: 4px solid var(--warning);">
        <div class="d-flex justify-content-between align-items-center pb-2 border-bottom border-secondary border-opacity-25">
            <div class="fw-bold text-white d-flex align-items-center gap-2">
                <i class="fa-solid fa-receipt text-warning"></i>
                <span>Pending Acceptance</span>
            </div>
            <span class="badge bg-warning text-dark fw-bold rounded-pill" id="badgeCountPending">0</span>
        </div>
        <div id="kdsColPending" style="min-height: 450px;">
            <div class="text-center py-5 text-muted small"><i class="fa-solid fa-spinner fa-spin fa-2x mb-2 text-warning"></i><div>Connecting live ticket feed...</div></div>
        </div>
    </div>

    <!-- 2. Preparing Column -->
    <div class="kds-column" style="border-top: 4px solid var(--info);">
        <div class="d-flex justify-content-between align-items-center pb-2 border-bottom border-secondary border-opacity-25">
            <div class="fw-bold text-white d-flex align-items-center gap-2">
                <i class="fa-solid fa-fire-burner text-info"></i>
                <span>Under Active Cooking</span>
            </div>
            <span class="badge bg-info text-dark fw-bold rounded-pill" id="badgeCountPreparing">0</span>
        </div>
        <div id="kdsColPreparing" style="min-height: 450px;">
            <div class="text-center py-5 text-muted small"><i class="fa-solid fa-spinner fa-spin fa-2x mb-2 text-info"></i><div>Loading active cooking queue...</div></div>
        </div>
    </div>

    <!-- 3. Ready at Pass Column -->
    <div class="kds-column" style="border-top: 4px solid var(--success);">
        <div class="d-flex justify-content-between align-items-center pb-2 border-bottom border-secondary border-opacity-25">
            <div class="fw-bold text-white d-flex align-items-center gap-2">
                <i class="fa-solid fa-bell-concierge text-success"></i>
                <span>Ready at the Pass</span>
            </div>
            <span class="badge bg-success text-white fw-bold rounded-pill" id="badgeCountReady">0</span>
        </div>
        <div id="kdsColReady" style="min-height: 450px;">
            <div class="text-center py-5 text-muted small"><i class="fa-solid fa-spinner fa-spin fa-2x mb-2 text-success"></i><div>Loading pass delivery queue...</div></div>
        </div>
    </div>
</div>

<script src="../assets/js/kitchen.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
