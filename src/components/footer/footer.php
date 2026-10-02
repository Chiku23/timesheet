<?php
$requestUri = currentUri();
$isAuthPage = in_array($requestUri, ['/login', '/signup']);
$loggedIn = isLoggedIn();
$projects = [];
if ($loggedIn) {
    $projects = \Chiku\TimeSheet\Models\Project::orderBy('name')->get();
}
?>

<?php if ($loggedIn && !$isAuthPage): ?>
<!-- Floating Timer Launcher -->
<div class="floatingBtn" id="floatingTimerBtn" title="Open Time Tracker">
    <div class="timerBtn" id="timerBtnIndicator">
        <svg class="launcher-clock-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 16 14"></polyline>
        </svg>
        <span class="launcher-badge" id="launcherTimerBadge" style="display: none;">00:00</span>
    </div>
</div>

<!-- Floating Timer Window (Draggable & Non-blocking) -->
<div class="timer-modal" id="timerModal" role="dialog" aria-labelledby="timerModalTitle">
    <!-- Grabbable Header Bar -->
    <div class="timer-modal-header" id="timerModalHeader">
        <div class="header-drag-zone">
            <svg class="grip-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <circle cx="9" cy="6" r="1.5"></circle>
                <circle cx="15" cy="6" r="1.5"></circle>
                <circle cx="9" cy="12" r="1.5"></circle>
                <circle cx="15" cy="12" r="1.5"></circle>
                <circle cx="9" cy="18" r="1.5"></circle>
                <circle cx="15" cy="18" r="1.5"></circle>
            </svg>
            <h2 id="timerModalTitle">Time Tracker</h2>
        </div>
        <div class="header-right-zone">
            <span class="timer-state-pill state-idle" id="timerStatePill">
                <span class="state-dot"></span>
                <span class="state-text" id="timerStateText">Idle</span>
            </span>
            <button type="button" class="modal-close" id="closeTimerModal" aria-label="Close timer popup">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
    </div>

    <!-- Timer Digits Display -->
    <div class="time-box" id="timeBox">
        <span id="hours">00</span><span class="colon">:</span>
        <span id="minutes">00</span><span class="colon">:</span>
        <span id="seconds">00</span>
    </div>

    <!-- Timer Form & Action Controls -->
    <div class="action">
        <div class="timer-input-group">
            <input type="text" id="timerTask" placeholder="What are you working on?" autocomplete="off">
        </div>
        <div class="timer-input-group">
            <select id="timerProject" class="select-project">
                <option value="">No Project</option>
                <?php foreach ($projects as $p): ?>
                    <option value="<?= $p->id ?>" data-color="<?= htmlspecialchars($p->color_hex) ?>">
                        <?= htmlspecialchars($p->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Dynamic Action Controls: Start, Pause, Resume, Stop & Save -->
        <div class="timer-controls">
            <!-- Idle Controls -->
            <div id="controlsIdle" class="control-row">
                <button type="button" id="startTimerBtn" class="btn-timer-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <polygon points="5 3 19 12 5 21 5 3"></polygon>
                    </svg>
                    <span>Start Timer</span>
                </button>
            </div>

            <!-- Running Controls -->
            <div id="controlsRunning" class="control-row" style="display: none;">
                <button type="button" id="pauseTimerBtn" class="btn-timer-warning">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
                        <rect x="6" y="4" width="4" height="16"></rect>
                        <rect x="14" y="4" width="4" height="16"></rect>
                    </svg>
                    <span>Pause</span>
                </button>
                <button type="button" id="stopTimerBtn" class="btn-timer-danger">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                        <rect x="4" y="4" width="16" height="16" rx="2"></rect>
                    </svg>
                    <span>Stop & Save</span>
                </button>
            </div>

            <!-- Paused Controls -->
            <div id="controlsPaused" class="control-row" style="display: none;">
                <button type="button" id="resumeTimerBtn" class="btn-timer-success">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
                        <polygon points="5 3 19 12 5 21 5 3"></polygon>
                    </svg>
                    <span>Resume</span>
                </button>
                <button type="button" id="stopPausedTimerBtn" class="btn-timer-danger">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                        <rect x="4" y="4" width="16" height="16" rx="2"></rect>
                    </svg>
                    <span>Stop & Save</span>
                </button>
            </div>
        </div>

        <!-- Footer reset/discard link when running/paused -->
        <div class="timer-sub-actions" id="timerSubActions" style="display: none;">
            <button type="button" class="btn-text-link" id="discardTimerBtn">Discard Session</button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Global Custom Confirmation Dialog (Replaces native JS confirm) -->
<div class="confirm-backdrop" id="globalConfirmBackdrop"></div>
<div class="confirm-modal" id="globalConfirmModal" role="dialog" aria-labelledby="globalConfirmTitle">
    <div class="confirm-content">
        <div class="confirm-icon-box">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
        </div>
        <h3 id="globalConfirmTitle">Are you sure?</h3>
        <p id="globalConfirmMessage">This action cannot be undone.</p>
        <div class="confirm-actions">
            <button type="button" class="btn-cancel" id="globalConfirmCancelBtn">Cancel</button>
            <button type="button" class="btn-delete" id="globalConfirmAcceptBtn">Confirm</button>
        </div>
    </div>
</div>

<?php if (!$isAuthPage): ?>
<footer>
    <div class="footer-inner">
        <div class="footer-left">
            <span class="footer-brand">TimeSheet Workspace</span>
            <span class="footer-version-tag">v1.2.0</span>
        </div>
        <div class="footer-right">
            <span>&copy; <?= date('Y') ?> TimeSheet. All rights reserved.</span>
        </div>
    </div>
</footer>
<?php endif; ?>

<?php if (isset($jsFiles) && is_array($jsFiles)): ?>
    <?php foreach ($jsFiles as $js): ?>
        <script src="<?= htmlspecialchars($js) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>