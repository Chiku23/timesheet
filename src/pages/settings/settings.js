// ==========================================
// Workspace & System Settings Controller
// Goal Configuration & Project Management
// ==========================================

document.addEventListener('DOMContentLoaded', () => {
    // ─── State ───
    let allProjects = [];
    let deleteTargetProjectId = null;

    // ─── DOM Elements ───
    const settingsToast = document.getElementById('settingsToast');
    const workspaceSettingsForm = document.getElementById('workspaceSettingsForm');
    const companyNameInput = document.getElementById('companyNameInput');
    const weeklyGoalInput = document.getElementById('weeklyGoalInput');
    const saveSettingsBtn = document.getElementById('saveSettingsBtn');
    const goalPresetButtons = document.querySelectorAll('.preset-btn');

    // Project Elements
    const newProjectForm = document.getElementById('newProjectForm');
    const newProjectName = document.getElementById('newProjectName');
    const newProjectColor = document.getElementById('newProjectColor');
    const newColorSwatches = document.getElementById('newColorSwatches');
    const addProjectBtn = document.getElementById('addProjectBtn');

    const projectsTableBody = document.getElementById('projectsTableBody');
    const projectSearchInput = document.getElementById('projectSearchInput');
    const projectCountBadge = document.getElementById('projectCountBadge');

    // Edit Modal Elements
    const editProjectModal = document.getElementById('editProjectModal');
    const editProjectForm = document.getElementById('editProjectForm');
    const editProjectId = document.getElementById('editProjectId');
    const editProjectName = document.getElementById('editProjectName');
    const editProjectColor = document.getElementById('editProjectColor');
    const editColorSwatches = document.getElementById('editColorSwatches');
    const closeEditModalBtn = document.getElementById('closeEditModalBtn');
    const cancelEditBtn = document.getElementById('cancelEditBtn');

    // Delete Modal Elements
    const deleteProjectModal = document.getElementById('deleteProjectModal');
    const deleteProjectName = document.getElementById('deleteProjectName');
    const closeDeleteModalBtn = document.getElementById('closeDeleteModalBtn');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const confirmDeleteProjectBtn = document.getElementById('confirmDeleteProjectBtn');

    // ─── Toast Feedback System ───
    let toastTimeout = null;
    function showToast(message, type = 'success') {
        if (!settingsToast) return;
        if (toastTimeout) clearTimeout(toastTimeout);

        settingsToast.className = `settings-toast ${type}`;
        settingsToast.textContent = message;
        settingsToast.style.display = 'flex';

        toastTimeout = setTimeout(() => {
            settingsToast.style.display = 'none';
        }, 4000);
    }

    // ─── Escape HTML ───
    function escHtml(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

    // ─── Format Duration Helper ───
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

    // ─── Load Workspace Settings ───
    async function loadWorkspaceSettings() {
        try {
            const res = await fetch('/api/settings');
            const data = await res.json();
            if (data.success) {
                if (companyNameInput && data.company_name) {
                    companyNameInput.value = data.company_name;
                }
                if (weeklyGoalInput && data.weekly_goal_hours) {
                    weeklyGoalInput.value = data.weekly_goal_hours;
                }
            }
        } catch (err) {
            console.error('Failed to load workspace settings:', err);
        }
    }

    // ─── Save Workspace Settings ───
    if (workspaceSettingsForm) {
        workspaceSettingsForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const companyName = companyNameInput ? companyNameInput.value.trim() : '';
            const weeklyGoalHours = weeklyGoalInput ? parseInt(weeklyGoalInput.value, 10) : 40;

            if (isNaN(weeklyGoalHours) || weeklyGoalHours < 1 || weeklyGoalHours > 168) {
                showToast('Weekly goal hours must be between 1 and 168.', 'error');
                return;
            }

            if (saveSettingsBtn) saveSettingsBtn.disabled = true;

            try {
                const res = await fetch('/api/settings/update', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        company_name: companyName,
                        weekly_goal_hours: weeklyGoalHours,
                    }),
                });

                const data = await res.json();
                if (data.success) {
                    showToast('Workspace settings saved successfully.');
                } else {
                    showToast(data.message || 'Failed to save settings.', 'error');
                }
            } catch (err) {
                console.error('Error saving settings:', err);
                showToast('Network error while saving settings.', 'error');
            } finally {
                if (saveSettingsBtn) saveSettingsBtn.disabled = false;
            }
        });
    }

    // ─── Goal Preset Buttons ───
    goalPresetButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const hours = btn.dataset.hours;
            if (weeklyGoalInput && hours) {
                weeklyGoalInput.value = hours;
            }
        });
    });

    // ─── Swatch Selection Helper ───
    function initSwatchGroup(container, colorInput) {
        if (!container || !colorInput) return;

        const swatches = container.querySelectorAll('.color-swatch-btn');
        swatches.forEach(swatch => {
            swatch.addEventListener('click', () => {
                const color = swatch.dataset.color;
                if (color) {
                    colorInput.value = color;
                    swatches.forEach(s => s.classList.remove('active'));
                    swatch.classList.add('active');
                }
            });
        });

        colorInput.addEventListener('input', () => {
            swatches.forEach(s => {
                if (s.dataset.color.toLowerCase() === colorInput.value.toLowerCase()) {
                    s.classList.add('active');
                } else {
                    s.classList.remove('active');
                }
            });
        });
    }

    initSwatchGroup(newColorSwatches, newProjectColor);
    initSwatchGroup(editColorSwatches, editProjectColor);

    // ─── Load Projects ───
    async function loadProjects() {
        try {
            const res = await fetch('/api/projects');
            const data = await res.json();
            if (data.success) {
                allProjects = data.projects || [];
                renderProjectsTable();
            }
        } catch (err) {
            console.error('Failed to load projects:', err);
            if (projectsTableBody) {
                projectsTableBody.innerHTML = '<tr><td colspan="5" class="table-empty">Error loading projects.</td></tr>';
            }
        }
    }

    // ─── Render Projects Table ───
    function renderProjectsTable() {
        if (!projectsTableBody) return;

        const search = (projectSearchInput ? projectSearchInput.value : '').toLowerCase().trim();
        const filtered = allProjects.filter(p => !search || p.name.toLowerCase().includes(search));

        if (projectCountBadge) {
            projectCountBadge.textContent = allProjects.length;
        }

        if (filtered.length === 0) {
            projectsTableBody.innerHTML = '<tr><td colspan="5" class="table-empty">No projects found.</td></tr>';
            return;
        }

        let html = '';
        filtered.forEach(project => {
            const durationText = formatDuration(project.total_seconds || 0);
            const colorHex = project.color_hex || '#4f46e5';

            html += `
                <tr data-project-id="${project.id}">
                    <td>
                        <div class="project-name-cell">
                            <span class="project-color-dot" style="background-color: ${colorHex}"></span>
                            <span>${escHtml(project.name)}</span>
                        </div>
                    </td>
                    <td>
                        <span class="color-preview-pill">
                            <span class="project-color-dot" style="background-color: ${colorHex}"></span>
                            ${colorHex}
                        </span>
                    </td>
                    <td>${durationText}</td>
                    <td><span class="status-badge-active">Active</span></td>
                    <td class="col-actions">
                        <div class="row-actions">
                            <button type="button" class="btn-action-edit" onclick="window.editProject(${project.id})">Edit</button>
                            <button type="button" class="btn-action-del" onclick="window.deleteProject(${project.id}, '${escHtml(project.name).replace(/'/g, "\\'")}')">Delete</button>
                        </div>
                    </td>
                </tr>
            `;
        });

        projectsTableBody.innerHTML = html;
    }

    if (projectSearchInput) {
        projectSearchInput.addEventListener('input', renderProjectsTable);
    }

    // ─── Create Project ───
    if (newProjectForm) {
        newProjectForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const name = newProjectName ? newProjectName.value.trim() : '';
            const colorHex = newProjectColor ? newProjectColor.value : '#4f46e5';

            if (!name) {
                showToast('Project name is required.', 'error');
                return;
            }

            if (addProjectBtn) addProjectBtn.disabled = true;

            try {
                const res = await fetch('/api/projects/add', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name, color_hex: colorHex }),
                });

                const data = await res.json();
                if (data.success) {
                    showToast(`Project "${name}" created successfully.`);
                    if (newProjectName) newProjectName.value = '';
                    loadProjects();
                } else {
                    showToast(data.message || 'Failed to create project.', 'error');
                }
            } catch (err) {
                console.error('Error creating project:', err);
                showToast('Network error creating project.', 'error');
            } finally {
                if (addProjectBtn) addProjectBtn.disabled = false;
            }
        });
    }

    // ─── Edit Project Flow ───
    window.editProject = function(id) {
        const project = allProjects.find(p => p.id === id);
        if (!project || !editProjectModal) return;

        if (editProjectId) editProjectId.value = project.id;
        if (editProjectName) editProjectName.value = project.name;
        if (editProjectColor) {
            editProjectColor.value = project.color_hex || '#4f46e5';
            if (editColorSwatches) {
                const swatches = editColorSwatches.querySelectorAll('.color-swatch-btn');
                swatches.forEach(s => {
                    if (s.dataset.color.toLowerCase() === editProjectColor.value.toLowerCase()) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            }
        }

        editProjectModal.style.display = 'flex';
    };

    function closeEditModal() {
        if (editProjectModal) editProjectModal.style.display = 'none';
    }

    if (closeEditModalBtn) closeEditModalBtn.addEventListener('click', closeEditModal);
    if (cancelEditBtn) cancelEditBtn.addEventListener('click', closeEditModal);

    if (editProjectForm) {
        editProjectForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const id = editProjectId ? parseInt(editProjectId.value, 10) : 0;
            const name = editProjectName ? editProjectName.value.trim() : '';
            const colorHex = editProjectColor ? editProjectColor.value : '#4f46e5';

            if (!name || !id) {
                showToast('Valid project name is required.', 'error');
                return;
            }

            try {
                const res = await fetch('/api/projects/update', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, name, color_hex: colorHex }),
                });

                const data = await res.json();
                if (data.success) {
                    showToast('Project updated successfully.');
                    closeEditModal();
                    loadProjects();
                } else {
                    showToast(data.message || 'Failed to update project.', 'error');
                }
            } catch (err) {
                console.error('Error updating project:', err);
                showToast('Network error updating project.', 'error');
            }
        });
    }

    // ─── Delete Project Flow ───
    window.deleteProject = function(id, name) {
        deleteTargetProjectId = id;
        if (deleteProjectName) deleteProjectName.textContent = `"${name}"`;
        if (deleteProjectModal) deleteProjectModal.style.display = 'flex';
    };

    function closeDeleteModal() {
        deleteTargetProjectId = null;
        if (deleteProjectModal) deleteProjectModal.style.display = 'none';
    }

    if (closeDeleteModalBtn) closeDeleteModalBtn.addEventListener('click', closeDeleteModal);
    if (cancelDeleteBtn) cancelDeleteBtn.addEventListener('click', closeDeleteModal);

    if (confirmDeleteProjectBtn) {
        confirmDeleteProjectBtn.addEventListener('click', async () => {
            if (!deleteTargetProjectId) return;

            try {
                const res = await fetch('/api/projects/delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: deleteTargetProjectId }),
                });

                const data = await res.json();
                if (data.success) {
                    showToast('Project deleted successfully.');
                    closeDeleteModal();
                    loadProjects();
                } else {
                    showToast(data.message || 'Failed to delete project.', 'error');
                }
            } catch (err) {
                console.error('Error deleting project:', err);
                showToast('Network error deleting project.', 'error');
            }
        });
    }

    // ─── Initialize ───
    loadWorkspaceSettings();
    loadProjects();
});
