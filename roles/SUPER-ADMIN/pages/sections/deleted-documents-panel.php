<?php
declare(strict_types=1);

$superAdminDeletedDocumentsEndpoint = (string)($superAdminDeletedDocumentsEndpoint ?? app_url('actions/super-admin-deleted-documents.php'));
$superAdminDeletedDocuments = is_array($superAdminDeletedDocuments ?? null) ? $superAdminDeletedDocuments : [];
$superAdminCsrfToken = (string)($superAdminCsrfToken ?? ($csrfToken ?? ''));
?>
<section class="super-admin-grid" aria-label="Deleted document recycle bin">
    <article class="card super-admin-panel super-admin-panel-full">
        <div class="super-admin-panel-head">
            <h2>Document Recycle Bin</h2>
            <p>Deleted documents are archived here instead of being permanently removed. Restore returns the document, attachments, and timeline records to the active system.</p>
        </div>

        <div class="super-admin-toolbar" aria-label="Deleted document controls">
            <form class="super-admin-search" role="search" aria-label="Search deleted documents">
                <label class="sr-only" for="superAdminDeletedDocumentSearch">Search deleted documents</label>
                <input id="superAdminDeletedDocumentSearch" type="search" placeholder="Search tracking ID, subject, or deleted by">
            </form>
        </div>

        <div class="super-admin-table-wrap">
            <table class="super-admin-table super-admin-table-stack" id="superAdminDeletedDocumentsTable">
                <thead>
                    <tr>
                        <th>Tracking ID</th>
                        <th>Subject</th>
                        <th>Deleted At</th>
                        <th>Deleted By</th>
                        <th>Attachments</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="superAdminDeletedDocumentsBody">
                    <tr>
                        <td colspan="6" class="table-empty-state">Loading deleted documents...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="super-admin-pagination" aria-label="Deleted documents table pagination">
            <button type="button" class="super-admin-btn super-admin-btn-secondary" id="superAdminDeletedDocumentsPrev">Back</button>
            <p id="superAdminDeletedDocumentsPageInfo" class="super-admin-pagination-info">Page 1 of 1</p>
            <button type="button" class="super-admin-btn super-admin-btn-secondary" id="superAdminDeletedDocumentsNext">Next</button>
        </div>

        <p id="superAdminDeletedDocumentsEmpty" class="super-admin-empty" hidden>No deleted documents match your search.</p>
        <p id="superAdminDeletedDocumentsStatus" class="super-admin-status" role="status" aria-live="polite">Select a deleted document to restore it.</p>
    </article>
</section>

<script>
(function () {
    const endpoint = <?= json_encode($superAdminDeletedDocumentsEndpoint, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const csrfToken = <?= json_encode($superAdminCsrfToken, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    let deletedDocuments = <?= json_encode($superAdminDeletedDocuments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    const searchInput = document.getElementById('superAdminDeletedDocumentSearch');
    const tableBody = document.getElementById('superAdminDeletedDocumentsBody');
    const emptyState = document.getElementById('superAdminDeletedDocumentsEmpty');
    const statusNode = document.getElementById('superAdminDeletedDocumentsStatus');
    const paginationPrevButton = document.getElementById('superAdminDeletedDocumentsPrev');
    const paginationNextButton = document.getElementById('superAdminDeletedDocumentsNext');
    const paginationInfo = document.getElementById('superAdminDeletedDocumentsPageInfo');
    const deletedDocumentsPager = window.createSuperAdminTablePager({
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
        deletedDocuments = Array.isArray(payload && payload.deleted_documents) ? payload.deleted_documents.slice() : [];
        renderTable();
    }

    function getFilteredDeletedDocuments() {
        const searchTerm = String(searchInput ? searchInput.value : '').toLowerCase().trim();
        return deletedDocuments.filter(function (item) {
            const haystack = [
                item.tracking_id,
                item.subject,
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

        const filtered = getFilteredDeletedDocuments();

        if (filtered.length === 0) {
            deletedDocumentsPager.slice([]);
            tableBody.innerHTML = '<tr><td colspan="6" class="table-empty-state">No deleted documents found.</td></tr>';
            if (emptyState) {
                emptyState.hidden = false;
            }
            return;
        }

        if (emptyState) {
            emptyState.hidden = true;
        }

        const visibleDeletedDocuments = deletedDocumentsPager.slice(filtered);
        tableBody.innerHTML = visibleDeletedDocuments.map(function (item) {
            const deletedBy = String(item.deleted_by_name || '').trim() || String(item.deleted_by_email || '').trim() || 'Unknown';
            return `
                <tr>
                    <td data-label="Tracking ID">${escapeHtml(item.tracking_id || '-')}</td>
                    <td data-label="Subject">${escapeHtml(item.subject || '-')}</td>
                    <td data-label="Deleted At">${escapeHtml(item.deleted_at_label || '-')}</td>
                    <td data-label="Deleted By">${escapeHtml(deletedBy)}</td>
                    <td data-label="Attachments">${escapeHtml(item.attachment_count || 0)}</td>
                    <td data-label="Actions">
                        <button type="button" class="super-admin-table-btn" data-restore-id="${Number(item.archive_id || 0)}">Restore</button>
                    </td>
                </tr>
            `;
        }).join('');

        Array.from(tableBody.querySelectorAll('[data-restore-id]')).forEach(function (button) {
            button.addEventListener('click', function () {
                const archiveId = Number(button.getAttribute('data-restore-id') || 0);
                const target = deletedDocuments.find(function (item) {
                    return Number(item.archive_id || 0) === archiveId;
                });
                restoreDocument(archiveId, target);
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
            throw new Error(String(payload.message || 'Unable to load deleted documents.'));
        }
        applyPayload(payload);
    }

    async function restoreDocument(archiveId, target) {
        if (!archiveId) {
            return;
        }

        const trackingId = String(target && target.tracking_id ? target.tracking_id : '').trim() || ('archive #' + String(archiveId));
        if (!window.confirm('Restore deleted document ' + trackingId + '?')) {
            return;
        }

        try {
            setStatus('Restoring ' + trackingId + '...', false);

            const formData = new FormData();
            formData.set('csrf_token', csrfToken);
            formData.set('action', 'RESTORE_DOCUMENT');
            formData.set('archive_id', String(archiveId));

            const response = await fetch(endpoint, {
                method: 'POST',
                body: formData,
            });
            const payload = await response.json().catch(function () {
                return { ok: false, message: 'Unexpected server response.' };
            });
            if (!response.ok || !payload.ok) {
                throw new Error(String(payload.message || 'Unable to restore document.'));
            }

            applyPayload(payload);
            setStatus(String(payload.message || 'Document restored successfully.'), false);
        } catch (error) {
            setStatus(error && error.message ? error.message : 'Unable to restore document.', true);
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            deletedDocumentsPager.reset();
            renderTable();
        });
        const searchForm = searchInput.closest('form');
        if (searchForm) {
            searchForm.addEventListener('submit', function (event) {
                event.preventDefault();
            });
        }
    }

    deletedDocumentsPager.bind(renderTable);
    renderTable();
    fetchList().catch(function (error) {
        setStatus(error && error.message ? error.message : 'Unable to load deleted documents.', true);
    });
})();
</script>
