// ==========================================
// Admin Dashboard JavaScript
// Team stats, project management, audit logs
// ==========================================

document.addEventListener('DOMContentLoaded', () => {
    const teamTableBody = document.getElementById('teamTableBody');
    const projectsTableBody = document.getElementById('projectsTableBody');
    const adminLogsBody = document.getElementById('adminLogsBody');

    // Filter controls
    const adminFilterUser = document.getElementById('adminFilterUser');
    const adminFilterType = document.getElementById('adminFilterType');
    const adminFilterFrom = document.getElementById('adminFilterFrom');
    const adminFilterTo = document.getElementById('adminFilterTo');
    const adminFilterBtn = document.getElementById('adminFilterBtn');

    // KPI elements
    const adminTotalHours = document.getElementById('adminTotalHours');
    const adminWeekHours = document.getElementById('adminWeekHours');
    const adminActiveUsers = document.getElementById('adminActiveUsers');
    const adminTotalProjects = document.getElementById('adminTotalProjects');

    // Project modal elements
    const openAddProjectModal = document.getElementById('openAddProjectModal');
    const closeProjectModal = document.getElementById('closeProjectModal');
    const cancelProjectModal = document.getElementById('cancelProjectModal');
    const projectModal = document.getElementById('projectModal');
    const projectModalBackdrop = document.getElementById('projectModalBackdrop');
    const projectForm = document.getElementById('projectForm');
    const projectNameInput = document.getElementById('projectNameInput');
    const projectColorInput = document.getElementById('projectColorInput');

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

    // ─── Load Admin Stats & Team Table ───
    async function loadStats() {
        try {
            const res = await fetch('/api/admin/stats');
            const data = await res.json();

            if (data.success) {
                if (adminTotalHours) adminTotalHours.textContent = data.formatted_total_hours || '0m';
                if (adminWeekHours) adminWeekHours.textContent = data.formatted_week_hours || '0m';
                if (adminActiveUsers) adminActiveUsers.textContent = data.active_users;
                if (adminTotalProjects) adminTotalProjects.textContent = data.total_projects;

                renderTeamTable(data.users || []);
            }
        } catch (err) {
            console.error('Failed to load admin stats:', err);
        }
    }

    // ─── Render Team Table ───
    function renderTeamTable(users) {
        if (!teamTableBody) return;

        if (users.length === 0) {
            teamTableBody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:24px; color:var(--text-muted);">No team members found.</td></tr>';
            return;
        }

        let html = '';
        users.forEach(u => {
            const roleBadge = `<span class="role-badge role-${u.role}">${u.role.toUpperCase()}</span>`;
            const durationDisplay = u.formatted_total_hours || formatDuration(u.total_seconds || 0);

            html += `
                <tr>
                    <td>
                        <strong>${escHtml(u.name)}</strong>
                        <div style="font-size:0.75rem; color:var(--text-muted);">${escHtml(u.email)}</div>
                    </td>
                    <td>${roleBadge}</td>
                    <td>${u.log_count || 0}</td>
                    <td><strong>${durationDisplay}</strong></td>
                </tr>
            `;
        });

        teamTableBody.innerHTML = html;
    }

    // ─── Load Projects Table ───
    async function loadProjects() {
        if (!projectsTableBody) return;

        try {
            const res = await fetch('/api/projects');
            const data = await res.json();

            if (data.success) {
                renderProjectsTable(data.projects || []);
            }
        } catch (err) {
            console.error('Failed to load projects:', err);
        }
    }

    function renderProjectsTable(projects) {
        if (!projectsTableBody) return;

        if (projects.length === 0) {
            projectsTableBody.innerHTML = '<tr><td colspan="3" style="text-align:center; padding:24px; color:var(--text-muted);">No projects created yet.</td></tr>';
            return;
        }

        let html = '';
        projects.forEach(p => {
            html += `
                <tr>
                    <td>
                        <span class="project-dot" style="background:${p.color_hex};"></span>
                        <strong>${escHtml(p.name)}</strong>
                    </td>
                    <td><span style="display:inline-block; width:14px; height:14px; border-radius:3px; background:${p.color_hex}; vertical-align:middle; margin-right:4px;"></span><code>${p.color_hex}</code></td>
                    <td><strong>${p.formatted_duration || '0m'}</strong></td>
                </tr>
            `;
        });

        projectsTableBody.innerHTML = html;
    }

    // ─── Load Team Audit Logs ───
    async function loadTeamLogs() {
        const params = new URLSearchParams();

        if (adminFilterUser && adminFilterUser.value) {
            params.set('user_id', adminFilterUser.value);
        } else {
            params.set('all', '1');
        }
        if (adminFilterType && adminFilterType.value) {
            params.set('entry_type', adminFilterType.value);
        }
        if (adminFilterFrom && adminFilterFrom.value) {
            params.set('date_from', adminFilterFrom.value);
        }
        if (adminFilterTo && adminFilterTo.value) {
            params.set('date_to', adminFilterTo.value);
        }

        try {
            const res = await fetch('/api/logs?' + params.toString());
            const data = await res.json();

            if (data.success) {
                renderTeamLogs(data.logs || []);
            }
        } catch (err) {
            console.error('Failed to load team logs:', err);
        }
    }

    // ─── Render Team Logs (Showing actual Member Name!) ───
    function renderTeamLogs(logs) {
        if (!adminLogsBody) return;

        if (logs.length === 0) {
            adminLogsBody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:24px; color:var(--text-muted);">No logs match this filter criteria.</td></tr>';
            return;
        }

        let html = '';
        logs.forEach(log => {
            const projectBadge = log.project_name
                ? `<span class="project-badge"><span class="project-dot" style="background:${log.project_color}"></span>${escHtml(log.project_name)}</span>`
                : '<span class="text-muted">—</span>';

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

            const logDate = window.getIsoDateKeyWithTz ? window.getIsoDateKeyWithTz(log.start_time) : (log.date || log.start_time.slice(0, 10));

            html += `
                <tr>
                    <td>${logDate}</td>
                    <td><strong>${escHtml(log.user_name || 'Member #' + log.user_id)}</strong></td>
                    <td style="max-width:240px; word-break:break-word;">${escHtml(log.task_description)}</td>
                    <td>${projectBadge}</td>
                    <td>${sourceBadge}</td>
                    <td><span class="duration-badge">${log.formatted_duration}</span></td>
                </tr>
            `;
        });

        adminLogsBody.innerHTML = html;
    }

    // ─── Project Modal Handlers ───
    function openProjectModal() {
        if (projectModal) projectModal.classList.add('open');
        if (projectModalBackdrop) projectModalBackdrop.classList.add('open');
        if (projectNameInput) projectNameInput.focus();
    }

    function closeProjectModalFn() {
        if (projectModal) projectModal.classList.remove('open');
        if (projectModalBackdrop) projectModalBackdrop.classList.remove('open');
        if (projectForm) projectForm.reset();
    }

    const colorHexPreview = document.getElementById('colorHexPreview');
    if (projectColorInput && colorHexPreview) {
        projectColorInput.addEventListener('input', () => {
            colorHexPreview.textContent = projectColorInput.value;
        });
    }

    if (openAddProjectModal) openAddProjectModal.addEventListener('click', openProjectModal);
    if (closeProjectModal) closeProjectModal.addEventListener('click', closeProjectModalFn);
    if (cancelProjectModal) cancelProjectModal.addEventListener('click', closeProjectModalFn);
    if (projectModalBackdrop) projectModalBackdrop.addEventListener('click', closeProjectModalFn);

    // ─── Submit Project Form ───
    if (projectForm) {
        projectForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const name = projectNameInput.value.trim();
            const color = projectColorInput.value;

            if (!name) return;

            try {
                const res = await fetch('/api/projects/add', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name, color_hex: color }),
                });
                const data = await res.json();

                if (data.success) {
                    closeProjectModalFn();
                    loadProjects();
                    loadStats();
                } else {
                    alert(data.message || 'Failed to create project.');
                }
            } catch (err) {
                console.error('Failed to add project:', err);
            }
        });
    }

    // ─── Filter Button Listener ───
    if (adminFilterBtn) {
        adminFilterBtn.addEventListener('click', loadTeamLogs);
    }

    // ─── Timezone Switch Listener ───
    window.addEventListener('timesheet:timezone-changed', () => {
        loadTeamLogs();
    });

    // ─── Initial Load ───
    loadStats();
    loadProjects();
    loadTeamLogs();
});
