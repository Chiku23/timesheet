// ==========================================
// Reports Page JavaScript
// Clean analytics, formatted durations, audit log
// ==========================================

document.addEventListener('DOMContentLoaded', () => {
    const presetBtns = document.querySelectorAll('.preset-btn');
    const customDates = document.getElementById('customDates');
    const reportFrom = document.getElementById('reportFrom');
    const reportTo = document.getElementById('reportTo');
    const applyCustomBtn = document.getElementById('applyCustomRange');
    const reportSourceSelect = document.getElementById('reportSourceSelect');
    const exportCsvBtn = document.getElementById('exportCsv');

    // Summary Elements
    const reportTotalHours = document.getElementById('reportTotalHours');
    const reportAvgDaily = document.getElementById('reportAvgDaily');
    const reportDays = document.getElementById('reportDays');

    // Chart Elements
    const dailyBars = document.getElementById('dailyBars');
    const projectBreakdown = document.getElementById('projectBreakdown');
    const reportAuditBody = document.getElementById('reportAuditBody');

    // Pagination Elements
    const reportLogsPagination = document.getElementById('reportLogsPagination');
    const reportPaginationInfo = document.getElementById('reportPaginationInfo');
    const reportPaginationButtons = document.getElementById('reportPaginationButtons');
    const reportPageSizeSelect = document.getElementById('reportPageSizeSelect');

    let currentFrom = '';
    let currentTo = '';
    let currentType = '';
    let reportLogs = [];
    let reportCurrentPage = 1;
    let reportPageSize = 10;

    // ─── Format Duration Helper: "45s", "15m", "1h 30m", "2h" ───
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

    // ─── Escape HTML ───
    function escHtml(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

    // ─── Date Range Helpers ───
    function getMonday(d) {
        const date = new Date(d);
        const day = date.getDay();
        const diff = date.getDate() - day + (day === 0 ? -6 : 1);
        date.setDate(diff);
        date.setHours(0, 0, 0, 0);
        return date;
    }

    function formatDateISO(d) {
        return d.toISOString().slice(0, 10);
    }

    function setRange(range) {
        const today = new Date();
        let from, to;

        switch (range) {
            case 'week':
                from = getMonday(today);
                to = today;
                break;
            case 'month':
                from = new Date(today.getFullYear(), today.getMonth(), 1);
                to = today;
                break;
            case 'last-month':
                from = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                to = new Date(today.getFullYear(), today.getMonth(), 0);
                break;
            case 'custom':
                if (customDates) customDates.style.display = 'flex';
                return; // Wait for apply button
            default:
                from = getMonday(today);
                to = today;
        }

        if (customDates && range !== 'custom') customDates.style.display = 'none';

        currentFrom = formatDateISO(from);
        currentTo = formatDateISO(to);

        if (reportFrom) reportFrom.value = currentFrom;
        if (reportTo) reportTo.value = currentTo;

        loadReport();
    }

    // ─── Preset Button Listeners ───
    presetBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            presetBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            setRange(btn.dataset.range);
        });
    });

    // ─── Source Filter Listener ───
    if (reportSourceSelect) {
        reportSourceSelect.addEventListener('change', () => {
            currentType = reportSourceSelect.value;
            loadReport();
        });
    }

    // ─── Custom Range Apply ───
    if (applyCustomBtn) {
        applyCustomBtn.addEventListener('click', () => {
            if (reportFrom.value && reportTo.value) {
                currentFrom = reportFrom.value;
                currentTo = reportTo.value;
                loadReport();
            }
        });
    }

    // ─── Load Report Data ───
    async function loadReport() {
        if (!currentFrom || !currentTo) return;

        const params = new URLSearchParams({
            date_from: currentFrom,
            date_to: currentTo,
        });

        if (currentType) {
            params.set('entry_type', currentType);
        }

        try {
            const res = await fetch(`/api/reports?${params.toString()}`);
            const data = await res.json();

            if (data.success) {
                renderSummary(data);
                renderDailyChart(data.daily_totals || {});
                renderProjectBreakdown(data.project_totals || {});
                renderAuditTable(data.logs || []);
            }
        } catch (err) {
            console.error('Report load error:', err);
        }
    }

    // ─── Render Summary Cards ───
    function renderSummary(data) {
        const totalSecs = data.total_seconds || 0;
        const daysCount = Object.keys(data.daily_totals || {}).length;
        const avgDailySecs = daysCount > 0 ? Math.round(totalSecs / daysCount) : 0;

        if (reportTotalHours) reportTotalHours.textContent = formatDuration(totalSecs);
        if (reportAvgDaily) reportAvgDaily.textContent = formatDuration(avgDailySecs);
        if (reportDays) reportDays.textContent = daysCount;
    }

    // ─── Render Daily Bar Chart ───
    function renderDailyChart(dailyTotals) {
        if (!dailyBars) return;

        const entries = Object.entries(dailyTotals);
        if (entries.length === 0) {
            dailyBars.innerHTML = '<p class="chart-empty">No tracked time in this period.</p>';
            return;
        }

        const maxSeconds = Math.max(...entries.map(e => (typeof e[1] === 'object' ? e[1].seconds : e[1])));
        const maxHeight = 150; // px

        dailyBars.classList.toggle('chart-bars-stretch', entries.length <= 7);

        let html = '';
        entries.forEach(([date, val]) => {
            const secs = typeof val === 'object' ? val.seconds : val;
            const displayDuration = formatDuration(secs);
            const barHeight = maxSeconds > 0 ? Math.max(6, (secs / maxSeconds) * maxHeight) : 6;
            const dayLabel = new Date(date + 'T00:00:00').toLocaleDateString('en-US', { weekday: 'short', day: 'numeric' });

            html += `
                <div class="chart-bar-col" title="${dayLabel}: ${displayDuration}">
                    <div class="chart-bar-wrapper">
                        <span class="chart-bar-value">${displayDuration}</span>
                        <div class="chart-bar" style="height: ${barHeight}px;"></div>
                    </div>
                    <span class="chart-bar-label">${dayLabel}</span>
                </div>
            `;
        });

        dailyBars.innerHTML = html;
    }

    // ─── Render Project Breakdown ───
    function renderProjectBreakdown(projectTotals) {
        if (!projectBreakdown) return;

        const entries = Object.entries(projectTotals);
        if (entries.length === 0) {
            projectBreakdown.innerHTML = '<p class="chart-empty">No project time in this period.</p>';
            return;
        }

        // Sort descending by seconds
        entries.sort((a, b) => b[1].seconds - a[1].seconds);
        const maxSeconds = entries[0][1].seconds;

        let html = '';
        entries.forEach(([name, data]) => {
            const displayDuration = formatDuration(data.seconds);
            const pct = maxSeconds > 0 ? Math.max(5, (data.seconds / maxSeconds) * 100) : 5;

            html += `
                <div class="project-bar-row">
                    <div class="project-bar-label">
                        <span class="project-dot" style="background:${data.color};"></span>
                        <span class="project-name-text">${escHtml(name)}</span>
                    </div>
                    <div class="project-bar-track">
                        <div class="project-bar-fill" style="width: ${pct}%; background-color: ${data.color};"></div>
                    </div>
                    <span class="project-bar-hours">${displayDuration}</span>
                </div>
            `;
        });

        projectBreakdown.innerHTML = html;
    }

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

    function renderReportPaginationControls(totalItems, totalPages, fromItem, toItem) {
        if (!reportPaginationInfo || !reportPaginationButtons) return;

        reportPaginationInfo.textContent = `Showing ${fromItem}–${toItem} of ${totalItems} entries`;

        let btnsHtml = `
            <button type="button" class="page-btn page-btn-prev" data-page="${reportCurrentPage - 1}" ${reportCurrentPage <= 1 ? 'disabled' : ''} aria-label="Previous page">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
            </button>
        `;

        const pageNumbers = getPageRange(reportCurrentPage, totalPages);
        let prevNum = 0;

        pageNumbers.forEach(p => {
            if (prevNum && p - prevNum > 1) {
                btnsHtml += `<span class="page-ellipsis">…</span>`;
            }
            btnsHtml += `
                <button type="button" class="page-btn ${p === reportCurrentPage ? 'active' : ''}" data-page="${p}" ${p === reportCurrentPage ? 'aria-current="page"' : ''}>
                    ${p}
                </button>
            `;
            prevNum = p;
        });

        btnsHtml += `
            <button type="button" class="page-btn page-btn-next" data-page="${reportCurrentPage + 1}" ${reportCurrentPage >= totalPages ? 'disabled' : ''} aria-label="Next page">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>
        `;

        reportPaginationButtons.innerHTML = btnsHtml;
    }

    // ─── Render Period Time Logs Table with Pagination ───
    function renderAuditTable(logs, resetPage = true) {
        if (!reportAuditBody) return;

        if (logs) {
            reportLogs = logs;
        }

        if (resetPage) {
            reportCurrentPage = 1;
        }

        const totalItems = reportLogs.length;
        const hasMemberCol = !!document.querySelector('#reportAuditTable th.col-member');
        const colSpan = hasMemberCol ? 6 : 5;

        if (totalItems === 0) {
            reportAuditBody.innerHTML = `<tr><td colspan="${colSpan}" style="text-align:center; padding:24px; color:var(--text-muted);">No entries recorded for this filter.</td></tr>`;
            if (reportLogsPagination) reportLogsPagination.style.display = 'none';
            return;
        }

        if (reportLogsPagination) reportLogsPagination.style.display = 'flex';

        const totalPages = Math.max(1, Math.ceil(totalItems / reportPageSize));
        if (reportCurrentPage > totalPages) reportCurrentPage = totalPages;
        if (reportCurrentPage < 1) reportCurrentPage = 1;

        const startIndex = (reportCurrentPage - 1) * reportPageSize;
        const endIndex = Math.min(startIndex + reportPageSize, totalItems);
        const pagedLogs = reportLogs.slice(startIndex, endIndex);

        let html = '';
        pagedLogs.forEach(log => {
            let sourceBadge = '';
            if (log.entry_type === 'timer') {
                if (log.is_edited) {
                    sourceBadge = `<span class="entry-badge entry-timer entry-edited" title="Recorded with live timer, later edited">
                         <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                         Timer <span class="badge-edited-mark">Edited</span>
                       </span>`;
                } else {
                    sourceBadge = `<span class="entry-badge entry-timer">
                         <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                         Timer
                       </span>`;
                }
            } else {
                if (log.is_edited) {
                    sourceBadge = `<span class="entry-badge entry-manual entry-edited" title="Logged manually, later edited">
                         <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                         Manual <span class="badge-edited-mark">Edited</span>
                       </span>`;
                } else {
                    sourceBadge = `<span class="entry-badge entry-manual">
                         <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                         Manual
                       </span>`;
                }
            }

            const projectBadge = log.project_name
                ? `<span class="project-badge"><span class="project-dot" style="background:${log.project_color || '#6366f1'}"></span>${escHtml(log.project_name)}</span>`
                : '<span class="text-muted">—</span>';

            const displayDate = (log.start_time && typeof window.getIsoDateKeyWithTz === 'function')
                ? window.getIsoDateKeyWithTz(log.start_time)
                : log.date;

            html += `
                <tr>
                    <td>${displayDate}</td>
                    ${hasMemberCol ? `<td><strong>${escHtml(log.user_name || 'Member')}</strong></td>` : ''}
                    <td>${projectBadge}</td>
                    <td>${escHtml(log.task_description)}</td>
                    <td>${sourceBadge}</td>
                    <td><span class="duration-badge">${log.formatted_duration}</span></td>
                </tr>
            `;
        });

        reportAuditBody.innerHTML = html;
        renderReportPaginationControls(totalItems, totalPages, startIndex + 1, endIndex);
    }

    // ─── Export CSV ───
    if (exportCsvBtn) {
        exportCsvBtn.addEventListener('click', () => {
            if (!currentFrom || !currentTo) {
                alert('Please select a date range first.');
                return;
            }
            let url = `/api/export/csv?date_from=${currentFrom}&date_to=${currentTo}`;
            if (currentType) url += `&entry_type=${currentType}`;
            window.location.href = url;
        });
    }

    // ─── Pagination Listeners ───
    if (reportPaginationButtons) {
        reportPaginationButtons.addEventListener('click', (e) => {
            const btn = e.target.closest('.page-btn');
            if (!btn || btn.disabled) return;
            const targetPage = parseInt(btn.dataset.page, 10);
            if (!isNaN(targetPage) && targetPage !== reportCurrentPage) {
                reportCurrentPage = targetPage;
                renderAuditTable(null, false);
            }
        });
    }

    if (reportPageSizeSelect) {
        reportPageSizeSelect.addEventListener('change', (e) => {
            reportPageSize = parseInt(e.target.value, 10) || 10;
            reportCurrentPage = 1;
            renderAuditTable(null, false);
        });
    }

    // ─── Timezone Switch Listener ───
    window.addEventListener('timesheet:timezone-changed', () => {
        loadReport();
    });

    // Initial load: This Week
    setRange('week');
});
