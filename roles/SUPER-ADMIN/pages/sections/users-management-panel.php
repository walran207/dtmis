<?php
declare(strict_types=1);

$superAdminUsers = is_array($superAdminUsers ?? null) ? $superAdminUsers : [];
$superAdminRoles = is_array($superAdminRoles ?? null) ? $superAdminRoles : [];
$superAdminOffices = is_array($superAdminOffices ?? null) ? $superAdminOffices : [];
$superAdminOfficeOptionsPayload = is_array($superAdminOfficeOptionsPayload ?? null) ? $superAdminOfficeOptionsPayload : [];
$superAdminFixedOfficeIdByRoleName = is_array($superAdminFixedOfficeIdByRoleName ?? null) ? $superAdminFixedOfficeIdByRoleName : [];
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
                <input id="superAdminUserSearch" type="search" placeholder="Search name, username, email, role, office">
            </form>
            <label class="super-admin-filter" for="superAdminUserStatusFilter">
                <span>Status</span>
                <select id="superAdminUserStatusFilter">
                    <option value="">All</option>
                    <option value="active">Active</option>
                    <option value="blocked">Blocked</option>
                    <option value="locked">Locked</option>
                </select>
            </label>
            <button type="button" class="super-admin-btn super-admin-btn-secondary" id="superAdminOpenImportUsers">Import Users</button>
            <button type="button" class="super-admin-btn super-admin-btn-primary" id="superAdminOpenCreateUser">Add User</button>
        </div>

        <div class="super-admin-table-wrap">
            <table class="super-admin-table" id="superAdminUsersTable">
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
                <tbody id="superAdminUsersBody">
                    <tr>
                        <td colspan="9" class="table-empty-state">Loading users...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="super-admin-pagination" aria-label="Users table pagination">
            <button type="button" class="super-admin-btn super-admin-btn-secondary" id="superAdminUsersPrev">Back</button>
            <p id="superAdminUsersPageInfo" class="super-admin-pagination-info">Page 1 of 1</p>
            <button type="button" class="super-admin-btn super-admin-btn-secondary" id="superAdminUsersNext">Next</button>
        </div>
        <p id="superAdminUsersEmpty" class="super-admin-empty" hidden>No users match your current filters.</p>
    </article>

    <article class="card super-admin-panel super-admin-panel-full">
        <p id="superAdminUsersStatus" class="super-admin-status" role="status" aria-live="polite">Select a user to begin.</p>
    </article>
</section>

<div id="superAdminEditUserModal" class="action-modal" hidden>
    <button type="button" class="action-modal-backdrop" data-super-admin-edit-close="true" aria-label="Close edit user dialog"></button>
    <div class="action-modal-dialog details-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="superAdminEditUserModalTitle">
        <form id="superAdminUserForm" class="action-modal-form details-modal-form super-admin-form" autocomplete="off">
            <h3 id="superAdminEditUserModalTitle" class="action-modal-title">Edit Selected User</h3>
            <p class="action-modal-meta">Changes apply immediately. Security reset clears lock and MFA counters.</p>

            <input type="hidden" id="superAdminUserId" value="">
            <label class="super-admin-field">
                <span>Full Name</span>
                <input type="text" id="superAdminUserName" value="" disabled>
            </label>
            <label class="super-admin-field">
                <span>Username</span>
                <input type="text" id="superAdminUserUsername" value="" disabled>
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

            <p id="superAdminEditStatus" class="super-admin-status" role="status" aria-live="polite">Save your changes when you are ready.</p>

            <div class="action-modal-actions">
                <button type="button" class="super-admin-btn super-admin-btn-secondary" data-super-admin-edit-close="true">Cancel</button>
                <button type="button" class="super-admin-btn super-admin-btn-secondary" id="superAdminResetOnly">Reset Security</button>
                <button type="button" class="super-admin-btn super-admin-btn-secondary" id="superAdminClearSelection">Clear</button>
                <button type="submit" class="super-admin-btn super-admin-btn-primary" id="superAdminSaveUser">Save User</button>
            </div>
        </form>
    </div>
</div>

<div id="superAdminCreateUserModal" class="action-modal" hidden>
    <button type="button" class="action-modal-backdrop" data-super-admin-create-close="true" aria-label="Close create user dialog"></button>
    <div class="action-modal-dialog details-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="superAdminCreateUserModalTitle">
        <form id="superAdminCreateUserForm" class="action-modal-form details-modal-form super-admin-form" autocomplete="off">
            <h3 id="superAdminCreateUserModalTitle" class="action-modal-title">Add User</h3>
            <p class="action-modal-meta">Create a new local DTMIS account for any office and role. Default password is <strong>denr@12</strong> and the user must change it on first login.</p>

            <label class="super-admin-field">
                <span>First Name</span>
                <input type="text" id="superAdminCreateFirstName" maxlength="50" required>
            </label>
            <label class="super-admin-field">
                <span>Last Name</span>
                <input type="text" id="superAdminCreateLastName" maxlength="50" required>
            </label>
            <label class="super-admin-field">
                <span>Username</span>
                <input type="text" id="superAdminCreateUsername" maxlength="50" placeholder="Generated automatically" readonly>
            </label>
            <label class="super-admin-field">
                <span>Email</span>
                <input type="email" id="superAdminCreateEmail" maxlength="100" placeholder="name@denr.gov.ph" required>
            </label>
            <label class="super-admin-field">
                <span>Role</span>
                <select id="superAdminCreateRole" required>
                    <option value="">Select role</option>
                </select>
            </label>
            <label class="super-admin-field">
                <span>Office</span>
                <select id="superAdminCreateOffice" required>
                    <option value="">Select office</option>
                </select>
            </label>
            <label class="super-admin-checkbox" for="superAdminCreateActive">
                <input type="checkbox" id="superAdminCreateActive" checked>
                <span>Create account as active</span>
            </label>

            <p id="superAdminCreateStatus" class="super-admin-status" role="status" aria-live="polite">Fill out the form to create a new user.</p>

            <div class="action-modal-actions">
                <button type="button" class="super-admin-btn super-admin-btn-secondary" data-super-admin-create-close="true">Cancel</button>
                <button type="button" class="super-admin-btn super-admin-btn-secondary" id="superAdminCreateUserReset">Clear</button>
                <button type="submit" class="super-admin-btn super-admin-btn-primary" id="superAdminCreateUserSubmit">Create User</button>
            </div>
        </form>
    </div>
</div>

<div id="superAdminImportUsersModal" class="action-modal" hidden>
    <button type="button" class="action-modal-backdrop" data-super-admin-import-close="true" aria-label="Close import users dialog"></button>
    <div class="action-modal-dialog details-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="superAdminImportUsersModalTitle">
        <form id="superAdminImportUsersForm" class="action-modal-form details-modal-form super-admin-form" autocomplete="off">
            <h3 id="superAdminImportUsersModalTitle" class="action-modal-title">Import Users</h3>
            <p id="superAdminImportUsersMeta" class="action-modal-meta">Upload a CSV or Excel <strong>.xlsx</strong> file. Imported accounts use the default password <strong>denr@12</strong> and must change it on first login.</p>

            <label class="super-admin-field">
                <span>Import File</span>
                <input type="file" id="superAdminImportUsersFile" accept=".csv,.xlsx" required>
            </label>
            <p class="super-admin-file-help">Required columns: <code>first_name</code>, <code>last_name</code>, <code>email</code>, <code>role</code>, <code>office</code>. Optional: <code>is_active</code>. Usernames are generated automatically.</p>
            <ul class="super-admin-note-list">
                <li>Use exact role names and office names from DTMIS.</li>
                <li><code>is_active</code> accepts <code>1</code>, <code>0</code>, <code>yes</code>, <code>no</code>, <code>active</code>, or <code>inactive</code>.</li>
                <li>The import stops if any row has an error so no partial batch is created.</li>
            </ul>

            <p id="superAdminImportStatus" class="super-admin-status" role="status" aria-live="polite">Choose a CSV or Excel file to begin importing users.</p>
            <div id="superAdminImportErrors" class="super-admin-errors" hidden></div>

            <div class="action-modal-actions">
                <button type="button" class="super-admin-btn super-admin-btn-secondary" id="superAdminDownloadImportTemplate">Download CSV Template</button>
                <button type="button" class="super-admin-btn super-admin-btn-secondary" data-super-admin-import-close="true">Cancel</button>
                <button type="button" class="super-admin-btn super-admin-btn-secondary" id="superAdminImportUsersReset">Clear</button>
                <button type="submit" class="super-admin-btn super-admin-btn-primary" id="superAdminImportUsersSubmit">Import Users</button>
            </div>
        </form>
    </div>
</div>

<div id="superAdminConfirmModal" class="action-modal" hidden>
    <button type="button" class="action-modal-backdrop" data-super-admin-confirm-close="true" aria-label="Close confirmation dialog"></button>
    <div class="action-modal-dialog details-modal-dialog super-admin-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="superAdminConfirmTitle">
        <div class="action-modal-form super-admin-confirm-shell">
            <div class="super-admin-confirm-head">
                <div id="superAdminConfirmIcon" class="super-admin-confirm-icon" aria-hidden="true"></div>
                <div class="super-admin-confirm-copy">
                    <h3 id="superAdminConfirmTitle" class="action-modal-title">Confirm Action</h3>
                    <p id="superAdminConfirmMessage" class="action-modal-meta">Please confirm this action.</p>
                </div>
            </div>
            <p id="superAdminConfirmNote" class="super-admin-confirm-note" hidden></p>
            <div class="action-modal-actions super-admin-confirm-actions">
                <button type="button" id="superAdminConfirmCancel" class="super-admin-btn super-admin-btn-secondary" data-super-admin-confirm-close="true">Cancel</button>
                <button type="button" id="superAdminConfirmSubmit" class="super-admin-btn super-admin-btn-primary">Confirm</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const endpoint = <?= json_encode($superAdminUsersEndpoint, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const csrfToken = <?= json_encode($csrfToken, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    let users = <?= json_encode($superAdminUsers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    let roles = <?= json_encode($superAdminRoles, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    let offices = <?= json_encode($superAdminOffices, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    let officeOptionsPayload = <?= json_encode($superAdminOfficeOptionsPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    let fixedOfficeIdByRoleName = <?= json_encode($superAdminFixedOfficeIdByRoleName, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    const usersBody = document.getElementById('superAdminUsersBody');
    const usersEmpty = document.getElementById('superAdminUsersEmpty');
    const searchInput = document.getElementById('superAdminUserSearch');
    const statusFilter = document.getElementById('superAdminUserStatusFilter');
    const paginationPrevButton = document.getElementById('superAdminUsersPrev');
    const paginationNextButton = document.getElementById('superAdminUsersNext');
    const paginationInfo = document.getElementById('superAdminUsersPageInfo');
    const openImportUsersButton = document.getElementById('superAdminOpenImportUsers');
    const openCreateUserButton = document.getElementById('superAdminOpenCreateUser');
    const importModal = document.getElementById('superAdminImportUsersModal');
    const createModal = document.getElementById('superAdminCreateUserModal');
    const editModal = document.getElementById('superAdminEditUserModal');
    const confirmModal = document.getElementById('superAdminConfirmModal');
    const confirmTitleNode = document.getElementById('superAdminConfirmTitle');
    const confirmMessageNode = document.getElementById('superAdminConfirmMessage');
    const confirmNoteNode = document.getElementById('superAdminConfirmNote');
    const confirmIconNode = document.getElementById('superAdminConfirmIcon');
    const confirmCancelButton = document.getElementById('superAdminConfirmCancel');
    const confirmSubmitButton = document.getElementById('superAdminConfirmSubmit');

    const importUsersForm = document.getElementById('superAdminImportUsersForm');
    const importUsersFileInput = document.getElementById('superAdminImportUsersFile');
    const importUsersResetButton = document.getElementById('superAdminImportUsersReset');
    const importUsersSubmitButton = document.getElementById('superAdminImportUsersSubmit');
    const importUsersMeta = document.getElementById('superAdminImportUsersMeta');
    const importStatusMessage = document.getElementById('superAdminImportStatus');
    const importErrorsNode = document.getElementById('superAdminImportErrors');
    const downloadImportTemplateButton = document.getElementById('superAdminDownloadImportTemplate');

    const createUserForm = document.getElementById('superAdminCreateUserForm');
    const createFirstNameInput = document.getElementById('superAdminCreateFirstName');
    const createLastNameInput = document.getElementById('superAdminCreateLastName');
    const createUsernameInput = document.getElementById('superAdminCreateUsername');
    const createEmailInput = document.getElementById('superAdminCreateEmail');
    const createRoleSelect = document.getElementById('superAdminCreateRole');
    const createOfficeSelect = document.getElementById('superAdminCreateOffice');
    const createActiveCheckbox = document.getElementById('superAdminCreateActive');
    const createResetButton = document.getElementById('superAdminCreateUserReset');
    const createSubmitButton = document.getElementById('superAdminCreateUserSubmit');
    const createStatusMessage = document.getElementById('superAdminCreateStatus');

    const userForm = document.getElementById('superAdminUserForm');
    const userIdInput = document.getElementById('superAdminUserId');
    const userNameInput = document.getElementById('superAdminUserName');
    const userUsernameInput = document.getElementById('superAdminUserUsername');
    const userEmailInput = document.getElementById('superAdminUserEmail');
    const userRoleSelect = document.getElementById('superAdminUserRole');
    const userOfficeSelect = document.getElementById('superAdminUserOffice');
    const userActiveCheckbox = document.getElementById('superAdminUserActive');
    const resetSecurityCheckbox = document.getElementById('superAdminResetSecurity');
    const resetOnlyButton = document.getElementById('superAdminResetOnly');
    const clearSelectionButton = document.getElementById('superAdminClearSelection');
    const saveButton = document.getElementById('superAdminSaveUser');
    const statusMessage = document.getElementById('superAdminUsersStatus');
    const editStatusMessage = document.getElementById('superAdminEditStatus');

    let selectedUserId = 0;
    let xlsxImportSupported = true;
    let pendingConfirmationResolver = null;
    const usersPager = window.createSuperAdminTablePager({
        prevButton: paginationPrevButton,
        nextButton: paginationNextButton,
        infoNode: paginationInfo,
        pageSize: 10
    });
    let createOfficeFilter = bindRoleOfficeFilter(createRoleSelect, createOfficeSelect);
    let editOfficeFilter = bindRoleOfficeFilter(userRoleSelect, userOfficeSelect);

    function setStatus(message, isError) {
        if (!statusMessage) {
            return;
        }
        statusMessage.textContent = String(message || '');
        statusMessage.classList.toggle('is-error', !!isError);
    }

    function setEditStatus(message, isError) {
        if (!editStatusMessage) {
            return;
        }
        editStatusMessage.textContent = String(message || '');
        editStatusMessage.classList.toggle('is-error', !!isError);
    }

    function setCreateStatus(message, isError) {
        if (!createStatusMessage) {
            return;
        }
        createStatusMessage.textContent = String(message || '');
        createStatusMessage.classList.toggle('is-error', !!isError);
    }

    function setImportStatus(message, isError) {
        if (!importStatusMessage) {
            return;
        }
        importStatusMessage.textContent = String(message || '');
        importStatusMessage.classList.toggle('is-error', !!isError);
    }

    function getImportChooserLabel() {
        return xlsxImportSupported ? 'CSV or Excel file' : 'CSV file';
    }

    function getImportStartMessage() {
        return 'Choose a ' + getImportChooserLabel() + ' to begin importing users.';
    }

    function applyImportCapabilityState() {
        if (importUsersFileInput) {
            importUsersFileInput.setAttribute('accept', xlsxImportSupported ? '.csv,.xlsx' : '.csv');
        }

        if (importUsersMeta) {
            importUsersMeta.innerHTML = xlsxImportSupported
                ? 'Upload a CSV or Excel <strong>.xlsx</strong> file. Imported accounts use the default password <strong>denr@12</strong> and must change it on first login.'
                : 'Upload a <strong>.csv</strong> file. Excel <strong>.xlsx</strong> import is unavailable on this server right now. Imported accounts use the default password <strong>denr@12</strong> and must change it on first login.';
        }
    }

    function renderImportErrors(errors) {
        if (!importErrorsNode) {
            return;
        }

        if (!Array.isArray(errors) || errors.length === 0) {
            importErrorsNode.hidden = true;
            importErrorsNode.innerHTML = '';
            return;
        }

        importErrorsNode.hidden = false;
        importErrorsNode.innerHTML = '<ul>' + errors.map(function (errorMessage) {
            return '<li>' + escapeHtml(errorMessage) + '</li>';
        }).join('') + '</ul>';
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
        document.body.classList.remove('modal-open');
    }

    function buildConfirmationIconHtml(tone) {
        if (tone === 'danger') {
            return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8v4" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"></path><path d="M12 16h.01" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"></path><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"></path></svg>';
        }

        if (tone === 'warning') {
            return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4.5 12a7.5 7.5 0 1 0 2.2-5.3L4 9.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path><path d="M4 4v5.5h5.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path><path d="M12 9v3l2 2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path></svg>';
        }

        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 12 2 2 4-4" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"></path><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="1.8"></circle></svg>';
    }

    function resolveConfirmation(result) {
        if (typeof pendingConfirmationResolver === 'function') {
            const resolver = pendingConfirmationResolver;
            pendingConfirmationResolver = null;
            resolver(!!result);
        }
    }

    function closeConfirmation(result) {
        closeModal(confirmModal);
        resolveConfirmation(result);
    }

    function requestConfirmation(options) {
        const config = options && typeof options === 'object' ? options : {};
        const tone = String(config.tone || 'danger').toLowerCase();
        const confirmLabel = String(config.confirmLabel || 'Confirm');
        const cancelLabel = String(config.cancelLabel || 'Cancel');
        const title = String(config.title || 'Confirm Action');
        const message = String(config.message || 'Please confirm this action.');
        const note = String(config.note || '');

        if (confirmTitleNode) {
            confirmTitleNode.textContent = title;
        }
        if (confirmMessageNode) {
            confirmMessageNode.textContent = message;
        }
        if (confirmNoteNode) {
            confirmNoteNode.textContent = note;
            confirmNoteNode.hidden = note === '';
        }
        if (confirmIconNode) {
            confirmIconNode.className = 'super-admin-confirm-icon is-' + tone;
            confirmIconNode.innerHTML = buildConfirmationIconHtml(tone);
        }
        if (confirmCancelButton) {
            confirmCancelButton.textContent = cancelLabel;
        }
        if (confirmSubmitButton) {
            confirmSubmitButton.textContent = confirmLabel;
            confirmSubmitButton.className = tone === 'danger'
                ? 'super-admin-btn super-admin-btn-danger'
                : 'super-admin-btn super-admin-btn-primary';
        }

        openModal(confirmModal);
        window.setTimeout(function () {
            if (confirmSubmitButton) {
                confirmSubmitButton.focus();
            }
        }, 0);

        return new Promise(function (resolve) {
            pendingConfirmationResolver = resolve;
        });
    }

    function setBusy(isBusy) {
        const disabled = !!isBusy;
        if (openImportUsersButton) {
            openImportUsersButton.disabled = disabled;
        }
        if (openCreateUserButton) {
            openCreateUserButton.disabled = disabled;
        }
        if (importUsersSubmitButton) {
            importUsersSubmitButton.disabled = disabled;
        }
        if (importUsersResetButton) {
            importUsersResetButton.disabled = disabled;
        }
        if (downloadImportTemplateButton) {
            downloadImportTemplateButton.disabled = disabled;
        }
        if (createSubmitButton) {
            createSubmitButton.disabled = disabled;
        }
        if (createResetButton) {
            createResetButton.disabled = disabled;
        }
        if (saveButton) {
            saveButton.disabled = disabled;
        }
        if (resetOnlyButton) {
            resetOnlyButton.disabled = disabled;
        }
        if (confirmCancelButton) {
            confirmCancelButton.disabled = disabled;
        }
        if (confirmSubmitButton) {
            confirmSubmitButton.disabled = disabled;
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

    function normalizeRoleName(value) {
        return String(value || '').trim().toUpperCase();
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
        if (!createUsernameInput) {
            return '';
        }

        const preview = buildUsernamePreview(
            createFirstNameInput ? createFirstNameInput.value : '',
            createLastNameInput ? createLastNameInput.value : ''
        );
        createUsernameInput.value = preview;
        return preview;
    }

    function resolveRoleOfficeGroup(roleName) {
        const normalized = normalizeRoleName(roleName);
        if (normalized.indexOf('PAMO_') === 0 || normalized.indexOf('PASU_') === 0) {
            return 'PAMO';
        }
        if (normalized.indexOf('CENRO_') === 0) {
            return 'CENRO';
        }
        if (normalized.indexOf('PENRO_') === 0) {
            return 'PENRO';
        }
        return 'Regional';
    }
    function isOfficeAllowedForRole(roleName, officeLevel, officeName) {
        const role = normalizeRoleName(roleName);
        const level = normalizeRoleName(officeLevel);
        const name = normalizeRoleName(officeName);

        if (role === '') {
            return true;
        }

        if (role === 'CENRO_ADMIN_RECORD') {
            return level === 'CENRO_ADMIN_RECORD' || (name.indexOf('CENRO') !== -1 && name.indexOf('ADMIN RECORD') !== -1);
        }
        if (role === 'CENRO_OFFICER') {
            return level === 'CENRO_OFFICER' || (name.indexOf('CENRO') !== -1 && name.indexOf('OFFICER') !== -1);
        }
        if (role === 'CENRO_SECTION') {
            return level === 'CENRO_SECTION';
        }
        if (role === 'CENRO_UNIT') {
            return level === 'CENRO_UNIT';
        }
        if (role === 'PENRO_ADMIN_RECORD') {
            return level === 'PENRO_ADMIN_RECORD' || (name.indexOf('PENRO') !== -1 && name.indexOf('ADMIN RECORD') !== -1);
        }
        if (role === 'PENRO_OFFICER') {
            return level === 'PENRO_OFFICER' || (name.indexOf('PENRO') !== -1 && name.indexOf('OFFICER') !== -1);
        }
        if (role === 'PENRO_DIVISION') {
            return level === 'PENRO_DIVISION';
        }
        if (role === 'PENRO_SECTION') {
            return level === 'PENRO_SECTION';
        }
        if (role === 'PENRO_SECTION_UNIT') {
            return level === 'PENRO_SECTION' || level === 'PENRO_UNIT';
        }
        if (role === 'PAMO_ADMIN') {
            return level === 'PAMO_ADMIN' || (name.indexOf('PAMO') !== -1 && name.indexOf('ADMIN') !== -1);
        }
        if (role === 'PASU_OFFICER') {
            return level === 'PASU_OFFICER' || (name.indexOf('PASU') !== -1 && name.indexOf('OFFICER') !== -1);
        }
        if (role === 'PAMO_UNIT') {
            return level === 'PAMO_UNIT';
        }
        if (role === 'DIVISION_CHIEF') {
            return level === 'DIVISION' || level === 'PENRO_DIVISION';
        }
        if (role === 'SECTION_STAFF') {
            return level === 'SECTION' || level === 'CENRO_SECTION' || level === 'PENRO_SECTION' || level === 'PENRO_UNIT';
        }

        return true;
    }

    function bindRoleOfficeFilter(roleSelect, officeSelect) {
        if (!roleSelect || !officeSelect) {
            return;
        }

        function renderOfficeOptionsByRole(preferredOfficeValue) {
            const selectedRole = roleSelect.options[roleSelect.selectedIndex];
            const selectedRoleName = roleSelect.value && selectedRole ? String(selectedRole.textContent || '') : '';
            const fixedOfficeId = selectedRole ? String(selectedRole.getAttribute('data-fixed-office-id') || '').trim() : '';
            const allowedGroup = resolveRoleOfficeGroup(selectedRoleName);
            const previousOfficeValue = typeof preferredOfficeValue === 'undefined' ? officeSelect.value : String(preferredOfficeValue || '');
            const groupedOptions = {
                PAMO: [],
                CENRO: [],
                PENRO: [],
                Regional: []
            };

            officeSelect.innerHTML = '';

            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'Select office';
            officeSelect.appendChild(placeholder);

            officeOptionsPayload.forEach(function (optionData) {
                const groupLabel = String(optionData && optionData.group || '').trim();
                if (!groupLabel || !groupedOptions[groupLabel]) {
                    return;
                }

                groupedOptions[groupLabel].push({
                    value: String(optionData && optionData.value || ''),
                    text: String(optionData && optionData.text || ''),
                    group: groupLabel,
                    level: String(optionData && optionData.level || '').trim()
                });
            });

            let availableCount = 0;
            let firstOfficeValue = '';
            ['PAMO', 'CENRO', 'PENRO', 'Regional'].forEach(function (groupLabel) {
                if (allowedGroup && groupLabel !== allowedGroup) {
                    return;
                }

                const options = groupedOptions[groupLabel] || [];
                if (!options.length) {
                    return;
                }

                const filteredOptions = fixedOfficeId !== ''
                    ? options.filter(function (optionData) {
                        return optionData.value === fixedOfficeId;
                    })
                    : options;

                const matchedOptions = fixedOfficeId === ''
                    ? filteredOptions.filter(function (optionData) {
                        return isOfficeAllowedForRole(selectedRoleName, optionData.level, optionData.text);
                    })
                    : filteredOptions;

                const refinedOptions = matchedOptions.length ? matchedOptions : filteredOptions;
                if (!refinedOptions.length) {
                    return;
                }

                const optgroup = document.createElement('optgroup');
                optgroup.label = groupLabel;

                refinedOptions.forEach(function (optionData) {
                    const option = document.createElement('option');
                    option.value = optionData.value;
                    option.textContent = optionData.text;
                    option.setAttribute('data-office-group', optionData.group);
                    option.setAttribute('data-office-level', optionData.level);
                    optgroup.appendChild(option);

                    availableCount += 1;
                    if (firstOfficeValue === '') {
                        firstOfficeValue = optionData.value;
                    }
                });

                officeSelect.appendChild(optgroup);
            });

            const stillAvailable = previousOfficeValue !== ''
                && Array.from(officeSelect.options).some(function (option) {
                    return option.value === previousOfficeValue;
                });

            if (stillAvailable) {
                officeSelect.value = previousOfficeValue;
            } else if (availableCount === 1 && firstOfficeValue !== '') {
                officeSelect.value = firstOfficeValue;
            } else {
                officeSelect.value = '';
            }
        }

        roleSelect.addEventListener('change', function () {
            renderOfficeOptionsByRole('');
        });

        return {
            render: renderOfficeOptionsByRole
        };
    }

    function buildRoleOfficeMaps() {
        if (!createRoleSelect || !createOfficeSelect || !userRoleSelect || !userOfficeSelect) {
            return;
        }

        createRoleSelect.innerHTML = '<option value="">Select role</option>';
        userRoleSelect.innerHTML = '<option value="">Select role</option>';
        roles.forEach(function (role) {
            const roleId = Number(role && role.id || 0);
            if (!roleId) {
                return;
            }
            [createRoleSelect, userRoleSelect].forEach(function (select) {
                const option = document.createElement('option');
                option.value = String(roleId);
                option.textContent = String(role.name || 'Role');
                option.setAttribute('data-fixed-office-id', String(fixedOfficeIdByRoleName[normalizeRoleName(role.name || '')] || ''));
                select.appendChild(option);
            });
        });

        if (createOfficeFilter) {
            createOfficeFilter.render(createOfficeSelect ? createOfficeSelect.value : '');
        }
        if (editOfficeFilter) {
            editOfficeFilter.render(userOfficeSelect ? userOfficeSelect.value : '');
        }
    }

    function getFilteredUsers() {
        const searchTerm = String(searchInput ? searchInput.value : '').toLowerCase().trim();
        const statusValue = String(statusFilter ? statusFilter.value : '').toLowerCase().trim();

        return users.filter(function (user) {
            const fullName = String((user.first_name || '') + ' ' + (user.last_name || '')).trim();
            const rowText = [
                fullName,
                user.username,
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
            } else if (statusValue === 'blocked' || statusValue === 'inactive') {
                matchesStatus = !isActive;
            } else if (statusValue === 'locked') {
                matchesStatus = isLocked;
            }

            return matchesSearch && matchesStatus;
        });
    }

    function ensureSelectedUserVisible(userId) {
        usersPager.ensureItemVisible(getFilteredUsers(), function (user) {
            return Number(user.id || 0) === Number(userId || 0);
        });
    }

    function renderUsersTable() {
        if (!usersBody) {
            return;
        }

        const filtered = getFilteredUsers();

        if (filtered.length === 0) {
            usersPager.slice([]);
            usersBody.innerHTML = '<tr><td colspan="9" class="table-empty-state">No users found for current filters.</td></tr>';
            if (usersEmpty) {
                usersEmpty.hidden = false;
            }
            return;
        }

        if (usersEmpty) {
            usersEmpty.hidden = true;
        }

        const visibleUsers = usersPager.slice(filtered);
        usersBody.innerHTML = visibleUsers.map(function (user) {
            const userId = Number(user.id || 0);
            const name = String((user.first_name || '') + ' ' + (user.last_name || '')).trim() || 'Unknown User';
            const isActive = !!user.is_active;
            const isLocked = normalizeLockState(user);
            const blockActionLabel = isActive ? 'Block user' : 'Unblock user';
            const blockActionClass = isActive ? 'super-admin-icon-btn is-danger' : 'super-admin-icon-btn is-success';
            const statusLabel = isActive ? 'Active' : 'Blocked';
            const lockLabel = isLocked ? (user.locked_until_label || 'Locked') : 'No lock';
            const activeClass = isActive ? 'status-pill is-active' : 'status-pill is-inactive';
            const selectedClass = userId === selectedUserId ? 'is-selected-row' : '';
            const editIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h4l10.5-10.5a2.12 2.12 0 0 0-3-3L5 17v3Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path><path d="m13.5 6.5 4 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path></svg>';
            const blockIcon = isActive
                ? '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8" fill="none" stroke="currentColor" stroke-width="1.8"></circle><path d="M8.5 15.5l7-7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path></svg>'
                : '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 11V8a5 5 0 0 1 10 0v3" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path><rect x="5" y="11" width="14" height="10" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"></rect><path d="m10 16 2 2 4-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path></svg>';
            const resetIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4.5 12a7.5 7.5 0 1 0 2.2-5.3L4 9.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path><path d="M4 4v5.5h5.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path><path d="M12 9v3l2 2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path></svg>';
            const deleteIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path><path d="M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path><path d="M7 7l1 12a1 1 0 0 0 1 .9h6a1 1 0 0 0 1-.9L17 7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path><path d="M10 11v5M14 11v5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path></svg>';

            return `
                <tr class="${selectedClass}" data-user-id="${userId}">
                    <td data-label="User">${escapeHtml(name)}</td>
                    <td data-label="Username">${escapeHtml(user.username || '-')}</td>
                    <td data-label="Email">${escapeHtml(user.email || '-')}</td>
                    <td data-label="Role">${escapeHtml(user.role_name || '-')}</td>
                    <td data-label="Office">${escapeHtml(user.office_name || '-')}</td>
                    <td data-label="Status"><span class="${activeClass}">${escapeHtml(statusLabel)}</span></td>
                    <td data-label="Last Login">${escapeHtml(user.last_login_label || '-')}</td>
                    <td data-label="Lock">${escapeHtml(lockLabel)}</td>
                    <td data-label="Actions" class="super-admin-actions-cell">
                        <div class="super-admin-action-group" role="group" aria-label="User actions for ${escapeHtml(name)}">
                            <button type="button" class="super-admin-icon-btn" data-user-select="${userId}" title="Edit user" aria-label="Edit user">
                                ${editIcon}
                            </button>
                            <button type="button" class="${blockActionClass}" data-user-block-toggle="${userId}" data-user-block-state="${isActive ? 'block' : 'unblock'}" title="${escapeHtml(blockActionLabel)}" aria-label="${escapeHtml(blockActionLabel)}">
                                ${blockIcon}
                            </button>
                            <button type="button" class="super-admin-icon-btn is-warning" data-user-reset-password="${userId}" title="Reset password" aria-label="Reset password">
                                ${resetIcon}
                            </button>
                            <button type="button" class="super-admin-icon-btn is-danger" data-user-delete="${userId}" title="Delete user" aria-label="Delete user">
                                ${deleteIcon}
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        Array.from(usersBody.querySelectorAll('[data-user-select]')).forEach(function (button) {
            button.addEventListener('click', function () {
                const userId = Number(button.getAttribute('data-user-select') || 0);
                selectUser(userId);
                openEditModalForSelection();
            });
        });

        Array.from(usersBody.querySelectorAll('[data-user-block-toggle]')).forEach(function (button) {
            button.addEventListener('click', async function () {
                const userId = Number(button.getAttribute('data-user-block-toggle') || 0);
                const mode = String(button.getAttribute('data-user-block-state') || '').toLowerCase();
                if (!userId || (mode !== 'block' && mode !== 'unblock')) {
                    return;
                }

                const target = users.find(function (user) {
                    return Number(user.id || 0) === userId;
                });
                const label = target
                    ? (String((target.first_name || '') + ' ' + (target.last_name || '')).trim() || String(target.email || target.username || 'this user'))
                    : 'this user';
                const actionName = mode === 'block' ? 'block' : 'unblock';
                const confirmMessage = mode === 'block'
                    ? 'Block ' + label + '? They will no longer be able to log in to the system.'
                    : 'Unblock ' + label + '? They will be able to log in again.';

                const confirmed = await requestConfirmation({
                    title: mode === 'block' ? 'Block User Account' : 'Unblock User Account',
                    message: confirmMessage,
                    note: mode === 'block'
                        ? 'Blocked users are prevented from signing in until you unblock them.'
                        : 'Unblocking restores account access immediately.',
                    confirmLabel: mode === 'block' ? 'Block User' : 'Unblock User',
                    tone: mode === 'block' ? 'danger' : 'success'
                });

                if (!confirmed) {
                    return;
                }

                try {
                    setBusy(true);
                    const result = await postAction({
                        action: mode === 'block' ? 'BLOCK_USER' : 'UNBLOCK_USER',
                        user_id: userId
                    });
                    applyUsersPayload(result);
                    setStatus(result.message || ('User account ' + actionName + 'ed successfully.'), false);
                    if (selectedUserId === userId) {
                        setEditStatus(result.message || ('User account ' + actionName + 'ed successfully.'), false);
                    }
                } catch (error) {
                    const message = error.message || ('Unable to ' + actionName + ' the selected user.');
                    setStatus(message, true);
                    if (selectedUserId === userId) {
                        setEditStatus(message, true);
                    }
                } finally {
                    setBusy(false);
                }
            });
        });

        Array.from(usersBody.querySelectorAll('[data-user-reset-password]')).forEach(function (button) {
            button.addEventListener('click', async function () {
                const userId = Number(button.getAttribute('data-user-reset-password') || 0);
                if (!userId) {
                    return;
                }

                const target = users.find(function (user) {
                    return Number(user.id || 0) === userId;
                });
                const label = target
                    ? (String((target.first_name || '') + ' ' + (target.last_name || '')).trim() || String(target.email || target.username || 'this user'))
                    : 'this user';

                const confirmed = await requestConfirmation({
                    title: 'Reset Password',
                    message: 'Reset the password for ' + label + ' to the default password denr@12?',
                    note: 'The user will be required to change the password on their next login.',
                    confirmLabel: 'Reset Password',
                    tone: 'warning'
                });

                if (!confirmed) {
                    return;
                }

                try {
                    setBusy(true);
                    const result = await postAction({ action: 'RESET_PASSWORD', user_id: userId });
                    applyUsersPayload(result);
                    setStatus(result.message || 'Password reset to the default temporary password.', false);
                    if (selectedUserId === userId) {
                        setEditStatus(result.message || 'Password reset to the default temporary password.', false);
                    }
                } catch (error) {
                    const message = error.message || 'Unable to reset the selected user password.';
                    setStatus(message, true);
                    if (selectedUserId === userId) {
                        setEditStatus(message, true);
                    }
                } finally {
                    setBusy(false);
                }
            });
        });

        Array.from(usersBody.querySelectorAll('[data-user-delete]')).forEach(function (button) {
            button.addEventListener('click', async function () {
                const userId = Number(button.getAttribute('data-user-delete') || 0);
                if (!userId) {
                    return;
                }

                const target = users.find(function (user) {
                    return Number(user.id || 0) === userId;
                });
                const label = target
                    ? (String((target.first_name || '') + ' ' + (target.last_name || '')).trim() || String(target.email || target.username || 'this user'))
                    : 'this user';

                const confirmed = await requestConfirmation({
                    title: 'Move User To Deleted Users',
                    message: 'Move ' + label + ' to Deleted Users?',
                    note: 'This action removes the account from the active users list and sends it to the recycle bin.',
                    confirmLabel: 'Delete User',
                    tone: 'danger'
                });

                if (!confirmed) {
                    return;
                }

                try {
                    setBusy(true);
                    const result = await postAction({ action: 'DELETE_USER', user_id: userId });
                    if (selectedUserId === userId) {
                        selectedUserId = 0;
                        closeModal(editModal);
                    }
                    applyUsersPayload(result);
                    renderUsersTable();
                    setStatus(result.message || 'User account moved to recycle bin.', false);
                } catch (error) {
                    setStatus(error.message || 'Unable to delete the selected user.', true);
                } finally {
                    setBusy(false);
                }
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
        if (userUsernameInput) {
            userUsernameInput.value = '';
        }
        if (userEmailInput) {
            userEmailInput.value = '';
        }
        if (userRoleSelect) {
            userRoleSelect.value = '';
        }
        if (userOfficeSelect) {
            if (editOfficeFilter) {
                editOfficeFilter.render('');
            } else {
                userOfficeSelect.value = '';
            }
        }
        if (userActiveCheckbox) {
            userActiveCheckbox.checked = true;
        }
        if (resetSecurityCheckbox) {
            resetSecurityCheckbox.checked = false;
        }
        renderUsersTable();
        setEditStatus('Save your changes when you are ready.', false);
    }

    function resetCreateUserForm() {
        if (createUserForm) {
            createUserForm.reset();
        }
        if (createActiveCheckbox) {
            createActiveCheckbox.checked = true;
        }
        if (createRoleSelect) {
            createRoleSelect.value = '';
        }
        if (createOfficeSelect) {
            if (createOfficeFilter) {
                createOfficeFilter.render('');
            } else {
                createOfficeSelect.value = '';
            }
        }
        syncCreateUsernamePreview();
        setCreateStatus('Fill out the form to create a new user. Username will be generated automatically.', false);
    }

    function resetImportUsersForm() {
        if (importUsersForm) {
            importUsersForm.reset();
        }
        renderImportErrors([]);
        applyImportCapabilityState();
        setImportStatus(getImportStartMessage(), false);
    }

    function downloadImportTemplate() {
        const lines = [
            'first_name,last_name,email,role,office,is_active',
            '"Juan","Dela Cruz","juan.delacruz@denr.gov.ph","SUPER_ADMIN","Office of the Regional Executive Director (ORED)","1"'
        ];
        const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const anchor = document.createElement('a');
        anchor.href = url;
        anchor.download = 'super-admin-user-import-template.csv';
        document.body.appendChild(anchor);
        anchor.click();
        document.body.removeChild(anchor);
        window.setTimeout(function () {
            URL.revokeObjectURL(url);
        }, 0);
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
        if (userIdInput) {
            userIdInput.value = String(selectedUserId);
        }
        if (userNameInput) {
            userNameInput.value = String((target.first_name || '') + ' ' + (target.last_name || '')).trim();
        }
        if (userUsernameInput) {
            userUsernameInput.value = String(target.username || '');
        }
        if (userEmailInput) {
            userEmailInput.value = String(target.email || '');
        }
        if (userRoleSelect) {
            userRoleSelect.value = String(target.role_id || '');
            if (editOfficeFilter) {
                editOfficeFilter.render(String(target.office_id || ''));
            }
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
            throw new Error(String(json.message || 'Unable to save changes.'));
        }

        return json;
    }

    function applyUsersPayload(payload) {
        if (!payload || typeof payload !== 'object') {
            return;
        }

        if (Array.isArray(payload.users)) {
            users = payload.users.slice();
        }
        if (Array.isArray(payload.roles)) {
            roles = payload.roles.slice();
        }
        if (Array.isArray(payload.offices)) {
            offices = payload.offices.slice();
        }
        if (Array.isArray(payload.office_options_payload)) {
            officeOptionsPayload = payload.office_options_payload.slice();
        }
        if (payload.fixed_office_id_by_role_name && typeof payload.fixed_office_id_by_role_name === 'object') {
            fixedOfficeIdByRoleName = payload.fixed_office_id_by_role_name;
        }
        if (typeof payload.xlsx_import_supported === 'boolean') {
            xlsxImportSupported = payload.xlsx_import_supported;
        }

        applyImportCapabilityState();
        buildRoleOfficeMaps();
        renderUsersTable();
        if (selectedUserId > 0) {
            selectUser(selectedUserId);
        } else {
            clearSelection();
        }
    }

    async function fetchUsersList() {
        const response = await fetch(endpoint, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });

        const json = await response.json().catch(function () {
            return { ok: false, message: 'Unexpected server response.' };
        });

        if (!response.ok || !json.ok) {
            throw new Error(String(json.message || 'Unable to load users right now.'));
        }

        applyUsersPayload(json);
        return json;
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            usersPager.reset();
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
            usersPager.reset();
            renderUsersTable();
        });
    }

    usersPager.bind(renderUsersTable);

    Array.from(document.querySelectorAll('[data-super-admin-create-close="true"]')).forEach(function (button) {
        button.addEventListener('click', function () {
            closeModal(createModal);
        });
    });

    Array.from(document.querySelectorAll('[data-super-admin-import-close="true"]')).forEach(function (button) {
        button.addEventListener('click', function () {
            closeModal(importModal);
        });
    });

    Array.from(document.querySelectorAll('[data-super-admin-edit-close="true"]')).forEach(function (button) {
        button.addEventListener('click', function () {
            closeModal(editModal);
        });
    });

    Array.from(document.querySelectorAll('[data-super-admin-confirm-close="true"]')).forEach(function (button) {
        button.addEventListener('click', function () {
            closeConfirmation(false);
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && confirmModal && !confirmModal.hidden) {
            closeConfirmation(false);
        }
        if (event.key === 'Escape' && importModal && !importModal.hidden) {
            closeModal(importModal);
        }
        if (event.key === 'Escape' && createModal && !createModal.hidden) {
            closeModal(createModal);
        }
        if (event.key === 'Escape' && editModal && !editModal.hidden) {
            closeModal(editModal);
        }
    });

    if (openImportUsersButton) {
        openImportUsersButton.addEventListener('click', function () {
            resetImportUsersForm();
            openModal(importModal);
        });
    }

    if (confirmSubmitButton) {
        confirmSubmitButton.addEventListener('click', function () {
            closeConfirmation(true);
        });
    }

    if (openCreateUserButton) {
        openCreateUserButton.addEventListener('click', function () {
            resetCreateUserForm();
            openModal(createModal);
        });
    }

    if (downloadImportTemplateButton) {
        downloadImportTemplateButton.addEventListener('click', function () {
            downloadImportTemplate();
        });
    }

    if (importUsersResetButton) {
        importUsersResetButton.addEventListener('click', function () {
            resetImportUsersForm();
            setImportStatus('Import form cleared.', false);
        });
    }

    if (createResetButton) {
        createResetButton.addEventListener('click', function () {
            resetCreateUserForm();
            setCreateStatus('Form cleared.', false);
        });
    }

    [createFirstNameInput, createLastNameInput].forEach(function (input) {
        if (!input) {
            return;
        }
        input.addEventListener('input', function () {
            syncCreateUsernamePreview();
        });
    });

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
                applyUsersPayload(result);
                setStatus(result.message || 'Security counters reset.', false);
                setEditStatus(result.message || 'Security counters reset.', false);
            } catch (error) {
                const message = error.message || 'Unable to reset security counters.';
                setStatus(message, true);
                setEditStatus(message, true);
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
                setEditStatus('Select a user, role, and office before saving.', true);
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
                applyUsersPayload(result);
                closeModal(editModal);
                setStatus(result.message || 'User updated successfully.', false);
            } catch (error) {
                setEditStatus(error.message || 'Unable to save user changes.', true);
            } finally {
                setBusy(false);
            }
        });
    }

    if (createUserForm) {
        createUserForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            const firstName = String(createFirstNameInput ? createFirstNameInput.value : '').trim();
            const lastName = String(createLastNameInput ? createLastNameInput.value : '').trim();
            const username = syncCreateUsernamePreview();
            const email = String(createEmailInput ? createEmailInput.value : '').trim();
            const roleId = Number(createRoleSelect ? createRoleSelect.value : 0);
            const officeId = Number(createOfficeSelect ? createOfficeSelect.value : 0);

            if (!firstName || !lastName || !email || !roleId || !officeId) {
                setCreateStatus('Complete all required fields before creating the user.', true);
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
                    is_active: createActiveCheckbox && createActiveCheckbox.checked ? 1 : 0,
                });
                applyUsersPayload(result);
                resetCreateUserForm();
                closeModal(createModal);
                usersPager.reset();
                renderUsersTable();
                setStatus(result.message || 'Local user account created successfully.', false);
            } catch (error) {
                setCreateStatus(error.message || 'Unable to create the user right now.', true);
            } finally {
                setBusy(false);
            }
        });
    }

    if (importUsersForm) {
        importUsersForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            const selectedFile = importUsersFileInput && importUsersFileInput.files ? importUsersFileInput.files[0] : null;
            if (!selectedFile) {
                setImportStatus('Choose a ' + getImportChooserLabel() + ' first.', true);
                renderImportErrors([]);
                return;
            }

            const formData = new FormData();
            formData.set('csrf_token', csrfToken);
            formData.set('action', 'IMPORT_USERS');
            formData.set('users_file', selectedFile);

            try {
                setBusy(true);
                renderImportErrors([]);
                setImportStatus('Importing users. Please wait...', false);

                const response = await fetch(endpoint, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json().catch(function () {
                    return { ok: false, message: 'Unexpected server response.' };
                });

                if (!response.ok || !result.ok) {
                    renderImportErrors(Array.isArray(result.errors) ? result.errors : []);
                    throw new Error(String(result.message || 'Unable to import users right now.'));
                }

                applyUsersPayload(result);
                resetImportUsersForm();
                closeModal(importModal);
                usersPager.reset();
                renderUsersTable();
                setStatus(result.message || 'Users imported successfully.', false);
            } catch (error) {
                setImportStatus(error.message || 'Unable to import users right now.', true);
            } finally {
                setBusy(false);
            }
        });
    }

    buildRoleOfficeMaps();
    applyImportCapabilityState();
    resetImportUsersForm();
    resetCreateUserForm();
    renderUsersTable();
    clearSelection();
    setStatus('Refreshing latest users...', false);
    fetchUsersList()
        .then(function () {
            setStatus('Users synced with the latest server data.', false);
        })
        .catch(function (error) {
            setStatus(error && error.message ? error.message : 'Unable to refresh users right now.', true);
        });
})();
</script>
