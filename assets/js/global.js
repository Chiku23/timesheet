// ==========================================
// Global JavaScript
// Server-Authoritative Timer & Draggable Window
// Theme Switcher & Shared Utilities
// ==========================================

document.addEventListener('DOMContentLoaded', () => {
    // ─── Theme Toggle ───
    const themeToggleBtn = document.getElementById('themeToggleBtn');
    const savedTheme = localStorage.getItem('app-theme');

    if (savedTheme === 'light') {
        document.body.classList.remove('theme-dark');
    } else if (savedTheme === 'dark') {
        document.body.classList.add('theme-dark');
    }

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            const isDark = document.body.classList.toggle('theme-dark');
            localStorage.setItem('app-theme', isDark ? 'dark' : 'light');
        });
    }

    // ─── Timezone State & Switcher ───
    let currentAppTimezone = localStorage.getItem('app-timezone') || 'local';
    const tzLocalBtn = document.getElementById('tzLocalBtn');
    const tzUtcBtn = document.getElementById('tzUtcBtn');

    function updateTimezoneUI() {
        if (tzLocalBtn && tzUtcBtn) {
            if (currentAppTimezone === 'utc') {
                tzUtcBtn.classList.add('active');
                tzLocalBtn.classList.remove('active');
            } else {
                tzLocalBtn.classList.add('active');
                tzUtcBtn.classList.remove('active');
            }
        }
    }

    function setAppTimezone(tz) {
        currentAppTimezone = tz === 'utc' ? 'utc' : 'local';
        localStorage.setItem('app-timezone', currentAppTimezone);
        updateTimezoneUI();
        window.dispatchEvent(new CustomEvent('timesheet:timezone-changed', {
            detail: { timezone: currentAppTimezone }
        }));
    }

    if (tzLocalBtn) tzLocalBtn.addEventListener('click', () => setAppTimezone('local'));
    if (tzUtcBtn) tzUtcBtn.addEventListener('click', () => setAppTimezone('utc'));
    updateTimezoneUI();

    // ─── Timer Elements ───
    const floatingBtn = document.getElementById('floatingTimerBtn');
    const timerBtnIndicator = document.getElementById('timerBtnIndicator');
    const launcherTimerBadge = document.getElementById('launcherTimerBadge');

    const timerModal = document.getElementById('timerModal');
    const timerModalHeader = document.getElementById('timerModalHeader');
    const closeTimerBtn = document.getElementById('closeTimerModal');

    const timerStatePill = document.getElementById('timerStatePill');
    const timerStateText = document.getElementById('timerStateText');

    const hoursSpan = document.getElementById('hours');
    const minutesSpan = document.getElementById('minutes');
    const secondsSpan = document.getElementById('seconds');

    const timerTaskInput = document.getElementById('timerTask');
    const timerProjectSelect = document.getElementById('timerProject');

    const controlsIdle = document.getElementById('controlsIdle');
    const controlsRunning = document.getElementById('controlsRunning');
    const controlsPaused = document.getElementById('controlsPaused');
    const timerSubActions = document.getElementById('timerSubActions');

    const startTimerBtn = document.getElementById('startTimerBtn');
    const pauseTimerBtn = document.getElementById('pauseTimerBtn');
    const resumeTimerBtn = document.getElementById('resumeTimerBtn');
    const stopTimerBtn = document.getElementById('stopTimerBtn');
    const stopPausedTimerBtn = document.getElementById('stopPausedTimerBtn');
    const discardTimerBtn = document.getElementById('discardTimerBtn');

    // ─── Timer State Variables ───
    let timerStatus = 'idle'; // 'idle' | 'running' | 'paused'
    let accumulatedSeconds = 0;
    let runSegmentStartTime = null; // timestamp ms
    let timerInterval = null;

    // Helper: Pad numbers to 2 digits
    function padZero(num) {
        return String(num).padStart(2, '0');
    }

    // Format seconds into 00:00:00 display
    function updateClockDisplay(totalSecs) {
        const hrs = Math.floor(totalSecs / 3600);
        const mins = Math.floor((totalSecs % 3600) / 60);
        const secs = totalSecs % 60;

        if (hoursSpan) hoursSpan.textContent = padZero(hrs);
        if (minutesSpan) minutesSpan.textContent = padZero(mins);
        if (secondsSpan) secondsSpan.textContent = padZero(secs);

        // Update launcher badge
        if (launcherTimerBadge) {
            if (timerStatus === 'running' || timerStatus === 'paused') {
                launcherTimerBadge.style.display = 'block';
                launcherTimerBadge.textContent = `${padZero(hrs > 0 ? hrs : mins)}:${padZero(hrs > 0 ? mins : secs)}`;
            } else {
                launcherTimerBadge.style.display = 'none';
            }
        }
    }

    // Calculate current live elapsed seconds
    function getCurrentTotalSeconds() {
        if (timerStatus === 'running' && runSegmentStartTime) {
            const currentSegment = Math.max(0, Math.floor((Date.now() - runSegmentStartTime) / 1000));
            return accumulatedSeconds + currentSegment;
        }
        return accumulatedSeconds;
    }

    // Update UI controls and state badges
    function renderTimerUI() {
        if (!timerModal) return;

        if (timerStatePill) {
            timerStatePill.className = 'timer-state-pill';
            timerStatePill.classList.add(`state-${timerStatus}`);
        }

        if (timerStateText) {
            if (timerStatus === 'running') timerStateText.textContent = 'Running';
            else if (timerStatus === 'paused') timerStateText.textContent = 'Paused';
            else timerStateText.textContent = 'Idle';
        }

        if (timerBtnIndicator) {
            if (timerStatus === 'running') {
                timerBtnIndicator.classList.add('active', 'running');
                timerBtnIndicator.classList.remove('paused');
            } else if (timerStatus === 'paused') {
                timerBtnIndicator.classList.add('active', 'paused');
                timerBtnIndicator.classList.remove('running');
            } else {
                timerBtnIndicator.classList.remove('active', 'running', 'paused');
            }
        }

        if (controlsIdle) controlsIdle.style.display = timerStatus === 'idle' ? 'flex' : 'none';
        if (controlsRunning) controlsRunning.style.display = timerStatus === 'running' ? 'flex' : 'none';
        if (controlsPaused) controlsPaused.style.display = timerStatus === 'paused' ? 'flex' : 'none';
        if (timerSubActions) timerSubActions.style.display = timerStatus !== 'idle' ? 'flex' : 'none';

        updateClockDisplay(getCurrentTotalSeconds());
    }

    // ─── Start Timer (Server Synchronized) ───
    async function startTimer() {
        const taskDesc = timerTaskInput ? timerTaskInput.value.trim() : '';
        const projectId = timerProjectSelect ? timerProjectSelect.value : '';

        timerStatus = 'running';
        accumulatedSeconds = 0;
        runSegmentStartTime = Date.now();

        renderTimerUI();

        if (timerInterval) clearInterval(timerInterval);
        timerInterval = setInterval(() => {
            updateClockDisplay(getCurrentTotalSeconds());
        }, 1000);

        try {
            const res = await fetch('/api/timer/start', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    task_description: taskDesc,
                    project_id: projectId || null,
                }),
            });
            const data = await res.json();
            if (!data.success) {
                console.error('Server timer start error:', data.message);
            }
        } catch (err) {
            console.error('Network error starting timer session:', err);
        }
    }

    // ─── Pause Timer (Server Calculates Elapsed Segment) ───
    async function pauseTimer() {
        if (timerStatus !== 'running') return;

        // Synchronously capture exact duration at the moment the pause button was clicked
        const clientRecordedDuration = getCurrentTotalSeconds();
        const segmentSeconds = Math.max(0, Math.floor((Date.now() - runSegmentStartTime) / 1000));
        accumulatedSeconds += segmentSeconds;
        runSegmentStartTime = null;
        timerStatus = 'paused';

        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }

        renderTimerUI();

        try {
            const res = await fetch('/api/timer/pause', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    client_duration_seconds: clientRecordedDuration,
                }),
            });
            const data = await res.json();
            if (data.success && data.accumulated_seconds !== undefined) {
                accumulatedSeconds = data.accumulated_seconds;
                renderTimerUI();
            }
        } catch (err) {
            console.error('Network error pausing timer session:', err);
        }
    }

    // ─── Resume Timer (Server Synchronized) ───
    async function resumeTimer() {
        if (timerStatus !== 'paused') return;

        runSegmentStartTime = Date.now();
        timerStatus = 'running';

        renderTimerUI();

        if (timerInterval) clearInterval(timerInterval);
        timerInterval = setInterval(() => {
            updateClockDisplay(getCurrentTotalSeconds());
        }, 1000);

        try {
            const res = await fetch('/api/timer/resume', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
            });
            const data = await res.json();
            if (!data.success) {
                console.error('Server timer resume error:', data.message);
            }
        } catch (err) {
            console.error('Network error resuming timer session:', err);
        }
    }

    // ─── Stop & Save (Server Computes Verified Duration) ───
    async function stopAndSaveTimer() {
        // Synchronously capture exact duration and click timestamp BEFORE any network or debugger pause
        const clientRecordedDuration = getCurrentTotalSeconds();
        const clientClickMs = Date.now();

        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }

        const taskDesc = timerTaskInput ? timerTaskInput.value.trim() : '';
        const projectId = timerProjectSelect ? timerProjectSelect.value : '';

        try {
            const res = await fetch('/api/timer/stop', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    task_description: taskDesc,
                    project_id: projectId || null,
                    client_duration_seconds: clientRecordedDuration,
                    client_click_time_ms: clientClickMs,
                }),
            });

            const data = await res.json();
            if (data.success) {
                timerStatus = 'idle';
                accumulatedSeconds = 0;
                runSegmentStartTime = null;

                if (timerTaskInput) timerTaskInput.value = '';
                if (timerProjectSelect) timerProjectSelect.value = '';

                renderTimerUI();
                window.dispatchEvent(new CustomEvent('timesheet:log-updated'));
            } else {
                alert(data.message || 'Failed to stop timer.');
                // Re-sync with server state
                syncActiveTimerFromServer();
            }
        } catch (err) {
            console.error('Network error stopping timer session:', err);
            alert('Failed to save timer session. Please check your connection.');
        }
    }

    // ─── Discard Session ───
    async function discardTimer() {
        const confirmed = await window.showConfirmDialog({
            title: 'Discard Live Tracking Session?',
            message: 'Are you sure you want to discard this live timer? Unsaved elapsed duration will be permanently lost.',
            confirmText: 'Discard Session',
            isDanger: true,
        });
        if (!confirmed) return;

        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }

        timerStatus = 'idle';
        accumulatedSeconds = 0;
        runSegmentStartTime = null;

        if (timerTaskInput) timerTaskInput.value = '';
        if (timerProjectSelect) timerProjectSelect.value = '';

        renderTimerUI();

        try {
            await fetch('/api/timer/discard', { method: 'POST' });
        } catch (err) {
            console.error('Error discarding session:', err);
        }
    }

    // ─── Fetch Active Session from Server on Load ───
    async function syncActiveTimerFromServer() {
        if (!timerModal) return;

        try {
            const res = await fetch('/api/timer/active');
            const data = await res.json();

            if (data.success && data.active && data.session) {
                const s = data.session;
                timerStatus = s.status;
                accumulatedSeconds = s.accumulated_seconds || 0;

                if (timerTaskInput && s.task_description) {
                    timerTaskInput.value = s.task_description;
                }
                if (timerProjectSelect && s.project_id) {
                    timerProjectSelect.value = s.project_id;
                }

                if (s.status === 'running') {
                    if (s.segment_started_at) {
                        const segDate = new Date(s.segment_started_at.replace(' ', 'T'));
                        runSegmentStartTime = segDate.getTime();
                    } else {
                        runSegmentStartTime = Date.now();
                    }

                    renderTimerUI();

                    if (timerInterval) clearInterval(timerInterval);
                    timerInterval = setInterval(() => {
                        updateClockDisplay(getCurrentTotalSeconds());
                    }, 1000);
                } else if (s.status === 'paused') {
                    runSegmentStartTime = null;
                    renderTimerUI();
                }
            } else {
                timerStatus = 'idle';
                accumulatedSeconds = 0;
                runSegmentStartTime = null;
                renderTimerUI();
            }
        } catch (err) {
            console.error('Error syncing active timer from server:', err);
        }
    }

    // ─── Event Listeners for Timer Actions ───
    if (startTimerBtn) startTimerBtn.addEventListener('click', startTimer);
    if (pauseTimerBtn) pauseTimerBtn.addEventListener('click', pauseTimer);
    if (resumeTimerBtn) resumeTimerBtn.addEventListener('click', resumeTimer);
    if (stopTimerBtn) stopTimerBtn.addEventListener('click', stopAndSaveTimer);
    if (stopPausedTimerBtn) stopPausedTimerBtn.addEventListener('click', stopAndSaveTimer);
    if (discardTimerBtn) discardTimerBtn.addEventListener('click', discardTimer);

    // ─── Modal Open/Close Controls ───
    function openTimerModal() {
        if (!timerModal) return;
        timerModal.classList.add('open');
    }

    function closeTimerModalFn() {
        if (!timerModal) return;
        timerModal.classList.remove('open');
    }

    if (floatingBtn) {
        floatingBtn.addEventListener('click', () => {
            if (timerModal.classList.contains('open')) {
                closeTimerModalFn();
            } else {
                openTimerModal();
            }
        });
    }

    if (closeTimerBtn) {
        closeTimerBtn.addEventListener('click', closeTimerModalFn);
    }

    // ─── Draggable Popup Header Window (Fixes Stretching Bug) ───
    if (timerModal && timerModalHeader) {
        let isDragging = false;
        let startX = 0, startY = 0;
        let initialModalLeft = 0, initialModalTop = 0;

        // Restore saved position without stretching
        const savedPos = localStorage.getItem('timer_window_pos');
        if (savedPos) {
            try {
                const pos = JSON.parse(savedPos);
                if (pos.left !== undefined && pos.top !== undefined) {
                    const safeLeft = Math.max(10, Math.min(window.innerWidth - 360, pos.left));
                    const safeTop = Math.max(10, Math.min(window.innerHeight - 320, pos.top));

                    timerModal.classList.add('is-dragged');
                    timerModal.style.removeProperty('transform');
                    timerModal.style.removeProperty('bottom');
                    timerModal.style.removeProperty('right');
                    timerModal.style.bottom = 'auto';
                    timerModal.style.right = 'auto';
                    timerModal.style.left = `${safeLeft}px`;
                    timerModal.style.top = `${safeTop}px`;
                }
            } catch (e) {}
        }

        function onDragStart(clientX, clientY, target) {
            if (target && target.closest('button')) return false;

            isDragging = true;
            timerModalHeader.classList.add('dragging');
            timerModal.classList.add('is-dragged');

            const rect = timerModal.getBoundingClientRect();
            startX = clientX;
            startY = clientY;
            initialModalLeft = rect.left;
            initialModalTop = rect.top;

            // Clear bottom and right to prevent vertical stretching
            timerModal.style.removeProperty('transform');
            timerModal.style.removeProperty('bottom');
            timerModal.style.removeProperty('right');
            timerModal.style.bottom = 'auto';
            timerModal.style.right = 'auto';
            timerModal.style.left = `${initialModalLeft}px`;
            timerModal.style.top = `${initialModalTop}px`;
            return true;
        }

        function onDragMove(clientX, clientY) {
            if (!isDragging) return;

            const deltaX = clientX - startX;
            const deltaY = clientY - startY;

            let newLeft = initialModalLeft + deltaX;
            let newTop = initialModalTop + deltaY;

            const modalWidth = timerModal.offsetWidth || 340;
            const modalHeight = timerModal.offsetHeight || 300;
            const maxLeft = Math.max(10, window.innerWidth - modalWidth - 10);
            const maxTop = Math.max(10, window.innerHeight - modalHeight - 10);

            newLeft = Math.max(10, Math.min(maxLeft, newLeft));
            newTop = Math.max(10, Math.min(maxTop, newTop));

            timerModal.style.bottom = 'auto';
            timerModal.style.right = 'auto';
            timerModal.style.left = `${newLeft}px`;
            timerModal.style.top = `${newTop}px`;
        }

        function onDragEnd() {
            if (isDragging) {
                isDragging = false;
                timerModalHeader.classList.remove('dragging');

                const rect = timerModal.getBoundingClientRect();
                localStorage.setItem('timer_window_pos', JSON.stringify({
                    left: Math.round(rect.left),
                    top: Math.round(rect.top),
                }));
            }
        }

        // Mouse Events
        timerModalHeader.addEventListener('mousedown', (e) => {
            if (onDragStart(e.clientX, e.clientY, e.target)) {
                e.preventDefault();
            }
        });

        document.addEventListener('mousemove', (e) => {
            onDragMove(e.clientX, e.clientY);
        });

        document.addEventListener('mouseup', onDragEnd);

        // Touch Events
        timerModalHeader.addEventListener('touchstart', (e) => {
            if (e.touches.length === 1) {
                if (onDragStart(e.touches[0].clientX, e.touches[0].clientY, e.target)) {
                    e.preventDefault();
                }
            }
        }, { passive: false });

        document.addEventListener('touchmove', (e) => {
            if (isDragging && e.touches.length === 1) {
                onDragMove(e.touches[0].clientX, e.touches[0].clientY);
                e.preventDefault();
            }
        }, { passive: false });

        document.addEventListener('touchend', onDragEnd);
    }

    // Sync active timer session from server
    syncActiveTimerFromServer();
});

// ─── Global Timezone Formatting Helpers ───
window.getAppTimezone = function() {
    return localStorage.getItem('app-timezone') || 'local';
};

window.formatTimeWithTz = function(datetimeStr) {
    if (!datetimeStr) return '-';
    let s = String(datetimeStr).trim();
    if (!s.includes('Z') && !s.includes('+') && !s.includes('T')) {
        s = s.replace(' ', 'T') + 'Z';
    } else if (!s.includes('Z') && !s.includes('+')) {
        s = s + 'Z';
    }
    const d = new Date(s);
    if (isNaN(d.getTime())) return datetimeStr;

    const tz = window.getAppTimezone();
    return d.toLocaleTimeString('en-US', {
        timeZone: tz === 'utc' ? 'UTC' : undefined,
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
};

window.formatDateWithTz = function(datetimeStr) {
    if (!datetimeStr) return '';
    let s = String(datetimeStr).trim();
    if (!s.includes('Z') && !s.includes('+') && !s.includes('T')) {
        s = s.replace(' ', 'T') + 'Z';
    } else if (!s.includes('Z') && !s.includes('+')) {
        s = s + 'Z';
    }
    const d = new Date(s);
    if (isNaN(d.getTime())) return datetimeStr;

    const tz = window.getAppTimezone();
    const opts = { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' };
    if (tz === 'utc') {
        opts.timeZone = 'UTC';
    }
    return d.toLocaleDateString('en-US', opts);
};

window.getIsoDateKeyWithTz = function(datetimeStr) {
    if (!datetimeStr) return '';
    let s = String(datetimeStr).trim();
    if (!s.includes('Z') && !s.includes('+') && !s.includes('T')) {
        s = s.replace(' ', 'T') + 'Z';
    } else if (!s.includes('Z') && !s.includes('+')) {
        s = s + 'Z';
    }
    const d = new Date(s);
    if (isNaN(d.getTime())) return datetimeStr.slice(0, 10);

    const tz = window.getAppTimezone();
    if (tz === 'utc') {
        return d.toISOString().slice(0, 10);
    }

    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
};

// ─── Global Custom Confirmation Dialog (Zero Native JS confirm Popups) ───
window.showConfirmDialog = function({
    title = 'Confirm Action',
    message = 'Are you sure you want to proceed?',
    confirmText = 'Confirm',
    cancelText = 'Cancel',
    isDanger = true
} = {}) {
    return new Promise((resolve) => {
        const modal = document.getElementById('globalConfirmModal');
        const backdrop = document.getElementById('globalConfirmBackdrop');
        const titleEl = document.getElementById('globalConfirmTitle');
        const msgEl = document.getElementById('globalConfirmMessage');
        const acceptBtn = document.getElementById('globalConfirmAcceptBtn');
        const cancelBtn = document.getElementById('globalConfirmCancelBtn');

        if (!modal || !backdrop) {
            resolve(false);
            return;
        }

        if (titleEl) titleEl.textContent = title;
        if (msgEl) msgEl.textContent = message;
        if (acceptBtn) {
            acceptBtn.textContent = confirmText;
            acceptBtn.className = isDanger ? 'btn-delete' : 'btn-save';
        }
        if (cancelBtn) cancelBtn.textContent = cancelText;

        function cleanup(result) {
            modal.classList.remove('open');
            backdrop.classList.remove('open');
            acceptBtn.removeEventListener('click', onAccept);
            cancelBtn.removeEventListener('click', onCancel);
            backdrop.removeEventListener('click', onCancel);
            resolve(result);
        }

        function onAccept() { cleanup(true); }
        function onCancel() { cleanup(false); }

        acceptBtn.addEventListener('click', onAccept);
        cancelBtn.addEventListener('click', onCancel);
        backdrop.addEventListener('click', onCancel);

        backdrop.classList.add('open');
        modal.classList.add('open');
    });
};
