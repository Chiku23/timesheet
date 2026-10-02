// ==========================================
// Dashboard / Home Page JavaScript
// Detailed Log View & Weekly Timesheet Matrix
// ==========================================

document.addEventListener('DOMContentLoaded', () => {
    // ─── State ───
    let allLogs = [];
    let deleteLogId = null;
    let matrixWeekOffset = 0; // 0 = current week, -1 = last week, etc.
    let configuredWeeklyGoalHours = 40;
    let currentPage = 1;
    let pageSize = 10;

    // ─── DOM References ───
    const logsTableBody = document.getElementById('logsTableBody');
    const logsEmpty = document.getElementById('logsEmpty');
    const logsTableWrapper = document.querySelector('.logs-table-wrapper');
    const logsPagination = document.getElementById('logsPagination');
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationButtons = document.getElementById('paginationButtons');
    const pageSizeSelect = document.getElementById('pageSizeSelect');

    // KPI Elements
    const kpiTodayHours = document.getElementById('kpiTodayHours');
    const kpiWeekHours = document.getElementById('kpiWeekHours');
    const kpiTotalLogs = document.getElementById('kpiTotalLogs');
    const kpiSourceSplit = document.getElementById('kpiSourceSplit');
    const goalTitle = document.getElementById('goalTitle');
    const goalStats = document.getElementById('goalStats');
    const goalFill = document.getElementById('goalFill');
    const dashGoalCard = document.getElementById('dashGoalCard');

    if (dashGoalCard && dashGoalCard.dataset.goalHours) {
        configuredWeeklyGoalHours = Number(dashGoalCard.dataset.goalHours) || 40;
    }

    // View Switcher Tabs
    const tabListView = document.getElementById('tabListView');
    const tabMatrixView = document.getElementById('tabMatrixView');
    const listViewPanel = document.getElementById('listViewPanel');
    const matrixViewPanel = document.getElementById('matrixViewPanel');

    // Filter Toolbar
    const logSearchInput = document.getElementById('logSearchInput');
    const logFilterProject = document.getElementById('logFilterProject');
    const logFilterType = document.getElementById('logFilterType');

    // Matrix Elements
    const prevWeekBtn = document.getElementById('prevWeekBtn');
    const nextWeekBtn = document.getElementById('nextWeekBtn');
    const currentWeekBtn = document.getElementById('currentWeekBtn');
    const matrixWeekLabel = document.getElementById('matrixWeekLabel');
    const matrixHeaderRow = document.getElementById('matrixHeaderRow');
    const matrixTableBody = document.getElementById('matrixTableBody');
    const matrixTableFoot = document.getElementById('matrixTableFoot');

    // Manual Log Modal
    const logModal = document.getElementById('logModal');
    const logModalBackdrop = document.getElementById('logModalBackdrop');
    const logModalTitle = document.getElementById('logModalTitle');
    const logForm = document.getElementById('logForm');
    const logEditId = document.getElementById('logEditId');
    const logTask = document.getElementById('logTask');
    const logProject = document.getElementById('logProject');
    const manualDate = document.getElementById('manualDate');
    const manualStartTime = document.getElementById('manualStartTime');
    const manualEndTime = document.getElementById('manualEndTime');
    const durationPreviewValue = document.getElementById('durationPreviewValue');
    const formErrorMsg = document.getElementById('formErrorMsg');
    const openAddBtn = document.getElementById('openAddLogModal');
    const closeLogBtn = document.getElementById('closeLogModal');
    const cancelLogBtn = document.getElementById('cancelLogModal');

    // Delete Confirm Modal
    const confirmModal = document.getElementById('confirmModal');
    const confirmBackdrop = document.getElementById('confirmBackdrop');
    const confirmCancel = document.getElementById('confirmCancel');
    const confirmDelete = document.getElementById('confirmDelete');

    // ─── Format Duration helper: "45s", "15m", "1h 30m", "2h" ───
    function formatDuration(totalSeconds) {
        totalSeconds = Math.max(0, Math.round(totalSeconds));
        if (totalSeconds === 0) return '0m';
        if (totalSeconds < 60) return `${totalSeconds}s`;

        const h = Math.floor(totalSeconds / 3600);
        const m = Math.floor((totalSeconds % 3600) / 60);

        if (h > 0 && m > 0) return `${h}h ${m}m`;
        if (h > 0) return `${h}h`;
        return `${m}m`;
    }

    // ─── Format Date (Respecting Active Timezone) ───
    function formatDateDisplay(dateStr) {
        if (!dateStr) return '';
        const todayStr = window.getIsoDateKeyWithTz ? window.getIsoDateKeyWithTz(new Date().toISOString()) : new Date().toISOString().slice(0, 10);
        
        const yest = new Date();
        yest.setDate(yest.getDate() - 1);
        const yestStr = window.getIsoDateKeyWithTz ? window.getIsoDateKeyWithTz(yest.toISOString()) : yest.toISOString().slice(0, 10);

        if (dateStr === todayStr) return 'Today';
        if (dateStr === yestStr) return 'Yesterday';

        if (window.formatDateWithTz) {
            return window.formatDateWithTz(dateStr + 'T00:00:00Z');
        }
        const d = new Date(dateStr + 'T00:00:00');
        return d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
    }

    // ─── Format Time (Respecting Active Timezone) ───
    function formatTime(datetimeStr) {
        if (!datetimeStr) return '-';
        if (window.formatTimeWithTz) {
            return window.formatTimeWithTz(datetimeStr);
        }
        const d = new Date(datetimeStr);
        return d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
    }

    // ─── Escape HTML ───
    function escHtml(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

    // ─── Fetch Logs ───
    async function loadLogs(preservePage = false) {
        try {
            const res = await fetch('/api/logs');
            const data = await res.json();

            if (data.success) {
                allLogs = data.logs || [];
                applyFiltersAndRender(!preservePage);
                updateKPIs(allLogs);
                renderMatrixView();
            }
        } catch (err) {
            console.error('Failed to load logs:', err);
        }
    }

    // ─── Helper: Pagination Range Generation ───
    function getPageRange(current, total) {
        if (total <= 7) {
            return Array.from({ length: total }, (_, i) => i + 1);
        }
        const pages = new Set();
        pages.add(1);
        pages.add(total);
        for (let i = Math.max(1, current - 1); i <= Math.min(total, current + 1); i++) {
            pages.add(i);
        }
        return Array.from(pages).sort((a, b) => a - b);
    }

    // ─── Render Pagination Controls ───
    function renderPaginationControls(totalItems, totalPages, fromItem, toItem) {
        if (!paginationInfo || !paginationButtons) return;

        paginationInfo.textContent = `Showing ${fromItem}–${toItem} of ${totalItems} entries`;

        let btnsHtml = '';

        // Previous button
        btnsHtml += `
            <button type="button" class="page-btn page-btn-prev" data-page="${currentPage - 1}" ${currentPage <= 1 ? 'disabled' : ''} aria-label="Previous page">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
            </button>
        `;

        const pageNumbers = getPageRange(currentPage, totalPages);
        let prevNum = 0;

        pageNumbers.forEach(p => {
            if (prevNum && p - prevNum > 1) {
                btnsHtml += `<span class="page-ellipsis">…</span>`;
            }
            btnsHtml += `
                <button type="button" class="page-btn ${p === currentPage ? 'active' : ''}" data-page="${p}" ${p === currentPage ? 'aria-current="page"' : ''}>
                    ${p}
                </button>
            `;
            prevNum = p;
        });

        // Next button
        btnsHtml += `
            <button type="button" class="page-btn page-btn-next" data-page="${currentPage + 1}" ${currentPage >= totalPages ? 'disabled' : ''} aria-label="Next page">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>
        `;

        paginationButtons.innerHTML = btnsHtml;
    }

    // ─── Filter & Render List View with Pagination ───
    function applyFiltersAndRender(resetPage = true) {
        if (!logsTableBody) return;

        if (resetPage) {
            currentPage = 1;
        }

        const search = (logSearchInput ? logSearchInput.value : '').toLowerCase().trim();
        const projectFilter = logFilterProject ? logFilterProject.value : '';
        const typeFilter = logFilterType ? logFilterType.value : '';

        const filtered = allLogs.filter(log => {
            if (search && !log.task_description.toLowerCase().includes(search)) return false;
            if (projectFilter && String(log.project_id) !== projectFilter) return false;
            if (typeFilter && log.entry_type !== typeFilter) return false;
            return true;
        });

        const totalItems = filtered.length;

        if (totalItems === 0) {
            logsTableBody.innerHTML = '';
            if (logsTableWrapper) logsTableWrapper.style.display = 'none';
            if (logsEmpty) logsEmpty.style.display = 'flex';
            if (logsPagination) logsPagination.style.display = 'none';
            return;
        }

        if (logsTableWrapper) logsTableWrapper.style.display = 'block';
        if (logsEmpty) logsEmpty.style.display = 'none';
        if (logsPagination) logsPagination.style.display = 'flex';

        const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
        if (currentPage > totalPages) {
            currentPage = totalPages;
        }
        if (currentPage < 1) {
            currentPage = 1;
        }

        const startIndex = (currentPage - 1) * pageSize;
        const endIndex = Math.min(startIndex + pageSize, totalItems);
        const pagedLogs = filtered.slice(startIndex, endIndex);

        let html = '';
        let currentDate = '';

        pagedLogs.forEach(log => {
            const logDateKey = window.getIsoDateKeyWithTz ? window.getIsoDateKeyWithTz(log.start_time) : (log.date || log.start_time.slice(0, 10));

            if (logDateKey !== currentDate) {
                currentDate = logDateKey;
                html += `
                    <tr class="date-group-row">
                        <td colspan="7">
                            <span class="date-group-badge">${formatDateDisplay(logDateKey)}</span>
                            <span class="date-sub-text">${logDateKey}</span>
                        </td>
                    </tr>
                `;
            }

            const projectBadge = log.project_name
                ? `<span class="project-badge" title="${escHtml(log.project_name)}"><span class="project-dot" style="background:${log.project_color || '#6366f1'}"></span>${escHtml(log.project_name)}</span>`
                : '<span class="text-muted">—</span>';

            let sourceBadge = '';
            if (log.entry_type === 'timer') {
                if (log.is_edited) {
                    sourceBadge = `<span class="entry-badge entry-timer entry-edited" title="Recorded with live timer, later edited">
                         <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                         Timer <span class="badge-edited-mark">Edited</span>
                       </span>`;
                } else {
                    sourceBadge = `<span class="entry-badge entry-timer" title="Recorded with live timer">
                         <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                         Timer
                       </span>`;
                }
            } else {
                if (log.is_edited) {
                    sourceBadge = `<span class="entry-badge entry-manual entry-edited" title="Logged manually, later edited">
                         <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                         Manual <span class="badge-edited-mark">Edited</span>
                       </span>`;
                } else {
                    sourceBadge = `<span class="entry-badge entry-manual" title="Logged manually">
                         <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                         Manual
                       </span>`;
                }
            }

            const timeRange = `${formatTime(log.start_time)} – ${formatTime(log.end_time)}`;

            html += `
                <tr data-log-id="${log.id}">
                    <td class="col-date">${logDateKey}</td>
                    <td class="col-task" title="${escHtml(log.task_description)}">${escHtml(log.task_description)}</td>
                    <td>${projectBadge}</td>
                    <td>${sourceBadge}</td>
                    <td class="col-timerange">${timeRange}</td>
                    <td><span class="duration-badge">${log.formatted_duration}</span></td>
                    <td class="col-actions">
                        <button type="button" class="btn-action btn-action-edit" onclick="editLog(${log.id})" title="Edit Log">
                            Edit
                        </button>
                        <button type="button" class="btn-action btn-action-del" onclick="deleteLog(${log.id})" title="Delete Log">
                            Delete
                        </button>
                    </td>
                </tr>
            `;
        });

        logsTableBody.innerHTML = html;
        renderPaginationControls(totalItems, totalPages, startIndex + 1, endIndex);
    }

    // ─── Update KPI Summary Cards & Weekly Goal ───
    function updateKPIs(logs) {
        const today = window.getIsoDateKeyWithTz ? window.getIsoDateKeyWithTz(new Date().toISOString()) : new Date().toISOString().slice(0, 10);
        const weekMonday = getMonday(new Date()).toISOString().slice(0, 10);

        let todaySecs = 0;
        let weekSecs = 0;
        let timerCount = 0;
        let manualCount = 0;

        logs.forEach(log => {
            const logDateKey = window.getIsoDateKeyWithTz ? window.getIsoDateKeyWithTz(log.start_time) : (log.date || log.start_time.slice(0, 10));
            if (logDateKey === today) todaySecs += log.duration_seconds;
            if (logDateKey >= weekMonday) weekSecs += log.duration_seconds;
            if (log.entry_type === 'timer') timerCount++;
            else manualCount++;
        });

        if (kpiTodayHours) kpiTodayHours.textContent = formatDuration(todaySecs);
        if (kpiWeekHours) kpiWeekHours.textContent = formatDuration(weekSecs);
        if (kpiTotalLogs) kpiTotalLogs.textContent = logs.length;
        if (kpiSourceSplit) kpiSourceSplit.textContent = `${timerCount} Timer • ${manualCount} Manual`;

        // Dynamic weekly target hours from workspace settings
        const targetSecs = configuredWeeklyGoalHours * 3600;
        const pct = targetSecs > 0 ? Math.min(100, Math.round((weekSecs / targetSecs) * 100)) : 0;
        if (goalTitle) goalTitle.textContent = `Weekly Target (${configuredWeeklyGoalHours}h)`;
        if (goalStats) goalStats.textContent = `${formatDuration(weekSecs)} / ${configuredWeeklyGoalHours}h (${pct}%)`;
        if (goalFill) goalFill.style.width = `${pct}%`;
    }

    // ─── Helper: Get Monday of a Date ───
    function getMonday(d) {
        const date = new Date(d);
        const day = date.getDay();
        const diff = date.getDate() - day + (day === 0 ? -6 : 1);
        date.setDate(diff);
        date.setHours(0, 0, 0, 0);
        return date;
    }

    // ─── Weekly Timesheet Matrix View ───
    function renderMatrixView() {
        if (!matrixTableBody) return;

        // Determine the Monday for current matrix offset
        const targetDate = new Date();
        targetDate.setDate(targetDate.getDate() + (matrixWeekOffset * 7));
        const monday = getMonday(targetDate);

        // Generate 7 days (Mon - Sun)
        const weekDays = [];
        for (let i = 0; i < 7; i++) {
            const d = new Date(monday);
            d.setDate(monday.getDate() + i);
            weekDays.push({
                iso: d.toISOString().slice(0, 10),
                dayName: d.toLocaleDateString('en-US', { weekday: 'short' }),
                displayDay: d.getDate(),
                monthName: d.toLocaleDateString('en-US', { month: 'short' })
            });
        }

        const startStr = `${weekDays[0].monthName} ${weekDays[0].displayDay}`;
        const endStr = `${weekDays[6].monthName} ${weekDays[6].displayDay}, ${weekDays[6].iso.slice(0, 4)}`;
        if (matrixWeekLabel) {
            matrixWeekLabel.textContent = matrixWeekOffset === 0 ? `This Week (${startStr} – ${endStr})` : `${startStr} – ${endStr}`;
        }

        const todayIso = window.getIsoDateKeyWithTz ? window.getIsoDateKeyWithTz(new Date().toISOString()) : new Date().toISOString().slice(0, 10);

        // Render Matrix Table Header
        let headerHtml = `<th class="col-project">Project</th>`;
        weekDays.forEach(day => {
            const isToday = day.iso === todayIso;
            headerHtml += `<th class="${isToday ? 'today-col' : ''}">${day.dayName} <span class="header-day-num">${day.displayDay}</span></th>`;
        });
        headerHtml += `<th class="col-total">Total</th>`;
        if (matrixHeaderRow) matrixHeaderRow.innerHTML = headerHtml;

        // Group logs within this week by Project
        const weekIsoSet = new Set(weekDays.map(w => w.iso));
        const projectMap = {}; // { 'projectName': { color, days: { 'YYYY-MM-DD': secs }, totalSecs } }
        const dailySum = {}; // { 'YYYY-MM-DD': secs }
        weekDays.forEach(w => dailySum[w.iso] = 0);
        let grandTotalSecs = 0;

        allLogs.forEach(log => {
            const logDateKey = window.getIsoDateKeyWithTz ? window.getIsoDateKeyWithTz(log.start_time) : (log.date || log.start_time.slice(0, 10));
            if (!weekIsoSet.has(logDateKey)) return;

            const pName = log.project_name || 'No Project';
            const pColor = log.project_color || '#6b7280';

            if (!projectMap[pName]) {
                projectMap[pName] = {
                    color: pColor,
                    days: {},
                    totalSecs: 0
                };
                weekDays.forEach(w => projectMap[pName].days[w.iso] = 0);
            }

            projectMap[pName].days[logDateKey] += log.duration_seconds;
            projectMap[pName].totalSecs += log.duration_seconds;
            dailySum[logDateKey] += log.duration_seconds;
            grandTotalSecs += log.duration_seconds;
        });

        const projectEntries = Object.entries(projectMap);

        if (projectEntries.length === 0) {
            matrixTableBody.innerHTML = `<tr><td colspan="9" style="text-align:center; padding:32px; color:var(--text-muted);">No time tracked for this week.</td></tr>`;
            if (matrixTableFoot) matrixTableFoot.innerHTML = '';
            return;
        }

        let bodyHtml = '';
        projectEntries.forEach(([pName, data]) => {
            bodyHtml += `<tr>
                <td class="col-project">
                    <span class="project-dot" style="background:${data.color}"></span>
                    <strong>${escHtml(pName)}</strong>
                </td>`;

            weekDays.forEach(day => {
                const secs = data.days[day.iso] || 0;
                const formatted = secs > 0 ? formatDuration(secs) : '—';
                const cellClass = secs > 0 ? 'matrix-cell-active' : 'matrix-cell-empty';
                bodyHtml += `<td class="${cellClass}">${formatted}</td>`;
            });

            bodyHtml += `<td class="col-total"><strong>${formatDuration(data.totalSecs)}</strong></td></tr>`;
        });

        matrixTableBody.innerHTML = bodyHtml;

        // Footer Totals Row
        let footHtml = `<tr>
            <td class="col-project"><strong>Daily Totals</strong></td>`;
        weekDays.forEach(day => {
            const sumSecs = dailySum[day.iso] || 0;
            const formatted = sumSecs > 0 ? formatDuration(sumSecs) : '—';
            footHtml += `<td><strong>${formatted}</strong></td>`;
        });
        footHtml += `<td class="col-total"><strong class="grand-total-val">${formatDuration(grandTotalSecs)}</strong></td></tr>`;

        if (matrixTableFoot) matrixTableFoot.innerHTML = footHtml;
    }

    // ─── Live Duration Calculation in Modal ───
    function updateCalculatedDurationPreview() {
        if (!manualDate || !manualStartTime || !manualEndTime) return;

        const dateVal = manualDate.value;
        const startVal = manualStartTime.value;
        const endVal = manualEndTime.value;

        if (!dateVal || !startVal || !endVal) {
            if (durationPreviewValue) durationPreviewValue.textContent = '0m';
            if (formErrorMsg) formErrorMsg.style.display = 'none';
            return;
        }

        const startDt = new Date(`${dateVal}T${startVal}:00`);
        const endDt = new Date(`${dateVal}T${endVal}:00`);

        if (endDt <= startDt) {
            if (formErrorMsg) {
                formErrorMsg.textContent = 'End time must be later than start time.';
                formErrorMsg.style.display = 'block';
            }
            if (durationPreviewValue) durationPreviewValue.textContent = 'Invalid range';
            return;
        }

        if (formErrorMsg) formErrorMsg.style.display = 'none';
        const diffSecs = Math.max(0, Math.floor((endDt.getTime() - startDt.getTime()) / 1000));
        if (durationPreviewValue) durationPreviewValue.textContent = formatDuration(diffSecs);
    }

    if (manualDate) manualDate.addEventListener('change', updateCalculatedDurationPreview);
    if (manualStartTime) manualStartTime.addEventListener('input', updateCalculatedDurationPreview);
    if (manualEndTime) manualEndTime.addEventListener('input', updateCalculatedDurationPreview);

    // ─── Modal Open / Close Handlers ───
    function openLogModal() {
        if (logModal) logModal.classList.add('open');
        if (logModalBackdrop) logModalBackdrop.classList.add('open');
    }

    function closeLogModalFn() {
        if (logModal) logModal.classList.remove('open');
        if (logModalBackdrop) logModalBackdrop.classList.remove('open');
        if (formErrorMsg) formErrorMsg.style.display = 'none';
    }

    function openAddModal() {
        if (logModalTitle) logModalTitle.textContent = 'Log Time Manually';
        if (logEditId) logEditId.value = '';
        if (logForm) logForm.reset();

        const today = new Date();
        const dateStr = today.toISOString().slice(0, 10);
        if (manualDate) manualDate.value = dateStr;

        // Default 1 hour prior to now
        const nowH = String(today.getHours()).padStart(2, '0');
        const nowM = String(today.getMinutes()).padStart(2, '0');
        const prevH = String((today.getHours() - 1 + 24) % 24).padStart(2, '0');

        if (manualStartTime) manualStartTime.value = `${prevH}:${nowM}`;
        if (manualEndTime) manualEndTime.value = `${nowH}:${nowM}`;

        updateCalculatedDurationPreview();
        openLogModal();
    }

    window.editLog = function (id) {
        const log = allLogs.find(l => l.id === id);
        if (!log) return;

        if (logModalTitle) logModalTitle.textContent = 'Edit Time Log';
        if (logEditId) logEditId.value = log.id;
        if (logTask) logTask.value = log.task_description;
        if (logProject) logProject.value = log.project_id || '';

        const startDt = new Date(log.start_time);
        const tz = window.getAppTimezone ? window.getAppTimezone() : 'local';

        if (tz === 'utc') {
            if (manualDate) manualDate.value = startDt.toISOString().slice(0, 10);
            const uh = String(startDt.getUTCHours()).padStart(2, '0');
            const um = String(startDt.getUTCMinutes()).padStart(2, '0');
            if (manualStartTime) manualStartTime.value = `${uh}:${um}`;

            if (log.end_time) {
                const endDt = new Date(log.end_time);
                const eh = String(endDt.getUTCHours()).padStart(2, '0');
                const em = String(endDt.getUTCMinutes()).padStart(2, '0');
                if (manualEndTime) manualEndTime.value = `${eh}:${em}`;
            }
        } else {
            const ly = startDt.getFullYear();
            const lm = String(startDt.getMonth() + 1).padStart(2, '0');
            const ld = String(startDt.getDate()).padStart(2, '0');
            if (manualDate) manualDate.value = `${ly}-${lm}-${ld}`;
            const lh = String(startDt.getHours()).padStart(2, '0');
            const lmin = String(startDt.getMinutes()).padStart(2, '0');
            if (manualStartTime) manualStartTime.value = `${lh}:${lmin}`;

            if (log.end_time) {
                const endDt = new Date(log.end_time);
                const eh = String(endDt.getHours()).padStart(2, '0');
                const em = String(endDt.getMinutes()).padStart(2, '0');
                if (manualEndTime) manualEndTime.value = `${eh}:${em}`;
            }
        }

        updateCalculatedDurationPreview();
        openLogModal();
    };

    window.deleteLog = function (id) {
        deleteLogId = id;
        if (confirmModal) confirmModal.classList.add('open');
        if (confirmBackdrop) confirmBackdrop.classList.add('open');
    };

    function closeConfirmModal() {
        if (confirmModal) confirmModal.classList.remove('open');
        if (confirmBackdrop) confirmBackdrop.classList.remove('open');
        deleteLogId = null;
    }

    // Modal Events
    if (openAddBtn) openAddBtn.addEventListener('click', openAddModal);
    if (closeLogBtn) closeLogBtn.addEventListener('click', closeLogModalFn);
    if (cancelLogBtn) cancelLogBtn.addEventListener('click', closeLogModalFn);
    if (logModalBackdrop) logModalBackdrop.addEventListener('click', closeLogModalFn);

    if (confirmCancel) confirmCancel.addEventListener('click', closeConfirmModal);
    if (confirmBackdrop) confirmBackdrop.addEventListener('click', closeConfirmModal);

    // Confirm Delete
    if (confirmDelete) {
        confirmDelete.addEventListener('click', async () => {
            if (!deleteLogId) return;

            try {
                const res = await fetch('/api/logs/delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: deleteLogId }),
                });
                const data = await res.json();
                if (data.success) {
                    closeConfirmModal();
                    loadLogs(true);
                } else {
                    alert(data.message || 'Failed to delete log.');
                }
            } catch (err) {
                console.error('Delete error:', err);
            }
        });
    }

    // Form Save (Add or Edit)
    if (logForm) {
        logForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const dateVal = manualDate.value;
            const startVal = manualStartTime.value;
            const endVal = manualEndTime.value;

            const tz = window.getAppTimezone ? window.getAppTimezone() : 'local';
            let startIso, endIso;

            if (tz === 'utc') {
                startIso = `${dateVal}T${startVal}:00Z`;
                endIso = `${dateVal}T${endVal}:00Z`;
            } else {
                const startDt = new Date(`${dateVal}T${startVal}:00`);
                const endDt = new Date(`${dateVal}T${endVal}:00`);
                startIso = startDt.toISOString();
                endIso = endDt.toISOString();
            }

            const startCheck = new Date(startIso);
            const endCheck = new Date(endIso);

            if (endCheck <= startCheck) {
                if (formErrorMsg) {
                    formErrorMsg.textContent = 'End time must be later than start time.';
                    formErrorMsg.style.display = 'block';
                }
                return;
            }

            const isEdit = !!(logEditId && logEditId.value);
            const url = isEdit ? '/api/logs/update' : '/api/logs/add';

            const payload = {
                task_description: logTask.value.trim(),
                project_id: logProject.value || null,
                start_time: startIso,
                end_time: endIso,
            };

            if (isEdit) {
                payload.id = parseInt(logEditId.value, 10);
            }

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();

                if (data.success) {
                    closeLogModalFn();
                    loadLogs();
                } else {
                    alert(data.message || 'Failed to save log.');
                }
            } catch (err) {
                console.error('Save error:', err);
                alert('Failed to save log. Please check your connection.');
            }
        });
    }

    // ─── View Switcher Tabs ───
    if (tabListView && tabMatrixView) {
        tabListView.addEventListener('click', () => {
            tabListView.classList.add('active');
            tabMatrixView.classList.remove('active');
            if (listViewPanel) listViewPanel.style.display = 'block';
            if (matrixViewPanel) matrixViewPanel.style.display = 'none';
        });

        tabMatrixView.addEventListener('click', () => {
            tabMatrixView.classList.add('active');
            tabListView.classList.remove('active');
            if (listViewPanel) listViewPanel.style.display = 'none';
            if (matrixViewPanel) matrixViewPanel.style.display = 'block';
            renderMatrixView();
        });
    }

    // ─── Filter Listeners ───
    if (logSearchInput) logSearchInput.addEventListener('input', () => applyFiltersAndRender(true));
    if (logFilterProject) logFilterProject.addEventListener('change', () => applyFiltersAndRender(true));
    if (logFilterType) logFilterType.addEventListener('change', () => applyFiltersAndRender(true));

    // ─── Pagination Listeners ───
    if (paginationButtons) {
        paginationButtons.addEventListener('click', (e) => {
            const btn = e.target.closest('.page-btn');
            if (!btn || btn.disabled) return;
            const targetPage = parseInt(btn.dataset.page, 10);
            if (!isNaN(targetPage) && targetPage !== currentPage) {
                currentPage = targetPage;
                applyFiltersAndRender(false);
            }
        });
    }

    if (pageSizeSelect) {
        pageSizeSelect.addEventListener('change', (e) => {
            pageSize = parseInt(e.target.value, 10) || 10;
            currentPage = 1;
            applyFiltersAndRender(false);
        });
    }

    // ─── Matrix Week Nav Listeners ───
    if (prevWeekBtn) {
        prevWeekBtn.addEventListener('click', () => {
            matrixWeekOffset--;
            renderMatrixView();
        });
    }
    if (nextWeekBtn) {
        nextWeekBtn.addEventListener('click', () => {
            matrixWeekOffset++;
            renderMatrixView();
        });
    }
    if (currentWeekBtn) {
        currentWeekBtn.addEventListener('click', () => {
            matrixWeekOffset = 0;
            renderMatrixView();
        });
    }

    // ─── Fetch Workspace Settings ───
    async function fetchWorkspaceSettings() {
        try {
            const res = await fetch('/api/settings');
            const data = await res.json();
            if (data.success && data.weekly_goal_hours) {
                configuredWeeklyGoalHours = Number(data.weekly_goal_hours);
                if (allLogs.length > 0) {
                    updateKPIs(allLogs);
                }
            }
        } catch (err) {
            console.error('Failed to load workspace settings:', err);
        }
    }

    // ─── Listen for external log updates and timezone switch ───
    window.addEventListener('timesheet:log-updated', () => {
        loadLogs(true);
    });

    function updateDateBanner() {
        const dashDateBanner = document.getElementById('dashDateBanner');
        if (!dashDateBanner) return;
        const now = new Date();
        const tz = window.getAppTimezone ? window.getAppTimezone() : 'local';
        const opts = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        if (tz === 'utc') {
            opts.timeZone = 'UTC';
            dashDateBanner.textContent = now.toLocaleDateString('en-US', opts) + ' (UTC)';
        } else {
            dashDateBanner.textContent = now.toLocaleDateString('en-US', opts);
        }
    }

    window.addEventListener('timesheet:timezone-changed', () => {
        applyFiltersAndRender(false);
        updateKPIs(allLogs);
        renderMatrixView();
        updateDateBanner();
    });

    // Initial load
    fetchWorkspaceSettings();
    updateDateBanner();
    loadLogs(false);
});
