<?php
declare(strict_types=1);

$superAdminDataEndpoint = (string)($superAdminDataEndpoint ?? app_url('actions/super-admin-data.php'));
$superAdminDocumentTypes = is_array($superAdminDocumentTypes ?? null) ? $superAdminDocumentTypes : [];
$superAdminDataOverview = is_array($superAdminDataOverview ?? null) ? $superAdminDataOverview : [];
$superAdminCsrfToken = (string)($superAdminCsrfToken ?? ($csrfToken ?? ''));
?>
<section class="super-admin-grid" aria-label="Super Admin data management">
    <article class="card super-admin-panel">
        <div class="super-admin-panel-head">
            <h2>Document Type Data Registry</h2>
            <p>Update complexity category, ARTA days limit, and active state.</p>
        </div>

        <div class="super-admin-toolbar" aria-label="Document type controls">
            <form class="super-admin-search" role="search" aria-label="Search document types">
                <label class="sr-only" for="superAdminTypeSearch">Search document types</label>
                <input id="superAdminTypeSearch" type="search" placeholder="Search type name or category">
            </form>
            <label class="super-admin-filter" for="superAdminTypeStatusFilter">
                <span>Status</span>
                <select id="superAdminTypeStatusFilter">
                    <option value="">All</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </label>
        </div>

        <div class="super-admin-table-wrap">
            <table class="super-admin-table" id="superAdminTypeTable">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Days</th>
                        <th>Status</th>
                        <th>Created By Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="superAdminTypeBody">
                    <tr>
                        <td colspan="6" class="table-empty-state">Loading document types...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p id="superAdminTypeEmpty" class="super-admin-empty" hidden>No document types match your current filters.</p>
    </article>

    <article class="card super-admin-panel">
        <div class="super-admin-panel-head">
            <h2>Edit Document Type</h2>
            <p>Changes are reflected immediately for new intake and queue calculations.</p>
        </div>

        <form id="superAdminTypeForm" class="super-admin-form" autocomplete="off">
            <input type="hidden" id="superAdminTypeId" value="">
            <label class="super-admin-field">
                <span>Document Type</span>
                <input type="text" id="superAdminTypeName" value="" disabled>
            </label>
            <label class="super-admin-field">
                <span>Category</span>
                <select id="superAdminTypeCategory" required>
                    <option value="Simple">Simple</option>
                    <option value="Complex">Complex</option>
                    <option value="Highly Technical">Highly Technical</option>
                </select>
            </label>
            <label class="super-admin-field">
                <span>Days Limit</span>
                <input type="number" id="superAdminTypeDays" min="1" max="365" value="3" required>
            </label>
            <label class="super-admin-checkbox" for="superAdminTypeActive">
                <input type="checkbox" id="superAdminTypeActive" checked>
                <span>Document type is active</span>
            </label>

            <div class="super-admin-form-actions">
                <button type="submit" class="super-admin-btn super-admin-btn-primary" id="superAdminTypeSave">Save Data</button>
                <button type="button" class="super-admin-btn super-admin-btn-secondary" id="superAdminTypeClear">Clear</button>
            </div>
        </form>

        <div class="super-admin-mini-metrics" id="superAdminDataMiniMetrics">
            <p>Total Documents: <strong data-metric="documents_total">0</strong></p>
            <p>Pending: <strong data-metric="documents_pending">0</strong></p>
            <p>Overdue: <strong data-metric="documents_overdue">0</strong></p>
            <p>Compliance: <strong data-metric="compliance_rate">0%</strong></p>
        </div>

        <p id="superAdminDataStatus" class="super-admin-status" role="status" aria-live="polite">Select a document type to begin.</p>
    </article>
</section>

<script>
(function () {
    const endpoint = <?= json_encode($superAdminDataEndpoint, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const csrfToken = <?= json_encode($superAdminCsrfToken, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    let documentTypes = <?= json_encode($superAdminDocumentTypes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    let overview = <?= json_encode($superAdminDataOverview, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    const tableBody = document.getElementById('superAdminTypeBody');
    const emptyState = document.getElementById('superAdminTypeEmpty');
    const searchInput = document.getElementById('superAdminTypeSearch');
    const statusFilter = document.getElementById('superAdminTypeStatusFilter');

    const form = document.getElementById('superAdminTypeForm');
    const typeIdInput = document.getElementById('superAdminTypeId');
    const typeNameInput = document.getElementById('superAdminTypeName');
    const typeCategorySelect = document.getElementById('superAdminTypeCategory');
    const typeDaysInput = document.getElementById('superAdminTypeDays');
    const typeActiveCheckbox = document.getElementById('superAdminTypeActive');
    const clearButton = document.getElementById('superAdminTypeClear');
    const saveButton = document.getElementById('superAdminTypeSave');
    const statusMessage = document.getElementById('superAdminDataStatus');
    const miniMetrics = document.getElementById('superAdminDataMiniMetrics');

    let selectedTypeId = 0;

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
        if (!statusMessage) {
            return;
        }
        statusMessage.textContent = String(message || '');
        statusMessage.classList.toggle('is-error', !!isError);
    }

    function setBusy(isBusy) {
        if (saveButton) {
            saveButton.disabled = !!isBusy;
        }
    }

    function updateMiniMetrics() {
        if (!miniMetrics) {
            return;
        }
        const metrics = {
            documents_total: Number(overview.documents_total || 0),
            documents_pending: Number(overview.documents_pending || 0),
            documents_overdue: Number(overview.documents_overdue || 0),
            compliance_rate: String(Number(overview.compliance_rate || 0)) + '%',
        };

        Object.keys(metrics).forEach(function (key) {
            const node = miniMetrics.querySelector('[data-metric="' + key + '"]');
            if (!node) {
                return;
            }
            node.textContent = String(metrics[key]);
        });
    }

    function renderTable() {
        if (!tableBody) {
            return;
        }

        const searchTerm = String(searchInput ? searchInput.value : '').toLowerCase().trim();
        const statusValue = String(statusFilter ? statusFilter.value : '').toLowerCase().trim();

        const filtered = documentTypes.filter(function (type) {
            const rowText = [type.name, type.category, type.created_by_role].join(' ').toLowerCase();
            const matchesSearch = searchTerm === '' || rowText.indexOf(searchTerm) !== -1;
            const isActive = !!type.is_active;
            let matchesStatus = true;
            if (statusValue === 'active') {
                matchesStatus = isActive;
            } else if (statusValue === 'inactive') {
                matchesStatus = !isActive;
            }
            return matchesSearch && matchesStatus;
        });

        if (filtered.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" class="table-empty-state">No document types found.</td></tr>';
            if (emptyState) {
                emptyState.hidden = false;
            }
            return;
        }

        if (emptyState) {
            emptyState.hidden = true;
        }

        tableBody.innerHTML = filtered.map(function (type) {
            const typeId = Number(type.id || 0);
            const statusClass = type.is_active ? 'status-pill is-active' : 'status-pill is-inactive';
            const statusText = type.is_active ? 'Active' : 'Inactive';
            const rowClass = selectedTypeId === typeId ? 'is-selected-row' : '';

            return `
                <tr class="${rowClass}">
                    <td>${escapeHtml(type.name || '-')}</td>
                    <td>${escapeHtml(type.category || '-')}</td>
                    <td>${escapeHtml(type.arta_days_limit || '-')}</td>
                    <td><span class="${statusClass}">${escapeHtml(statusText)}</span></td>
                    <td>${escapeHtml(type.created_by_role || '-')}</td>
                    <td><button type="button" class="super-admin-table-btn" data-type-select="${typeId}">Edit</button></td>
                </tr>
            `;
        }).join('');

        Array.from(tableBody.querySelectorAll('[data-type-select]')).forEach(function (button) {
            button.addEventListener('click', function () {
                const typeId = Number(button.getAttribute('data-type-select') || 0);
                selectType(typeId);
            });
        });
    }

    function clearSelection() {
        selectedTypeId = 0;
        if (typeIdInput) {
            typeIdInput.value = '';
        }
        if (typeNameInput) {
            typeNameInput.value = '';
        }
        if (typeCategorySelect) {
            typeCategorySelect.value = 'Simple';
        }
        if (typeDaysInput) {
            typeDaysInput.value = '3';
        }
        if (typeActiveCheckbox) {
            typeActiveCheckbox.checked = true;
        }
        renderTable();
    }

    function selectType(typeId) {
        const target = documentTypes.find(function (type) {
            return Number(type.id || 0) === Number(typeId || 0);
        });
        if (!target) {
            return;
        }

        selectedTypeId = Number(target.id || 0);
        if (typeIdInput) {
            typeIdInput.value = String(selectedTypeId);
        }
        if (typeNameInput) {
            typeNameInput.value = String(target.name || '');
        }
        if (typeCategorySelect) {
            typeCategorySelect.value = String(target.category || 'Simple');
        }
        if (typeDaysInput) {
            typeDaysInput.value = String(Math.max(1, Number(target.arta_days_limit || 3)));
        }
        if (typeActiveCheckbox) {
            typeActiveCheckbox.checked = !!target.is_active;
        }

        renderTable();
        setStatus('Editing document type #' + selectedTypeId + '.', false);
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
            throw new Error(String(json.message || 'Unable to process action.'));
        }

        return json;
    }

    function applyResponse(json) {
        if (Array.isArray(json.document_types)) {
            documentTypes = json.document_types.slice();
        }
        if (json.overview && typeof json.overview === 'object') {
            overview = json.overview;
        }
        renderTable();
        updateMiniMetrics();
        if (selectedTypeId > 0) {
            selectType(selectedTypeId);
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', renderTable);
        const searchForm = searchInput.closest('form');
        if (searchForm) {
            searchForm.addEventListener('submit', function (event) {
                event.preventDefault();
            });
        }
    }

    if (statusFilter) {
        statusFilter.addEventListener('change', renderTable);
    }

    if (clearButton) {
        clearButton.addEventListener('click', function () {
            clearSelection();
            setStatus('Selection cleared.', false);
        });
    }

    if (form) {
        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            const typeId = Number(typeIdInput ? typeIdInput.value : 0);
            if (!typeId) {
                setStatus('Select a document type before saving.', true);
                return;
            }

            const daysLimit = Math.max(1, Math.min(365, Number(typeDaysInput ? typeDaysInput.value : 3)));
            const category = String(typeCategorySelect ? typeCategorySelect.value : 'Simple');

            try {
                setBusy(true);
                const result = await postAction({
                    action: 'UPDATE_DOCUMENT_TYPE',
                    document_type_id: typeId,
                    category: category,
                    arta_days_limit: daysLimit,
                    is_active: typeActiveCheckbox && typeActiveCheckbox.checked ? 1 : 0,
                });
                applyResponse(result);
                setStatus(result.message || 'Document type updated.', false);
            } catch (error) {
                setStatus(error.message || 'Unable to update document type.', true);
            } finally {
                setBusy(false);
            }
        });
    }

    updateMiniMetrics();
    renderTable();
    clearSelection();
})();
</script>
