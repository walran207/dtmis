<?php
$recordsStampRoleName = (string)($_SESSION['role_name'] ?? 'RECORDS_UNIT');
$recordsStampRoleKey = app_normalize_role_key($recordsStampRoleName);
$recordsStampWorkspaceLabel = match ($recordsStampRoleKey) {
    'CENRO_ADMIN_RECORD' => 'CENRO Admin Record',
    'PENRO_ADMIN_RECORD' => 'PENRO Admin Record',
    'PAMO_ADMIN' => 'PAMO Admin',
    default => 'Records Unit',
};
$recordsStampTrackingId = trim((string)($_GET['tracking_id'] ?? ''));
$recordsStampType = strtolower(trim((string)($_GET['stamp_type'] ?? 'received')));
if (!in_array($recordsStampType, ['received', 'released'], true)) {
    $recordsStampType = 'received';
}
$recordsStampTheme = strtolower(trim((string)($_GET['theme'] ?? '')));
if (!in_array($recordsStampTheme, ['light', 'dark'], true)) {
    $recordsStampTheme = '';
}

$recordsStampFrameQuery = [
    'embedded' => '1',
    'stamp_type' => $recordsStampType,
];
if ($recordsStampTrackingId !== '') {
    $recordsStampFrameQuery['tracking_id'] = $recordsStampTrackingId;
}
if ($recordsStampTheme !== '') {
    $recordsStampFrameQuery['theme'] = $recordsStampTheme;
}
$recordsStampFrameUrl = app_url('softcopy-records-unit-stamp.php') . '?' . http_build_query($recordsStampFrameQuery);
?>
<section class="card records-stamp-card" aria-label="<?php echo e($recordsStampWorkspaceLabel); ?> stamp workspace">
    <div class="records-stamp-head">
        <h2><?php echo e($recordsStampWorkspaceLabel); ?> Stamp Workspace</h2>
        <p>Open dynamic RECEIVED/RELEASED stamp layout, edit date/time/signature, drag/resize, then print.</p>
    </div>

    <form method="get" action="" class="records-stamp-toolbar" aria-label="Stamp options">
        <label class="records-stamp-field records-stamp-field-small">
            <span>Stamp Type</span>
            <select name="stamp_type">
                <option value="received" <?php echo $recordsStampType === 'received' ? 'selected' : ''; ?>>RECEIVED</option>
                <option value="released" <?php echo $recordsStampType === 'released' ? 'selected' : ''; ?>>RELEASED</option>
            </select>
        </label>
        <label class="records-stamp-field">
            <span>Tracking ID (optional)</span>
            <input type="text" name="tracking_id" value="<?php echo e($recordsStampTrackingId); ?>" placeholder="Example: DENR-XII-2026-0001">
        </label>
        <div class="records-stamp-actions">
            <button type="submit" class="records-stamp-btn">Load Stamp</button>
            <a href="" class="records-stamp-btn ghost">Clear</a>
        </div>
    </form>

    <div class="records-stamp-frame-wrap">
        <iframe
            class="records-stamp-frame"
            src="<?php echo e($recordsStampFrameUrl); ?>"
            data-base-src="<?php echo e($recordsStampFrameUrl); ?>"
            title="<?php echo e($recordsStampWorkspaceLabel); ?> stamp workspace"
            loading="lazy"
            referrerpolicy="same-origin"
        ></iframe>
    </div>
</section>

<style>
    .records-stamp-card {
        display: grid;
        gap: 14px;
        padding: 16px;
        background: var(--surface);
        border-color: var(--line);
        color: var(--text);
    }

    .records-stamp-head {
        padding: 12px 14px 8px;
    }

    .records-stamp-head h2 {
        margin: 0;
        font-size: 1.12rem;
        line-height: 1.3;
    }

    .records-stamp-head p {
        margin: 8px 0 0;
        color: var(--muted);
        font-size: 0.92rem;
        line-height: 1.45;
    }

    .records-stamp-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: flex-end;
    }

    .records-stamp-field {
        display: grid;
        gap: 6px;
        min-width: 280px;
        flex: 1 1 320px;
    }

    .records-stamp-field.records-stamp-field-small {
        min-width: 180px;
        max-width: 220px;
        flex: 0 1 220px;
    }

    .records-stamp-field span {
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--muted);
    }

    .records-stamp-field input,
    .records-stamp-field select {
        width: 100%;
        border: 1px solid var(--line);
        border-radius: 10px;
        height: 40px;
        padding: 0 12px;
        font-size: 0.9rem;
        background: var(--surface);
        color: var(--text);
    }

    .records-stamp-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .records-stamp-btn {
        border: 1px solid #1f6ecb;
        background: #1f6ecb;
        color: #fff;
        border-radius: 10px;
        padding: 10px 14px;
        text-decoration: none;
        font-weight: 700;
        font-size: 0.84rem;
        cursor: pointer;
    }

    .records-stamp-btn.ghost {
        background: #f4f8ff;
        color: #24598f;
        border-color: #bfd1e8;
        background: color-mix(in srgb, var(--secondary) 12%, var(--surface));
        color: color-mix(in srgb, var(--secondary) 65%, var(--text));
        border-color: color-mix(in srgb, var(--secondary) 26%, var(--line));
    }

    .records-stamp-frame-wrap {
        border: 1px solid var(--line);
        border-radius: 12px;
        overflow: hidden;
        background: #f7fbff;
        background: color-mix(in srgb, var(--surface) 82%, var(--bg));
    }

    .records-stamp-frame {
        width: 100%;
        min-height: 86vh;
        border: 0;
        display: block;
        background: #f7fbff;
        background: color-mix(in srgb, var(--surface) 82%, var(--bg));
    }

    @media (max-width: 900px) {
        .records-stamp-frame {
            min-height: 75vh;
        }
    }

    body[data-theme="dark"] .records-stamp-card,
    html[data-theme="dark"] body .records-stamp-card {
        background: #162232;
        border-color: #2e4159;
        color: #e6eef8;
    }

    body[data-theme="dark"] .records-stamp-head p,
    html[data-theme="dark"] body .records-stamp-head p,
    body[data-theme="dark"] .records-stamp-field span,
    html[data-theme="dark"] body .records-stamp-field span {
        color: #a4b5c8;
    }

    body[data-theme="dark"] .records-stamp-field input,
    html[data-theme="dark"] body .records-stamp-field input,
    body[data-theme="dark"] .records-stamp-field select,
    html[data-theme="dark"] body .records-stamp-field select {
        background: #132235;
        border-color: #3b5570;
        color: #e6eef8;
    }

    body[data-theme="dark"] .records-stamp-frame-wrap,
    html[data-theme="dark"] body .records-stamp-frame-wrap,
    body[data-theme="dark"] .records-stamp-frame,
    html[data-theme="dark"] body .records-stamp-frame {
        background: #0f1b2a;
        border-color: #334b66;
    }

    body[data-theme="dark"] .records-stamp-btn.ghost,
    html[data-theme="dark"] body .records-stamp-btn.ghost {
        background: #20344a;
        color: #cfe2f6;
        border-color: #41607f;
    }
</style>

<script>
    (function () {
        const frame = document.querySelector('.records-stamp-frame');
        if (!frame) {
            return;
        }

        const baseSrc = frame.getAttribute('data-base-src') || frame.getAttribute('src') || '';
        if (!baseSrc) {
            return;
        }

        const resolveTheme = function () {
            const htmlTheme = String(document.documentElement.getAttribute('data-theme') || '').toLowerCase();
            const bodyTheme = String(document.body.getAttribute('data-theme') || '').toLowerCase();
            const theme = htmlTheme || bodyTheme;
            return theme === 'dark' ? 'dark' : 'light';
        };

        const applyThemeToFrame = function () {
            try {
                const url = new URL(baseSrc, window.location.origin);
                url.searchParams.set('theme', resolveTheme());
                const nextSrc = url.toString();
                if (frame.getAttribute('src') !== nextSrc) {
                    frame.setAttribute('src', nextSrc);
                }
            } catch (error) {
                // Ignore URL parsing failures to keep panel usable.
            }
        };

        applyThemeToFrame();
        document.addEventListener('DTMIS:theme-changed', applyThemeToFrame);
    })();
</script>
