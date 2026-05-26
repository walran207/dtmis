<?php
declare(strict_types=1);

$adminOverview = is_array($adminOverview ?? null) ? $adminOverview : [];
$adminUsers = is_array($adminUsers ?? null) ? $adminUsers : [];
$adminRoles = is_array($adminRoles ?? null) ? $adminRoles : [];
$adminOffices = is_array($adminOffices ?? null) ? $adminOffices : [];
$adminScope = is_array($adminScope ?? null) ? $adminScope : [];
$adminUsersEndpoint = (string)($adminUsersEndpoint ?? app_url('actions/admin-users.php'));
$csrfToken = (string)($dtrCsrfToken ?? ($csrfToken ?? ''));
$scopeRootOfficeName = trim((string)(($adminScope['root_office']['name'] ?? '') ?: 'Assigned Scope'));
$scopePenroName = trim((string)(($adminScope['penro_office']['name'] ?? '') ?: ''));
$scopeType = trim((string)($adminScope['scope_type'] ?? 'DIRECT'));
$scopeSummaryLabel = trim((string)($adminScope['summary_label'] ?? 'Managed office scope'));
$scopeIncludesPamo = !empty($adminScope['include_pamo']);
?>
<section class="admin-grid" aria-label="Scoped admin account management">
    <article class="card admin-panel admin-panel-full">
        <div class="admin-scope-banner">
            <div class="admin-scope-copy">
                <p class="admin-scope-kicker">Management Scope</p>
                <h2><?= htmlspecialchars($scopeRootOfficeName, ENT_QUOTES, 'UTF-8') ?></h2>
                <p><?= htmlspecialchars($scopeSummaryLabel, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <div class="admin-scope-chips" aria-label="Scope details">
                <span class="admin-scope-chip"><?= htmlspecialchars($scopeType, ENT_QUOTES, 'UTF-8') ?> scope</span>
                <span class="admin-scope-chip"><?= (int)($adminOverview['offices_total'] ?? 0) ?> offices available</span>
                <span class="admin-scope-chip">Create, update, block, delete</span>
                <?php if ($scopeIncludesPamo): ?>
                <span class="admin-scope-chip">Includes PAMO/PASU in <?= htmlspecialchars($scopePenroName, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>
        </div>
    </article>

    <article class="card admin-panel admin-panel-full">
        <div class="admin-toolbar admin-toolbar-top" aria-label="Account management filters">
            <form class="admin-search" role="search" aria-label="Search users">
                <label class="sr-only" for="adminUserSearch">Search users</label>
                <input id="adminUserSearch" type="search" placeholder="Search name, username, email, role, office">
            </form>
            <label class="admin-filter" for="adminUserStatusFilter">
                <span>Status</span>
                <select id="adminUserStatusFilter">
                    <option value="">All Status Categories</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="locked">Locked</option>
                </select>
            </label>
        </div>

        <div class="admin-command-strip">
            <div class="admin-selected-summary">
                <p class="admin-selected-label">Selected Account</p>
                <p id="adminSelectedSummary" class="admin-selected-value">No account selected</p>
                <p id="adminSelectedMeta" class="admin-selected-meta">Click a row in the table to select it. Use the row icons for edit, block, and delete.</p>
            </div>
            <div class="admin-command-actions">
                <button type="button" class="admin-btn admin-btn-primary" id="adminOpenCreateModal">Create Account</button>
                <button type="button" class="admin-btn admin-btn-secondary" id="adminResetOnly" disabled>Reset Security</button>
            </div>
        </div>

        <p id="adminUsersStatus" class="admin-status admin-inline-status" role="status" aria-live="polite">Select a user to begin.</p>
    </article>

    <article class="card admin-panel admin-panel-full">
        <div class="admin-panel-head">
            <h2>Managed Accounts</h2>
            <p>Search and review existing users in your scope, then launch account actions from the command bar.</p>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table" id="adminUsersTable">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Office</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Lock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="adminUsersBody">
                    <tr>
                        <td colspan="9" class="table-empty-state">Loading users...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="admin-pagination" aria-label="User table pagination">
            <button type="button" class="admin-btn admin-btn-secondary" id="adminPageBack">Back</button>
            <p id="adminPageInfo" class="admin-pagination-info">Page 1 of 1</p>
            <button type="button" class="admin-btn admin-btn-secondary" id="adminPageNext">Next</button>
        </div>

        <div class="admin-mini-metrics" aria-label="Managed account overview">
            <p><strong>Managed users:</strong> <span id="adminMetricUsers"><?= (int)($adminOverview['users_total'] ?? 0) ?></span></p>
            <p><strong>Active:</strong> <span id="adminMetricActive"><?= (int)($adminOverview['users_active'] ?? 0) ?></span></p>
            <p><strong>Inactive:</strong> <span id="adminMetricInactive"><?= (int)($adminOverview['users_inactive'] ?? 0) ?></span></p>
            <p><strong>Locked:</strong> <span id="adminMetricLocked"><?= (int)($adminOverview['users_locked'] ?? 0) ?></span></p>
        </div>
        <p id="adminUsersEmpty" class="admin-empty" hidden>No users match your current filters.</p>
    </article>
</section>

<div id="adminCreateModal" class="action-modal" hidden>
    <button type="button" class="action-modal-backdrop" data-admin-create-close="true" aria-label="Close create account dialog"></button>
    <div class="action-modal-dialog details-modal-dialog admin-account-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="adminCreateModalTitle">
        <form id="adminCreateUserForm" class="action-modal-form details-modal-form admin-modal-form" autocomplete="off">
            <h3 id="adminCreateModalTitle" class="action-modal-title">Create Account</h3>
            <p class="action-modal-meta">Create a new local DTMIS account inside your allowed office scope.</p>

            <div class="admin-field-grid">
                <label class="admin-field">
                    <span>First Name</span>
                    <input type="text" id="adminCreateFirstName" maxlength="50" required>
                </label>
                <label class="admin-field">
                    <span>Last Name</span>
                    <input type="text" id="adminCreateLastName" maxlength="50" required>
                </label>
            </div>

                <label class="admin-field">
                    <span>Username</span>
                    <input type="text" id="adminCreateUsername" maxlength="50" placeholder="Generated automatically" readonly>
                    <small class="admin-form-hint">Generated from first and last name. If it already exists, DTMIS will add a number automatically.</small>
                </label>
                <label class="admin-field">
                    <span>Email</span>
                    <input type="email" id="adminCreateEmail" maxlength="100" placeholder="name@denr.gov.ph" required>
                </label>

            <div class="admin-field-grid">
                <label class="admin-field">
                    <span>Role</span>
                    <select id="adminCreateRole" required>
                        <option value="">Select role</option>
                    </select>
                </label>
                <label class="admin-field">
                    <span>Office</span>
                    <select id="adminCreateOffice" required>
                        <option value="">Select office</option>
                    </select>
                </label>
            </div>

            <label class="admin-field">
                <span>Initial Password</span>
                <input type="password" id="adminCreatePassword" minlength="8" maxlength="128" required>
                <small class="admin-form-hint">Use at least 8 characters. This password is stored as a local DTMIS login for the new account.</small>
            </label>

            <label class="admin-checkbox" for="adminCreateActive">
                <input type="checkbox" id="adminCreateActive" checked>
                <span>Create account as active</span>
            </label>

            <p id="adminCreateStatus" class="admin-status" role="status" aria-live="polite">Fill out the form to create a scoped local account.</p>

            <div class="action-modal-actions">
                <button type="button" class="admin-btn admin-btn-secondary" data-admin-create-close="true">Cancel</button>
                <button type="button" class="admin-btn admin-btn-secondary" id="adminCreateResetButton">Clear</button>
                <button type="submit" class="admin-btn admin-btn-primary" id="adminCreateUserButton">Create Account</button>
            </div>
        </form>
    </div>
</div>

<div id="adminEditModal" class="action-modal" hidden>
    <button type="button" class="action-modal-backdrop" data-admin-edit-close="true" aria-label="Close edit account dialog"></button>
    <div class="action-modal-dialog details-modal-dialog admin-account-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="adminEditModalTitle">
        <form id="adminUserForm" class="action-modal-form details-modal-form admin-modal-form" autocomplete="off">
            <h3 id="adminEditModalTitle" class="action-modal-title">Edit Account</h3>
            <p class="action-modal-meta">Update role, office assignment, activation state, and optional security reset for the selected account.</p>

            <input type="hidden" id="adminUserId" value="">

            <div class="admin-field-grid">
                <label class="admin-field">
                    <span>First Name</span>
                    <input type="text" id="adminUserFirstName" value="" maxlength="50" required>
                </label>
                <label class="admin-field">
                    <span>Last Name</span>
                    <input type="text" id="adminUserLastName" value="" maxlength="50" required>
                </label>
            </div>

            <div class="admin-field-grid">
                <label class="admin-field">
                    <span>Username</span>
                    <input type="text" id="adminUserUsername" value="" maxlength="50" required>
                </label>
                <label class="admin-field">
                    <span>Email</span>
                    <input type="email" id="adminUserEmail" value="" maxlength="100" required>
                </label>
            </div>

            <div class="admin-field-grid">
                <label class="admin-field">
                    <span>Role</span>
                    <select id="adminUserRole" required>
                        <option value="">Select role</option>
                    </select>
                </label>
                <label class="admin-field">
                    <span>Office</span>
                    <select id="adminUserOffice" required>
                        <option value="">Select office</option>
                    </select>
                </label>
            </div>

            <label class="admin-checkbox" for="adminUserActive">
                <input type="checkbox" id="adminUserActive" checked>
                <span>Account is active</span>
            </label>
            <label class="admin-checkbox" for="adminResetSecurity">
                <input type="checkbox" id="adminResetSecurity">
                <span>Also reset security lock counters</span>
            </label>

            <p id="adminEditStatus" class="admin-status" role="status" aria-live="polite">Save your changes when you are ready.</p>

            <div class="action-modal-actions">
                <button type="button" class="admin-btn admin-btn-secondary" data-admin-edit-close="true">Cancel</button>
                <button type="button" class="admin-btn admin-btn-secondary" id="adminClearSelection">Clear</button>
                <button type="submit" class="admin-btn admin-btn-primary" id="adminSaveUser">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const endpoint = <?= json_encode($adminUsersEndpoint, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const csrfToken = <?= json_encode($csrfToken, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    let users = <?= json_encode($adminUsers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    let overview = <?= json_encode($adminOverview, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const roles = <?= json_encode($adminRoles, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const offices = <?= json_encode($adminOffices, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    const createModal = document.getElementById('adminCreateModal');
    const editModal = document.getElementById('adminEditModal');
    const openCreateButton = document.getElementById('adminOpenCreateModal');
    const createForm = document.getElementById('adminCreateUserForm');
    const createFirstName = document.getElementById('adminCreateFirstName');
    const createLastName = document.getElementById('adminCreateLastName');
    const createUsername = document.getElementById('adminCreateUsername');
    const createEmail = document.getElementById('adminCreateEmail');
    const createRole = document.getElementById('adminCreateRole');
    const createOffice = document.getElementById('adminCreateOffice');
    const createPassword = document.getElementById('adminCreatePassword');
    const createActive = document.getElementById('adminCreateActive');
    const createButton = document.getElementById('adminCreateUserButton');
    const createResetButton = document.getElementById('adminCreateResetButton');
    const createStatus = document.getElementById('adminCreateStatus');

    const usersBody = document.getElementById('adminUsersBody');
    const usersEmpty = document.getElementById('adminUsersEmpty');
    const searchInput = document.getElementById('adminUserSearch');
    const statusFilter = document.getElementById('adminUserStatusFilter');
    const paginationBackButton = document.getElementById('adminPageBack');
    const paginationNextButton = document.getElementById('adminPageNext');
    const paginationInfo = document.getElementById('adminPageInfo');

    const userForm = document.getElementById('adminUserForm');
    const userIdInput = document.getElementById('adminUserId');
    const userFirstNameInput = document.getElementById('adminUserFirstName');
    const userLastNameInput = document.getElementById('adminUserLastName');
    const userUsernameInput = document.getElementById('adminUserUsername');
    const userEmailInput = document.getElementById('adminUserEmail');
    const userRoleSelect = document.getElementById('adminUserRole');
    const userOfficeSelect = document.getElementById('adminUserOffice');
    const userActiveCheckbox = document.getElementById('adminUserActive');
    const resetSecurityCheckbox = document.getElementById('adminResetSecurity');
    const saveButton = document.getElementById('adminSaveUser');
    const resetOnlyButton = document.getElementById('adminResetOnly');
    const clearSelectionButton = document.getElementById('adminClearSelection');
    const editStatus = document.getElementById('adminEditStatus');

    const statusMessage = document.getElementById('adminUsersStatus');
    const selectedSummary = document.getElementById('adminSelectedSummary');
    const selectedMeta = document.getElementById('adminSelectedMeta');

    const metricUsers = document.getElementById('adminMetricUsers');
    const metricActive = document.getElementById('adminMetricActive');
    const metricInactive = document.getElementById('adminMetricInactive');
    const metricLocked = document.getElementById('adminMetricLocked');

    let selectedUserId = 0;
    let currentPage = 1;
    const pageSize = 15;

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
        return String(user && user.locked_until || '').trim() !== '';
    }

    function normalizeUsernamePart(value) {
        let normalized = String(value || '').trim();
        if (!normalized) {
            return '';
        }

        if (typeof normalized.normalize === 'function') {
            normalized = normalized.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }

        normalized = normalized
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '.')
            .replace(/\.{2,}/g, '.')
            .replace(/^[._-]+|[._-]+$/g, '');

        return normalized;
    }

    function buildUsernamePreview(firstName, lastName) {
        const normalizedFirstName = normalizeUsernamePart(firstName);
        const normalizedLastName = normalizeUsernamePart(lastName);
        const hasSourceName = String(firstName || '').trim() !== '' || String(lastName || '').trim() !== '';
        if (!hasSourceName) {
            return '';
        }

        let preview = [normalizedFirstName, normalizedLastName].filter(Boolean).join('.');
        if (!preview) {
            preview = 'user';
        }

        preview = preview.slice(0, 50);
        if (preview.length < 3) {
            preview = (preview + 'user').slice(0, 3);
        }

        return preview.replace(/[._-]+$/g, '');
    }

    function syncCreateUsernamePreview() {
        if (!createUsername) {
            return '';
        }

        const preview = buildUsernamePreview(
            createFirstName ? createFirstName.value : '',
            createLastName ? createLastName.value : ''
        );
        createUsername.value = preview;
        return preview;
    }

    function openModal(modal) {
        if (!modal) {
            return;
        }
        modal.hidden = false;
        document.body.classList.add('modal-open');
    }

    function closeModal(modal) {
        if (!modal) {
            return;
        }
        modal.hidden = true;
        if ((createModal && !createModal.hidden) || (editModal && !editModal.hidden)) {
            return;
        }
        document.body.classList.remove('modal-open');
    }

    function setStatus(message, isError) {
        if (!statusMessage) {
            return;
        }
        statusMessage.textContent = String(message || '');
        statusMessage.classList.toggle('is-error', !!isError);
    }

    function setCreateStatus(message, isError) {
        if (!createStatus) {
            return;
        }
        createStatus.textContent = String(message || '');
        createStatus.classList.toggle('is-error', !!isError);
    }

    function setEditStatus(message, isError) {
        if (!editStatus) {
            return;
        }
        editStatus.textContent = String(message || '');
        editStatus.classList.toggle('is-error', !!isError);
    }

    function setBusy(isBusy) {
        const disabled = !!isBusy;
        [
            openCreateButton,
            createButton,
            createResetButton,
            saveButton,
            resetOnlyButton,
            clearSelectionButton
        ].forEach(function (button) {
            if (button) {
                button.disabled = disabled || (button.dataset.requiresSelection === 'true' && selectedUserId <= 0);
            }
        });
    }

    function buildSelectOptions() {
        const roleMarkup = '<option value="">Select role</option>';
        const officeMarkup = '<option value="">Select office</option>';

        createRole.innerHTML = roleMarkup;
        userRoleSelect.innerHTML = roleMarkup;
        roles.forEach(function (role) {
            const roleId = Number(role && role.id || 0);
            if (!roleId) {
                return;
            }
            [createRole, userRoleSelect].forEach(function (select) {
                const option = document.createElement('option');
                option.value = String(roleId);
                option.textContent = String(role.name || 'Role');
                select.appendChild(option);
            });
        });

        createOffice.innerHTML = officeMarkup;
        userOfficeSelect.innerHTML = officeMarkup;
        offices.forEach(function (office) {
            const officeId = Number(office && office.id || 0);
            if (!officeId) {
                return;
            }
            [createOffice, userOfficeSelect].forEach(function (select) {
                const option = document.createElement('option');
                option.value = String(officeId);
                option.textContent = String((office.name || 'Office') + ' [' + (office.level || '-') + ']');
                select.appendChild(option);
            });
        });
    }

    function updateMetrics() {
        if (metricUsers) metricUsers.textContent = String(overview && overview.users_total || 0);
        if (metricActive) metricActive.textContent = String(overview && overview.users_active || 0);
        if (metricInactive) metricInactive.textContent = String(overview && overview.users_inactive || 0);
        if (metricLocked) metricLocked.textContent = String(overview && overview.users_locked || 0);
    }

    function getFilteredUsers() {
        const searchTerm = String(searchInput ? searchInput.value : '').toLowerCase().trim();
        const statusValue = String(statusFilter ? statusFilter.value : '').toLowerCase().trim();

        return users.filter(function (user) {
            const fullName = String((user.first_name || '') + ' ' + (user.last_name || '')).trim();
            const rowText = [fullName, user.username, user.email, user.role_name, user.office_name].join(' ').toLowerCase();
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
    }

    function renderUsersTable() {
        if (!usersBody) {
            return;
        }

        const filtered = getFilteredUsers();
        if (filtered.length === 0) {
            usersBody.innerHTML = '<tr><td colspan="9" class="table-empty-state">No users found for current filters.</td></tr>';
            if (usersEmpty) usersEmpty.hidden = false;
            if (paginationBackButton) paginationBackButton.disabled = true;
            if (paginationNextButton) paginationNextButton.disabled = true;
            if (paginationInfo) paginationInfo.textContent = 'Page 0 of 0';
            return;
        }

        if (usersEmpty) usersEmpty.hidden = true;

        const totalPages = Math.max(1, Math.ceil(filtered.length / pageSize));
        currentPage = Math.min(Math.max(currentPage, 1), totalPages);
        const pageStart = (currentPage - 1) * pageSize;
        const visibleRows = filtered.slice(pageStart, pageStart + pageSize);

        if (paginationBackButton) paginationBackButton.disabled = currentPage <= 1;
        if (paginationNextButton) paginationNextButton.disabled = currentPage >= totalPages;
        if (paginationInfo) paginationInfo.textContent = 'Page ' + currentPage + ' of ' + totalPages;

        usersBody.innerHTML = visibleRows.map(function (user) {
            const userId = Number(user.id || 0);
            const name = String((user.first_name || '') + ' ' + (user.last_name || '')).trim() || 'Unknown User';
            const isActive = !!user.is_active;
            const isLocked = normalizeLockState(user);
            const statusLabel = isActive ? 'Active' : 'Blocked';
            const lockLabel = isLocked ? (user.locked_until_label || 'Locked') : 'No lock';
            const activeClass = isActive ? 'status-pill is-active' : 'status-pill is-inactive';
            const selectedClass = userId === selectedUserId ? 'is-selected-row' : '';

            return `
                <tr class="${selectedClass}" data-user-id="${userId}">
                    <td>${escapeHtml(name)}</td>
                    <td>${escapeHtml(user.username || '-')}</td>
                    <td>${escapeHtml(user.email || '-')}</td>
                    <td>${escapeHtml(user.role_name || '-')}</td>
                    <td>${escapeHtml(user.office_name || '-')}</td>
                    <td><span class="${activeClass}">${escapeHtml(statusLabel)}</span></td>
                    <td>${escapeHtml(user.last_login_label || '-')}</td>
                    <td>${escapeHtml(lockLabel)}</td>
                    <td>
                        <div class="admin-row-actions">
                            <button type="button" class="admin-icon-btn" data-user-edit="${userId}" aria-label="Edit account" title="Edit account">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 20h9"></path>
                                    <path d="M16.5 3.5a2.12 2.12 0 1 1 3 3L7 19l-4 1 1-4Z"></path>
                                </svg>
                            </button>
                            <button
                                type="button"
                                class="admin-icon-btn"
                                data-user-toggle="${userId}"
                                aria-label="${isActive ? 'Block account' : 'Unblock account'}"
                                title="${isActive ? 'Block account' : 'Unblock account'}"
                            >
                                ${isActive
                                    ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="10" rx="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>'
                                    : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="10" rx="2"></rect><path d="M7 11V7a5 5 0 0 1 9.2-2.8"></path><path d="m14 4 3 3-3 3"></path></svg>'}
                            </button>
                            <button type="button" class="admin-icon-btn admin-icon-btn-danger" data-user-delete="${userId}" aria-label="Delete account" title="Delete account">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M3 6h18"></path>
                                    <path d="M8 6V4h8v2"></path>
                                    <path d="M19 6l-1 14H6L5 6"></path>
                                    <path d="M10 11v6"></path>
                                    <path d="M14 11v6"></path>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        Array.from(usersBody.querySelectorAll('tr[data-user-id]')).forEach(function (row) {
            row.addEventListener('click', function () {
                selectUser(Number(row.getAttribute('data-user-id') || 0));
            });
        });

        Array.from(usersBody.querySelectorAll('[data-user-edit]')).forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.stopPropagation();
                selectUser(Number(button.getAttribute('data-user-edit') || 0));
                openEditModalForSelection();
            });
        });

        Array.from(usersBody.querySelectorAll('[data-user-delete]')).forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.stopPropagation();
                selectUser(Number(button.getAttribute('data-user-delete') || 0));
                runDeleteForSelectedUser();
            });
        });

        Array.from(usersBody.querySelectorAll('[data-user-toggle]')).forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.stopPropagation();
                selectUser(Number(button.getAttribute('data-user-toggle') || 0));
                runToggleForSelectedUser();
            });
        });
    }

    function resetCreateForm() {
        if (createForm) {
            createForm.reset();
        }
        if (createActive) {
            createActive.checked = true;
        }
        syncCreateUsernamePreview();
        setCreateStatus('Fill out the form to create a scoped local account. Username will be generated automatically.', false);
    }

    function syncSelectionControls() {
        const selectedUser = users.find(function (user) {
            return Number(user.id || 0) === selectedUserId;
        }) || null;
        const hasSelection = !!selectedUser;

        [resetOnlyButton].forEach(function (button) {
            if (button) {
                button.disabled = !hasSelection;
                button.dataset.requiresSelection = 'true';
            }
        });

        if (!hasSelection) {
            if (selectedSummary) selectedSummary.textContent = 'No account selected';
            if (selectedMeta) selectedMeta.textContent = 'Click a row in the table to select it. Use the row icons for edit, block, and delete.';
            return;
        }

        const fullName = String((selectedUser.first_name || '') + ' ' + (selectedUser.last_name || '')).trim() || String(selectedUser.email || 'Selected account');
        if (selectedSummary) {
            selectedSummary.textContent = fullName;
        }
        if (selectedMeta) {
            selectedMeta.textContent = '@' + String(selectedUser.username || '-') + ' | ' + String(selectedUser.role_name || '-') + ' | ' + String(selectedUser.office_name || '-') + ' | ' + (selectedUser.is_active ? 'Active' : 'Blocked');
        }
    }

    function clearSelection() {
        selectedUserId = 0;
        if (userIdInput) userIdInput.value = '';
        if (userFirstNameInput) userFirstNameInput.value = '';
        if (userLastNameInput) userLastNameInput.value = '';
        if (userUsernameInput) userUsernameInput.value = '';
        if (userEmailInput) userEmailInput.value = '';
        if (userRoleSelect) userRoleSelect.value = '';
        if (userOfficeSelect) userOfficeSelect.value = '';
        if (userActiveCheckbox) userActiveCheckbox.checked = true;
        if (resetSecurityCheckbox) resetSecurityCheckbox.checked = false;
        syncSelectionControls();
        renderUsersTable();
        setEditStatus('Save your changes when you are ready.', false);
    }

    function ensureSelectedUserVisible(userId) {
        const filtered = getFilteredUsers();
        const targetIndex = filtered.findIndex(function (user) {
            return Number(user.id || 0) === Number(userId || 0);
        });
        if (targetIndex >= 0) {
            currentPage = Math.floor(targetIndex / pageSize) + 1;
        }
    }

    function selectUser(userId) {
        const target = users.find(function (user) {
            return Number(user.id || 0) === Number(userId || 0);
        });

        if (!target) {
            return;
        }

        selectedUserId = Number(target.id || 0);
        ensureSelectedUserVisible(selectedUserId);
        if (userIdInput) userIdInput.value = String(selectedUserId);
        if (userFirstNameInput) userFirstNameInput.value = String(target.first_name || '');
        if (userLastNameInput) userLastNameInput.value = String(target.last_name || '');
        if (userUsernameInput) userUsernameInput.value = String(target.username || '');
        if (userEmailInput) userEmailInput.value = String(target.email || '');
        if (userRoleSelect) userRoleSelect.value = String(target.role_id || '');
        if (userOfficeSelect) userOfficeSelect.value = String(target.office_id || '');
        if (userActiveCheckbox) userActiveCheckbox.checked = !!target.is_active;
        if (resetSecurityCheckbox) resetSecurityCheckbox.checked = false;

        syncSelectionControls();
        renderUsersTable();
        setStatus('Selected user #' + selectedUserId + '.', false);
        setEditStatus('Save your changes when you are ready.', false);
    }

    function openEditModalForSelection() {
        if (selectedUserId <= 0) {
            setStatus('Select a user first.', true);
            return;
        }
        openModal(editModal);
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
            throw new Error(String(json.message || 'Unable to process the request.'));
        }

        return json;
    }

    function updateUsersFromResponse(json) {
        if (json && Array.isArray(json.users)) {
            users = json.users.slice();
        }
        if (json && json.overview) {
            overview = json.overview;
        }
        updateMetrics();

        if (selectedUserId > 0) {
            const stillExists = users.some(function (user) {
                return Number(user.id || 0) === selectedUserId;
            });
            if (stillExists) {
                selectUser(selectedUserId);
                return;
            }
            clearSelection();
        }

        syncSelectionControls();
        renderUsersTable();
    }

    Array.from(document.querySelectorAll('[data-admin-create-close="true"]')).forEach(function (button) {
        button.addEventListener('click', function () {
            closeModal(createModal);
        });
    });

    Array.from(document.querySelectorAll('[data-admin-edit-close="true"]')).forEach(function (button) {
        button.addEventListener('click', function () {
            closeModal(editModal);
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
            return;
        }
        if (createModal && !createModal.hidden) {
            closeModal(createModal);
        }
        if (editModal && !editModal.hidden) {
            closeModal(editModal);
        }
    });

    if (openCreateButton) {
        openCreateButton.addEventListener('click', function () {
            resetCreateForm();
            openModal(createModal);
        });
    }

    if (createResetButton) {
        createResetButton.addEventListener('click', function () {
            resetCreateForm();
            setCreateStatus('Form cleared.', false);
        });
    }

    [createFirstName, createLastName].forEach(function (input) {
        if (!input) {
            return;
        }
        input.addEventListener('input', function () {
            syncCreateUsernamePreview();
        });
    });

    if (createForm) {
        createForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            const firstName = String(createFirstName ? createFirstName.value : '').trim();
            const lastName = String(createLastName ? createLastName.value : '').trim();
            const username = syncCreateUsernamePreview();
            const email = String(createEmail ? createEmail.value : '').trim();
            const roleId = Number(createRole ? createRole.value : 0);
            const officeId = Number(createOffice ? createOffice.value : 0);
            const password = String(createPassword ? createPassword.value : '');

            if (!firstName || !lastName || !email || !roleId || !officeId || password.length < 8) {
                setCreateStatus('Complete all required fields before creating the account.', true);
                return;
            }

            try {
                setBusy(true);
                const result = await postAction({
                    action: 'CREATE_USER',
                    first_name: firstName,
                    last_name: lastName,
                    username: username,
                    email: email,
                    role_id: roleId,
                    office_id: officeId,
                    password: password,
                    is_active: createActive && createActive.checked ? 1 : 0
                });

                updateUsersFromResponse(result);
                resetCreateForm();
                closeModal(createModal);
                setStatus(result.message || 'Local user account created successfully.', false);
            } catch (error) {
                setCreateStatus(error.message || 'Unable to create the local account.', true);
            } finally {
                setBusy(false);
            }
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            currentPage = 1;
            renderUsersTable();
        });
        const searchForm = searchInput.closest('form');
        if (searchForm) {
            searchForm.addEventListener('submit', function (event) {
                event.preventDefault();
            });
        }
    }

    if (statusFilter) {
        statusFilter.addEventListener('change', function () {
            currentPage = 1;
            renderUsersTable();
        });
    }

    if (paginationBackButton) {
        paginationBackButton.addEventListener('click', function () {
            if (currentPage > 1) {
                currentPage -= 1;
                renderUsersTable();
            }
        });
    }

    if (paginationNextButton) {
        paginationNextButton.addEventListener('click', function () {
            currentPage += 1;
            renderUsersTable();
        });
    }

    if (clearSelectionButton) {
        clearSelectionButton.addEventListener('click', function () {
            clearSelection();
            closeModal(editModal);
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

    async function runToggleForSelectedUser() {
        const userId = Number(userIdInput ? userIdInput.value : 0);
        const roleId = Number(userRoleSelect ? userRoleSelect.value : 0);
        const officeId = Number(userOfficeSelect ? userOfficeSelect.value : 0);
        const firstName = String(userFirstNameInput ? userFirstNameInput.value : '').trim();
        const lastName = String(userLastNameInput ? userLastNameInput.value : '').trim();
        const username = String(userUsernameInput ? userUsernameInput.value : '').trim().toLowerCase();
        const email = String(userEmailInput ? userEmailInput.value : '').trim();
        if (!userId || !roleId || !officeId || !firstName || !lastName || !username || !email) {
            setStatus('Select a user first.', true);
            return;
        }

        try {
            setBusy(true);
            const nextActiveState = userActiveCheckbox && userActiveCheckbox.checked ? 0 : 1;
            const result = await postAction({
                action: 'UPDATE_USER',
                user_id: userId,
                first_name: firstName,
                last_name: lastName,
                username: username,
                email: email,
                role_id: roleId,
                office_id: officeId,
                is_active: nextActiveState,
                reset_security: 0
            });
            updateUsersFromResponse(result);
            setStatus(nextActiveState === 1 ? 'Account unblocked successfully.' : 'Account blocked successfully.', false);
        } catch (error) {
            setStatus(error.message || 'Unable to update account state.', true);
        } finally {
            setBusy(false);
        }
    }

    async function runDeleteForSelectedUser() {
        const userId = Number(userIdInput ? userIdInput.value : 0);
        if (!userId) {
            setStatus('Select a user first.', true);
            return;
        }

        if (!window.confirm('Move this user account to the recycle bin? Super Admin can restore it later if needed.')) {
            return;
        }

        try {
            setBusy(true);
            const result = await postAction({ action: 'DELETE_USER', user_id: userId });
            selectedUserId = 0;
            updateUsersFromResponse(result);
            closeModal(editModal);
            setStatus(result.message || 'User account moved to recycle bin.', false);
        } catch (error) {
            setStatus(error.message || 'Unable to delete the user account.', true);
        } finally {
            setBusy(false);
        }
    }

    if (userForm) {
        userForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            const userId = Number(userIdInput ? userIdInput.value : 0);
            const firstName = String(userFirstNameInput ? userFirstNameInput.value : '').trim();
            const lastName = String(userLastNameInput ? userLastNameInput.value : '').trim();
            const username = String(userUsernameInput ? userUsernameInput.value : '').trim().toLowerCase();
            const email = String(userEmailInput ? userEmailInput.value : '').trim();
            const roleId = Number(userRoleSelect ? userRoleSelect.value : 0);
            const officeId = Number(userOfficeSelect ? userOfficeSelect.value : 0);

            if (!userId || !firstName || !lastName || !username || !email || !roleId || !officeId) {
                setEditStatus('Complete first name, last name, username, email, role, and office before saving.', true);
                return;
            }

            try {
                setBusy(true);
                const result = await postAction({
                    action: 'UPDATE_USER',
                    user_id: userId,
                    first_name: firstName,
                    last_name: lastName,
                    username: username,
                    email: email,
                    role_id: roleId,
                    office_id: officeId,
                    is_active: userActiveCheckbox && userActiveCheckbox.checked ? 1 : 0,
                    reset_security: resetSecurityCheckbox && resetSecurityCheckbox.checked ? 1 : 0
                });
                updateUsersFromResponse(result);
                closeModal(editModal);
                setStatus(result.message || 'User updated successfully.', false);
            } catch (error) {
                setEditStatus(error.message || 'Unable to save user changes.', true);
            } finally {
                setBusy(false);
            }
        });
    }

    buildSelectOptions();
    updateMetrics();
    resetCreateForm();
    clearSelection();
    syncSelectionControls();
    renderUsersTable();
})();
</script>
