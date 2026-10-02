<?php
$user = currentUser();
$projects = \Chiku\TimeSheet\Models\Project::orderBy('name')->get();
$weeklyGoalHours = intval(\Chiku\TimeSheet\Models\Setting::get('weekly_goal_hours', 40));
?>

<div class="dashboard">
    <!-- Welcome & Weekly Target Goal Banner -->
    <div class="dash-header">
        <div class="dash-welcome">
            <h2>Welcome back, <?= htmlspecialchars($user->name) ?></h2>
            <p class="dash-date" id="dashDateBanner"><?= date('l, F j, Y') ?></p>
        </div>
        <div class="dash-goal-card" id="dashGoalCard" data-goal-hours="<?= $weeklyGoalHours ?>">
            <div class="goal-header">
                <span class="goal-title" id="goalTitle">Weekly Target (<?= $weeklyGoalHours ?>h)</span>
                <span class="goal-stats" id="goalStats">0m / <?= $weeklyGoalHours ?>h (0%)</span>
            </div>
            <div class="goal-track">
                <div class="goal-fill" id="goalFill" style="width: 0%;"></div>
            </div>
        </div>
    </div>

    <!-- KPI Summary Cards (Clean SVG Icons & Formatted Durations) -->
    <div class="kpi-grid" id="kpiGrid">
        <div class="kpi-card">
            <div class="kpi-icon icon-indigo">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
            <div class="kpi-data">
                <span class="kpi-value" id="kpiTodayHours">0m</span>
                <span class="kpi-label">Today's Tracked Time</span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-emerald">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
            </div>
            <div class="kpi-data">
                <span class="kpi-value" id="kpiWeekHours">0m</span>
                <span class="kpi-label">This Week's Total</span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-amber">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="20" x2="18" y2="10"></line>
                    <line x1="12" y1="20" x2="12" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="14"></line>
                </svg>
            </div>
            <div class="kpi-data">
                <span class="kpi-value" id="kpiTotalLogs">0</span>
                <span class="kpi-label">Total Time Entries</span>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon icon-violet">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                    <polyline points="2 17 12 22 22 17"></polyline>
                    <polyline points="2 12 12 17 22 12"></polyline>
                </svg>
            </div>
            <div class="kpi-data">
                <span class="kpi-value" id="kpiSourceSplit">0 Timer • 0 Manual</span>
                <span class="kpi-label">Tracking Sources</span>
            </div>
        </div>
    </div>

    <!-- Timesheet Main Section -->
    <div class="logs-section">
        <!-- Section Header with View Toggle & Action -->
        <div class="timesheet-header-bar">
            <div class="timesheet-tabs">
                <button type="button" class="tab-btn active" id="tabListView" data-target="listView">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="8" y1="6" x2="21" y2="6"></line>
                        <line x1="8" y1="12" x2="21" y2="12"></line>
                        <line x1="8" y1="18" x2="21" y2="18"></line>
                        <line x1="3" y1="6" x2="3.01" y2="6"></line>
                        <line x1="3" y1="12" x2="3.01" y2="12"></line>
                        <line x1="3" y1="18" x2="3.01" y2="18"></line>
                    </svg>
                    <span>Detailed Log View</span>
                </button>
                <button type="button" class="tab-btn" id="tabMatrixView" data-target="matrixView">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="3" y1="9" x2="21" y2="9"></line>
                        <line x1="3" y1="15" x2="21" y2="15"></line>
                        <line x1="9" y1="3" x2="9" y2="21"></line>
                        <line x1="15" y1="3" x2="15" y2="21"></line>
                    </svg>
                    <span>Weekly Timesheet</span>
                </button>
            </div>

            <div class="timesheet-actions">
                <button class="btn-add-log" id="openAddLogModal">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Log Time Manually</span>
                </button>
            </div>
        </div>

        <!-- 1. Detailed Log View (Table with Search & Filters) -->
        <div class="view-panel" id="listViewPanel">
            <!-- Filter Bar -->
            <div class="logs-filter-toolbar">
                <div class="search-box">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" id="logSearchInput" placeholder="Filter tasks by name...">
                </div>

                <div class="toolbar-dropdowns">
                    <select id="logFilterProject" class="filter-select">
                        <option value="">All Projects</option>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?= $p->id ?>"><?= htmlspecialchars($p->name) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select id="logFilterType" class="filter-select">
                        <option value="">All Sources</option>
                        <option value="timer">Timer Tracked</option>
                        <option value="manual">Manual Entry</option>
                    </select>
                </div>
            </div>

            <div class="logs-table-wrapper">
                <table class="logs-table" id="logsTable">
                    <colgroup>
                        <col style="width: 105px;">
                        <col style="width: auto;">
                        <col style="width: 135px;">
                        <col style="width: 110px;">
                        <col style="width: 185px;">
                        <col style="width: 100px;">
                        <col style="width: 120px;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th style="width: 105px;">Date</th>
                            <th>Task Description</th>
                            <th style="width: 135px;">Project</th>
                            <th style="width: 110px;">Source</th>
                            <th style="width: 185px;">Time Period</th>
                            <th style="width: 100px;">Duration</th>
                            <th style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="logsTableBody">
                        <tr class="date-group-row skeleton-group-row">
                            <td colspan="7"><div class="skeleton-pulse" style="width: 140px; height: 18px;"></div></td>
                        </tr>
                        <?php for ($i = 0; $i < 10; $i++): ?>
                        <tr class="logs-loading-row">
                            <td><div class="skeleton-pulse" style="width: 75px;"></div></td>
                            <td><div class="skeleton-pulse" style="width: 75%;"></div></td>
                            <td><div class="skeleton-pulse" style="width: 90px;"></div></td>
                            <td><div class="skeleton-pulse" style="width: 80px;"></div></td>
                            <td><div class="skeleton-pulse" style="width: 110px;"></div></td>
                            <td><div class="skeleton-pulse" style="width: 50px;"></div></td>
                            <td><div class="skeleton-pulse" style="width: 70px; margin-left: auto;"></div></td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>

            <!-- Detailed Log View Pagination -->
            <div class="logs-pagination" id="logsPagination">
                <div class="pagination-info" id="paginationInfo">
                    Loading entries...
                </div>
                <div class="pagination-controls">
                    <label class="page-size-label" for="pageSizeSelect">
                        <span>Show</span>
                        <select id="pageSizeSelect" class="page-size-select">
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                        <span>per page</span>
                    </label>
                    <div class="pagination-buttons" id="paginationButtons">
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

            <div id="logsEmpty" class="logs-empty" style="display: none;">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <p>No matching time logs found. Start the timer or add a manual entry!</p>
            </div>
        </div>

        <!-- 2. Weekly Timesheet Matrix View -->
        <div class="view-panel" id="matrixViewPanel" style="display: none;">
            <div class="matrix-toolbar">
                <div class="week-nav">
                    <button type="button" class="btn-week-nav" id="prevWeekBtn" title="Previous Week">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </button>
                    <span class="week-label" id="matrixWeekLabel">This Week</span>
                    <button type="button" class="btn-week-nav" id="nextWeekBtn" title="Next Week">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </button>
                    <button type="button" class="btn-week-today" id="currentWeekBtn">Current Week</button>
                </div>
            </div>

            <div class="matrix-table-wrapper">
                <table class="matrix-table" id="matrixTable">
                    <thead>
                        <tr id="matrixHeaderRow">
                            <th class="col-project">Project</th>
                            <th>Mon</th>
                            <th>Tue</th>
                            <th>Wed</th>
                            <th>Thu</th>
                            <th>Fri</th>
                            <th>Sat</th>
                            <th>Sun</th>
                            <th class="col-total">Total</th>
                        </tr>
                    </thead>
                    <tbody id="matrixTableBody">
                        <tr><td colspan="9" style="text-align: center; padding: 24px; color: var(--text-muted);">Loading weekly timesheet...</td></tr>
                    </tbody>
                    <tfoot id="matrixTableFoot">
                        <!-- Filled by JS -->
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add / Edit Manual Log Modal -->
<div class="log-modal-backdrop" id="logModalBackdrop"></div>
<div class="log-modal" id="logModal" role="dialog" aria-labelledby="logModalTitle">
    <div class="log-modal-header">
        <h2 id="logModalTitle">Log Time Manually</h2>
        <button type="button" class="modal-close" id="closeLogModal" aria-label="Close modal">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>

    <form id="logForm" class="log-form">
        <input type="hidden" id="logEditId" value="">

        <div class="form-group">
            <label for="logTask">Task Description</label>
            <input type="text" id="logTask" placeholder="e.g. Design review, Client meeting, Bug fixing" required autocomplete="off">
        </div>

        <div class="form-group">
            <label for="logProject">Project</label>
            <select id="logProject" class="select-project">
                <option value="">No Project</option>
                <?php foreach ($projects as $p): ?>
                    <option value="<?= $p->id ?>"><?= htmlspecialchars($p->name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Date and Time Inputs -->
        <div class="form-group">
            <label for="manualDate">Date</label>
            <input type="date" id="manualDate" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="manualStartTime">Start Time</label>
                <input type="time" id="manualStartTime" required>
            </div>
            <div class="form-group">
                <label for="manualEndTime">End Time</label>
                <input type="time" id="manualEndTime" required>
            </div>
        </div>

        <!-- Real-time Calculated Duration Preview Box -->
        <div class="duration-preview-card" id="durationPreviewCard">
            <span class="preview-label">Calculated Duration:</span>
            <span class="preview-val" id="durationPreviewValue">0m</span>
        </div>
        <div class="form-error-msg" id="formErrorMsg" style="display: none;"></div>

        <div class="form-actions">
            <button type="button" class="btn-cancel" id="cancelLogModal">Cancel</button>
            <button type="submit" class="btn-save" id="saveLogBtn">Save Entry</button>
        </div>
    </form>
</div>

<!-- Delete Confirmation Modal (No Emojis) -->
<div class="confirm-backdrop" id="confirmBackdrop"></div>
<div class="confirm-modal" id="confirmModal" role="dialog">
    <div class="confirm-content">
        <div class="confirm-icon-box">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
        </div>
        <h3>Delete this time log?</h3>
        <p>This action cannot be undone and will permanently remove this entry.</p>
        <div class="confirm-actions">
            <button type="button" class="btn-cancel" id="confirmCancel">Cancel</button>
            <button type="button" class="btn-delete" id="confirmDelete">Delete</button>
        </div>
    </div>
</div>
