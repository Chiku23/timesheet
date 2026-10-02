<?php
$user = currentUser();
$companyName = \Chiku\TimeSheet\Models\Setting::get('company_name', 'TimeSheet Workspace');
$weeklyGoalHours = intval(\Chiku\TimeSheet\Models\Setting::get('weekly_goal_hours', 40));
$projects = \Chiku\TimeSheet\Models\Project::withCount('timeLogs')->orderBy('name')->get();
?>

<div class="settings-page">
    <div class="settings-header">
        <div class="settings-title-zone">
            <svg class="page-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="3"></circle>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
            </svg>
            <div>
                <h2>Workspace & System Settings</h2>
                <p class="settings-subtitle">Manage organization tracking targets, operational goals, and client projects</p>
            </div>
        </div>
    </div>

    <!-- Alert / Toast Container -->
    <div id="settingsToast" class="settings-toast" style="display: none;"></div>

    <div class="settings-grid">
        <!-- Card 1: Workspace & Goals Configuration -->
        <div class="settings-card">
            <div class="card-header">
                <div class="card-title-group">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                    </svg>
                    <h3>Company & Goal Settings</h3>
                </div>
            </div>
            <div class="card-body">
                <form id="workspaceSettingsForm" class="settings-form">
                    <div class="form-group">
                        <label for="companyNameInput">Workspace / Company Name</label>
                        <input type="text" id="companyNameInput" class="form-control" value="<?= htmlspecialchars($companyName) ?>" required placeholder="e.g. Acme Corporation">
                        <span class="field-hint">Displayed across navigation and generated timesheet audit reports.</span>
                    </div>

                    <div class="form-group">
                        <label for="weeklyGoalInput">Weekly Work Target (Hours)</label>
                        <div class="input-with-addons">
                            <input type="number" id="weeklyGoalInput" class="form-control" value="<?= $weeklyGoalHours ?>" min="1" max="168" required>
                            <span class="input-addon">Hours / Week</span>
                        </div>
                        <span class="field-hint">Determines the weekly completion metric displayed on all team dashboards.</span>

                        <div class="goal-presets">
                            <button type="button" class="preset-btn" data-hours="35">35h (Standard)</button>
                            <button type="button" class="preset-btn" data-hours="40">40h (Full Time)</button>
                            <button type="button" class="preset-btn" data-hours="45">45h (Extended)</button>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary" id="saveSettingsBtn">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Card 2: Quick Project Creator -->
        <div class="settings-card">
            <div class="card-header">
                <div class="card-title-group">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <h3>Create New Project</h3>
                </div>
            </div>
            <div class="card-body">
                <form id="newProjectForm" class="settings-form">
                    <div class="form-group">
                        <label for="newProjectName">Project Name</label>
                        <input type="text" id="newProjectName" class="form-control" placeholder="e.g. Mobile Application Re-architecture" required>
                    </div>

                    <div class="form-group">
                        <label for="newProjectColor">Project Accent Color</label>
                        <div class="color-picker-group">
                            <input type="color" id="newProjectColor" class="color-picker-input" value="#4f46e5">
                            <div class="color-swatches" id="newColorSwatches">
                                <button type="button" class="color-swatch-btn active" data-color="#4f46e5" style="background:#4f46e5" title="Indigo"></button>
                                <button type="button" class="color-swatch-btn" data-color="#0284c7" style="background:#0284c7" title="Sky"></button>
                                <button type="button" class="color-swatch-btn" data-color="#059669" style="background:#059669" title="Emerald"></button>
                                <button type="button" class="color-swatch-btn" data-color="#d97706" style="background:#d97706" title="Amber"></button>
                                <button type="button" class="color-swatch-btn" data-color="#dc2626" style="background:#dc2626" title="Rose"></button>
                                <button type="button" class="color-swatch-btn" data-color="#7c3aed" style="background:#7c3aed" title="Purple"></button>
                                <button type="button" class="color-swatch-btn" data-color="#2563eb" style="background:#2563eb" title="Blue"></button>
                                <button type="button" class="color-swatch-btn" data-color="#475569" style="background:#475569" title="Slate"></button>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary" id="addProjectBtn">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            Add Project
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Project Management Master Table -->
    <div class="settings-card full-width-card">
        <div class="card-header project-table-header">
            <div class="card-title-group">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                </svg>
                <h3>Active Projects Directory</h3>
                <span class="count-badge" id="projectCountBadge"><?= count($projects) ?></span>
            </div>
            <div class="table-search-box">
                <input type="text" id="projectSearchInput" class="form-control search-input" placeholder="Search projects...">
            </div>
        </div>

        <div class="settings-table-wrapper">
            <table class="settings-table" id="projectsDirectoryTable">
                <thead>
                    <tr>
                        <th>Project Name</th>
                        <th>Color</th>
                        <th>Tracked Time</th>
                        <th>Status</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody id="projectsTableBody">
                    <tr><td colspan="5" class="table-loading">Loading projects directory...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Project Modal -->
<div class="settings-modal" id="editProjectModal" style="display: none;">
    <div class="settings-modal-backdrop" id="editModalBackdrop"></div>
    <div class="settings-modal-dialog">
        <div class="settings-modal-header">
            <h3>Edit Project</h3>
            <button type="button" class="modal-close-btn" id="closeEditModalBtn" aria-label="Close">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <form id="editProjectForm">
            <input type="hidden" id="editProjectId">
            <div class="settings-modal-body">
                <div class="form-group">
                    <label for="editProjectName">Project Name</label>
                    <input type="text" id="editProjectName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="editProjectColor">Accent Color</label>
                    <div class="color-picker-group">
                        <input type="color" id="editProjectColor" class="color-picker-input">
                        <div class="color-swatches" id="editColorSwatches">
                            <button type="button" class="color-swatch-btn" data-color="#4f46e5" style="background:#4f46e5"></button>
                            <button type="button" class="color-swatch-btn" data-color="#0284c7" style="background:#0284c7"></button>
                            <button type="button" class="color-swatch-btn" data-color="#059669" style="background:#059669"></button>
                            <button type="button" class="color-swatch-btn" data-color="#d97706" style="background:#d97706"></button>
                            <button type="button" class="color-swatch-btn" data-color="#dc2626" style="background:#dc2626"></button>
                            <button type="button" class="color-swatch-btn" data-color="#7c3aed" style="background:#7c3aed"></button>
                            <button type="button" class="color-swatch-btn" data-color="#2563eb" style="background:#2563eb"></button>
                            <button type="button" class="color-swatch-btn" data-color="#475569" style="background:#475569"></button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="settings-modal-footer">
                <button type="button" class="btn-secondary" id="cancelEditBtn">Cancel</button>
                <button type="submit" class="btn-primary" id="saveEditProjectBtn">Update Project</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Project Confirmation Modal -->
<div class="settings-modal" id="deleteProjectModal" style="display: none;">
    <div class="settings-modal-backdrop" id="deleteModalBackdrop"></div>
    <div class="settings-modal-dialog modal-dialog-sm">
        <div class="settings-modal-header">
            <h3>Delete Project</h3>
            <button type="button" class="modal-close-btn" id="closeDeleteModalBtn" aria-label="Close">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
        <div class="settings-modal-body">
            <p>Are you sure you want to delete <strong id="deleteProjectName">this project</strong>?</p>
            <p class="field-hint" style="margin-top: 8px;">Existing time log entries previously associated with this project will be preserved.</p>
        </div>
        <div class="settings-modal-footer">
            <button type="button" class="btn-secondary" id="cancelDeleteBtn">Cancel</button>
            <button type="button" class="btn-danger" id="confirmDeleteProjectBtn">Delete Project</button>
        </div>
    </div>
</div>
