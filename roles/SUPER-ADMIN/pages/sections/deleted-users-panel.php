<?php
declare(strict_types=1);

$superAdminDeletedUsersEndpoint = (string)($superAdminDeletedUsersEndpoint ?? app_url('actions/super-admin-deleted-users.php'));
$superAdminDeletedUsers = is_array($superAdminDeletedUsers ?? null) ? $superAdminDeletedUsers : [];
$superAdminCsrfToken = (string)($superAdminCsrfToken ?? ($csrfToken ?? ''));
?>
<section class="super-admin-grid" aria-label="Deleted user recycle bin">
    <article class="card super-admin-panel super-admin-panel-full">
        <div class="super-admin-panel-head">
            <h2>User Recycle Bin</h2>
            <p>Deleted user accounts are archived here instead of being permanently removed. Restore returns the original account record with its role and office assignment.</p>
        </div>

        <div class="super-admin-toolbar" aria-label="Deleted user controls">
            <form class="super-admin-search" role="search" aria-label="Search deleted users">
                <label class="sr-only" for="superAdminDeletedUserSearch">Search deleted users</label>
                <input id="superAdminDeletedUserSearch" type="search" placeholder="Search name, email, role, office, or deleted by">
            </form>
        </div>

        <div class="super-admin-table-wrap">
            <table class="super-admin-table super-admin-table-stack" id="superAdminDeletedUsersTable">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Office</th>
                        <th>Deleted At</th>
                        <th>Deleted By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="superAdminDeletedUsersBody">
                    <tr>
                        <td colspan="7" class="table-empty-state">Loading deleted users...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="super-admin-pagination" aria-label="Deleted users table pagination">
            <button type="button" class="super-admin-btn super-admin-btn-secondary" id="superAdminDeletedUsersPrev">Back</button>
            <p id="superAdminDeletedUsersPageInfo" class="super-admin-pagination-info">Page 1 of 1</p>
            <button type="button" class="super-admin-btn super-admin-btn-secondary" id="superAdminDeletedUsersNext">Next</button>
        </div>

        <p id="superAdminDeletedUsersEmpty" class="super-admin-empty" hidden>No deleted users match your search.</p>
        <p id="superAdminDeletedUsersStatus" class="super-admin-status" role="status" aria-live="polite">Select a deleted user to restore the account.</p>
    </article>
</section>

<script>
(function () {
    const endpoint = <?= json_encode($superAdminDeletedUsersEndpoint, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const csrfToken = <?= json_encode($superAdminCsrfToken, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    let deletedUsers = <?= json_encode($superAdminDeletedUsers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    const searchInput = document.getElementById('superAdminDeletedUserSearch');
    const tableBody = document.getElementById('superAdminDeletedUsersBody');
    const emptyState = document.getElementById('superAdminDeletedUsersEmpty');
    const statusNode = document.getElementById('superAdminDeletedUsersStatus');
    const paginationPrevButton = document.getElementById('superAdminDeletedUsersPrev');
    const paginationNextButton = document.getElementById('superAdminDeletedUsersNext');
    const paginationInfo = document.getElementById('superAdminDeletedUsersPageInfo');
    const deletedUsersPager = window.createSuperAdminTablePager({
        prevButton: paginationPrevButton,
        nextButton: paginationNextButton,
        infoNode: paginationInfo,
        pageSize: 10
    });

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

    function setStatus(message, isError) {
        if (!statusNode) {
            return;
        }
        statusNode.textContent = String(message || '');
        statusNode.classList.toggle('is-error', !!isError);
    }

    function applyPayload(payload) {
        deletedUsers = Array.isArray(payload && payload.deleted_users) ? payload.deleted_users.slice() : [];
        renderTable();
    }

    function getFilteredDeletedUsers() {
        const searchTerm = String(searchInput ? searchInput.value : '').toLowerCase().trim();
        return deletedUsers.filter(function (item) {
            const haystack = [
                item.full_name,
                item.email,
                item.role_name,
                item.office_name,
                item.deleted_by_name,
                item.deleted_by_email
            ].join(' ').toLowerCase();
            return searchTerm === '' || haystack.indexOf(searchTerm) !== -1;
        });
    }

    function renderTable() {
        if (!tableBody) {
            return;
        }

        const filtered = getFilteredDeletedUsers();

        if (filtered.length === 0) {
            deletedUsersPager.slice([]);
            tableBody.innerHTML = '<tr><td colspan="7" class="table-empty-state">No deleted users found.</td></tr>';
            if (emptyState) {
                emptyState.hidden = false;
            }
            return;
        }

        if (emptyState) {
            emptyState.hidden = true;
        }

        const visibleDeletedUsers = deletedUsersPager.slice(filtered);
        tableBody.innerHTML = visibleDeletedUsers.map(function (item) {
            const deletedBy = String(item.deleted_by_name || '').trim() || String(item.deleted_by_email || '').trim() || 'Unknown';
            return `
                <tr>
                    <td data-label="User">${escapeHtml(item.full_name || '-')}</td>
                    <td data-label="Email">${escapeHtml(item.email || '-')}</td>
                    <td data-label="Role">${escapeHtml(item.role_name || '-')}</td>
                    <td data-label="Office">${escapeHtml(item.office_name || '-')}</td>
                    <td data-label="Deleted At">${escapeHtml(item.deleted_at_label || '-')}</td>
                    <td data-label="Deleted By">${escapeHtml(deletedBy)}</td>
                    <td data-label="Actions">
                        <button type="button" class="super-admin-table-btn" data-restore-id="${Number(item.archive_id || 0)}">Restore</button>
                    </td>
                </tr>
            `;
        }).join('');

        Array.from(tableBody.querySelectorAll('[data-restore-id]')).forEach(function (button) {
            button.addEventListener('click', function () {
                const archiveId = Number(button.getAttribute('data-restore-id') || 0);
                const target = deletedUsers.find(function (item) {
                    return Number(item.archive_id || 0) === archiveId;
                });
                restoreUser(archiveId, target);
            });
        });
    }

    async function fetchList() {
        const response = await fetch(endpoint, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });
        const payload = await response.json().catch(function () {
            return { ok: false, message: 'Unexpected server response.' };
        });
        if (!response.ok || !payload.ok) {
            throw new Error(String(payload.message || 'Unable to load deleted users.'));
        }
        applyPayload(payload);
    }

    async function restoreUser(archiveId, target) {
        if (!archiveId) {
            return;
        }

        const email = String(target && target.email ? target.email : '').trim() || ('archive #' + String(archiveId));
        if (!window.confirm('Restore deleted user ' + email + '?')) {
            return;
        }

        try {
            setStatus('Restoring ' + email + '...', false);

            const formData = new FormData();
            formData.set('csrf_token', csrfToken);
            formData.set('action', 'RESTORE_USER');
            formData.set('archive_id', String(archiveId));

            const response = await fetch(endpoint, {
                method: 'POST',
                body: formData,
            });
            const payload = await response.json().catch(function () {
                return { ok: false, message: 'Unexpected server response.' };
            });
            if (!response.ok || !payload.ok) {
                throw new Error(String(payload.message || 'Unable to restore user.'));
            }

            applyPayload(payload);
            setStatus(String(payload.message || 'User restored successfully.'), false);
        } catch (error) {
            setStatus(error && error.message ? error.message : 'Unable to restore user.', true);
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            deletedUsersPager.reset();
            renderTable();
        });
        const searchForm = searchInput.closest('form');
        if (searchForm) {
            searchForm.addEventListener('submit', function (event) {
                event.preventDefault();
            });
        }
    }

    deletedUsersPager.bind(renderTable);
    renderTable();
    fetchList().catch(function (error) {
        setStatus(error && error.message ? error.message : 'Unable to load deleted users.', true);
    });
})();
</script>
