<?php
$user = currentUser();
$allUsers = \Chiku\TimeSheet\Models\User::orderBy('name')->get();
$projects = \Chiku\TimeSheet\Models\Project::orderBy('name')->get();
?>

<div class="admin-page">
    <div class="admin-header">
        <div class="admin-title-zone">
            <svg class="page-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
            </svg>
            <div>
                <h2>Administration Panel</h2>
                <p class="admin-subtitle">Team oversight, company projects, and organizational timesheet audit</p>
            </div>
        </div>
    </div>

    <!-- Admin KPI Summary Cards (Formatted durations, no gradients) -->
    <div class="admin-kpi-grid" id="adminKpiGrid">
        <div class="admin-kpi accent-indigo">
            <span class="admin-kpi-value" id="adminTotalHours">—</span>
            <span class="admin-kpi-label">Total Company Tracked Time</span>
        </div>
        <div class="admin-kpi accent-emerald">
            <span class="admin-kpi-value" id="adminWeekHours">—</span>
            <span class="admin-kpi-label">This Week's Team Total</span>
        </div>
        <div class="admin-kpi accent-amber">
            <span class="admin-kpi-value" id="adminActiveUsers">—</span>
            <span class="admin-kpi-label">Active Team Members</span>
        </div>
        <div class="admin-kpi accent-violet">
            <span class="admin-kpi-value" id="adminTotalProjects">—</span>
            <span class="admin-kpi-label">Active Client Projects</span>
        </div>
    </div>

    <!-- Two-Column Grid: Team Members & Projects Management -->
    <div class="admin-grid-two">
        <!-- Team Members Section -->
        <div class="admin-section">
            <div class="admin-section-header">
                <div class="section-title-wrap">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <h3>Team Members</h3>
                </div>
            </div>
            <div class="admin-table-wrapper">
                <table class="admin-table" id="teamTable">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Role</th>
                            <th>Entries</th>
                            <th>Total Time</th>
                        </tr>
                    </thead>
                    <tbody id="teamTableBody">
                        <tr><td colspan="4" style="text-align:center; padding:24px; color:var(--text-muted);">Loading team data...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Project Management Section (Admin Only Feature) -->
        <div class="admin-section">
            <div class="admin-section-header">
                <div class="section-title-wrap">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <h3>Company Projects</h3>
                </div>
                <button type="button" class="btn-create-project" id="openAddProjectModal">+ New Project</button>
            </div>
            <div class="admin-table-wrapper">
                <table class="admin-table" id="projectsTable">
                    <thead>
                        <tr>
                            <th>Project Name</th>
                            <th>Color</th>
                            <th>Total Tracked Time</th>
                        </tr>
                    </thead>
                    <tbody id="projectsTableBody">
                        <tr><td colspan="3" style="text-align:center; padding:24px; color:var(--text-muted);">Loading projects...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Team Time Logs Inspector / Audit Trail -->
    <div class="admin-section">
        <div class="admin-section-header">
            <div class="section-title-wrap">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="8" y1="6" x2="21" y2="6"></line>
                    <line x1="8" y1="12" x2="21" y2="12"></line>
                    <line x1="8" y1="18" x2="21" y2="18"></line>
                    <line x1="3" y1="6" x2="3.01" y2="6"></line>
                    <line x1="3" y1="12" x2="3.01" y2="12"></line>
                    <line x1="3" y1="18" x2="3.01" y2="18"></line>
                </svg>
                <h3>Team Audit Logs</h3>
            </div>
            <div class="admin-filters">
                <select id="adminFilterUser" class="admin-select">
                    <option value="">All Team Members</option>
                    <?php foreach ($allUsers as $u): ?>
                        <option value="<?= $u->id ?>"><?= htmlspecialchars($u->name) ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="adminFilterType" class="admin-select">
                    <option value="">All Sources</option>
                    <option value="timer">Timer Tracked</option>
                    <option value="manual">Manual Entry</option>
                </select>

                <div class="admin-date-group">
                    <input type="date" id="adminFilterFrom" class="admin-input" value="<?= date('Y-m-d', strtotime('-14 days')) ?>">
                    <span class="date-sep">to</span>
                    <input type="date" id="adminFilterTo" class="admin-input" value="<?= date('Y-m-d') ?>">
                </div>

                <button type="button" class="btn-filter" id="adminFilterBtn">Filter Logs</button>
            </div>
        </div>
        <div class="admin-table-wrapper">
            <table class="admin-table" id="adminLogsTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Member Name</th>
                        <th>Task Description</th>
                        <th>Project</th>
                        <th>Source</th>
                        <th>Duration</th>
                    </tr>
                </thead>
                <tbody id="adminLogsBody">
                    <tr><td colspan="6" style="text-align:center; padding:24px; color:var(--text-muted);">Loading team logs...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Project Modal -->
<div class="log-modal-backdrop" id="projectModalBackdrop"></div>
<div class="log-modal" id="projectModal" role="dialog" aria-labelledby="projectModalTitle">
    <div class="log-modal-header">
        <h2 id="projectModalTitle">Add Company Project</h2>
        <button type="button" class="modal-close" id="closeProjectModal" aria-label="Close modal">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>
    <form id="projectForm" class="log-form">
        <div class="form-group">
            <label for="projectNameInput">Project Name</label>
            <input type="text" id="projectNameInput" placeholder="e.g. Website Redesign, Mobile App" required autocomplete="off">
        </div>
        <div class="form-group">
            <label for="projectColorInput">Color Accent</label>
            <div class="color-picker-row">
                <input type="color" id="projectColorInput" value="#4f46e5" class="color-picker-swatch">
                <span class="color-hex-preview" id="colorHexPreview">#4f46e5</span>
            </div>
        </div>
        <div class="form-actions">
            <button type="button" class="btn-cancel" id="cancelProjectModal">Cancel</button>
            <button type="submit" class="btn-save">Create Project</button>
        </div>
    </form>
</div>
