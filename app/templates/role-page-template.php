<?php
declare(strict_types=1);

require_once __DIR__ . '/_role_bootstrap.php';
$preloadStyleIncludes = array_values(array_unique(array_merge(
    is_array($preloadStyleIncludes ?? null) ? $preloadStyleIncludes : [],
    [__DIR__ . '/_role_styles.php']
)));
include $roleBasePath . '/partials/header.php';
include $roleBasePath . '/partials/sidebar.php';
?>

        <button type="button" class="sidebar-backdrop" aria-label="Close sidebar" tabindex="-1"></button>
        <main class="content">
            <header class="content-header">
                <div class="header-title-row">
                    <button type="button" class="mobile-nav-toggle" aria-label="Open navigation menu">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                    </button>
                    <span class="sr-only"><?php echo e($pageHeading); ?></span>
                </div>
                <div class="header-actions">
                    <div
                        id="headerOfflineIndicator"
                        class="header-offline-indicator is-online"
                        role="status"
                        aria-live="polite"
                        aria-label="Network status: Online"
                        title="Connected."
                    >
                        <span class="header-offline-indicator-dot" aria-hidden="true"></span>
                        <span id="headerOfflineIndicatorText">Online</span>
                    </div>
                    <?php if ($showHeaderSearch): ?>
                    <form class="top-search" method="get" action="" role="search" aria-label="Queue search">
                        <label class="sr-only" for="queueSearch">Search queue</label>
                        <input id="queueSearch" type="search" name="q" placeholder="<?php echo e($searchPlaceholder); ?>" title="Search by tracking ID, subject, or office. Results update as you type.">
                    </form>
                    <?php endif; ?>

                    <div class="notif-wrap">
                        <button id="notifToggle" type="button" class="notif-btn" aria-label="Open notifications" aria-expanded="false" aria-controls="notifDropdown" title="Open notifications and alerts.">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V10a6 6 0 1 0-12 0v4.2a2 2 0 0 1-.6 1.4L4 17h5"></path>
                                <path d="M9 17a3 3 0 0 0 6 0"></path>
                            </svg>
                            <span class="notif-count"><?php echo e((string)count($notifications)); ?></span>
                        </button>

                        <div id="notifDropdown" class="notif-dropdown" role="dialog" aria-label="Notifications">
                            <div class="notif-head">
                                <h2>Notifications</h2>
                                <div class="notif-head-actions">
                                    <a class="notif-open-page" href="<?php echo e($notificationsPageUrl); ?>">Open page</a>
                                    <button type="button" class="notif-clear">Mark all read</button>
                                </div>
                            </div>
                            <div class="notif-list" tabindex="0">
                                <?php if (empty($notifications)): ?>
                                <article class="notif-empty">No notifications yet.</article>
                                <?php else: ?>
                                    <?php foreach ($notifications as $notification): ?>
                                        <?php
                                            $notificationId = (int)($notification['id'] ?? 0);
                                            $notificationDateTime = (string)($notification['datetime'] ?? '');
                                            $notificationTrackingId = trim((string)($notification['tracking_id'] ?? ''));
                                            $notificationIndicatorKeyRaw = strtolower(trim((string)($notification['indicator_key'] ?? '')));
                                            $notificationIndicatorKey = in_array($notificationIndicatorKeyRaw, ['yellow', 'pink', 'blue', 'green', 'neutral'], true)
                                                ? $notificationIndicatorKeyRaw
                                                : 'neutral';
                                            $notificationIndicatorLabel = trim((string)($notification['indicator_label'] ?? ''));
                                            if ($notificationIndicatorLabel === '') {
                                                if ($notificationIndicatorKey === 'yellow') {
                                                    $notificationIndicatorLabel = 'Yellow - Urgent';
                                                } elseif ($notificationIndicatorKey === 'blue') {
                                                    $notificationIndicatorLabel = 'Blue - Complex/Highly Technical';
                                                } elseif ($notificationIndicatorKey === 'green') {
                                                    $notificationIndicatorLabel = 'Green - Released';
                                                } elseif ($notificationIndicatorKey === 'pink') {
                                                    $notificationIndicatorLabel = 'Pink - Simple';
                                                } else {
                                                    $notificationIndicatorLabel = 'Indicator';
                                                }
                                            }
                                            $notificationHref = $notificationTrackingId !== ''
                                                ? app_public_url('tracking-slip.php') . '?tracking_id=' . rawurlencode($notificationTrackingId) . '&public=1'
                                                : $notificationsPageUrl;
                                        ?>
                                <article
                                    class="notif-item <?php echo !empty($notification['unread']) ? 'is-unread' : ''; ?>"
                                    data-notification-id="<?php echo e((string)$notificationId); ?>"
                                    data-datetime="<?php echo e($notificationDateTime); ?>"
                                    data-tracking-id="<?php echo e($notificationTrackingId); ?>"
                                    data-indicator-key="<?php echo e($notificationIndicatorKey); ?>"
                                >
                                    <a class="notif-item-link" href="<?php echo e($notificationHref); ?>">
                                        <span class="notif-item-indicator is-<?php echo e($notificationIndicatorKey); ?>" title="<?php echo e($notificationIndicatorLabel); ?>" aria-label="<?php echo e($notificationIndicatorLabel); ?>"></span>
                                        <div class="notif-item-copy">
                                            <h3><?php echo e((string)($notification['title'] ?? 'Update')); ?></h3>
                                            <p><?php echo e((string)($notification['message'] ?? '')); ?></p>
                                            <time datetime="<?php echo e($notificationDateTime); ?>"><?php echo e((string)($notification['timeLabel'] ?? 'Now')); ?></time>
                                        </div>
                                    </a>
                                </article>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="theme-toggle-btn"
                        data-theme-toggle
                        aria-label="Switch theme"
                        title="Switch theme"
                    >
                        <span class="theme-icon-light" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                        </span>
                        <span class="theme-icon-dark" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                        </span>
                    </button>
                    <button
                        type="button"
                        id="capturePageScreenshotHeaderBtn"
                        class="screenshot-page-btn"
                        aria-label="Capture screenshot of this page"
                        title="Capture screenshot of this page"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h3.2l1.6-2h6.4l1.6 2H20a2 2 0 0 1 2 2z"></path>
                            <circle cx="12" cy="13" r="3.2"></circle>
                        </svg>
                        <span class="label">Screenshot</span>
                    </button>

                    <div class="profile-wrap">
                        <button id="profileToggle" type="button" class="profile-btn" aria-label="Open profile menu" aria-expanded="false" aria-controls="profileDropdown" title="Open profile settings and logout menu.">
                            <span class="profile-avatar" aria-hidden="true"><?php echo e($initials); ?></span>
                            <span class="profile-name"><?php echo e($fullName); ?></span>
                            <svg class="profile-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M6 9l6 6 6-6"></path>
                            </svg>
                        </button>
                        <div id="profileDropdown" class="profile-dropdown" role="menu" aria-label="Profile menu">
                            <button type="button" class="profile-menu-btn" role="menuitem" data-profile-settings="true">Profile settings</button>
                            <button type="button" id="setScreenshotDirectoryBtn" class="profile-menu-btn" role="menuitem">Set Screenshot Folder</button>
                            <button type="button" id="capturePageScreenshotBtn" class="profile-menu-btn" role="menuitem">Capture This Page</button>
                            <button type="button" id="toggleAutoScreenshotBtn" class="profile-menu-btn" role="menuitem" aria-pressed="false">Auto Screenshot: Off</button>
                            <button type="button" class="profile-menu-btn danger" role="menuitem" data-logout="true">Logout</button>
                        </div>
                    </div>

                    <?php if ($showHeaderDateFilter): ?>
                    <div class="date-filter-wrap">
                        <button id="dateFilterToggle" type="button" class="date-filter-icon-btn" aria-label="Open date range filter" aria-expanded="false" aria-controls="dateFilterDropdown" title="Filter queue by date range.">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                <path d="M16 2v4"></path>
                                <path d="M8 2v4"></path>
                                <path d="M3 10h18"></path>
                            </svg>
                        </button>
                        <form id="dateFilterDropdown" class="date-range-dropdown" aria-label="Date range filter">
                            <label class="date-field">
                                <span>From</span>
                                <input type="date" name="fromDate" value="<?php echo e((string)($activeDateFilterRange['from_input'] ?? '')); ?>" title="Show documents dated on or after this date.">
                            </label>
                            <label class="date-field">
                                <span>To</span>
                                <input type="date" name="toDate" value="<?php echo e((string)($activeDateFilterRange['to_input'] ?? '')); ?>" title="Show documents dated on or before this date.">
                            </label>
                            <button type="button" class="date-filter-btn" title="Apply selected date range filters.">Apply</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </header>

            <?php if ($showStickyActionsBar): ?>
            <div class="page-sticky-actions" aria-label="Quick actions">
                <?php if ($showStickySearch): ?>
                <form class="top-search sticky-search" method="get" action="" role="search" aria-label="Queue search">
                    <label class="sr-only" for="queueSearch">Search queue</label>
                    <input id="queueSearch" type="search" name="q" placeholder="<?php echo e($searchPlaceholder); ?>" title="Search by tracking ID, subject, or office. Results update as you type.">
                </form>
                <?php endif; ?>
                <?php if ($showStickyStatusFilter && $showStatusCategoryFilter): ?>
                <div class="status-filter-wrap sticky-status-filter">
                    <label class="sr-only" for="statusFilterSelect">Filter queue by status category</label>
                    <select id="statusFilterSelect" class="status-filter-select" aria-label="Filter queue by status category" title="Filter the queue by workflow status category.">
                        <?php foreach ($statusFilterOptions as $statusFilterOption): ?>
                        <option value="<?php echo e((string)$statusFilterOption['value']); ?>"><?php echo e((string)$statusFilterOption['label']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <?php foreach ($stickyActions as $stickyAction): ?>
                <button type="button" title="<?php echo e(role_sticky_action_tooltip((string)$stickyAction)); ?>"><?php echo e((string)$stickyAction); ?></button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ($roleKeyAttr === 'SUPER_ADMIN'): ?>
            <script>
                (function () {
                    if (typeof window.createSuperAdminTablePager === 'function') {
                        return;
                    }

                    window.createSuperAdminTablePager = function createSuperAdminTablePager(options) {
                        const settings = options && typeof options === 'object' ? options : {};
                        const prevButton = settings.prevButton || null;
                        const nextButton = settings.nextButton || null;
                        const infoNode = settings.infoNode || null;
                        const pageSize = Math.max(1, Number(settings.pageSize || 10));
                        let currentPage = 1;

                        function updateControls(totalItems) {
                            const totalPages = totalItems > 0 ? Math.max(1, Math.ceil(totalItems / pageSize)) : 0;

                            if (totalPages === 0) {
                                currentPage = 1;
                                if (prevButton) {
                                    prevButton.disabled = true;
                                }
                                if (nextButton) {
                                    nextButton.disabled = true;
                                }
                                if (infoNode) {
                                    infoNode.textContent = 'Page 0 of 0';
                                }
                                return totalPages;
                            }

                            currentPage = Math.min(Math.max(currentPage, 1), totalPages);
                            if (prevButton) {
                                prevButton.disabled = currentPage <= 1;
                            }
                            if (nextButton) {
                                nextButton.disabled = currentPage >= totalPages;
                            }
                            if (infoNode) {
                                infoNode.textContent = 'Page ' + currentPage + ' of ' + totalPages;
                            }

                            return totalPages;
                        }

                        return {
                            reset: function () {
                                currentPage = 1;
                            },
                            ensureItemVisible: function (items, predicate) {
                                if (!Array.isArray(items) || typeof predicate !== 'function') {
                                    return;
                                }

                                const targetIndex = items.findIndex(predicate);
                                if (targetIndex >= 0) {
                                    currentPage = Math.floor(targetIndex / pageSize) + 1;
                                }
                            },
                            slice: function (items) {
                                const rows = Array.isArray(items) ? items : [];
                                const totalPages = updateControls(rows.length);
                                if (totalPages === 0) {
                                    return [];
                                }

                                const start = (currentPage - 1) * pageSize;
                                return rows.slice(start, start + pageSize);
                            },
                            bind: function (onChange) {
                                if (prevButton) {
                                    prevButton.addEventListener('click', function () {
                                        if (currentPage <= 1) {
                                            return;
                                        }
                                        currentPage -= 1;
                                        if (typeof onChange === 'function') {
                                            onChange();
                                        }
                                    });
                                }

                                if (nextButton) {
                                    nextButton.addEventListener('click', function () {
                                        currentPage += 1;
                                        if (typeof onChange === 'function') {
                                            onChange();
                                        }
                                    });
                                }
                            }
                        };
                    };
                })();
            </script>
            <?php endif; ?>

            <?php if ($customSectionInclude !== '' && is_file($customSectionInclude)): ?>
                <?php include $customSectionInclude; ?>
            <?php endif; ?>

            <?php if ($renderStandardContent): ?>
            <section class="stats-grid" aria-label="Summary cards">
                <?php foreach ($kpiCards as $card): ?>
                <article class="card stat-card" data-card-label="<?php echo e(strtolower(trim((string)($card['label'] ?? '')))); ?>">
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
            <?php if ($isIntakePage): ?>
            <div id="createIntakeModal" class="action-modal create-intake-modal" hidden>
                <button type="button" class="action-modal-backdrop" data-create-intake-close="true" aria-label="Close create intake dialog"></button>
                <div class="action-modal-dialog details-modal-dialog create-intake-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="createIntakeModalTitle">
                    <section class="intake-panel intake-modal-panel" aria-label="Document intake form">
                        <div class="intake-panel-head">
                            <h2 id="createIntakeModalTitle">Create Document Intake</h2>
                            <p>Submit and route a new document with tracking ID and custody-ready history.</p>
                        </div>
                        <form id="intakeForm" class="intake-form details-modal-form" method="post" enctype="multipart/form-data" novalidate data-current-office-name="<?php echo e(trim((string)($officeContext['name'] ?? 'Current Office'))); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                    <div class="intake-grid">
                        <label class="intake-field">
                            <span>Sender Source</span>
                            <select id="intakeSourceType" name="source_type">
                                <option value="INTERNAL" selected>Current Office</option>
                                <option value="EXTERNAL">Client</option>
                            </select>
                            <small class="intake-hint">Choose whether the sender should come from your office or from a client.</small>
                        </label>
                        <label class="intake-field">
                            <span>Routing Office</span>
                            <input type="text" id="intakeRoutingOffice" value="<?php echo e(trim((string)($officeContext['name'] ?? 'Current Office'))); ?>" readonly>
                            <small class="intake-hint">Used internally for DENR workflow routing and permissions.</small>
                        </label>
                        <label class="intake-field intake-field-full">
                            <span id="intakeOriginatingEntityLabel">Originating Office / Entity</span>
                            <input type="text" id="intakeOriginatingEntity" name="originating_entity_name" maxlength="255" placeholder="Enter originating office or entity" value="<?php echo e(trim((string)($officeContext['name'] ?? 'Current Office'))); ?>">
                            <small id="intakeOriginatingEntityHint" class="intake-hint">Shown on the tracking slip, print package, and search tables.</small>
                        </label>
                        <label class="intake-field intake-field-full">
                            <span id="intakeSenderLabel">Sender</span>
                            <input type="text" id="intakeExternalClient" name="external_client_name" placeholder="Sender name">
                            <small id="intakeSenderHint" class="intake-hint">Shown on the tracking slip above Encoder.</small>
                        </label>
                        <label id="intakeClientAddressField" class="intake-field intake-field-full">
                            <span id="intakeClientAddressLabel">Address</span>
                            <input type="text" id="intakeClientAddress" name="client_address" maxlength="255" placeholder="Enter client address">
                            <small id="intakeClientAddressHint" class="intake-hint">Shown in the tracking slip Address field.</small>
                        </label>
                        <label class="intake-field intake-field-full">
                            <span>Subject / Title</span>
                            <textarea id="intakeSubject" name="subject" rows="3" placeholder="Enter document subject" required></textarea>
                            <small class="intake-hint">You can resize this field like Remarks for longer titles.</small>
                        </label>
                        <label class="intake-field">
                            <span>Document Type</span>
                            <select id="intakeDocumentType" name="document_type_id">
                                <option value="">Select document type</option>
                                <?php foreach ($documentTypeOptions as $docType): ?>
                                <?php
                                $docTypeNameRaw = trim((string)($docType['name'] ?? ''));
                                $docTypeNameNormalized = strtolower(preg_replace('/\s+/', ' ', $docTypeNameRaw) ?? '');
                                $docTypeCategoryNormalized = strtolower(trim((string)($docType['category'] ?? '')));
                                $isOthersDocumentType = $docTypeNameNormalized === 'others'
                                    || $docTypeNameNormalized === 'other'
                                    || $docTypeCategoryNormalized === 'others';
                                ?>
                                <option
                                    value="<?php echo e((string)($docType['id'] ?? '')); ?>"
                                    data-category="<?php echo e((string)($docType['category'] ?? '')); ?>"
                                    data-is-others="<?php echo $isOthersDocumentType ? '1' : '0'; ?>"
                                >
                                    <?php echo e((string)($docType['name'] ?? 'Document Type')); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="intake-hint">Select an active document type. Custom type requests are managed in Document Type Requests.</small>
                            <?php if (empty($documentTypeOptions)): ?>
                            <small class="intake-hint" style="color:#b42318;">No active document types found. Please seed `document_types` in `denr_db` and refresh.</small>
                            <?php endif; ?>
                        </label>
                        <label id="intakeOtherDocumentTypeField" class="intake-field" hidden>
                            <span>Others (Specify Document Type)</span>
                            <input type="text" id="customDocumentTypeName" name="custom_document_type_name" maxlength="255" placeholder="Enter specific document type name">
                            <small class="intake-hint">Used for this intake record only. The master Document Type list will not be changed.</small>
                        </label>
                        <label class="intake-field">
                            <span>Type of Complexity</span>
                            <select id="intakeComplexityType" name="intake_complexity_type">
                                <option value="Simple" selected>Simple</option>
                                <option value="Complex">Complex</option>
                                <option value="Highly Technical">Highly Technical</option>
                                <option value="Others">Others</option>
                            </select>
                        </label>
                        <label class="intake-field">
                            <span>Days (Custom)</span>
                            <input type="number" id="intakeComplexityDays" name="intake_complexity_days" min="1" max="365" value="3">
                            <small class="intake-hint">Defaults: Simple=3, Complex=7, Highly Technical=20, Others=custom. You can set your own days.</small>
                        </label>
                        <label class="intake-field">
                            <span>Color Classification</span>
                            <select id="intakeColorClassification" name="intake_color_classification">
                                <option value="PINK_SIMPLE" selected>Pink - Simple</option>
                                <option value="YELLOW_URGENT">Yellow - Urgent</option>
                                <option value="BLUE_COMPLEX_HIGHLY_TECHNICAL">Blue - Complex/Highly Technical</option>
                                <option value="GREEN_RELEASED">Green - Released</option>
                            </select>
                            <small class="intake-hint">Classification guide: Yellow=Urgent, Pink=Simple, Blue=Complex/Highly Technical, Green=Released.</small>
                        </label>
                        <label class="intake-field intake-field-full">
                            <span>Attachments</span>
                            <input type="file" id="intakeAttachments" name="attachment_files[]" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp,.txt" multiple>
                            <small class="intake-hint">Upload soft copy when available. If no file is uploaded, remarks are required.</small>
                        </label>
                        <?php if ($showIntakeForwardToField): ?>
                        <label class="intake-field">
                            <span>Forward To</span>
                            <select
                                id="intakeDestinationOffice"
                                name="destination_office_id_disabled"
                                disabled
                                aria-disabled="true"
                            >
                                <option value="" selected>Manual forwarding only (after intake creation)</option>
                            </select>
                            <small class="intake-hint">Automatic forward on intake is disabled for all roles. Use queue actions (Receive, Forward) manually.</small>
                        </label>
                        <?php endif; ?>
                        <?php if ($showIntakeActionRequiredField): ?>
                        <label class="intake-field">
                            <span>Action Required</span>
                            <select id="intakeActionRequired" name="action_required">
                                <option value="">Select action</option>
                                <option value="For Information">For Information</option>
                                <option value="For Appropriate Action">For Appropriate Action</option>
                                <option value="For Signature/Approval">For Signature/Approval</option>
                            </select>
                            <small class="intake-hint">Optional instruction for the receiving office and tracking slip.</small>
                        </label>
                        <?php endif; ?>
                        <label class="intake-field intake-field-full">
                            <span>Remarks</span>
                            <textarea id="intakeRemarks" name="remarks" rows="3" placeholder="Optional remarks for logs"></textarea>
                        </label>
                    </div>
                    <div class="intake-actions">
                        <button type="button" class="intake-btn intake-btn-muted" data-create-intake-close="true">Close</button>
                        <button type="button" id="intakeSaveDraft" class="intake-btn intake-btn-muted">Save Draft</button>
                        <button type="submit" id="intakeSubmit" class="intake-btn intake-btn-primary">Submit Intake</button>
                        <?php if (!empty($showIntakeForwardButton)): ?>
                        <button type="button" id="intakeSubmitForward" class="intake-btn intake-btn-primary">Submit &amp; Forward</button>
                        <?php endif; ?>
                    </div>
                        </form>
                    </section>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($panels)): ?>
            <section class="insight-grid" aria-label="Page elements">
                <?php foreach ($panels as $panel): ?>
                <article class="card panel">
                    <h2><?php echo e((string)($panel['title'] ?? 'Panel')); ?></h2>
                    <?php if (!empty($panel['rows']) && is_array($panel['rows'])): ?>
                    <div class="progress-list">
                        <?php foreach ($panel['rows'] as $row): ?>
                        <div class="progress-row">
                            <div class="row-meta"><span><?php echo e((string)($row['label'] ?? '')); ?></span><span><?php echo e((string)($row['value'] ?? '')); ?></span></div>
                            <div class="bar"><span style="width: <?php echo e((string)($row['width'] ?? '0%')); ?>"></span></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($panel['chips']) && is_array($panel['chips'])): ?>
                    <div class="action-chip-row">
                        <?php foreach ($panel['chips'] as $chip): ?>
                        <span class="action-chip"><?php echo e((string)$chip); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </article>
                <?php endforeach; ?>
            </section>
            <?php endif; ?>

            <?php if ($enableCharts): ?>
            <section class="charts-grid" aria-label="Dashboard charts">
                <article class="card panel chart-panel" data-chart-kind="arta_mix">
                    <div class="chart-head">
                        <div class="chart-head-main">
                            <h2>ARTA Workload Mix</h2>
                        </div>
                        <div class="chart-head-tools">
                            <span class="chart-note">Active workload</span>
                            <span class="chart-filter-caption" data-chart-filter-caption="arta_mix">Page range</span>
                            <div class="chart-date-filter" data-chart-date-filter="arta_mix">
                                <button type="button" class="chart-date-filter-toggle" aria-label="Open chart date filter" aria-expanded="false" title="Filter this chart by date range.">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                        <path d="M16 2v4"></path>
                                        <path d="M8 2v4"></path>
                                        <path d="M3 10h18"></path>
                                    </svg>
                                </button>
                                <form class="chart-date-filter-dropdown" aria-label="Chart date range filter">
                                    <label class="date-field">
                                        <span>From</span>
                                        <input type="date" name="fromDate" value="<?php echo e((string)($activeDateFilterRange['from_input'] ?? '')); ?>" title="Show chart data on or after this date.">
                                    </label>
                                    <label class="date-field">
                                        <span>To</span>
                                        <input type="date" name="toDate" value="<?php echo e((string)($activeDateFilterRange['to_input'] ?? '')); ?>" title="Show chart data on or before this date.">
                                    </label>
                                    <div class="chart-date-filter-actions">
                                        <button type="button" class="chart-date-filter-btn is-secondary" data-chart-date-reset="arta_mix" title="Reset this chart to the page date range.">Reset</button>
                                        <button type="button" class="chart-date-filter-btn" data-chart-date-apply="arta_mix" title="Apply this date range to the chart.">Apply</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="chart-pie-wrap">
                        <?php if ($chartPieTotal > 0): ?>
                            <?php
                            $angleStart = 0.0;
                            $gradientStops = [];
                            foreach ($chartPieSeries as $pieItem) {
                                $value = max((int)($pieItem['value'] ?? 0), 0);
                                if ($value <= 0) {
                                    continue;
                                }
                                $slice = ($value / $chartPieTotal) * 360;
                                $angleEnd = $angleStart + $slice;
                                $gradientStops[] = (string)$pieItem['color'] . ' ' . round($angleStart, 2) . 'deg ' . round($angleEnd, 2) . 'deg';
                                $angleStart = $angleEnd;
                            }
                            $gradientCss = implode(', ', $gradientStops);
                            ?>
                            <div class="chart-donut" style="background: conic-gradient(<?php echo e($gradientCss); ?>);">
                                <div class="chart-donut-hole">
                                    <strong><?php echo e((string)$chartPieTotal); ?></strong>
                                    <span>Docs</span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="chart-empty">No ARTA data yet.</div>
                        <?php endif; ?>
                        <div class="chart-legend-list">
                            <?php foreach ($chartPieSeries as $pieItem): ?>
                            <div class="chart-legend-item">
                                <span class="legend-dot" style="background: <?php echo e((string)$pieItem['color']); ?>;"></span>
                                <span><?php echo e((string)$pieItem['label']); ?></span>
                                <strong><?php echo e((string)($pieItem['value'] ?? 0)); ?></strong>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </article>

                <article class="card panel chart-panel" data-chart-kind="queue_status">
                    <div class="chart-head">
                        <div class="chart-head-main">
                            <h2>Queue Status Bars</h2>
                        </div>
                        <div class="chart-head-tools">
                            <span class="chart-note">Current scope</span>
                            <span class="chart-filter-caption" data-chart-filter-caption="queue_status">Page range</span>
                            <div class="chart-date-filter" data-chart-date-filter="queue_status">
                                <button type="button" class="chart-date-filter-toggle" aria-label="Open chart date filter" aria-expanded="false" title="Filter this chart by date range.">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                        <path d="M16 2v4"></path>
                                        <path d="M8 2v4"></path>
                                        <path d="M3 10h18"></path>
                                    </svg>
                                </button>
                                <form class="chart-date-filter-dropdown" aria-label="Chart date range filter">
                                    <label class="date-field">
                                        <span>From</span>
                                        <input type="date" name="fromDate" value="<?php echo e((string)($activeDateFilterRange['from_input'] ?? '')); ?>" title="Show chart data on or after this date.">
                                    </label>
                                    <label class="date-field">
                                        <span>To</span>
                                        <input type="date" name="toDate" value="<?php echo e((string)($activeDateFilterRange['to_input'] ?? '')); ?>" title="Show chart data on or before this date.">
                                    </label>
                                    <div class="chart-date-filter-actions">
                                        <button type="button" class="chart-date-filter-btn is-secondary" data-chart-date-reset="queue_status" title="Reset this chart to the page date range.">Reset</button>
                                        <button type="button" class="chart-date-filter-btn" data-chart-date-apply="queue_status" title="Apply this date range to the chart.">Apply</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="chart-bar-wrap">
                        <?php foreach ($chartBarSeries as $barItem): ?>
                        <div class="chart-bar-col">
                            <div class="chart-bar-rail">
                                <span class="chart-bar-fill" style="--bar-val: <?php echo e((string)$barItem['height']); ?>; background: <?php echo e((string)$barItem['color']); ?>;"></span>
                            </div>
                            <strong><?php echo e((string)($barItem['value'] ?? 0)); ?></strong>
                            <span><?php echo e((string)($barItem['label'] ?? '')); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </article>

                <article class="card panel chart-panel chart-panel-wide" data-chart-kind="workflow_trend">
                    <div class="chart-head">
                        <div class="chart-head-main">
                            <h2>7-Day Workflow Spline</h2>
                        </div>
                        <div class="chart-head-tools">
                            <span class="chart-note">Received vs routed vs completed</span>
                            <span class="chart-filter-caption" data-chart-filter-caption="workflow_trend">Page range</span>
                            <div class="chart-date-filter" data-chart-date-filter="workflow_trend">
                                <button type="button" class="chart-date-filter-toggle" aria-label="Open chart date filter" aria-expanded="false" title="Filter this chart by date range.">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                        <path d="M16 2v4"></path>
                                        <path d="M8 2v4"></path>
                                        <path d="M3 10h18"></path>
                                    </svg>
                                </button>
                                <form class="chart-date-filter-dropdown" aria-label="Chart date range filter">
                                    <label class="date-field">
                                        <span>From</span>
                                        <input type="date" name="fromDate" value="<?php echo e((string)($activeDateFilterRange['from_input'] ?? '')); ?>" title="Show chart data on or after this date.">
                                    </label>
                                    <label class="date-field">
                                        <span>To</span>
                                        <input type="date" name="toDate" value="<?php echo e((string)($activeDateFilterRange['to_input'] ?? '')); ?>" title="Show chart data on or before this date.">
                                    </label>
                                    <div class="chart-date-filter-actions">
                                        <button type="button" class="chart-date-filter-btn is-secondary" data-chart-date-reset="workflow_trend" title="Reset this chart to the page date range.">Reset</button>
                                        <button type="button" class="chart-date-filter-btn" data-chart-date-apply="workflow_trend" title="Apply this date range to the chart.">Apply</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="chart-spline-wrap">
                        <svg id="workflowSplineChart" class="chart-spline" viewBox="0 0 620 220" preserveAspectRatio="none" aria-label="Workflow trend chart"></svg>
                        <?php if (!$chartHasTrendPoints): ?>
                        <div class="chart-empty chart-empty-overlay">No trend data yet.</div>
                        <?php endif; ?>
                    </div>
                    <div class="chart-axis-row" style="grid-template-columns: repeat(<?php echo e((string)max(count($chartTrendLabels), 1)); ?>, minmax(0, 1fr));">
                        <?php foreach ($chartTrendLabels as $chartLabel): ?>
                        <span><?php echo e((string)$chartLabel); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <div class="chart-legend-inline">
                        <?php foreach ($chartTrendSeries as $seriesItem): ?>
                        <span><i style="background: <?php echo e((string)$seriesItem['color']); ?>;"></i><?php echo e((string)$seriesItem['label']); ?></span>
                        <?php endforeach; ?>
                    </div>
                </article>
            </section>
            <?php endif; ?>

            <?php if ($showLiveFlowTracker && $showQueueTable): ?>
            <section id="liveFlowTrackerCard" class="card live-flow-tracker" aria-label="Live horizontal document flow">
                <div class="live-flow-head">
                    <div>
                        <h2><?php echo e($liveFlowTrackerTitle); ?></h2>
                        <p>Drag to pan. Use mouse wheel or controls to zoom.</p>
                    </div>
                    <div class="live-flow-meta">
                        <span id="liveFlowTrackingId" class="live-flow-pill">Tracking: -</span>
                        <span id="liveFlowCurrentHolder" class="live-flow-pill">Current Holder: -</span>
                        <span id="liveFlowStatus" class="live-flow-pill is-status">Status: Pending</span>
                        <div class="live-flow-tools" aria-label="Diagram zoom controls">
                            <button id="liveFlowZoomOut" type="button" class="live-flow-tool-btn" title="Zoom out" aria-label="Zoom out">-</button>
                            <button id="liveFlowZoomIn" type="button" class="live-flow-tool-btn" title="Zoom in" aria-label="Zoom in">+</button>
                            <button id="liveFlowResetView" type="button" class="live-flow-tool-btn reset" title="Reset view" aria-label="Reset view">Reset</button>
                        </div>
                    </div>
                </div>
                <div id="liveFlowViewport" class="live-flow-viewport" aria-label="Flow diagram canvas">
                    <div id="liveFlowCanvas" class="live-flow-canvas">
                        <svg class="live-flow-svg" viewBox="0 0 2200 640" preserveAspectRatio="none" aria-hidden="true">
                            <path class="live-flow-line-outer" d="M64 104 H2128 Q2164 104 2164 138 V204 Q2164 238 2128 238 H58 Q24 238 24 272 V356 Q24 392 60 392 H2088 Q2128 392 2128 430 V492 Q2128 526 2092 526 H180" />
                            <path class="live-flow-line-guide" d="M148 272 H2120" />
                            <path class="live-flow-line-main" d="M238 408 H1922" />
                            <g class="live-flow-marker start" transform="translate(64 104)">
                                <text class="marker-label" x="0" y="-29">START</text>
                                <circle class="marker-glow" cx="0" cy="0" r="20"></circle>
                                <circle class="marker-halo" cx="0" cy="0" r="16"></circle>
                                <circle class="marker-core" cx="0" cy="0" r="11.5"></circle>
                                <path class="marker-icon" d="M-3.8 -4.8 L4.6 0 L-3.8 4.8 Z"></path>
                            </g>
                            <g class="live-flow-marker end" transform="translate(195 526)">
                                <text class="marker-label" x="0" y="-29">END</text>
                                <circle class="marker-glow" cx="0" cy="0" r="20"></circle>
                                <circle class="marker-halo" cx="0" cy="0" r="16"></circle>
                                <circle class="marker-core" cx="0" cy="0" r="11.5"></circle>
                                <path class="marker-icon" d="M-4 0 L-0.5 3.5 L5 -3.2"></path>
                            </g>
                        </svg>
                        <div class="live-flow-steps">
                        <?php foreach ($liveFlowTrackerSteps as $stepLabel): ?>
                        <?php $flowStepLabel = (string)$stepLabel; $flowStepKey = strtolower($flowStepLabel); ?>
                        <article class="live-flow-step is-pending">
                            <div class="live-flow-step-head">
                                <span class="live-flow-step-dot" aria-hidden="true"></span>
                                <span class="live-flow-step-glyph" aria-hidden="true">
                                    <?php if (str_contains($flowStepKey, 'origin') || str_contains($flowStepKey, 'cenro') || str_contains($flowStepKey, 'pasu')): ?>
                                    <svg viewBox="0 0 24 24"><path d="M4 6h12l4 4v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"></path><path d="M16 6v4h4"></path><path d="M8 13h8"></path><path d="M8 17h5"></path></svg>
                                    <?php elseif (str_contains($flowStepKey, 'penro')): ?>
                                    <svg viewBox="0 0 24 24"><path d="M12 3v18"></path><path d="M6 8h12"></path><path d="M7 16h10"></path><circle cx="12" cy="12" r="9"></circle></svg>
                                    <?php elseif (
                                        str_contains($flowStepKey, 'pacdo')
                                        || str_contains($flowStepKey, 'records_unit')
                                        || str_contains($flowStepKey, 'records-unit')
                                    ): ?>
                                    <svg viewBox="0 0 24 24"><path d="M4 7h16"></path><path d="M4 12h16"></path><path d="M4 17h10"></path><circle cx="17" cy="17" r="3"></circle></svg>
                                    <?php elseif (str_contains($flowStepKey, 'ored') && str_contains($flowStepKey, 'sign')): ?>
                                    <svg viewBox="0 0 24 24"><path d="M4 20h16"></path><path d="M8 16l8-8"></path><path d="M15 7l2 2"></path><path d="M7 17l2 2"></path></svg>
                                    <?php elseif (str_contains($flowStepKey, 'ard')): ?>
                                    <svg viewBox="0 0 24 24"><path d="M4 19h16"></path><path d="M6 19V8l6-4 6 4v11"></path><path d="M9.5 13h5"></path></svg>
                                    <?php elseif (str_contains($flowStepKey, 'ored')): ?>
                                    <svg viewBox="0 0 24 24"><path d="M12 2l8 4v6c0 5-3 8-8 10-5-2-8-5-8-10V6z"></path><path d="M9 12l2 2 4-4"></path></svg>
                                    <?php elseif (str_contains($flowStepKey, 'division') || str_contains($flowStepKey, 'section')): ?>
                                    <svg viewBox="0 0 24 24"><rect x="3" y="4" width="7" height="7"></rect><rect x="14" y="4" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect><path d="M10 7h4"></path><path d="M7 11v3"></path><path d="M17 11v3"></path><path d="M10 17h4"></path></svg>
                                    <?php elseif (str_contains($flowStepKey, 'release')): ?>
                                    <svg viewBox="0 0 24 24"><path d="M4 12h11"></path><path d="M12 8l4 4-4 4"></path><path d="M4 6h6"></path><path d="M4 18h6"></path></svg>
                                    <?php else: ?>
                                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M8 12h8"></path><path d="M12 8v8"></path></svg>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <h3 class="live-flow-step-title"><?php echo e($flowStepLabel); ?></h3>
                            <span class="live-flow-spinner" aria-hidden="true"></span>
                            <span class="live-flow-step-state">Pending</span>
                        </article>
                        <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="live-flow-legend" aria-label="Flow legend">
                    <span><i class="is-completed" aria-hidden="true"></i>Completed</span>
                    <span><i class="is-active" aria-hidden="true"></i>In Progress</span>
                    <span><i class="is-pending" aria-hidden="true"></i>Pending</span>
                </div>
            </section>
            <?php endif; ?>

            <?php if ($showQueueTable): ?>
            <section class="card table-panel" aria-label="Queue table">
                <div class="table-header">
                    <h2><?php echo e($tableTitle); ?></h2>
                    <?php if ($enableBulkRowExport): ?>
                    <span id="bulkSelectedCount" class="table-selected-count">Selected: 0</span>
                    <?php endif; ?>
                    <a href="#" class="table-link">Export</a>
                </div>
                <?php if (!empty($pageActions)): ?>
                <p class="table-panel-intro">Actions: <?php echo e(implode(' | ', array_map(static fn($v): string => (string)$v, $pageActions))); ?></p>
                <?php endif; ?>
                <?php if ($showTableCardFilterControls || $showTableCardDateFilter): ?>
                <div class="table-panel-controls" aria-label="Queue controls">
                    <?php if ($showTableCardFilterControls): ?>
                    <form class="top-search sticky-search" method="get" action="" role="search" aria-label="Queue search">
                        <label class="sr-only" for="queueSearch">Search queue</label>
                        <input id="queueSearch" type="search" name="q" placeholder="<?php echo e($searchPlaceholder); ?>" title="Search by tracking ID, subject, or office. Results update as you type.">
                    </form>
                    <?php if ($showQueueFilterControls && $showStatusCategoryFilter): ?>
                    <div class="status-filter-wrap sticky-status-filter">
                        <label class="sr-only" for="statusFilterSelect">Filter queue by status category</label>
                        <select id="statusFilterSelect" class="status-filter-select" aria-label="Filter queue by status category" title="Filter the queue by workflow status category.">
                            <?php foreach ($statusFilterOptions as $statusFilterOption): ?>
                            <option value="<?php echo e((string)$statusFilterOption['value']); ?>"><?php echo e((string)$statusFilterOption['label']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($showTableCardDateFilter): ?>
                    <div class="date-filter-wrap">
                        <button id="dateFilterToggle" type="button" class="date-filter-icon-btn" aria-label="Open date range filter" aria-expanded="false" aria-controls="dateFilterDropdown" title="Filter queue by date range.">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                <path d="M16 2v4"></path>
                                <path d="M8 2v4"></path>
                                <path d="M3 10h18"></path>
                            </svg>
                        </button>
                        <form id="dateFilterDropdown" class="date-range-dropdown" aria-label="Date range filter">
                            <label class="date-field">
                                <span>From</span>
                                <input type="date" name="fromDate" value="<?php echo e((string)($activeDateFilterRange['from_input'] ?? '')); ?>" title="Show documents dated on or after this date.">
                            </label>
                            <label class="date-field">
                                <span>To</span>
                                <input type="date" name="toDate" value="<?php echo e((string)($activeDateFilterRange['to_input'] ?? '')); ?>" title="Show documents dated on or before this date.">
                            </label>
                            <button type="button" class="date-filter-btn" title="Apply selected date range filters.">Apply</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <div class="queue-live-meta">
                    <p id="queueLiveStatus" class="table-panel-intro queue-live-status">Live sync: waiting for updates.</p>
                    <span id="queueLiveChannelIndicator" class="queue-live-indicator is-pending" role="status" aria-live="polite">Sync Endpoint: Waiting</span>
                </div>
                <div id="documentTools" class="document-tools" hidden>
                    <p id="documentToolsMeta" class="document-tools-meta">
                        <?php if ($isIntakePage && $showQrStampDocumentTool): ?>
                        Use Create Intake or select a document row to view details, tracking slip, print package, or QR stamp.
                        <?php elseif ($isIntakePage): ?>
                        Use Create Intake or select a document row to view details, tracking slip, or print package.
                        <?php elseif ($showQrStampDocumentTool): ?>
                        Select a document row to view details, tracking slip, print package, or QR stamp.
                        <?php else: ?>
                        Select a document row to view details, tracking slip, or print package.
                        <?php endif; ?>
                    </p>
                    <div class="document-tools-actions">
                        <?php if ($isIntakePage): ?>
                        <button type="button" id="openCreateIntakeModalBtn" class="document-tools-btn" title="Open the create intake form for a new document.">Create Intake</button>
                        <?php endif; ?>
                        <button type="button" id="selectedDetailsBtn" class="document-tools-btn" disabled title="Open complete document details and attachment preview.">View Details</button>
                        <button type="button" id="selectedViewSlipBtn" class="document-tools-btn" disabled title="Open the tracking slip timeline for the selected document.">View Tracking Slip</button>
                        <button type="button" id="selectedPrintPackageBtn" class="document-tools-btn" disabled title="Print the package for the selected document.">Print Package</button>
                        <?php if ($showQrStampDocumentTool): ?>
                        <button type="button" id="selectedQrStampBtn" class="document-tools-btn" disabled title="Open QR stamp workspace for the selected tracking ID.">QR Stamp</button>
                        <?php endif; ?>
                        <?php if ($showQrReceiveScanner): ?>
                        <button type="button" id="openQrReceiveScannerBtn" class="document-tools-btn" title="Open the QR scanner tools and start a camera scan.">QR Scan</button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <?php if ($enableBulkRowExport): ?>
                                <th class="bulk-select-col" data-export-ignore="1">
                                    <input id="bulkSelectAllRows" type="checkbox" aria-label="Select all visible rows" title="Select or deselect all visible rows.">
                                </th>
                                <?php endif; ?>
                                <?php foreach ($tableColumns as $column): ?>
                                <th><?php echo e((string)$column); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <?php
                        $indicatorColumnIndex = -1;
                        $dateCreatedColumnIndex = -1;
                        foreach ($tableColumns as $tableColumnIndex => $tableColumnLabel) {
                            $normalizedTableColumn = strtolower(trim((string)$tableColumnLabel));
                            if ($indicatorColumnIndex < 0 && ($normalizedTableColumn === 'indicator' || str_contains($normalizedTableColumn, 'indicator'))) {
                                $indicatorColumnIndex = (int)$tableColumnIndex;
                            }
                            if (
                                $normalizedTableColumn !== ''
                                && (
                                    str_contains($normalizedTableColumn, 'date created')
                                    || str_contains($normalizedTableColumn, 'created date')
                                    || str_contains($normalizedTableColumn, 'created at')
                                    || (str_contains($normalizedTableColumn, 'created') && str_contains($normalizedTableColumn, 'date'))
                                )
                            ) {
                                $dateCreatedColumnIndex = (int)$tableColumnIndex;
                                break;
                            }
                        }
                        ?>
                        <tbody>
                            <?php if (empty($tableRows)): ?>
                            <tr>
                                <td class="table-empty-state" colspan="<?php echo e((string)max(count($tableColumns) + ($enableBulkRowExport ? 1 : 0), 1)); ?>">No documents found for this queue.</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($tableRows as $row): ?>
                                <?php
                                $cells = [];
                                $rowMeta = [];
                                if (is_array($row) && array_key_exists('value', $row) && is_array($row['value'])) {
                                    $cells = $row['value'];
                                    if (array_key_exists('meta', $row) && is_array($row['meta'])) {
                                        $rowMeta = $row['meta'];
                                    }
                                } elseif (is_array($row)) {
                                    $cells = $row;
                                } else {
                                    $cells = [(string)$row];
                                }

                                $rowDocumentId = isset($rowMeta['document_id']) ? (int)$rowMeta['document_id'] : 0;
                                $rowDocumentVersion = isset($rowMeta['row_version']) ? (int)$rowMeta['row_version'] : 0;
                                $rowTrackingId = trim((string)($rowMeta['tracking_id'] ?? ($cells[0] ?? '')));
                                $rowDateRaw = trim((string)($rowMeta['date_received_raw'] ?? ''));
                                $rowDateCreatedRaw = trim((string)($rowMeta['date_created_raw'] ?? ''));
                                $rowDeadlineRaw = trim((string)($rowMeta['deadline_at_raw'] ?? ''));
                                $rowDeadlineBucket = trim((string)($rowMeta['deadline_bucket'] ?? ''));
                                $rowStatusRaw = trim((string)($rowMeta['status_raw'] ?? ''));
                                $rowHasSectionReceive = (string)($rowMeta['has_section_receive'] ?? '0') === '1';
                                $rowHasSignedAction = (string)($rowMeta['has_signed_action'] ?? '0') === '1';
                                $rowHasReturnAction = (string)($rowMeta['has_return_action'] ?? '0') === '1';
                                $rowCreatedByUserId = isset($rowMeta['created_by_user_id']) ? (int)$rowMeta['created_by_user_id'] : 0;
                                $rowOriginOfficeId = isset($rowMeta['origin_office_id']) ? (int)$rowMeta['origin_office_id'] : 0;
                                $rowCurrentOfficeId = isset($rowMeta['current_office_id']) ? (int)$rowMeta['current_office_id'] : 0;
                                $rowPendingOfficeId = isset($rowMeta['pending_office_id']) ? (int)$rowMeta['pending_office_id'] : 0;
                                $rowCurrentOfficeLevel = strtoupper(trim((string)($rowMeta['current_office_level'] ?? '')));
                                $rowPendingOfficeLevel = strtoupper(trim((string)($rowMeta['pending_office_level'] ?? '')));
                                $rowCurrentHolder = trim((string)($rowMeta['current_holder'] ?? ''));
                                $documentAttr = $rowDocumentId > 0 ? ' data-document-id="' . e((string)$rowDocumentId) . '"' : '';
                                $documentVersionAttr = $rowDocumentVersion > 0 ? ' data-document-version="' . e((string)$rowDocumentVersion) . '"' : '';
                                $trackingAttr = $rowTrackingId !== '' ? ' data-tracking-id="' . e($rowTrackingId) . '"' : '';
                                $dateAttr = $rowDateRaw !== '' ? ' data-date-received="' . e($rowDateRaw) . '"' : '';
                                $dateCreatedAttr = $rowDateCreatedRaw !== '' ? ' data-date-created="' . e($rowDateCreatedRaw) . '"' : '';
                                $deadlineAttr = $rowDeadlineRaw !== '' ? ' data-deadline-at="' . e($rowDeadlineRaw) . '"' : '';
                                $deadlineBucketAttr = $rowDeadlineBucket !== '' ? ' data-deadline-bucket="' . e($rowDeadlineBucket) . '"' : '';
                                $statusAttr = $rowStatusRaw !== '' ? ' data-status="' . e($rowStatusRaw) . '"' : '';
                                $currentHolderAttr = $rowCurrentHolder !== '' ? ' data-current-holder="' . e($rowCurrentHolder) . '"' : '';
                                $sectionAttr = $rowHasSectionReceive ? ' data-has-section-receive="1"' : '';
                                $signedActionAttr = $rowHasSignedAction ? ' data-has-signed-action="1"' : '';
                                $returnActionAttr = $rowHasReturnAction ? ' data-has-return-action="1"' : '';
                                $createdByUserAttr = $rowCreatedByUserId > 0 ? ' data-created-by-user-id="' . e((string)$rowCreatedByUserId) . '"' : '';
                                $originOfficeAttr = $rowOriginOfficeId > 0 ? ' data-origin-office-id="' . e((string)$rowOriginOfficeId) . '"' : '';
                                $currentOfficeAttr = $rowCurrentOfficeId > 0 ? ' data-current-office-id="' . e((string)$rowCurrentOfficeId) . '"' : '';
                                $pendingOfficeAttr = $rowPendingOfficeId > 0 ? ' data-pending-office-id="' . e((string)$rowPendingOfficeId) . '"' : '';
                                $currentOfficeLevelAttr = $rowCurrentOfficeLevel !== '' ? ' data-current-office-level="' . e($rowCurrentOfficeLevel) . '"' : '';
                                $pendingOfficeLevelAttr = $rowPendingOfficeLevel !== '' ? ' data-pending-office-level="' . e($rowPendingOfficeLevel) . '"' : '';
                                $rowSelectionKey = '';
                                if ($rowDocumentId > 0) {
                                    $rowSelectionKey = 'doc:' . $rowDocumentId;
                                } elseif ($rowTrackingId !== '') {
                                    $rowSelectionKey = 'trk:' . strtoupper($rowTrackingId);
                                }
                                $rowSelectionKeyAttr = $rowSelectionKey !== '' ? ' data-row-key="' . e($rowSelectionKey) . '"' : '';
                                ?>
                                <tr<?php echo $documentAttr . $documentVersionAttr . $trackingAttr . $dateAttr . $dateCreatedAttr . $deadlineAttr . $deadlineBucketAttr . $statusAttr . $currentHolderAttr . $sectionAttr . $signedActionAttr . $returnActionAttr . $createdByUserAttr . $originOfficeAttr . $currentOfficeAttr . $pendingOfficeAttr . $currentOfficeLevelAttr . $pendingOfficeLevelAttr . $rowSelectionKeyAttr; ?>>
                                    <?php if ($enableBulkRowExport): ?>
                                    <td class="bulk-select-col" data-export-ignore="1">
                                        <input type="checkbox" class="bulk-row-select" aria-label="Select row for export">
                                    </td>
                                    <?php endif; ?>
                                    <?php foreach ($cells as $cellIndex => $cell): ?>
                                    <?php
                                    $cellText = (string)$cell;
                                    $isIndicatorCell = $indicatorColumnIndex >= 0
                                        && (int)$cellIndex === $indicatorColumnIndex;
                                    $isDateCreatedCell = $isIntakePage
                                        && $dateCreatedColumnIndex >= 0
                                        && (int)$cellIndex === $dateCreatedColumnIndex;
                                    $recencyLabel = '';
                                    $recencyClass = '';
                                    $indicatorClass = 'is-neutral';
                                    $indicatorLabel = 'N/A';
                                    $indicatorTitle = $cellText !== '' ? $cellText : 'Indicator';
                                    if ($isIndicatorCell) {
                                        $indicatorNormalized = strtolower($cellText);
                                        if (str_contains($indicatorNormalized, 'yellow') || str_contains($indicatorNormalized, 'urgent')) {
                                            $indicatorClass = 'is-yellow';
                                            $indicatorLabel = 'Urgent';
                                            $indicatorTitle = 'Yellow - Urgent';
                                        } elseif (str_contains($indicatorNormalized, 'pink') || str_contains($indicatorNormalized, 'simple')) {
                                            $indicatorClass = 'is-pink';
                                            $indicatorLabel = 'Simple';
                                            $indicatorTitle = 'Pink - Simple';
                                        } elseif (str_contains($indicatorNormalized, 'blue') || str_contains($indicatorNormalized, 'complex') || str_contains($indicatorNormalized, 'highly technical')) {
                                            $indicatorClass = 'is-blue';
                                            $indicatorLabel = 'Complex/HT';
                                            $indicatorTitle = 'Blue - Complex/Highly Technical';
                                        } elseif (str_contains($indicatorNormalized, 'green') || str_contains($indicatorNormalized, 'released')) {
                                            $indicatorClass = 'is-green';
                                            $indicatorLabel = 'Released';
                                            $indicatorTitle = 'Green - Released';
                                        }
                                    }
                                    if ($isDateCreatedCell) {
                                        $rawCreatedValue = $rowDateCreatedRaw !== '' ? $rowDateCreatedRaw : $cellText;
                                        $createdTimestamp = strtotime($rawCreatedValue);
                                        if ($createdTimestamp !== false) {
                                            $ageSeconds = time() - (int)$createdTimestamp;
                                            if ($ageSeconds < 0 || $ageSeconds <= 86400) {
                                                $recencyLabel = 'Recent';
                                                $recencyClass = 'is-recent';
                                            } else {
                                                $recencyLabel = 'Past';
                                                $recencyClass = 'is-past';
                                            }
                                        }
                                    }
                                    ?>
                                    <?php if ($isIndicatorCell): ?>
                                    <td class="queue-indicator-cell" data-indicator-label="<?php echo e($indicatorTitle); ?>">
                                        <span class="queue-indicator-badge <?php echo e($indicatorClass); ?>" title="<?php echo e($indicatorTitle); ?>" aria-label="<?php echo e($indicatorTitle); ?>"><?php echo e($indicatorLabel); ?></span>
                                    </td>
                                    <?php elseif ($isDateCreatedCell): ?>
                                    <td class="date-created-cell" data-date-created-label="<?php echo e($cellText); ?>">
                                        <span class="date-created-content">
                                            <span class="date-created-label"><?php echo e($cellText); ?></span>
                                            <?php if ($recencyLabel !== ''): ?>
                                            <span class="date-recency-indicator <?php echo e($recencyClass); ?>"><?php echo e($recencyLabel); ?></span>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <?php else: ?>
                                    <td><?php echo e($cellText); ?></td>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div id="queueTablePagination" class="table-pagination" hidden>
                    <p id="queueTablePaginationMeta" class="table-pagination-meta">Showing 0-0 of 0</p>
                    <div class="table-pagination-actions">
                        <button type="button" id="queueTablePrevBtn" class="table-pagination-btn" disabled>Back</button>
                        <button type="button" id="queueTableNextBtn" class="table-pagination-btn" disabled>Next</button>
                    </div>
                </div>
            </section>
            <?php endif; ?>

<?php endif; ?>

<?php include __DIR__ . '/_dashboard_footer.php'; ?>
        </main>
    </div>

    <?php if ($showWelcomeLoader): ?>
    <div id="welcomeLoader" class="welcome-loader" role="status" aria-live="polite" data-duration-ms="1000">
        <div class="welcome-loader-card">
            <div class="welcome-loader-spinner" aria-hidden="true"></div>
            <h2><?php echo e($welcomeLoaderMessage); ?></h2>
            <p>Preparing your dashboard...</p>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($showQrReceiveScanner): ?>
    <div id="qrReceiveModal" class="action-modal" hidden>
        <button type="button" class="action-modal-backdrop" data-qr-receive-close="true" aria-label="Close QR receive dialog"></button>
        <div class="action-modal-dialog details-modal-dialog qr-receive-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="qrReceiveModalTitle">
            <div id="qrReceivePanel" class="qr-receive-panel qr-receive-panel-modal" aria-label="QR quick receive">
                <div class="qr-receive-head qr-receive-head-row">
                    <div>
                        <h3 id="qrReceiveModalTitle">Fast Receive (QR)</h3>
                        <p>Scan the tracking slip QR code or paste a tracking ID to receive immediately.</p>
                    </div>
                    <button type="button" class="qr-receive-close-btn" data-qr-receive-close="true" aria-label="Close QR receive dialog">Close</button>
                </div>
                <div class="qr-receive-row">
                    <input type="text" id="qrReceiveTrackingInput" class="qr-receive-input" placeholder="Tracking ID or tracking-slip URL" title="Paste a tracking ID or tracking-slip URL to receive quickly.">
                    <button type="button" id="qrReceiveManualBtn" class="qr-receive-btn" title="Receive the document using the entered tracking ID.">Receive</button>
                    <button type="button" id="qrReceiveStartBtn" class="qr-receive-btn secondary" title="Start camera scan for QR code receiving.">Start QR Scan</button>
                    <button type="button" id="qrReceiveStopBtn" class="qr-receive-btn danger" disabled title="Stop QR camera scanning.">Stop</button>
                </div>
                <div id="qrReceiveViewport" class="qr-receive-viewport" hidden>
                    <video id="qrReceiveVideo" class="qr-receive-video" playsinline muted></video>
                    <div class="qr-receive-overlay" aria-hidden="true">
                        <div class="qr-mask top"></div>
                        <div class="qr-mask bottom"></div>
                        <div class="qr-mask left"></div>
                        <div class="qr-mask right"></div>
                        <div class="qr-focus-frame">
                            <span class="qr-corner tl"></span>
                            <span class="qr-corner tr"></span>
                            <span class="qr-corner bl"></span>
                            <span class="qr-corner br"></span>
                        </div>
                        <p class="qr-focus-hint">Align QR code within frame to scan</p>
                    </div>
                </div>
                <p id="qrReceiveStatus" class="qr-receive-status">Ready. Use camera scan or paste tracking ID.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div id="routeActionModal" class="action-modal" hidden>
        <button type="button" class="action-modal-backdrop" data-action-modal-close="true" aria-label="Close route dialog"></button>
        <div class="action-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="routeActionTitle">
            <form id="routeActionForm" class="action-modal-form" enctype="multipart/form-data">
                <h3 id="routeActionTitle" class="action-modal-title">Route Document</h3>
                <p id="routeActionMeta" class="action-modal-meta"></p>
                <div id="routeReleaseModeWrap" hidden>
                    <label class="action-modal-label" for="routeReleaseMode">Release Mode</label>
                    <p id="routeReleaseModeMeta" class="action-modal-meta">Choose whether to complete the document here or send the released document to another office.</p>
                    <select id="routeReleaseMode" class="action-modal-input">
                        <option value="complete_local">Complete in this office</option>
                        <option value="send_to_office">Release and send to office</option>
                    </select>
                </div>
                <div id="routeDestinationWrap">
                    <label class="action-modal-label" for="routeDestinationOffice">Destination Office</label>
                    <p id="routeDestinationMeta" class="action-modal-meta">Required for routing actions. Remarks are optional.</p>
                    <div id="routeDestinationTypeFilterWrap" class="route-destination-filter" hidden>
                        <p class="action-modal-meta">Choose path: <strong>ARD first</strong> is recommended. Direct Division or Section is a bypass and requires reason.</p>
                        <div class="route-destination-filter-actions" role="group" aria-label="Destination type filter">
                            <button type="button" class="route-destination-filter-btn is-active" data-route-destination-filter="ard">ARD (Recommended)</button>
                            <button type="button" class="route-destination-filter-btn" data-route-destination-filter="division">Division (Bypass)</button>
                            <button type="button" class="route-destination-filter-btn" data-route-destination-filter="section">Section (Bypass)</button>
                        </div>
                    </div>
                    <select id="routeDestinationOffice" class="action-modal-input" required>
                        <option value="">Select destination office</option>
                    </select>
                </div>
                <div id="routeBypassReasonWrap" hidden>
                    <label class="action-modal-label" for="routeBypassReason">Bypass Reason</label>
                    <p class="action-modal-meta">Required only when this routing path needs a direct-bypass justification.</p>
                    <textarea id="routeBypassReason" class="action-modal-input" rows="3" maxlength="1000" placeholder="Required when this routing path uses a direct bypass"></textarea>
                </div>
                <label class="action-modal-label" for="routeRemarks">Remarks</label>
                <p class="action-modal-meta">This text appears in the tracking slip under <strong>Action Required / Taken</strong> after the receiving office clicks Receive.</p>
                <textarea id="routeRemarks" class="action-modal-input" rows="4" maxlength="1000" placeholder="Optional remarks for activity logs"></textarea>
                <div id="routeNewSubjectWrap" hidden>
                    <label class="action-modal-label" for="routeNewSubject">Response Subject</label>
                    <p class="action-modal-meta">Optional. Use only when you need to add a response while routing.</p>
                    <input id="routeNewSubject" class="action-modal-input" type="text" maxlength="255" placeholder="Leave blank if there is no response">
                </div>
                <div id="routeAttachmentWrap" hidden>
                    <label id="routeAttachmentLabel" class="action-modal-label" for="routeAttachmentInput">Prepared Response (Attachment)</label>
                    <p id="routeAttachmentMeta" class="action-modal-meta">Attach prepared response file(s) when this routing path requires them.</p>
                    <input id="routeAttachmentInput" class="action-modal-input" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp,.txt" multiple>
                    <p id="routeExistingAttachmentsMeta" class="action-modal-meta" hidden></p>
                    <ul id="routeExistingAttachmentsList" class="route-existing-attachments" hidden></ul>
                </div>
                <div class="action-modal-actions">
                    <button type="button" class="action-modal-btn action-modal-btn-secondary" data-action-modal-close="true">Cancel</button>
                    <button type="submit" id="routeActionSubmit" class="action-modal-btn action-modal-btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editIntakeModal" class="action-modal" hidden>
        <button type="button" class="action-modal-backdrop" data-edit-intake-close="true" aria-label="Close edit intake dialog"></button>
        <div class="action-modal-dialog details-modal-dialog edit-intake-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="editIntakeTitle">
            <form id="editIntakeForm" class="action-modal-form details-modal-form" enctype="multipart/form-data">
                <h3 id="editIntakeTitle" class="action-modal-title">Edit Intake</h3>
                <p id="editIntakeMeta" class="action-modal-meta">Update source, subject, document type, complexity, days, attachments, and remarks.</p>
                <input type="hidden" id="editIntakeDocumentId" value="">
                <input type="hidden" id="editIntakeTrackingId" value="">

                <label class="action-modal-label" for="editIntakeSourceType">Source</label>
                <select id="editIntakeSourceType" class="action-modal-input" required>
                    <option value="INTERNAL">Current Office</option>
                    <option value="EXTERNAL">Client</option>
                </select>

                <label class="action-modal-label" for="editIntakeRoutingOffice">Routing Office</label>
                <input id="editIntakeRoutingOffice" class="action-modal-input" type="text" readonly>

                <label class="action-modal-label" for="editIntakeOriginatingEntity">Originating Office / Entity</label>
                <input id="editIntakeOriginatingEntity" class="action-modal-input" type="text" maxlength="255" placeholder="Enter originating office or entity" required>

                <label id="editIntakeExternalWrap" class="action-modal-label" for="editIntakeExternalClient">Sender Name</label>
                <input id="editIntakeExternalClient" class="action-modal-input" type="text" maxlength="255" placeholder="Enter sender name" required>

                <label id="editIntakeClientAddressWrap" class="action-modal-label" for="editIntakeClientAddress">Address</label>
                <input id="editIntakeClientAddress" class="action-modal-input" type="text" maxlength="255" placeholder="Enter address">

                <label class="action-modal-label" for="editIntakeSubject">Subject / Title</label>
                <textarea id="editIntakeSubject" class="action-modal-input" rows="3" maxlength="255" placeholder="Enter subject" required></textarea>

                <div class="edit-intake-fields-grid">
                    <div class="edit-intake-field-block">
                        <label class="action-modal-label" for="editIntakeDocumentType">Document Type</label>
                        <select id="editIntakeDocumentType" class="action-modal-input" required></select>
                        <small class="action-modal-meta">Select an active document type. Custom type requests are managed in Document Type Requests.</small>
                    </div>
                    <div class="edit-intake-field-block">
                        <label class="action-modal-label" for="editIntakeComplexityType">Type of Complexity</label>
                        <select id="editIntakeComplexityType" class="action-modal-input" required>
                            <option value="Simple">Simple</option>
                            <option value="Complex">Complex</option>
                            <option value="Highly Technical">Highly Technical</option>
                            <option value="Others">Others</option>
                        </select>
                    </div>
                    <div class="edit-intake-field-block edit-intake-field-wide">
                        <label class="action-modal-label" for="editIntakeComplexityDays">Days (Custom)</label>
                        <input id="editIntakeComplexityDays" class="action-modal-input" type="number" min="1" max="365" value="3" required>
                        <small class="action-modal-meta">Defaults: Simple=3, Complex=7, Highly Technical=20, Others=custom. You can set your own days.</small>
                    </div>
                </div>

                <label class="action-modal-label">Current Attachments</label>
                <div id="editIntakeCurrentAttachments" class="details-attachments">
                    <p id="editIntakeCurrentAttachmentsEmpty" class="details-preview-empty">No attachments uploaded.</p>
                    <ul id="editIntakeCurrentAttachmentsList" class="edit-intake-attachments-list" hidden></ul>
                </div>

                <label class="action-modal-label" for="editIntakeAttachments">Add New Attachments</label>
                <input id="editIntakeAttachments" class="action-modal-input" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp,.txt" multiple>
                <p class="action-modal-meta">Optional. New files are added as next versions and do not remove existing attachments.</p>

                <label class="action-modal-label" for="editIntakeRemarks">Remarks</label>
                <textarea id="editIntakeRemarks" class="action-modal-input" rows="3" maxlength="1000" placeholder="Optional remarks for this edit"></textarea>

                <div class="action-modal-actions">
                    <button type="button" class="action-modal-btn action-modal-btn-secondary" data-edit-intake-close="true">Cancel</button>
                    <button type="submit" id="editIntakeSubmit" class="action-modal-btn action-modal-btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div id="documentDetailsModal" class="action-modal" hidden>
        <button type="button" class="action-modal-backdrop" data-document-details-close="true" aria-label="Close details dialog"></button>
        <div class="action-modal-dialog details-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="documentDetailsTitle">
            <div class="action-modal-form details-modal-form">
                <h3 id="documentDetailsTitle" class="action-modal-title">Document Details</h3>
                <p id="documentDetailsMeta" class="action-modal-meta">Select a document row to inspect full details and attachments.</p>
                <p id="documentDetailsLoading" class="details-loading">Loading document details...</p>
                <div id="documentDetailsContent" class="details-modal-content" hidden>
                    <div id="documentDetailsFields" class="details-grid"></div>
                    <section class="details-attachments">
                        <div class="details-attachments-head">
                            <h4>Attachment Preview</h4>
                            <a id="documentDetailsOpenAttachment" class="details-open-file" href="#" target="_blank" rel="noopener noreferrer" hidden>Open File</a>
                        </div>
                        <div id="documentDetailsAttachmentFilterWrap" class="details-attachment-filter" hidden>
                            <div class="details-attachment-filter-actions" role="group" aria-label="Attachment category filter">
                                <button type="button" class="details-attachment-filter-btn is-active" data-details-attachment-filter="original">Original Attachment</button>
                                <button type="button" class="details-attachment-filter-btn" data-details-attachment-filter="endorsement">Endorsement Letter</button>
                                <button type="button" class="details-attachment-filter-btn" data-details-attachment-filter="response">Response</button>
                            </div>
                            <div id="documentDetailsAttachmentSourceFilterWrap" class="details-attachment-source-filter" hidden>
                                <label class="sr-only" for="documentDetailsAttachmentSourceFilter">Filter attachments by workflow source</label>
                                <select id="documentDetailsAttachmentSourceFilter" class="details-attachment-source-select" aria-label="Filter attachments by workflow source">
                                    <option value="all">All sources</option>
                                </select>
                            </div>
                        </div>
                        <select id="documentDetailsAttachmentSelect" class="details-attachment-select" aria-label="Select attachment">
                            <option value="">No attachment selected</option>
                        </select>
                        <div id="documentDetailsPreview" class="details-preview">
                            <p id="documentDetailsPreviewEmpty" class="details-preview-empty">Select a file to preview.</p>
                            <iframe id="documentDetailsPreviewFrame" title="Attachment PDF Preview" hidden></iframe>
                            <img id="documentDetailsPreviewImage" alt="Attachment Preview" hidden>
                        </div>
                    </section>
                </div>
                <div class="action-modal-actions">
                    <button type="button" class="action-modal-btn action-modal-btn-secondary" data-document-details-close="true">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div id="appDialogModal" class="action-modal" hidden>
        <button type="button" class="action-modal-backdrop" data-app-dialog-close="true" aria-label="Close dialog"></button>
        <div class="action-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="appDialogTitle">
            <div class="action-modal-form app-dialog-body">
                <h3 id="appDialogTitle" class="action-modal-title">Notice</h3>
                <p id="appDialogMessage" class="action-modal-meta"></p>
                <div class="action-modal-actions app-dialog-actions">
                    <button type="button" id="appDialogCancel" class="action-modal-btn action-modal-btn-secondary" data-app-dialog-close="true" hidden>Cancel</button>
                    <button type="button" id="appDialogConfirm" class="action-modal-btn action-modal-btn-primary">OK</button>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/_support_modals.php'; ?>
    <script src="<?php echo e(app_url('assets/js/support-modals.js')); ?>"></script>
    <?php include __DIR__ . '/_role_scripts.php'; ?>
</body>
</html>
 
