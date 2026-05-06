<?php
declare(strict_types=1);

$dtrMode = (string)($dtrMode ?? 'requester');
$dtrTableReady = (bool)($dtrTableReady ?? false);
$dtrRequests = is_array($dtrRequests ?? null) ? $dtrRequests : [];
$dtrStatsCards = is_array($dtrStatsCards ?? null) ? $dtrStatsCards : [];
$dtrCsrfToken = (string)($dtrCsrfToken ?? ($csrfToken ?? ''));
$dtrEndpoint = app_url('actions/document-type-request.php');

$dtrModeLabel = $dtrMode === 'reviewer' ? 'RECORDS-UNIT Review' : 'Request Queue';
$dtrModeHint = $dtrMode === 'reviewer'
    ? 'Review and decide on CENRO Admin Record, PENRO Admin Record, and PAMO Admin document type requests.'
    : 'Submit new document type requests for RECORDS-UNIT approval.';
?>
<?php if (!empty($dtrStatsCards)): ?>
<section class="stats-grid dtr-stats-grid" aria-label="Document type request summary cards">
    <?php foreach ($dtrStatsCards as $card): ?>
    <?php
        $cardLabelRaw = trim((string)($card['label'] ?? ''));
        $cardLabelKey = strtolower($cardLabelRaw);
        $cardFilter = '';
        $cardStatKey = 'total';
        if (str_contains($cardLabelKey, 'pending')) {
            $cardFilter = 'pending';
            $cardStatKey = 'pending';
        } elseif (str_contains($cardLabelKey, 'approved')) {
            $cardFilter = 'approved';
            $cardStatKey = 'approved';
        } elseif (str_contains($cardLabelKey, 'rejected')) {
            $cardFilter = 'rejected';
            $cardStatKey = 'rejected';
        }
    ?>
    <article
        class="card stat-card dtr-stat-card"
        data-dtr-card-filter="<?php echo e($cardFilter); ?>"
        data-dtr-card-label="<?php echo e($cardLabelRaw); ?>"
        data-dtr-stat-key="<?php echo e($cardStatKey); ?>"
        data-disable-stat-card-nav="1"
        data-disable-stat-card-live="1"
    >
        <div class="stat-head">
            <span class="stat-icon <?php echo e((string)($card['icon'] ?? 'blue')); ?>"></span>
            <?php if (!empty($card['pill'])): ?>
            <span class="live-pill"><?php echo e((string)$card['pill']); ?></span>
            <?php endif; ?>
        </div>
        <p class="stat-label"><?php echo e((string)($card['label'] ?? '')); ?></p>
        <p class="stat-value" data-dtr-stat-value="true"><?php echo e((string)($card['value'] ?? '0')); ?></p>
    </article>
    <?php endforeach; ?>
</section>
<?php endif; ?>
<section class="dtr-grid" aria-label="Document type requests module">
    <article class="card dtr-panel">
        <div class="dtr-head">
            <div>
                <h2>Document Type Requests</h2>
                <p><?php echo e($dtrModeHint); ?></p>
            </div>
            <span class="dtr-badge"><?php echo e($dtrModeLabel); ?></span>
        </div>

        <?php if (!$dtrTableReady): ?>
            <div class="dtr-warning">
                Request module is not ready. Run migration <code>2026_03_15_document_type_requests_module.sql</code>.
            </div>
        <?php else: ?>
            <form id="dtrForm" class="dtr-form" autocomplete="off">
                <input type="hidden" id="dtrRequestId" name="request_id" value="">
                <div class="dtr-form-grid">
                    <div class="dtr-field">
                        <label for="dtrRequestedName">Document Type Name</label>
                        <input type="text" id="dtrRequestedName" name="requested_name" placeholder="e.g., Reforestation Compliance Memo" required>
                    </div>
                    <div class="dtr-field dtr-field-full">
                        <label for="dtrJustification">Justification / Notes</label>
                        <textarea id="dtrJustification" name="justification" placeholder="Reason for adding or updating this document type."></textarea>
                    </div>
                    <?php if ($dtrMode === 'reviewer'): ?>
                    <div class="dtr-field dtr-field-full">
                        <label for="dtrReviewRemarks">RECORDS-UNIT Review Remarks</label>
                        <textarea id="dtrReviewRemarks" name="review_remarks" placeholder="Optional remarks for approval/rejection."></textarea>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="dtr-form-actions">
                    <?php if ($dtrMode === 'reviewer'): ?>
                    <button type="button" class="dtr-btn dtr-btn-secondary" data-dtr-run="REQUEST_UPDATE">Save Edit</button>
                    <button type="button" class="dtr-btn dtr-btn-primary" data-dtr-run="REQUEST_APPROVE">Approve</button>
                    <button type="button" class="dtr-btn dtr-btn-secondary" data-dtr-run="REQUEST_REJECT">Reject</button>
                    <button type="button" class="dtr-btn dtr-btn-danger" data-dtr-run="REQUEST_DELETE">Delete</button>
                    <?php else: ?>
                    <button type="submit" class="dtr-btn dtr-btn-primary" id="dtrSubmitButton">Submit Request</button>
                    <?php endif; ?>
                    <button type="button" class="dtr-btn dtr-btn-secondary" id="dtrClearButton">Clear</button>
                </div>
            </form>
        <?php endif; ?>
    </article>

    <article class="card dtr-panel">
        <div class="dtr-head">
            <div>
                <h2><?php echo $dtrMode === 'reviewer' ? 'All Requests' : 'My Requests'; ?></h2>
                <p id="dtrLoadedCount"><?php echo e((string)count($dtrRequests)); ?> request(s) loaded.</p>
            </div>
        </div>

        <div class="dtr-table-controls" aria-label="Request table controls">
            <form class="dtr-table-search" role="search" aria-label="Search document type requests">
                <label class="sr-only" for="dtrTableSearch">Search requests</label>
                <input
                    type="search"
                    id="dtrTableSearch"
                    placeholder="<?php echo e((string)($searchPlaceholder ?? 'Search request id or document type')); ?>"
                    title="Search by request id, document type, requester, office, remarks, or status.">
            </form>
            <div class="dtr-table-filter">
                <label class="sr-only" for="dtrStatusFilter">Filter by request status</label>
                <select id="dtrStatusFilter" title="Filter by request status.">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
        </div>

        <div class="dtr-table-wrap">
            <table class="dtr-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Document Type</th>
                        <th>Days</th>
                        <th>Status</th>
                        <th>Requester</th>
                        <th>Office</th>
                        <th>Remarks</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dtrRequests)): ?>
                    <tr>
                        <td colspan="8" class="dtr-empty">No document type requests found yet.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($dtrRequests as $request): ?>
                            <?php
                            $status = strtoupper((string)($request['status'] ?? 'PENDING'));
                            $statusClass = 'dtr-status-pending';
                            if ($status === 'APPROVED') {
                                $statusClass = 'dtr-status-approved';
                            } elseif ($status === 'REJECTED') {
                                $statusClass = 'dtr-status-rejected';
                            }
                            $rowPayload = [
                                'id' => (int)($request['id'] ?? 0),
                                'requested_name' => (string)($request['requested_name'] ?? ''),
                                'requested_category' => (string)($request['requested_category'] ?? 'Simple'),
                                'requested_days' => (int)($request['requested_days'] ?? 3),
                                'justification' => (string)($request['justification'] ?? ''),
                                'review_remarks' => (string)($request['review_remarks'] ?? ''),
                                'status' => $status,
                            ];
                            $payloadJson = json_encode($rowPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                            if (!is_string($payloadJson) || $payloadJson === '') {
                                $payloadJson = '{}';
                            }
                            $canManagePending = $status === 'PENDING';
                            ?>
                            <tr data-dtr-status="<?php echo e(strtolower($status)); ?>">
                                <td>#<?php echo e((string)$rowPayload['id']); ?></td>
                                <td><?php echo e((string)($request['requested_name'] ?? '')); ?></td>
                                <td><?php echo e((string)($request['requested_days'] ?? '')); ?></td>
                                <td><span class="dtr-status <?php echo e($statusClass); ?>"><?php echo e($status); ?></span></td>
                                <td>
                                    <?php echo e(trim((string)($request['requester_name'] ?? '')) !== '' ? (string)$request['requester_name'] : '-'); ?>
                                    <div class="dtr-muted"><?php echo e((string)($request['requester_role'] ?? '')); ?></div>
                                </td>
                                <td><?php echo e((string)($request['requester_office_name'] ?? '-')); ?></td>
                                <td>
                                    <div><?php echo e((string)($request['justification'] ?? '-')); ?></div>
                                    <?php if (!empty($request['review_remarks'])): ?>
                                    <div class="dtr-muted">RECORDS-UNIT: <?php echo e((string)$request['review_remarks']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="dtr-row-actions">
                                        <button type="button" class="dtr-btn dtr-btn-secondary" data-dtr-select="<?php echo e($payloadJson); ?>">Select</button>
                                        <?php if ($dtrMode === 'reviewer' && $canManagePending): ?>
                                        <button type="button" class="dtr-btn dtr-btn-primary" data-dtr-quick="REQUEST_APPROVE" data-dtr-select="<?php echo e($payloadJson); ?>">Approve</button>
                                        <button type="button" class="dtr-btn dtr-btn-secondary" data-dtr-quick="REQUEST_REJECT" data-dtr-select="<?php echo e($payloadJson); ?>">Reject</button>
                                        <button type="button" class="dtr-btn dtr-btn-danger" data-dtr-quick="REQUEST_DELETE" data-dtr-select="<?php echo e($payloadJson); ?>">Delete</button>
                                        <?php elseif ($dtrMode !== 'reviewer' && $canManagePending): ?>
                                        <button type="button" class="dtr-btn dtr-btn-danger" data-dtr-quick="REQUEST_DELETE" data-dtr-select="<?php echo e($payloadJson); ?>">Delete</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <p id="dtrFilterEmpty" class="dtr-filter-empty" hidden>No matching requests found for your current search/filter.</p>
    </article>
</section>

<div id="dtrDecisionModal" class="action-modal" hidden>
    <button type="button" class="action-modal-backdrop" data-dtr-decision-close="true" aria-label="Close review decision dialog"></button>
    <div class="action-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="dtrDecisionTitle">
        <div class="action-modal-form app-dialog-body">
            <h3 id="dtrDecisionTitle" class="action-modal-title">Confirm Action</h3>
            <p id="dtrDecisionMessage" class="action-modal-meta"></p>
            <div id="dtrDecisionRemarksWrap" hidden>
                <label class="action-modal-label" for="dtrDecisionRemarks">Review Remarks</label>
                <textarea id="dtrDecisionRemarks" class="action-modal-input" rows="4" maxlength="1000" placeholder="Add remarks for this request"></textarea>
                <p id="dtrDecisionRemarksMeta" class="action-modal-meta">Use remarks to guide the requester on the next steps.</p>
            </div>
            <div class="action-modal-actions app-dialog-actions">
                <button type="button" id="dtrDecisionCancel" class="action-modal-btn action-modal-btn-secondary" data-dtr-decision-close="true">Cancel</button>
                <button type="button" id="dtrDecisionConfirm" class="action-modal-btn action-modal-btn-primary">Confirm</button>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const mode = <?= json_encode($dtrMode) ?>;
        const endpoint = <?= json_encode($dtrEndpoint) ?>;
        const feedUrl = endpoint + '?action=REQUEST_LIST';
        const csrfToken = <?= json_encode($dtrCsrfToken) ?>;
        const tableReady = <?= $dtrTableReady ? 'true' : 'false' ?>;
        if (!tableReady) {
            return;
        }

        const form = document.getElementById('dtrForm');
        const requestIdInput = document.getElementById('dtrRequestId');
        const requestedNameInput = document.getElementById('dtrRequestedName');
        const justificationInput = document.getElementById('dtrJustification');
        const reviewRemarksInput = document.getElementById('dtrReviewRemarks');
        const clearButton = document.getElementById('dtrClearButton');
        const submitButton = document.getElementById('dtrSubmitButton');
        const tableSearchInput = document.getElementById('dtrTableSearch');
        const statusFilterSelect = document.getElementById('dtrStatusFilter');
        const requestsTableBody = document.querySelector('.dtr-table tbody');
        const requestsTableWrap = document.querySelector('.dtr-table-wrap');
        const filterEmptyState = document.getElementById('dtrFilterEmpty');
        const loadedCountLabel = document.getElementById('dtrLoadedCount');
        const statCards = Array.from(document.querySelectorAll('.dtr-stats-grid .dtr-stat-card'));
        const decisionModal = document.getElementById('dtrDecisionModal');
        const decisionTitle = document.getElementById('dtrDecisionTitle');
        const decisionMessage = document.getElementById('dtrDecisionMessage');
        const decisionRemarksWrap = document.getElementById('dtrDecisionRemarksWrap');
        const decisionRemarks = document.getElementById('dtrDecisionRemarks');
        const decisionRemarksMeta = document.getElementById('dtrDecisionRemarksMeta');
        const decisionConfirm = document.getElementById('dtrDecisionConfirm');
        const decisionCloseButtons = Array.from(document.querySelectorAll('[data-dtr-decision-close="true"]'));
        let highlightedRowTimerId = 0;
        let liveRefreshTimerId = 0;
        let liveRefreshInFlight = false;
        let currentSnapshotToken = '';
        let decisionDialogResolver = null;
        let decisionDialogRequiresRemarks = false;

        function normalizeTextKey(value) {
            return String(value || '').toLowerCase().trim();
        }

        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function escapeAttribute(value) {
            return escapeHtml(value);
        }

        function showAlert(message, title) {
            if (typeof window.showAlertDialog === 'function') {
                return window.showAlertDialog(message, title);
            }
            window.alert(String(message || ''));
            return Promise.resolve(true);
        }

        function statusClass(status) {
            const normalized = String(status || '').toUpperCase().trim();
            if (normalized === 'APPROVED') {
                return 'dtr-status-approved';
            }
            if (normalized === 'REJECTED') {
                return 'dtr-status-rejected';
            }
            return 'dtr-status-pending';
        }

        function buildRowPayload(request) {
            return {
                id: Number(request && request.id ? request.id : 0),
                requested_name: String(request && request.requested_name ? request.requested_name : ''),
                requested_category: String(request && request.requested_category ? request.requested_category : 'Simple'),
                requested_days: Number(request && request.requested_days ? request.requested_days : 3),
                justification: String(request && request.justification ? request.justification : ''),
                review_remarks: String(request && request.review_remarks ? request.review_remarks : ''),
                status: String(request && request.status ? request.status : 'PENDING').toUpperCase(),
            };
        }

        function setLoadedCount(count) {
            if (!loadedCountLabel) {
                return;
            }
            loadedCountLabel.textContent = String(count) + ' request(s) loaded.';
        }

        function renderStats(summary) {
            if (!summary || typeof summary !== 'object') {
                return;
            }
            const values = {
                total: Number(summary.total || 0),
                pending: Number(summary.pending || 0),
                approved: Number(summary.approved || 0),
                rejected: Number(summary.rejected || 0),
            };
            statCards.forEach(function (card) {
                const statKey = normalizeTextKey(card.getAttribute('data-dtr-stat-key'));
                const valueNode = card.querySelector('[data-dtr-stat-value="true"]');
                if (!valueNode || !Object.prototype.hasOwnProperty.call(values, statKey)) {
                    return;
                }
                valueNode.textContent = String(values[statKey]);
            });
        }

        function resolveCardFilterValue(card) {
            if (!card) {
                return '';
            }
            const explicitFilter = normalizeTextKey(card.getAttribute('data-dtr-card-filter'));
            if (explicitFilter !== '') {
                return explicitFilter;
            }
            const label = normalizeTextKey(card.getAttribute('data-dtr-card-label'));
            if (label.indexOf('pending') !== -1) {
                return 'pending';
            }
            if (label.indexOf('approved') !== -1) {
                return 'approved';
            }
            if (label.indexOf('rejected') !== -1) {
                return 'rejected';
            }
            return '';
        }

        function syncActiveStatCard() {
            if (statCards.length === 0) {
                return;
            }
            const activeFilter = normalizeTextKey(statusFilterSelect ? statusFilterSelect.value : '');
            statCards.forEach(function (card) {
                const cardFilter = resolveCardFilterValue(card);
                card.classList.toggle('is-active', cardFilter !== '' && cardFilter === activeFilter);
            });
        }

        function visibleDataRows() {
            if (!requestsTableBody) {
                return [];
            }
            return Array.from(requestsTableBody.querySelectorAll('tr'))
                .filter(function (row) {
                    return !row.querySelector('.dtr-empty') && row.style.display !== 'none';
                });
        }

        function clearHighlightedRows() {
            if (!requestsTableBody) {
                return;
            }
            Array.from(requestsTableBody.querySelectorAll('tr.dtr-row-focus')).forEach(function (row) {
                row.classList.remove('dtr-row-focus');
            });
        }

        function focusFirstVisibleRow() {
            const rows = visibleDataRows();
            if (rows.length === 0) {
                if (requestsTableWrap) {
                    requestsTableWrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
                return;
            }
            const firstRow = rows[0];
            clearHighlightedRows();
            firstRow.classList.add('dtr-row-focus');
            firstRow.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
            if (highlightedRowTimerId > 0) {
                window.clearTimeout(highlightedRowTimerId);
            }
            highlightedRowTimerId = window.setTimeout(function () {
                firstRow.classList.remove('dtr-row-focus');
                highlightedRowTimerId = 0;
            }, 1200);
        }

        function bindStatCardQuickFilters() {
            if (statCards.length === 0) {
                return;
            }
            statCards.forEach(function (card) {
                const cardFilter = resolveCardFilterValue(card);
                const cardLabel = String(card.getAttribute('data-dtr-card-label') || '').trim();
                card.classList.add('dtr-stat-card-clickable');
                card.setAttribute('role', 'button');
                card.setAttribute('tabindex', '0');
                card.setAttribute('aria-label', cardLabel !== '' ? ('Filter requests by ' + cardLabel) : 'Filter requests');

                const applyCardFilter = function () {
                    if (statusFilterSelect) {
                        statusFilterSelect.value = cardFilter;
                    }
                    applyRequestTableFilters();
                    focusFirstVisibleRow();
                };

                card.addEventListener('click', applyCardFilter);
                card.addEventListener('keydown', function (event) {
                    if (event.key !== 'Enter' && event.key !== ' ') {
                        return;
                    }
                    event.preventDefault();
                    applyCardFilter();
                });
            });
            syncActiveStatCard();
        }

        function applyRequestTableFilters() {
            if (!requestsTableBody) {
                return;
            }

            const dataRows = Array.from(requestsTableBody.querySelectorAll('tr')).filter(function (row) {
                return !row.querySelector('.dtr-empty');
            });
            if (dataRows.length === 0) {
                if (filterEmptyState) {
                    filterEmptyState.hidden = true;
                }
                return;
            }

            const searchTerm = String(tableSearchInput ? tableSearchInput.value : '').toLowerCase().trim();
            const statusValue = String(statusFilterSelect ? statusFilterSelect.value : '').toLowerCase().trim();
            let visibleRows = 0;

            dataRows.forEach(function (row) {
                const rowStatus = String(row.getAttribute('data-dtr-status') || '').toLowerCase().trim();
                const rowText = String(row.textContent || '').toLowerCase();
                const matchesSearch = searchTerm === '' || rowText.indexOf(searchTerm) !== -1;
                const matchesStatus = statusValue === '' || rowStatus === statusValue;
                const isVisible = matchesSearch && matchesStatus;

                row.style.display = isVisible ? '' : 'none';
                if (isVisible) {
                    visibleRows += 1;
                }
            });

            if (filterEmptyState) {
                filterEmptyState.hidden = visibleRows !== 0;
            }
            syncActiveStatCard();
        }

        function setBusy(button, isBusy) {
            if (!button) {
                return;
            }
            button.disabled = isBusy;
        }

        function resetForm() {
            if (!form) {
                return;
            }
            form.reset();
            if (requestIdInput) {
                requestIdInput.value = '';
            }
            if (submitButton && mode !== 'reviewer') {
                submitButton.textContent = 'Submit Request';
            }
        }

        function populateForm(payload) {
            if (!payload || !form) {
                return;
            }
            if (requestIdInput) {
                requestIdInput.value = String(payload.id || '');
            }
            if (requestedNameInput) {
                requestedNameInput.value = String(payload.requested_name || '');
            }
            if (justificationInput) {
                justificationInput.value = String(payload.justification || '');
            }
            if (reviewRemarksInput) {
                reviewRemarksInput.value = String(payload.review_remarks || '');
            }
            if (submitButton && mode !== 'reviewer') {
                submitButton.textContent = 'Save Changes';
            }
        }

        function parsePayload(rawPayload) {
            if (!rawPayload) {
                return null;
            }
            try {
                return JSON.parse(String(rawPayload));
            } catch (error) {
                return null;
            }
        }

        function closeDecisionDialog(result) {
            if (!decisionModal) {
                return;
            }
            decisionModal.classList.remove('is-open');
            decisionModal.hidden = true;
            document.body.classList.remove('modal-open');
            if (typeof decisionDialogResolver === 'function') {
                const resolver = decisionDialogResolver;
                decisionDialogResolver = null;
                resolver(result);
            }
        }

        function openDecisionDialog(options) {
            const settings = options && typeof options === 'object' ? options : {};
            const title = String(settings.title || 'Confirm Action').trim();
            const message = String(settings.message || '').trim();
            const confirmLabel = String(settings.confirmLabel || 'Confirm').trim();
            const remarksVisible = !!settings.showRemarks;
            const remarksRequired = !!settings.remarksRequired;
            const remarksValue = String(settings.remarksValue || '');
            const remarksLabel = String(settings.remarksLabel || 'Review Remarks').trim();
            const remarksHelp = String(settings.remarksHelp || '').trim();

            if (!decisionModal || !decisionTitle || !decisionMessage || !decisionConfirm) {
                if (remarksVisible && remarksRequired) {
                    const fallbackRemarks = window.prompt(message + '\n\nEnter review remarks:');
                    if (fallbackRemarks === null || String(fallbackRemarks).trim() === '') {
                        return Promise.resolve({ confirmed: false, remarks: '' });
                    }
                    return Promise.resolve({ confirmed: true, remarks: String(fallbackRemarks).trim() });
                }
                if (typeof window.showConfirmDialog === 'function') {
                    return window.showConfirmDialog(message, title, confirmLabel).then(function (confirmed) {
                        return { confirmed: !!confirmed, remarks: remarksValue };
                    });
                }
                return Promise.resolve({
                    confirmed: window.confirm(message),
                    remarks: remarksValue,
                });
            }

            decisionTitle.textContent = title === '' ? 'Confirm Action' : title;
            decisionMessage.textContent = message;
            decisionConfirm.textContent = confirmLabel === '' ? 'Confirm' : confirmLabel;
            if (decisionRemarksWrap) {
                decisionRemarksWrap.hidden = !remarksVisible;
            }
            const remarksLabelNode = decisionRemarksWrap ? decisionRemarksWrap.querySelector('label[for="dtrDecisionRemarks"]') : null;
            if (remarksLabelNode) {
                remarksLabelNode.textContent = remarksLabel === '' ? 'Review Remarks' : remarksLabel;
            }
            if (decisionRemarks) {
                decisionRemarks.value = remarksValue;
                decisionRemarks.placeholder = remarksRequired
                    ? 'Required remarks for this action'
                    : 'Optional remarks for this action';
            }
            if (decisionRemarksMeta) {
                decisionRemarksMeta.textContent = remarksHelp !== ''
                    ? remarksHelp
                    : (remarksRequired
                        ? 'Review remarks are required before continuing.'
                        : 'Remarks are optional for this action.');
            }
            decisionDialogRequiresRemarks = remarksVisible && remarksRequired;
            decisionModal.hidden = false;
            decisionModal.classList.add('is-open');
            document.body.classList.add('modal-open');
            window.setTimeout(function () {
                if (remarksVisible && decisionRemarks) {
                    decisionRemarks.focus();
                    return;
                }
                decisionConfirm.focus();
            }, 0);

            return new Promise(function (resolve) {
                decisionDialogResolver = resolve;
            });
        }

        function bindDecisionDialog() {
            if (!decisionModal || !decisionConfirm) {
                return;
            }

            decisionConfirm.addEventListener('click', async function () {
                const remarksValue = decisionRemarks ? String(decisionRemarks.value || '').trim() : '';
                if (decisionDialogRequiresRemarks && remarksValue === '') {
                    await showAlert('Review remarks are required before continuing.', 'Remarks Required');
                    if (decisionRemarks) {
                        decisionRemarks.focus();
                    }
                    return;
                }
                closeDecisionDialog({
                    confirmed: true,
                    remarks: remarksValue,
                });
            });

            decisionCloseButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    closeDecisionDialog({ confirmed: false, remarks: '' });
                });
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && decisionModal && !decisionModal.hidden) {
                    closeDecisionDialog({ confirmed: false, remarks: '' });
                }
            });
        }

        function buildTableRow(request) {
            const payload = buildRowPayload(request);
            const payloadJson = escapeAttribute(JSON.stringify(payload));
            const normalizedStatus = String(payload.status || 'PENDING').toUpperCase();
            const requesterName = String(request && request.requester_name ? request.requester_name : '').trim();
            const requesterRole = String(request && request.requester_role ? request.requester_role : '').trim();
            const requesterOffice = String(request && request.requester_office_name ? request.requester_office_name : '-').trim();
            const canManagePending = normalizedStatus === 'PENDING';
            let actionButtons = '<button type="button" class="dtr-btn dtr-btn-secondary" data-dtr-select="' + payloadJson + '">Select</button>';

            if (mode === 'reviewer' && canManagePending) {
                actionButtons += '<button type="button" class="dtr-btn dtr-btn-primary" data-dtr-quick="REQUEST_APPROVE" data-dtr-select="' + payloadJson + '">Approve</button>';
                actionButtons += '<button type="button" class="dtr-btn dtr-btn-secondary" data-dtr-quick="REQUEST_REJECT" data-dtr-select="' + payloadJson + '">Reject</button>';
                actionButtons += '<button type="button" class="dtr-btn dtr-btn-danger" data-dtr-quick="REQUEST_DELETE" data-dtr-select="' + payloadJson + '">Delete</button>';
            } else if (mode !== 'reviewer' && canManagePending) {
                actionButtons += '<button type="button" class="dtr-btn dtr-btn-danger" data-dtr-quick="REQUEST_DELETE" data-dtr-select="' + payloadJson + '">Delete</button>';
            }

            let remarksMarkup = '<div>' + escapeHtml(String(request && request.justification ? request.justification : '-')) + '</div>';
            if (String(request && request.review_remarks ? request.review_remarks : '').trim() !== '') {
                remarksMarkup += '<div class="dtr-muted">RECORDS-UNIT: ' + escapeHtml(String(request.review_remarks)) + '</div>';
            }

            return ''
                + '<tr data-dtr-status="' + escapeAttribute(normalizedStatus.toLowerCase()) + '" data-dtr-request-id="' + escapeAttribute(String(payload.id || 0)) + '">'
                + '<td>#' + escapeHtml(String(payload.id || 0)) + '</td>'
                + '<td>' + escapeHtml(payload.requested_name) + '</td>'
                + '<td>' + escapeHtml(String(payload.requested_days || 0)) + '</td>'
                + '<td><span class="dtr-status ' + escapeAttribute(statusClass(normalizedStatus)) + '">' + escapeHtml(normalizedStatus) + '</span></td>'
                + '<td>' + escapeHtml(requesterName !== '' ? requesterName : '-') + '<div class="dtr-muted">' + escapeHtml(requesterRole) + '</div></td>'
                + '<td>' + escapeHtml(requesterOffice) + '</td>'
                + '<td>' + remarksMarkup + '</td>'
                + '<td><div class="dtr-row-actions">' + actionButtons + '</div></td>'
                + '</tr>';
        }

        function renderRequestsTable(requests) {
            if (!requestsTableBody) {
                return;
            }

            if (!Array.isArray(requests) || requests.length === 0) {
                requestsTableBody.innerHTML = '<tr><td colspan="8" class="dtr-empty">No document type requests found yet.</td></tr>';
                setLoadedCount(0);
                applyRequestTableFilters();
                return;
            }

            requestsTableBody.innerHTML = requests.map(buildTableRow).join('');
            setLoadedCount(requests.length);
            applyRequestTableFilters();
        }

        function findPayloadById(requests, requestId) {
            const numericId = Number(requestId || 0);
            if (!Array.isArray(requests) || !Number.isFinite(numericId) || numericId <= 0) {
                return null;
            }
            const match = requests.find(function (request) {
                return Number(request && request.id ? request.id : 0) === numericId;
            });
            return match ? buildRowPayload(match) : null;
        }

        async function confirmDeleteRequest(payload, options) {
            const settings = options && typeof options === 'object' ? options : {};
            const requestName = String(
                (payload && payload.requested_name)
                || (requestedNameInput ? requestedNameInput.value : '')
                || 'this document type request'
            ).trim();
            const status = String((payload && payload.status) || '').trim();
            const ownerLabel = settings.ownerLabel ? String(settings.ownerLabel).trim() : 'request';
            const messageParts = [
                'Delete "' + requestName + '"?',
                'This will permanently remove the ' + ownerLabel + ' record.'
            ];
            if (status !== '') {
                messageParts.push('Current status: ' + status + '.');
            }
            messageParts.push('This action cannot be undone.');
            const message = messageParts.join(' ');

            if (typeof window.showConfirmDialog === 'function') {
                return !!(await window.showConfirmDialog(message, 'Delete Request', 'Delete'));
            }

            return window.confirm(message);
        }

        async function postAction(payload) {
            const formData = new FormData();
            formData.set('csrf_token', csrfToken);
            Object.keys(payload).forEach(function (key) {
                const value = payload[key];
                if (value === undefined || value === null) {
                    return;
                }
                formData.set(key, String(value));
            });

            const response = await fetch(endpoint, {
                method: 'POST',
                body: formData,
            });
            const json = await response.json().catch(function () {
                return { ok: false, message: 'Unexpected server response.' };
            });
            if (!response.ok || !json.ok) {
                throw new Error(json.message || 'Unable to process request.');
            }
            return json;
        }

        async function refreshLiveData(options) {
            const settings = options && typeof options === 'object' ? options : {};
            if (liveRefreshInFlight) {
                return;
            }

            liveRefreshInFlight = true;
            const selectedRequestId = Object.prototype.hasOwnProperty.call(settings, 'selectedRequestId')
                ? String(settings.selectedRequestId || '').trim()
                : (requestIdInput ? String(requestIdInput.value || '').trim() : '');
            const silentErrors = !!settings.silentErrors;

            try {
                const response = await fetch(feedUrl + '&_ts=' + Date.now(), {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                    },
                });
                const json = await response.json().catch(function () {
                    return { ok: false, message: 'Unexpected server response.' };
                });
                if (!response.ok || !json.ok) {
                    throw new Error(json.message || 'Unable to load document type requests.');
                }

                const snapshotToken = String(json.snapshot_token || '').trim();
                const requests = Array.isArray(json.requests) ? json.requests : [];
                const summary = json.summary && typeof json.summary === 'object' ? json.summary : {
                    total: 0,
                    pending: 0,
                    approved: 0,
                    rejected: 0,
                };

                if (snapshotToken !== '' && snapshotToken === currentSnapshotToken) {
                    return;
                }

                currentSnapshotToken = snapshotToken;
                renderStats(summary);
                renderRequestsTable(requests);

                if (selectedRequestId !== '') {
                    const selectedPayload = findPayloadById(requests, selectedRequestId);
                    if (selectedPayload) {
                        populateForm(selectedPayload);
                    } else {
                        resetForm();
                    }
                }
            } catch (error) {
                if (!silentErrors) {
                    await showAlert(error.message || 'Unable to load live document type requests.', 'Live Update Failed');
                }
            } finally {
                liveRefreshInFlight = false;
            }
        }

        function startLiveRefreshLoop() {
            if (liveRefreshTimerId > 0) {
                window.clearInterval(liveRefreshTimerId);
            }
            liveRefreshTimerId = window.setInterval(function () {
                if (document.hidden) {
                    return;
                }
                refreshLiveData({ silentErrors: true });
            }, mode === 'reviewer' ? 15000 : 20000);
        }

        async function collectReviewerDecision(action, payload) {
            const requestName = String(payload && payload.requested_name ? payload.requested_name : 'this request').trim() || 'this request';
            if (action === 'REQUEST_UPDATE') {
                return openDecisionDialog({
                    title: 'Save Request Changes',
                    message: 'Save changes to "' + requestName + '" before RECORDS-UNIT decides on it?',
                    confirmLabel: 'Save Changes',
                });
            }
            if (action === 'REQUEST_APPROVE') {
                return openDecisionDialog({
                    title: 'Approve Request',
                    message: 'Approve "' + requestName + '" and add or update it in the document type master list?',
                    confirmLabel: 'Approve',
                    showRemarks: true,
                    remarksRequired: false,
                    remarksValue: String(payload && payload.review_remarks ? payload.review_remarks : ''),
                    remarksHelp: 'Optional. Add approval notes if the requester needs context.',
                });
            }
            if (action === 'REQUEST_REJECT') {
                return openDecisionDialog({
                    title: 'Reject Request',
                    message: 'Reject "' + requestName + '"? Add clear review remarks so the requester knows what to fix.',
                    confirmLabel: 'Reject',
                    showRemarks: true,
                    remarksRequired: true,
                    remarksValue: String(payload && payload.review_remarks ? payload.review_remarks : ''),
                    remarksHelp: 'Required. Explain why the request is being rejected or what needs correction.',
                });
            }
            if (action === 'REQUEST_DELETE') {
                return openDecisionDialog({
                    title: 'Delete Request',
                    message: 'Delete "' + requestName + '"? This permanently removes the request record and cannot be undone.',
                    confirmLabel: 'Delete',
                });
            }
            return { confirmed: true, remarks: '' };
        }

        async function runReviewerAction(action) {
            if (!requestIdInput || String(requestIdInput.value || '').trim() === '') {
                await showAlert('Select a request first.', 'No Request Selected');
                return;
            }
            const requestId = String(requestIdInput.value || '').trim();
            const payload = {
                action: action,
                request_id: requestId,
                requested_name: requestedNameInput ? requestedNameInput.value : '',
                justification: justificationInput ? justificationInput.value : '',
                review_remarks: reviewRemarksInput ? reviewRemarksInput.value : '',
            };

            const decision = await collectReviewerDecision(action, payload);
            if (!decision || !decision.confirmed) {
                return;
            }

            if (Object.prototype.hasOwnProperty.call(decision, 'remarks')) {
                payload.review_remarks = String(decision.remarks || '').trim();
                if (reviewRemarksInput) {
                    reviewRemarksInput.value = payload.review_remarks;
                }
            }

            const result = await postAction(payload);
            if (action === 'REQUEST_DELETE') {
                resetForm();
            }
            await refreshLiveData({
                selectedRequestId: action === 'REQUEST_DELETE' ? '' : requestId,
                silentErrors: false,
            });
            await showAlert(result.message || 'Request action completed.', 'Action Complete');
        }

        if (clearButton) {
            clearButton.addEventListener('click', function () {
                resetForm();
            });
        }

        if (tableSearchInput) {
            tableSearchInput.addEventListener('input', applyRequestTableFilters);
            const searchForm = tableSearchInput.closest('form');
            if (searchForm) {
                searchForm.addEventListener('submit', function (event) {
                    event.preventDefault();
                });
            }
        }

        if (statusFilterSelect) {
            statusFilterSelect.addEventListener('change', applyRequestTableFilters);
        }

        if (requestsTableBody) {
            requestsTableBody.addEventListener('click', async function (event) {
                const quickButton = event.target.closest('[data-dtr-quick]');
                if (quickButton) {
                    const action = String(quickButton.getAttribute('data-dtr-quick') || '').trim();
                    const payload = parsePayload(quickButton.getAttribute('data-dtr-select'));
                    if (!action || !payload) {
                        return;
                    }

                    populateForm(payload);
                    if (mode === 'reviewer') {
                        try {
                            setBusy(quickButton, true);
                            await runReviewerAction(action);
                        } catch (error) {
                            await showAlert(error.message || 'Unable to process request.', 'Action Failed');
                        } finally {
                            setBusy(quickButton, false);
                        }
                        return;
                    }

                    if (action === 'REQUEST_DELETE') {
                        const okDelete = await confirmDeleteRequest(payload, {
                            ownerLabel: 'request',
                        });
                        if (!okDelete) {
                            return;
                        }
                        try {
                            setBusy(quickButton, true);
                            const result = await postAction({
                                action: 'REQUEST_DELETE',
                                request_id: String(payload.id || ''),
                            });
                            resetForm();
                            await refreshLiveData({
                                selectedRequestId: '',
                                silentErrors: false,
                            });
                            await showAlert(result.message || 'Request deleted.', 'Delete Complete');
                        } catch (error) {
                            await showAlert(error.message || 'Unable to delete request.', 'Delete Failed');
                        } finally {
                            setBusy(quickButton, false);
                        }
                    }
                    return;
                }

                const selectButton = event.target.closest('[data-dtr-select]');
                if (!selectButton) {
                    return;
                }
                const payload = parsePayload(selectButton.getAttribute('data-dtr-select'));
                if (!payload) {
                    return;
                }
                populateForm(payload);
                if (requestedNameInput) {
                    requestedNameInput.focus();
                }
            });
        }

        if (mode !== 'reviewer' && form) {
            form.addEventListener('submit', async function (event) {
                event.preventDefault();
                const requestId = requestIdInput ? String(requestIdInput.value || '').trim() : '';
                const action = requestId !== '' ? 'REQUEST_UPDATE' : 'REQUEST_CREATE';
                const payload = {
                    action: action,
                    request_id: requestId,
                    requested_name: requestedNameInput ? requestedNameInput.value : '',
                    justification: justificationInput ? justificationInput.value : '',
                };

                try {
                    setBusy(submitButton, true);
                    const result = await postAction(payload);
                    if (action === 'REQUEST_CREATE') {
                        resetForm();
                    }
                    await refreshLiveData({
                        selectedRequestId: action === 'REQUEST_UPDATE' ? requestId : '',
                        silentErrors: false,
                    });
                    await showAlert(result.message || 'Request submitted.', 'Request Saved');
                } catch (error) {
                    await showAlert(error.message || 'Unable to submit request.', 'Save Failed');
                } finally {
                    setBusy(submitButton, false);
                }
            });
        }

        if (mode === 'reviewer') {
            document.querySelectorAll('[data-dtr-run]').forEach(function (button) {
                button.addEventListener('click', async function () {
                    const action = String(button.getAttribute('data-dtr-run') || '').trim();
                    if (!action) {
                        return;
                    }
                    try {
                        setBusy(button, true);
                        await runReviewerAction(action);
                    } catch (error) {
                        await showAlert(error.message || 'Unable to process request.', 'Action Failed');
                    } finally {
                        setBusy(button, false);
                    }
                });
            });
        }

        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                refreshLiveData({ silentErrors: true });
            }
        });

        window.requestAnimationFrame(bindStatCardQuickFilters);
        bindDecisionDialog();
        applyRequestTableFilters();
        startLiveRefreshLoop();
        refreshLiveData({ silentErrors: true });
    })();
</script>

