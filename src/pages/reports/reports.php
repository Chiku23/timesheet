<?php
$user = currentUser();
$projects = \Chiku\TimeSheet\Models\Project::orderBy('name')->get();
?>

<div class="reports-page">
    <div class="reports-header">
        <div class="reports-title-zone">
            <svg class="page-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="20" x2="18" y2="10"></line>
                <line x1="12" y1="20" x2="12" y2="4"></line>
                <line x1="6" y1="20" x2="6" y2="14"></line>
            </svg>
            <div>
                <h2>Reports & Analytics</h2>
                <p class="reports-subtitle">Time distribution, project breakdowns, and exportable timesheet reports</p>
            </div>
        </div>
    </div>

    <!-- Filter Controls -->
    <div class="reports-controls">
        <div class="report-filter-row">
            <div class="report-presets">
                <button type="button" class="preset-btn active" data-range="week">This Week</button>
                <button type="button" class="preset-btn" data-range="month">This Month</button>
                <button type="button" class="preset-btn" data-range="last-month">Last Month</button>
                <button type="button" class="preset-btn" data-range="custom">Custom Range</button>
            </div>

            <div class="report-actions-group">
                <div class="report-source-filter">
                    <select id="reportSourceSelect" class="filter-select">
                        <option value="">All Sources</option>
                        <option value="timer">Timer Tracked Only</option>
                        <option value="manual">Manual Entries Only</option>
                    </select>
                </div>
                <button type="button" class="btn-export btn-csv" id="exportCsv">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    <span>Export CSV</span>
                </button>
            </div>
        </div>

        <div class="report-dates" id="customDates" style="display:none;">
            <div class="date-input-group">
                <label for="reportFrom">From</label>
                <input type="date" id="reportFrom" class="admin-input">
            </div>
            <span class="date-separator">to</span>
            <div class="date-input-group">
                <label for="reportTo">To</label>
                <input type="date" id="reportTo" class="admin-input">
            </div>
            <button type="button" class="btn-filter" id="applyCustomRange">Apply Filter</button>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="report-summary-grid" id="reportSummaryGrid">
        <div class="report-summary-card">
            <span class="report-summary-value" id="reportTotalHours">0m</span>
            <span class="report-summary-label">Total Tracked Time</span>
        </div>
        <div class="report-summary-card">
            <span class="report-summary-value" id="reportAvgDaily">0m</span>
            <span class="report-summary-label">Daily Average</span>
        </div>
        <div class="report-summary-card">
            <span class="report-summary-value" id="reportDays">0</span>
            <span class="report-summary-label">Days Logged</span>
        </div>
    </div>

    <!-- Visual Charts Grid (Daily Distribution + Project Breakdown) -->
    <div class="reports-charts-grid">
        <!-- Bar Chart: Daily Hours -->
        <div class="report-section">
            <div class="section-title-row">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <h3>Daily Tracked Time</h3>
            </div>
            <div class="chart-container" id="dailyChartContainer">
                <div class="chart-bars" id="dailyBars">
                    <p class="chart-empty">No data recorded for this period.</p>
                </div>
            </div>
        </div>

        <!-- Project Breakdown -->
        <div class="report-section">
            <div class="section-title-row">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                    <line x1="7" y1="7" x2="7.01" y2="7"></line>
                </svg>
                <h3>Time by Project</h3>
            </div>
            <div class="project-breakdown" id="projectBreakdown">
                <p class="chart-empty">No data recorded for this period.</p>
            </div>
        </div>
    </div>

    <!-- Detailed Audit Records Table -->
    <div class="report-section report-table-section">
        <div class="section-title-row">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="8" y1="6" x2="21" y2="6"></line>
                <line x1="8" y1="12" x2="21" y2="12"></line>
                <line x1="8" y1="18" x2="21" y2="18"></line>
                <line x1="3" y1="6" x2="3.01" y2="6"></line>
                <line x1="3" y1="12" x2="3.01" y2="12"></line>
                <line x1="3" y1="18" x2="3.01" y2="18"></line>
            </svg>
            <h3>Period Time Logs</h3>
        </div>
        <div class="report-table-wrapper">
            <table class="report-audit-table" id="reportAuditTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <?php if ($user && $user->isAdmin()): ?>
                            <th class="col-member">Member</th>
                        <?php endif; ?>
                        <th>Project</th>
                        <th>Task Description</th>
                        <th>Source</th>
                        <th>Duration</th>
                    </tr>
                </thead>
                <tbody id="reportAuditBody">
                    <tr><td colspan="<?= ($user && $user->isAdmin()) ? 6 : 5 ?>" style="text-align: center; padding: 24px; color: var(--text-muted);">Loading period logs...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Period Time Logs Pagination -->
        <div class="logs-pagination" id="reportLogsPagination">
            <div class="pagination-info" id="reportPaginationInfo">
                Loading entries...
            </div>
            <div class="pagination-controls">
                <label class="page-size-label" for="reportPageSizeSelect">
                    <span>Show</span>
                    <select id="reportPageSizeSelect" class="page-size-select">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <span>per page</span>
                </label>
                <div class="pagination-buttons" id="reportPaginationButtons">
                    <button type="button" class="page-btn page-btn-prev" disabled aria-label="Previous page">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                    </button>
                    <button type="button" class="page-btn active" disabled>1</button>
                    <button type="button" class="page-btn page-btn-next" disabled aria-label="Next page">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
