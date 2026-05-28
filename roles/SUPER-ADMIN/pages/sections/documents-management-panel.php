<?php
declare(strict_types=1);

$superAdminDocumentsEndpoint = (string)($superAdminDocumentsEndpoint ?? app_url('actions/super-admin-documents.php'));
$superAdminDocumentDetailsEndpoint = (string)($superAdminDocumentDetailsEndpoint ?? app_url('actions/document-details.php'));
$superAdminTrackingSlipPath = (string)($superAdminTrackingSlipPath ?? app_url('tracking-slip.php'));
$superAdminPrintPackagePath = (string)($superAdminPrintPackagePath ?? app_url('print-package.php'));
$superAdminCsrfToken = (string)($superAdminCsrfToken ?? ($csrfToken ?? ''));
?>
<section class="super-admin-grid" aria-label="Super Admin documents management">
    <article class="card super-admin-panel super-admin-panel-full">
        <div class="super-admin-panel-head">
            <h2>System-Wide Documents</h2>
            <p>Review every live document created by users, inspect its attachments and routing context, and archive records into the recycle bin when needed.</p>
        </div>

        <div class="super-admin-toolbar" aria-label="Document management controls">
            <form class="super-admin-search" role="search" aria-label="Search documents">
                <label class="sr-only" for="superAdminDocumentSearch">Search documents</label>
                <input id="superAdminDocumentSearch" type="search" placeholder="Search tracking ID, subject, creator, office, or document type">
            </form>
            <label class="super-admin-filter" for="superAdminDocumentStatusFilter">
                <span>Status</span>
                <select id="superAdminDocumentStatusFilter">
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="completed">Completed</option>
                    <option value="overdue">Overdue</option>
                </select>
            </label>
            <label class="super-admin-filter" for="superAdminDocumentSourceFilter">
                <span>Source</span>
                <select id="superAdminDocumentSourceFilter">
                    <option value="">All sources</option>
                    <option value="INTERNAL">Current Office</option>
                    <option value="EXTERNAL">Client</option>
                </select>
            </label>
            <label class="super-admin-filter" for="superAdminDocumentOfficeFilter">
                <span>Current Office</span>
                <select id="superAdminDocumentOfficeFilter">
                    <option value="">All offices</option>
                </select>
            </label>
            <label class="super-admin-filter" for="superAdminDocumentCreatorFilter">
                <span>Created By</span>
                <select id="superAdminDocumentCreatorFilter">
                    <option value="">All users</option>
                </select>
            </label>
        </div>

        <div class="super-admin-table-wrap">
            <table class="super-admin-table super-admin-table-stack" id="superAdminDocumentsTable">
                <thead>
                    <tr>
                        <th>Tracking ID</th>
                        <th>Subject</th>
                        <th>Created By</th>
                        <th>Document Type</th>
                        <th>Current Office</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Attachments</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="superAdminDocumentsBody">
                    <tr>
                        <td colspan="9" class="table-empty-state">Loading documents...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="super-admin-pagination" aria-label="Documents table pagination">
            <button type="button" class="super-admin-btn super-admin-btn-secondary" id="superAdminDocumentsPrev">Back</button>
            <p id="superAdminDocumentsPageInfo" class="super-admin-pagination-info">Page 1 of 1</p>
            <button type="button" class="super-admin-btn super-admin-btn-secondary" id="superAdminDocumentsNext">Next</button>
        </div>

        <div class="super-admin-mini-metrics" id="superAdminDocumentsSummary">
            <p><strong>Total:</strong> <span data-summary="documents_total">0</span></p>
            <p><strong>Active:</strong> <span data-summary="documents_active">0</span></p>
            <p><strong>Completed:</strong> <span data-summary="documents_completed">0</span></p>
            <p><strong>Overdue:</strong> <span data-summary="documents_overdue">0</span></p>
            <p><strong>Attachments:</strong> <span data-summary="attachments_total">0</span></p>
        </div>

        <p id="superAdminDocumentsEmpty" class="super-admin-empty" hidden>No documents match your current filters.</p>
        <p id="superAdminDocumentsStatus" class="super-admin-status" role="status" aria-live="polite">Loading the latest system-wide document list.</p>
    </article>
</section>

<div id="superAdminDocumentDetailsModal" class="action-modal" hidden>
    <button type="button" class="action-modal-backdrop" data-super-admin-documents-close="true" aria-label="Close document details dialog"></button>
    <div class="action-modal-dialog details-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="superAdminDocumentDetailsTitle">
        <div class="action-modal-form details-modal-form super-admin-document-details-shell">
            <h3 id="superAdminDocumentDetailsTitle" class="action-modal-title">Document Details</h3>
            <p id="superAdminDocumentDetailsMeta" class="action-modal-meta">Select a document to inspect its full record.</p>
            <p id="superAdminDocumentDetailsStatus" class="super-admin-status" role="status" aria-live="polite">Loading document details...</p>
            <div id="superAdminDocumentDetailsContent" class="super-admin-document-details-content" hidden>
                <div id="superAdminDocumentDetailsGrid" class="super-admin-details-grid"></div>
                <section class="super-admin-details-section">
                    <div class="super-admin-details-section-head">
                        <h4>Attachments</h4>
                        <p>Open the latest files exactly as users uploaded them.</p>
                    </div>
                    <div id="superAdminDocumentAttachments" class="super-admin-attachment-list"></div>
                </section>
            </div>
            <div class="action-modal-actions">
                <button type="button" class="super-admin-btn super-admin-btn-secondary" id="superAdminDocumentDetailsTrack">View Tracking Slip</button>
                <button type="button" class="super-admin-btn super-admin-btn-secondary" id="superAdminDocumentDetailsPrint">Open Print Package</button>
                <button type="button" class="super-admin-btn super-admin-btn-secondary" data-super-admin-documents-close="true">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const endpoint = <?= json_encode($superAdminDocumentsEndpoint, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const detailsEndpoint = <?= json_encode($superAdminDocumentDetailsEndpoint, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const trackingSlipPath = <?= json_encode($superAdminTrackingSlipPath, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const printPackagePath = <?= json_encode($superAdminPrintPackagePath, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const csrfToken = <?= json_encode($superAdminCsrfToken, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    let documents = [];
    let summary = {};
    let selectedDocumentId = 0;
    let selectedDocumentTrackingId = '';

    const searchInput = document.getElementById('superAdminDocumentSearch');
    const statusFilter = document.getElementById('superAdminDocumentStatusFilter');
    const sourceFilter = document.getElementById('superAdminDocumentSourceFilter');
    const officeFilter = document.getElementById('superAdminDocumentOfficeFilter');
    const creatorFilter = document.getElementById('superAdminDocumentCreatorFilter');
    const tableBody = document.getElementById('superAdminDocumentsBody');
    const emptyState = document.getElementById('superAdminDocumentsEmpty');
    const statusNode = document.getElementById('superAdminDocumentsStatus');
    const summaryNode = document.getElementById('superAdminDocumentsSummary');
    const detailsModal = document.getElementById('superAdminDocumentDetailsModal');
    const detailsMeta = document.getElementById('superAdminDocumentDetailsMeta');
    const detailsStatus = document.getElementById('superAdminDocumentDetailsStatus');
    const detailsContent = document.getElementById('superAdminDocumentDetailsContent');
    const detailsGrid = document.getElementById('superAdminDocumentDetailsGrid');
    const attachmentList = document.getElementById('superAdminDocumentAttachments');
    const detailsTrackButton = document.getElementById('superAdminDocumentDetailsTrack');
    const detailsPrintButton = document.getElementById('superAdminDocumentDetailsPrint');

    const documentsPager = window.createSuperAdminTablePager({
        prevButton: document.getElementById('superAdminDocumentsPrev'),
        nextButton: document.getElementById('superAdminDocumentsNext'),
        infoNode: document.getElementById('superAdminDocumentsPageInfo'),
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

    function setDetailsStatus(message, isError) {
        if (!detailsStatus) {
            return;
        }
        detailsStatus.textContent = String(message || '');
        detailsStatus.classList.toggle('is-error', !!isError);
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

    function openTrackingSlip(trackingId) {
        const value = String(trackingId || '').trim();
        if (!value) {
            return;
        }
        window.open(trackingSlipPath + '?tracking_id=' + encodeURIComponent(value), '_blank', 'noopener');
    }

    function openPrintPackage(trackingId) {
        const value = String(trackingId || '').trim();
        if (!value) {
            return;
        }
        window.open(printPackagePath + '?tracking_id=' + encodeURIComponent(value), '_blank', 'noopener');
    }

    function statusBadgeClass(item) {
        const category = String(item && item.status_category ? item.status_category : 'active');
        if (category === 'completed') {
            return 'status-pill is-active';
        }
        if (category === 'overdue') {
            return 'status-pill is-inactive';
        }
        return 'status-pill';
    }

    function sortByLabel(items) {
        return items.sort(function (left, right) {
            return String(left.label || '').localeCompare(String(right.label || ''), undefined, { sensitivity: 'base' });
        });
    }

    function updateFilterOptions() {
        const previousOffice = officeFilter ? officeFilter.value : '';
        const previousCreator = creatorFilter ? creatorFilter.value : '';
        const officeMap = {};
        const creatorMap = {};

        documents.forEach(function (item) {
            const officeName = String(item.current_office || '').trim();
            if (officeName) {
                officeMap[officeName] = { value: officeName, label: officeName };
            }

            const creatorId = String(item.created_by_user_id || '').trim();
            const creatorName = String(item.created_by_name || '').trim();
            if (creatorId && creatorName) {
                creatorMap[creatorId] = { value: creatorId, label: creatorName };
            }
        });

        if (officeFilter) {
            const officeOptions = ['<option value="">All offices</option>'].concat(
                sortByLabel(Object.keys(officeMap).map(function (key) {
                    return officeMap[key];
                })).map(function (item) {
                    return '<option value="' + escapeHtml(item.value) + '">' + escapeHtml(item.label) + '</option>';
                })
            );
            officeFilter.innerHTML = officeOptions.join('');
            officeFilter.value = previousOffice;
        }

        if (creatorFilter) {
            const creatorOptions = ['<option value="">All users</option>'].concat(
                sortByLabel(Object.keys(creatorMap).map(function (key) {
                    return creatorMap[key];
                })).map(function (item) {
                    return '<option value="' + escapeHtml(item.value) + '">' + escapeHtml(item.label) + '</option>';
                })
            );
            creatorFilter.innerHTML = creatorOptions.join('');
            creatorFilter.value = previousCreator;
        }
    }

    function updateSummary() {
        const resolved = summary && typeof summary === 'object' ? summary : {};
        if (!summaryNode) {
            return;
        }
        Array.from(summaryNode.querySelectorAll('[data-summary]')).forEach(function (node) {
            const key = String(node.getAttribute('data-summary') || '');
            node.textContent = String(resolved[key] || 0);
        });
    }

    function getFilteredDocuments() {
        const searchTerm = String(searchInput ? searchInput.value : '').toLowerCase().trim();
        const statusValue = String(statusFilter ? statusFilter.value : '').trim().toLowerCase();
        const sourceValue = String(sourceFilter ? sourceFilter.value : '').trim().toUpperCase();
        const officeValue = String(officeFilter ? officeFilter.value : '').trim();
        const creatorValue = String(creatorFilter ? creatorFilter.value : '').trim();

        return documents.filter(function (item) {
            const statusCategory = String(item.status_category || '').toLowerCase();
            const sourceTypeRaw = String(item.source_type_raw || '').toUpperCase();
            const currentOffice = String(item.current_office || '').trim();
            const creatorId = String(item.created_by_user_id || '').trim();
            const haystack = [
                item.tracking_id,
                item.subject,
                item.created_by_name,
                item.created_by_email,
                item.document_type,
                item.origin_office,
                item.current_office,
                item.pending_office,
                item.status
            ].join(' ').toLowerCase();

            if (statusValue && statusCategory !== statusValue) {
                return false;
            }
            if (sourceValue && sourceTypeRaw !== sourceValue) {
                return false;
            }
            if (officeValue && currentOffice !== officeValue) {
                return false;
            }
            if (creatorValue && creatorId !== creatorValue) {
                return false;
            }

            return searchTerm === '' || haystack.indexOf(searchTerm) !== -1;
        });
    }

    function renderDocumentsTable() {
        if (!tableBody) {
            return;
        }

        const filtered = getFilteredDocuments();
        if (filtered.length === 0) {
            documentsPager.slice([]);
            tableBody.innerHTML = '<tr><td colspan="9" class="table-empty-state">No matching documents found.</td></tr>';
            if (emptyState) {
                emptyState.hidden = false;
            }
            return;
        }

        if (emptyState) {
            emptyState.hidden = true;
        }

        const visibleDocuments = documentsPager.slice(filtered);
        tableBody.innerHTML = visibleDocuments.map(function (item) {
            const documentId = Number(item.document_id || 0);
            const statusClass = statusBadgeClass(item);
            return `
                <tr${documentId === selectedDocumentId ? ' class="is-selected-row"' : ''}>
                    <td data-label="Tracking ID">${escapeHtml(item.tracking_id || '-')}</td>
                    <td data-label="Subject">${escapeHtml(item.subject || '-')}</td>
                    <td data-label="Created By">
                        <div class="super-admin-cell-stack">
                            <strong>${escapeHtml(item.created_by_name || 'Unknown User')}</strong>
                            <span>${escapeHtml(item.created_by_email || '-')}</span>
                        </div>
                    </td>
                    <td data-label="Document Type">${escapeHtml(item.document_type || '-')}</td>
                    <td data-label="Current Office">
                        <div class="super-admin-cell-stack">
                            <strong>${escapeHtml(item.current_office || '-')}</strong>
                            <span>Pending: ${escapeHtml(item.pending_office || '-')}</span>
                        </div>
                    </td>
                    <td data-label="Status"><span class="${statusClass}">${escapeHtml(item.status || '-')}</span></td>
                    <td data-label="Created">${escapeHtml(item.date_created || '-')}</td>
                    <td data-label="Attachments">${escapeHtml(item.attachment_count || 0)}</td>
                    <td data-label="Actions" class="super-admin-actions-cell">
                        <div class="super-admin-action-group">
                            <button type="button" class="super-admin-table-btn" data-view-document="${documentId}">View</button>
                            <button type="button" class="super-admin-table-btn" data-track-document="${documentId}">Track</button>
                            <button type="button" class="super-admin-table-btn" data-print-document="${documentId}">Print</button>
                            <button type="button" class="super-admin-table-btn super-admin-table-btn-danger" data-delete-document="${documentId}">Delete</button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        Array.from(tableBody.querySelectorAll('[data-view-document]')).forEach(function (button) {
            button.addEventListener('click', function () {
                const documentId = Number(button.getAttribute('data-view-document') || 0);
                openDetails(documentId);
            });
        });

        Array.from(tableBody.querySelectorAll('[data-track-document]')).forEach(function (button) {
            button.addEventListener('click', function () {
                const documentId = Number(button.getAttribute('data-track-document') || 0);
                const item = documents.find(function (documentRow) {
                    return Number(documentRow.document_id || 0) === documentId;
                });
                openTrackingSlip(item ? item.tracking_id : '');
            });
        });

        Array.from(tableBody.querySelectorAll('[data-print-document]')).forEach(function (button) {
            button.addEventListener('click', function () {
                const documentId = Number(button.getAttribute('data-print-document') || 0);
                const item = documents.find(function (documentRow) {
                    return Number(documentRow.document_id || 0) === documentId;
                });
                openPrintPackage(item ? item.tracking_id : '');
            });
        });

        Array.from(tableBody.querySelectorAll('[data-delete-document]')).forEach(function (button) {
            button.addEventListener('click', function () {
                const documentId = Number(button.getAttribute('data-delete-document') || 0);
                archiveDocument(documentId);
            });
        });
    }

    function setDetailsLoading(message, isError) {
        if (detailsContent) {
            detailsContent.hidden = true;
        }
        if (detailsGrid) {
            detailsGrid.innerHTML = '';
        }
        if (attachmentList) {
            attachmentList.innerHTML = '';
        }
        setDetailsStatus(message, isError);
    }

    function renderDetailsPayload(payload) {
        const document = payload && payload.document ? payload.document : {};
        const attachments = Array.isArray(payload && payload.attachments) ? payload.attachments : [];

        if (detailsMeta) {
            detailsMeta.textContent = 'Tracking ID: ' + String(document.tracking_id || '-') + ' | Created by: ' + String(document.created_by || '-');
        }

        const detailFields = [
            ['Subject', document.subject || '-'],
            ['Status', document.status || '-'],
            ['Source', document.source_type || '-'],
            ['Sender', document.sender || '-'],
            ['Document Type', document.document_type || '-'],
            ['Originating Office / Entity', document.originating_entity_name || document.originating_office || '-'],
            ['Routing Office', document.routing_office || '-'],
            ['Current Office', document.current_office || '-'],
            ['Pending Office', document.pending_office || '-'],
            ['Created At', document.created_at || '-'],
            ['Last Action', document.last_action || '-'],
            ['Last Action At', document.last_action_at || '-'],
            ['Last Actor', document.last_actor || '-'],
            ['Last Remarks', document.last_remarks || '-'],
            ['Attachments', document.attachment_count || 0]
        ];

        if (detailsGrid) {
            detailsGrid.innerHTML = detailFields.map(function (field) {
                return `
                    <article class="super-admin-detail-card">
                        <span>${escapeHtml(field[0])}</span>
                        <strong>${escapeHtml(field[1])}</strong>
                    </article>
                `;
            }).join('');
        }

        if (attachmentList) {
            if (attachments.length === 0) {
                attachmentList.innerHTML = '<p class="super-admin-attachments-empty">No attachments found for this document.</p>';
            } else {
                attachmentList.innerHTML = attachments.map(function (attachment) {
                    const previewType = String(attachment.preview_type || 'file').toUpperCase();
                    return `
                        <article class="super-admin-attachment-card">
                            <div class="super-admin-attachment-copy">
                                <strong>${escapeHtml(attachment.file_name || 'Attachment')}</strong>
                                <span>Version ${escapeHtml(attachment.version_number || 1)} | ${escapeHtml(attachment.uploaded_by || 'Unknown')} | ${escapeHtml(attachment.uploaded_at || '-')}</span>
                                <span>${escapeHtml(previewType)}${attachment.uploaded_by_role ? ' | ' + escapeHtml(attachment.uploaded_by_role) : ''}</span>
                            </div>
                            <a class="super-admin-btn super-admin-btn-secondary" href="${escapeHtml(attachment.file_url || '#')}" target="_blank" rel="noopener">Open File</a>
                        </article>
                    `;
                }).join('');
            }
        }

        if (detailsContent) {
            detailsContent.hidden = false;
        }
        setDetailsStatus('Document details loaded.', false);
    }

    async function openDetails(documentId) {
        if (!documentId) {
            return;
        }

        const target = documents.find(function (item) {
            return Number(item.document_id || 0) === documentId;
        });
        selectedDocumentId = documentId;
        selectedDocumentTrackingId = target ? String(target.tracking_id || '') : '';
        renderDocumentsTable();
        openModal(detailsModal);
        setDetailsLoading('Loading document details...', false);

        try {
            const requestUrl = new URL(detailsEndpoint, window.location.origin);
            requestUrl.searchParams.set('document_id', String(documentId));
            const response = await fetch(requestUrl.toString(), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });
            const payload = await response.json().catch(function () {
                return { ok: false, message: 'Unexpected server response.' };
            });
            if (!response.ok || !payload.ok) {
                throw new Error(String(payload.message || 'Unable to load document details.'));
            }
            renderDetailsPayload(payload);
        } catch (error) {
            setDetailsLoading(error && error.message ? error.message : 'Unable to load document details.', true);
        }
    }

    async function fetchDocuments() {
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
            throw new Error(String(payload.message || 'Unable to load documents right now.'));
        }

        documents = Array.isArray(payload.documents) ? payload.documents.slice() : [];
        summary = payload.summary && typeof payload.summary === 'object' ? payload.summary : {};
        updateFilterOptions();
        updateSummary();
        renderDocumentsTable();
        return payload;
    }

    async function archiveDocument(documentId) {
        if (!documentId) {
            return;
        }

        const target = documents.find(function (item) {
            return Number(item.document_id || 0) === documentId;
        });
        const trackingId = String(target && target.tracking_id ? target.tracking_id : '').trim() || ('document #' + String(documentId));
        const confirmed = window.confirm('Move ' + trackingId + ' to the deleted documents recycle bin?');
        if (!confirmed) {
            return;
        }

        try {
            setStatus('Archiving ' + trackingId + '...', false);

            const formData = new FormData();
            formData.set('csrf_token', csrfToken);
            formData.set('action', 'DELETE_DOCUMENT');
            formData.set('document_id', String(documentId));

            const response = await fetch(endpoint, {
                method: 'POST',
                body: formData
            });
            const payload = await response.json().catch(function () {
                return { ok: false, message: 'Unexpected server response.' };
            });
            if (!response.ok || !payload.ok) {
                throw new Error(String(payload.message || 'Unable to archive the selected document.'));
            }

            documents = Array.isArray(payload.documents) ? payload.documents.slice() : [];
            summary = payload.summary && typeof payload.summary === 'object' ? payload.summary : {};
            updateFilterOptions();
            updateSummary();
            documentsPager.reset();
            if (selectedDocumentId === documentId) {
                selectedDocumentId = 0;
                selectedDocumentTrackingId = '';
                closeModal(detailsModal);
            }
            renderDocumentsTable();
            setStatus(String(payload.message || 'Document moved to recycle bin.'), false);
        } catch (error) {
            setStatus(error && error.message ? error.message : 'Unable to archive the selected document.', true);
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            documentsPager.reset();
            renderDocumentsTable();
        });
        const searchForm = searchInput.closest('form');
        if (searchForm) {
            searchForm.addEventListener('submit', function (event) {
                event.preventDefault();
            });
        }
    }

    [statusFilter, sourceFilter, officeFilter, creatorFilter].forEach(function (node) {
        if (!node) {
            return;
        }
        node.addEventListener('change', function () {
            documentsPager.reset();
            renderDocumentsTable();
        });
    });

    if (detailsTrackButton) {
        detailsTrackButton.addEventListener('click', function () {
            openTrackingSlip(selectedDocumentTrackingId);
        });
    }

    if (detailsPrintButton) {
        detailsPrintButton.addEventListener('click', function () {
            openPrintPackage(selectedDocumentTrackingId);
        });
    }

    Array.from(document.querySelectorAll('[data-super-admin-documents-close="true"]')).forEach(function (button) {
        button.addEventListener('click', function () {
            closeModal(detailsModal);
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && detailsModal && !detailsModal.hidden) {
            closeModal(detailsModal);
        }
    });

    documentsPager.bind(renderDocumentsTable);
    renderDocumentsTable();
    updateSummary();
    fetchDocuments()
        .then(function (payload) {
            setStatus(String(payload && payload.documents ? payload.documents.length : 0) + ' documents loaded for super admin review.', false);
        })
        .catch(function (error) {
            setStatus(error && error.message ? error.message : 'Unable to load documents right now.', true);
        });
})();
</script>
