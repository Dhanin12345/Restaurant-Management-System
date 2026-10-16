<!-- ========================================================
     Universal Step-by-Step System Flow & Data Pipeline Player
     ======================================================== -->
<div class="modal fade workflow-player-modal" id="workflowPlayerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-bottom border-secondary border-opacity-25 px-4 py-3">
                <div class="d-flex align-items-center gap-3">
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(249, 115, 22, 0.15); border: 1px solid rgba(249, 115, 22, 0.3); display: flex; align-items: center; justify-content: center; color: #f97316;">
                        <i class="fa-solid fa-play"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0">Step-by-Step Data Processing Flow</h5>
                        <small class="text-muted">Live Interactive System Execution &middot; 5 Operational Phases</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-primary-saas fw-bold px-3" id="flowAutoPlayBtn">
                        <i class="fa-solid fa-play me-1"></i> Auto-Play
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <div class="modal-body p-4">
                <!-- Stage Step Rail Navigation -->
                <div class="stage-tracker-rail mb-4" id="stageTrackerRail">
                    <!-- Populated by JS -->
                </div>

                <!-- Active Stage Card Display -->
                <div class="stage-display-card" id="stageDisplayCard">
                    <!-- Progress Bar for auto play -->
                    <div class="stage-progress-bar-wrap">
                        <div class="stage-progress-bar-fill" id="stageProgressBarFill"></div>
                    </div>

                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                        <div>
                            <span class="badge bg-primary text-white mb-1" id="stageBadge">Phase 1 of 5</span>
                            <h4 class="fw-bold text-white mb-1" id="stageTitle">Smart Table Allocation</h4>
                            <p class="text-muted small mb-0" id="stageDescription">Description goes here</p>
                        </div>
                        <div id="stageDbPill" class="db-live-state-pill">
                            <i class="fa-solid fa-database text-warning"></i> <span>TABLES: Available &rarr; Reserved</span>
                        </div>
                    </div>

                    <!-- Under The Hood Technical Breakdown Box -->
                    <div class="p-3 rounded-3 mb-3" style="background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(255, 255, 255, 0.08);">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <small class="text-muted d-block text-uppercase fw-bold mb-1" style="font-size:0.68rem; letter-spacing:0.05em;">What The User / Staff Sees</small>
                                <div class="text-light small" id="stageUiAction">UI Action</div>
                            </div>
                            <div class="col-md-6 border-start border-secondary border-opacity-25 ps-md-3">
                                <small class="text-muted d-block text-uppercase fw-bold mb-1" style="font-size:0.68rem; letter-spacing:0.05em;">Data Engine Execution (PHP + PDO + MySQL)</small>
                                <div class="font-monospace text-warning small" style="font-size:0.75rem;" id="stageDbAction">SQL Execution</div>
                            </div>
                        </div>
                    </div>

                    <!-- Live Highlight Metrics -->
                    <div class="row g-2 mb-4" id="stageMetricsRow">
                        <!-- Populated by JS -->
                    </div>

                    <!-- Stage Controls -->
                    <div class="flow-control-bar">
                        <button type="button" class="btn btn-secondary-saas btn-sm px-3" id="flowPrevBtn">
                            <i class="fa-solid fa-chevron-left me-1"></i> Previous Step
                        </button>
                        <div class="d-flex align-items-center gap-2">
                            <a href="#" class="btn btn-outline-light btn-sm px-3" id="stagePortalLink" target="_blank">
                                <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Open Live Portal Screen
                            </a>
                        </div>
                        <button type="button" class="btn btn-primary-saas btn-sm px-3 fw-bold" id="flowNextBtn">
                            Next Step <i class="fa-solid fa-chevron-right ms-1"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
