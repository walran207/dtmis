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
    ? 'Review and decide on PASU, CENRO, and PENRO document type requests.'
    : 'Submit new document type requests for RECORDS-UNIT approval.';
?>
<?php if (!empty($dtrStatsCards)): ?>
<section class="stats-grid dtr-stats-grid" aria-label="Document type request summary cards">
    <?php foreach ($dtrStatsCards as $card): ?>
    <?php
        $cardLabelRaw = trim((string)($card['label'] ?? ''));
        $cardLabelKey = strtolower($cardLabelRaw);
        $cardFilter = '';
        if (str_contains($cardLabelKey, 'pending')) {
            $cardFilter = 'pending';
        } elseif (str_contains($cardLabelKey, 'approved')) {
            $cardFilter = 'approved';
        } elseif (str_contains($cardLabelKey, 'rejected')) {
            $cardFilter = 'rejected';
        }
    ?>
    <article
        class="card stat-card dtr-stat-card"
        data-dtr-card-filter="<?php echo e($cardFilter); ?>"
        data-dtr-card-label="<?php echo e($cardLabelRaw); ?>"
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
        <p class="stat-value"><?php echo e((string)($card['value'] ?? '0')); ?></p>
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
                <p><?php echo e((string)count($dtrRequests)); ?> request(s) loaded.</p>
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
                        <th>Category</th>
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
                        <td colspan="9" class="dtr-empty">No document type requests found yet.</td>
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
                                <td><?php echo e((string)($request['requested_category'] ?? '')); ?></td>
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

<script>
    (function () {
        const mode = <?= json_encode($dtrMode) ?>;
        const endpoint = <?= json_encode($dtrEndpoint) ?>;
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
        const statCards = Array.from(document.querySelectorAll('.dtr-stats-grid .dtr-stat-card'));
        let highlightedRowTimerId = 0;

        function normalizeTextKey(value) {
            return String(value || '').toLowerCase().trim();
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

        async function runReviewerAction(action) {
            if (!requestIdInput || String(requestIdInput.value || '').trim() === '') {
                window.alert('Select a request first.');
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

            if (action === 'REQUEST_REJECT' && String(payload.review_remarks || '').trim() === '') {
                const remarks = window.prompt('Enter rejection remarks:');
                if (remarks === null) {
                    return;
                }
                payload.review_remarks = remarks;
            }

            if (action === 'REQUEST_DELETE') {
                const okDelete = window.confirm('Delete this request record?');
                if (!okDelete) {
                    return;
                }
            }

            await postAction(payload);
            window.location.reload();
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
        window.requestAnimationFrame(bindStatCardQuickFilters);
        applyRequestTableFilters();

        document.querySelectorAll('[data-dtr-select]').forEach(function (button) {
            button.addEventListener('click', function () {
                const payload = parsePayload(button.getAttribute('data-dtr-select'));
                if (!payload) {
                    return;
                }
                populateForm(payload);
                if (requestedNameInput) {
                    requestedNameInput.focus();
                }
            });
        });

        document.querySelectorAll('[data-dtr-quick]').forEach(function (button) {
            button.addEventListener('click', async function () {
                const action = String(button.getAttribute('data-dtr-quick') || '').trim();
                const payload = parsePayload(button.getAttribute('data-dtr-select'));
                if (!action || !payload) {
                    return;
                }

                populateForm(payload);
                if (mode === 'reviewer') {
                    try {
                        setBusy(button, true);
                        await runReviewerAction(action);
                    } catch (error) {
                        window.alert(error.message || 'Unable to process action.');
                    } finally {
                        setBusy(button, false);
                    }
                    return;
                }

                if (action === 'REQUEST_DELETE') {
                    const okDelete = window.confirm('Delete this pending request?');
                    if (!okDelete) {
                        return;
                    }
                    try {
                        setBusy(button, true);
                        await postAction({
                            action: 'REQUEST_DELETE',
                            request_id: String(payload.id || ''),
                        });
                        window.location.reload();
                    } catch (error) {
                        window.alert(error.message || 'Unable to delete request.');
                    } finally {
                        setBusy(button, false);
                    }
                }
            });
        });

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
                    await postAction(payload);
                    window.location.reload();
                } catch (error) {
                    window.alert(error.message || 'Unable to submit request.');
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
                        window.alert(error.message || 'Unable to process request.');
                    } finally {
                        setBusy(button, false);
                    }
                });
            });
        }
    })();
</script>

