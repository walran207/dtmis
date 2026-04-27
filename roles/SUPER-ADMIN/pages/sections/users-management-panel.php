<?php
declare(strict_types=1);

$superAdminUsers = is_array($superAdminUsers ?? null) ? $superAdminUsers : [];
$superAdminRoles = is_array($superAdminRoles ?? null) ? $superAdminRoles : [];
$superAdminOffices = is_array($superAdminOffices ?? null) ? $superAdminOffices : [];
$superAdminUsersEndpoint = (string)($superAdminUsersEndpoint ?? app_url('actions/super-admin-users.php'));
$csrfToken = (string)($dtrCsrfToken ?? ($csrfToken ?? ''));
?>
<section class="super-admin-grid" aria-label="Super Admin users management">
    <article class="card super-admin-panel">
        <div class="super-admin-panel-head">
            <h2>User Access Matrix</h2>
            <p>Click a user row to edit role assignment, office mapping, and account state.</p>
        </div>

        <div class="super-admin-toolbar" aria-label="User table controls">
            <form class="super-admin-search" role="search" aria-label="Search users">
                <label class="sr-only" for="superAdminUserSearch">Search users</label>
                <input id="superAdminUserSearch" type="search" placeholder="Search name, email, role, office">
            </form>
            <label class="super-admin-filter" for="superAdminUserStatusFilter">
                <span>Status</span>
                <select id="superAdminUserStatusFilter">
                    <option value="">All</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="locked">Locked</option>
                </select>
            </label>
        </div>

        <div class="super-admin-table-wrap">
            <table class="super-admin-table" id="superAdminUsersTable">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Office</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Lock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="superAdminUsersBody">
                    <tr>
                        <td colspan="8" class="table-empty-state">Loading users...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p id="superAdminUsersEmpty" class="super-admin-empty" hidden>No users match your current filters.</p>
    </article>

    <article class="card super-admin-panel">
        <div class="super-admin-panel-head">
            <h2>Edit Selected User</h2>
            <p>Changes apply immediately. Security reset clears lock and MFA counters.</p>
        </div>

        <form id="superAdminUserForm" class="super-admin-form" autocomplete="off">
            <input type="hidden" id="superAdminUserId" value="">
            <label class="super-admin-field">
                <span>Full Name</span>
                <input type="text" id="superAdminUserName" value="" disabled>
            </label>
            <label class="super-admin-field">
                <span>Email</span>
                <input type="text" id="superAdminUserEmail" value="" disabled>
            </label>
            <label class="super-admin-field">
                <span>Role</span>
                <select id="superAdminUserRole" required>
                    <option value="">Select role</option>
                </select>
            </label>
            <label class="super-admin-field">
                <span>Office</span>
                <select id="superAdminUserOffice" required>
                    <option value="">Select office</option>
                </select>
            </label>
            <label class="super-admin-checkbox" for="superAdminUserActive">
                <input type="checkbox" id="superAdminUserActive" checked>
                <span>Account is active</span>
            </label>
            <label class="super-admin-checkbox" for="superAdminResetSecurity">
                <input type="checkbox" id="superAdminResetSecurity">
                <span>Also reset security lock counters</span>
            </label>

            <div class="super-admin-form-actions">
                <button type="submit" class="super-admin-btn super-admin-btn-primary" id="superAdminSaveUser">Save User</button>
                <button type="button" class="super-admin-btn super-admin-btn-secondary" id="superAdminResetOnly">Reset Security</button>
                <button type="button" class="super-admin-btn super-admin-btn-secondary" id="superAdminClearSelection">Clear</button>
            </div>
        </form>

        <p id="superAdminUsersStatus" class="super-admin-status" role="status" aria-live="polite">Select a user to begin.</p>
    </article>
</section>

<script>
(function () {
    const endpoint = <?= json_encode($superAdminUsersEndpoint, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const csrfToken = <?= json_encode($csrfToken, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    let users = <?= json_encode($superAdminUsers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const roles = <?= json_encode($superAdminRoles, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const offices = <?= json_encode($superAdminOffices, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    const usersBody = document.getElementById('superAdminUsersBody');
    const usersEmpty = document.getElementById('superAdminUsersEmpty');
    const searchInput = document.getElementById('superAdminUserSearch');
    const statusFilter = document.getElementById('superAdminUserStatusFilter');

    const userForm = document.getElementById('superAdminUserForm');
    const userIdInput = document.getElementById('superAdminUserId');
    const userNameInput = document.getElementById('superAdminUserName');
    const userEmailInput = document.getElementById('superAdminUserEmail');
    const userRoleSelect = document.getElementById('superAdminUserRole');
    const userOfficeSelect = document.getElementById('superAdminUserOffice');
    const userActiveCheckbox = document.getElementById('superAdminUserActive');
    const resetSecurityCheckbox = document.getElementById('superAdminResetSecurity');
    const resetOnlyButton = document.getElementById('superAdminResetOnly');
    const clearSelectionButton = document.getElementById('superAdminClearSelection');
    const saveButton = document.getElementById('superAdminSaveUser');
    const statusMessage = document.getElementById('superAdminUsersStatus');

    const roleMap = new Map();
    const officeMap = new Map();
    let selectedUserId = 0;

    function setStatus(message, isError) {
        if (!statusMessage) {
            return;
        }
        statusMessage.textContent = String(message || '');
        statusMessage.classList.toggle('is-error', !!isError);
    }

    function setBusy(isBusy) {
        const disabled = !!isBusy;
        if (saveButton) {
            saveButton.disabled = disabled;
        }
        if (resetOnlyButton) {
            resetOnlyButton.disabled = disabled;
        }
    }

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>\"']/g, function (character) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            };
            return map[character] || character;
        });
    }

    function normalizeLockState(user) {
        const lockLabel = String(user && user.locked_until || '').trim();
        return lockLabel !== '';
    }

    function buildRoleOfficeMaps() {
        roleMap.clear();
        officeMap.clear();

        userRoleSelect.innerHTML = '<option value="">Select role</option>';
        roles.forEach(function (role) {
            const roleId = Number(role && role.id || 0);
            if (!roleId) {
                return;
            }
            roleMap.set(roleId, role);
            const option = document.createElement('option');
            option.value = String(roleId);
            option.textContent = String(role.name || 'Role');
            userRoleSelect.appendChild(option);
        });

        userOfficeSelect.innerHTML = '<option value="">Select office</option>';
        offices.forEach(function (office) {
            const officeId = Number(office && office.id || 0);
            if (!officeId) {
                return;
            }
            officeMap.set(officeId, office);
            const option = document.createElement('option');
            option.value = String(officeId);
            option.textContent = String((office.name || 'Office') + ' [' + (office.level || '-') + ']');
            userOfficeSelect.appendChild(option);
        });
    }

    function renderUsersTable() {
        if (!usersBody) {
            return;
        }

        const searchTerm = String(searchInput ? searchInput.value : '').toLowerCase().trim();
        const statusValue = String(statusFilter ? statusFilter.value : '').toLowerCase().trim();

        const filtered = users.filter(function (user) {
            const fullName = String((user.first_name || '') + ' ' + (user.last_name || '')).trim();
            const rowText = [
                fullName,
                user.email,
                user.role_name,
                user.office_name
            ].join(' ').toLowerCase();
            const matchesSearch = searchTerm === '' || rowText.indexOf(searchTerm) !== -1;

            const isActive = !!user.is_active;
            const isLocked = normalizeLockState(user);
            let matchesStatus = true;
            if (statusValue === 'active') {
                matchesStatus = isActive;
            } else if (statusValue === 'inactive') {
                matchesStatus = !isActive;
            } else if (statusValue === 'locked') {
                matchesStatus = isLocked;
            }

            return matchesSearch && matchesStatus;
        });

        if (filtered.length === 0) {
            usersBody.innerHTML = '<tr><td colspan="8" class="table-empty-state">No users found for current filters.</td></tr>';
            if (usersEmpty) {
                usersEmpty.hidden = false;
            }
            return;
        }

        if (usersEmpty) {
            usersEmpty.hidden = true;
        }

        usersBody.innerHTML = filtered.map(function (user) {
            const userId = Number(user.id || 0);
            const name = String((user.first_name || '') + ' ' + (user.last_name || '')).trim() || 'Unknown User';
            const isActive = !!user.is_active;
            const isLocked = normalizeLockState(user);
            const statusLabel = isActive ? 'Active' : 'Inactive';
            const lockLabel = isLocked ? (user.locked_until_label || 'Locked') : 'No lock';
            const activeClass = isActive ? 'status-pill is-active' : 'status-pill is-inactive';
            const selectedClass = userId === selectedUserId ? 'is-selected-row' : '';

            return `
                <tr class="${selectedClass}" data-user-id="${userId}">
                    <td>${escapeHtml(name)}</td>
                    <td>${escapeHtml(user.email || '-')}</td>
                    <td>${escapeHtml(user.role_name || '-')}</td>
                    <td>${escapeHtml(user.office_name || '-')}</td>
                    <td><span class="${activeClass}">${escapeHtml(statusLabel)}</span></td>
                    <td>${escapeHtml(user.last_login_label || '-')}</td>
                    <td>${escapeHtml(lockLabel)}</td>
                    <td>
                        <button type="button" class="super-admin-table-btn" data-user-select="${userId}">Edit</button>
                    </td>
                </tr>
            `;
        }).join('');

        Array.from(usersBody.querySelectorAll('[data-user-select]')).forEach(function (button) {
            button.addEventListener('click', function () {
                const userId = Number(button.getAttribute('data-user-select') || 0);
                selectUser(userId);
            });
        });
    }

    function clearSelection() {
        selectedUserId = 0;
        if (userIdInput) {
            userIdInput.value = '';
        }
        if (userNameInput) {
            userNameInput.value = '';
        }
        if (userEmailInput) {
            userEmailInput.value = '';
        }
        if (userRoleSelect) {
            userRoleSelect.value = '';
        }
        if (userOfficeSelect) {
            userOfficeSelect.value = '';
        }
        if (userActiveCheckbox) {
            userActiveCheckbox.checked = true;
        }
        if (resetSecurityCheckbox) {
            resetSecurityCheckbox.checked = false;
        }
        renderUsersTable();
    }

    function selectUser(userId) {
        const target = users.find(function (user) {
            return Number(user.id || 0) === Number(userId || 0);
        });

        if (!target) {
            return;
        }

        selectedUserId = Number(target.id || 0);
        if (userIdInput) {
            userIdInput.value = String(selectedUserId);
        }
        if (userNameInput) {
            userNameInput.value = String((target.first_name || '') + ' ' + (target.last_name || '')).trim();
        }
        if (userEmailInput) {
            userEmailInput.value = String(target.email || '');
        }
        if (userRoleSelect) {
            userRoleSelect.value = String(target.role_id || '');
        }
        if (userOfficeSelect) {
            userOfficeSelect.value = String(target.office_id || '');
        }
        if (userActiveCheckbox) {
            userActiveCheckbox.checked = !!target.is_active;
        }
        if (resetSecurityCheckbox) {
            resetSecurityCheckbox.checked = false;
        }

        renderUsersTable();
        setStatus('Editing user #' + selectedUserId + '.', false);
    }

    async function postAction(payload) {
        const formData = new FormData();
        formData.set('csrf_token', csrfToken);
        Object.keys(payload).forEach(function (key) {
            formData.set(key, String(payload[key]));
        });

        const response = await fetch(endpoint, {
            method: 'POST',
            body: formData,
        });

        const json = await response.json().catch(function () {
            return { ok: false, message: 'Unexpected server response.' };
        });

        if (!response.ok || !json.ok) {
            throw new Error(String(json.message || 'Unable to save changes.'));
        }

        return json;
    }

    function updateUsersFromResponse(json) {
        if (!json || !Array.isArray(json.users)) {
            return;
        }
        users = json.users.slice();
        renderUsersTable();
        if (selectedUserId > 0) {
            selectUser(selectedUserId);
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', renderUsersTable);
        const searchForm = searchInput.closest('form');
        if (searchForm) {
            searchForm.addEventListener('submit', function (event) {
                event.preventDefault();
            });
        }
    }

    if (statusFilter) {
        statusFilter.addEventListener('change', renderUsersTable);
    }

    if (clearSelectionButton) {
        clearSelectionButton.addEventListener('click', function () {
            clearSelection();
            setStatus('Selection cleared.', false);
        });
    }

    if (resetOnlyButton) {
        resetOnlyButton.addEventListener('click', async function () {
            const userId = Number(userIdInput ? userIdInput.value : 0);
            if (!userId) {
                setStatus('Select a user first.', true);
                return;
            }

            try {
                setBusy(true);
                const result = await postAction({ action: 'RESET_SECURITY', user_id: userId });
                updateUsersFromResponse(result);
                setStatus(result.message || 'Security counters reset.', false);
            } catch (error) {
                setStatus(error.message || 'Unable to reset security counters.', true);
            } finally {
                setBusy(false);
            }
        });
    }

    if (userForm) {
        userForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            const userId = Number(userIdInput ? userIdInput.value : 0);
            const roleId = Number(userRoleSelect ? userRoleSelect.value : 0);
            const officeId = Number(userOfficeSelect ? userOfficeSelect.value : 0);

            if (!userId || !roleId || !officeId) {
                setStatus('Select a user, role, and office before saving.', true);
                return;
            }

            try {
                setBusy(true);
                const payload = {
                    action: 'UPDATE_USER',
                    user_id: userId,
                    role_id: roleId,
                    office_id: officeId,
                    is_active: userActiveCheckbox && userActiveCheckbox.checked ? 1 : 0,
                    reset_security: resetSecurityCheckbox && resetSecurityCheckbox.checked ? 1 : 0,
                };
                const result = await postAction(payload);
                updateUsersFromResponse(result);
                setStatus(result.message || 'User updated successfully.', false);
            } catch (error) {
                setStatus(error.message || 'Unable to save user changes.', true);
            } finally {
                setBusy(false);
            }
        });
    }

    buildRoleOfficeMaps();
    renderUsersTable();
    clearSelection();
})();
</script>
