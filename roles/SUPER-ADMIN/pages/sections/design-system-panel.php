<?php
$paletteGroups = [
    'brand' => [
        'label' => 'Brand Colors',
        'tokens' => [
            'primary' => 'Primary',
            'secondary' => 'Secondary',
            'tertiary' => 'Tertiary',
            'alternate' => 'Alternate',
        ],
    ],
    'utility' => [
        'label' => 'Utility Colors',
        'tokens' => [
            'primaryText' => 'Primary Text',
            'secondaryText' => 'Secondary Text',
            'primaryBg' => 'Primary Background',
            'secondaryBg' => 'Secondary Background',
        ],
    ],
    'accents' => [
        'label' => 'Accent Colors',
        'tokens' => [
            'accent1' => 'Accent 1',
            'accent2' => 'Accent 2',
            'accent3' => 'Accent 3',
            'accent4' => 'Accent 4',
        ],
    ],
    'semantic' => [
        'label' => 'Semantic Colors',
        'tokens' => [
            'success' => 'Success',
            'error' => 'Error',
            'warning' => 'Warning',
            'info' => 'Info',
        ],
    ],
];

$themeBlocks = [
    'light' => 'Light Mode Theme',
    'dark' => 'Dark Mode Theme',
];

$roleAccentRoles = [
    'CENRO_ADMIN_RECORD' => 'CENRO Admin Record',
    'PASU_OFFICER' => 'PASU',
    'PENRO_ADMIN_RECORD' => 'PENRO Admin Record',
    'RECORDS_UNIT' => 'RECORDS-UNIT',
    'ORED' => 'ORED',
    'DIVISION_CHIEF' => 'Division Chief',
    'SECTION_STAFF' => 'Section Staff',
    'ARD_TS' => 'ARD-TS',
    'ARD_MS' => 'ARD-MS',
    'SUPER_ADMIN' => 'Super Admin',
];
?>
<section class="super-admin-grid" aria-label="Design system customizer">
    <article class="card super-admin-panel super-admin-panel-full super-admin-design-workspace">
        <div class="super-admin-panel-head">
            <h2>System Design Tokens</h2>
            <p>Manage responsive breakpoints and global light/dark theme colors with live preview.</p>
        </div>

        <section class="super-admin-design-block super-admin-breakpoints-block" aria-label="Responsive breakpoints controls">
            <div class="super-admin-design-block-head">
                <h3>Breakpoints</h3>
                <p>Each next breakpoint must be greater than the previous one.</p>
            </div>
            <div class="super-admin-breakpoint-track">
                <label class="super-admin-breakpoint-node" for="superAdminBpMobileRange">
                    <span class="super-admin-breakpoint-icon" aria-hidden="true">M</span>
                    <span class="super-admin-breakpoint-label">Mobile</span>
                    <input id="superAdminBpMobileRange" type="range" min="320" max="900" step="1">
                    <input id="superAdminBpMobileValue" type="number" min="320" max="900" step="1">
                </label>

                <label class="super-admin-breakpoint-node" for="superAdminBpTabletRange">
                    <span class="super-admin-breakpoint-icon" aria-hidden="true">T</span>
                    <span class="super-admin-breakpoint-label">Tablet</span>
                    <input id="superAdminBpTabletRange" type="range" min="321" max="1200" step="1">
                    <input id="superAdminBpTabletValue" type="number" min="321" max="1200" step="1">
                </label>

                <label class="super-admin-breakpoint-node" for="superAdminBpLandscapeRange">
                    <span class="super-admin-breakpoint-icon" aria-hidden="true">TL</span>
                    <span class="super-admin-breakpoint-label">Tablet (Landscape)</span>
                    <input id="superAdminBpLandscapeRange" type="range" min="322" max="1800" step="1">
                    <input id="superAdminBpLandscapeValue" type="number" min="322" max="1800" step="1">
                </label>

                <div class="super-admin-breakpoint-node is-static" aria-label="Desktop breakpoint">
                    <span class="super-admin-breakpoint-icon" aria-hidden="true">D</span>
                    <span class="super-admin-breakpoint-label">Desktop</span>
                    <span class="super-admin-breakpoint-static">Infinity</span>
                </div>
            </div>
            <p id="superAdminBreakpointSummary" class="super-admin-breakpoint-summary">Mobile <= 479px, Tablet <= 767px, Landscape <= 991px, Desktop > 991px</p>
        </section>

        <section class="super-admin-design-block" aria-label="Layout controls">
            <div class="super-admin-design-block-head">
                <h3>Layout Controls</h3>
                <p>Control global theme mode, density, and workspace dimensions.</p>
            </div>
            <div class="super-admin-layout-grid">
                <label class="super-admin-field">
                    <span>Theme Mode</span>
                    <select id="superAdminThemeMode">
                        <option value="system">System</option>
                        <option value="light">Light</option>
                        <option value="dark">Dark</option>
                    </select>
                </label>

                <label class="super-admin-field">
                    <span>Density</span>
                    <select id="superAdminDensityMode">
                        <option value="comfortable">Comfortable</option>
                        <option value="compact">Compact</option>
                    </select>
                </label>

                <label class="super-admin-field">
                    <span>Card Radius <strong id="superAdminRadiusValue">16px</strong></span>
                    <input type="range" id="superAdminRadius" min="6" max="30" step="1" value="16">
                </label>

                <label class="super-admin-field">
                    <span>Sidebar Width <strong id="superAdminSidebarValue">280px</strong></span>
                    <input type="range" id="superAdminSidebarWidth" min="220" max="420" step="2" value="280">
                </label>

                <label class="super-admin-field">
                    <span>Content Max Width <strong id="superAdminContentValue">1280px</strong></span>
                    <input type="range" id="superAdminContentWidth" min="960" max="2200" step="10" value="1280">
                </label>
            </div>
        </section>

        <section class="super-admin-design-block" aria-label="Role sidebar accent controls">
            <div class="super-admin-design-block-head">
                <h3>Role Sidebar Accents</h3>
                <p>Set separate role accent colors for light mode and dark mode. Changes here save instantly.</p>
            </div>
            <?php foreach ($themeBlocks as $themeKey => $themeLabel): ?>
            <div class="super-admin-token-group">
                <h4><?php echo htmlspecialchars($themeKey === 'dark' ? 'Dark Mode Role Accents' : 'Light Mode Role Accents', ENT_QUOTES, 'UTF-8'); ?></h4>
                <div class="super-admin-token-grid super-admin-role-accent-grid">
                    <?php foreach ($roleAccentRoles as $roleKey => $roleLabel): ?>
                        <?php $roleAccentPath = $themeKey . '.' . $roleKey; ?>
                        <label class="super-admin-token-card" for="roleAccent-<?php echo htmlspecialchars($roleAccentPath, ENT_QUOTES, 'UTF-8'); ?>">
                            <input
                                class="super-admin-token-swatch super-admin-token-picker"
                                type="color"
                                data-role-accent-swatch="<?php echo htmlspecialchars($roleAccentPath, ENT_QUOTES, 'UTF-8'); ?>"
                                data-role-accent-picker="<?php echo htmlspecialchars($roleAccentPath, ENT_QUOTES, 'UTF-8'); ?>"
                                aria-label="<?php echo htmlspecialchars((string)$themeLabel . ' ' . (string)$roleLabel . ' accent color picker', ENT_QUOTES, 'UTF-8'); ?>"
                            >
                            <span class="super-admin-token-name"><?php echo htmlspecialchars((string)$roleLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                            <input
                                id="roleAccent-<?php echo htmlspecialchars($roleAccentPath, ENT_QUOTES, 'UTF-8'); ?>"
                                class="super-admin-token-input"
                                type="text"
                                inputmode="text"
                                spellcheck="false"
                                autocomplete="off"
                                data-role-accent="<?php echo htmlspecialchars($roleAccentPath, ENT_QUOTES, 'UTF-8'); ?>"
                                placeholder="#000000"
                            >
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </section>

        <?php foreach ($themeBlocks as $themeKey => $themeLabel): ?>
        <section class="super-admin-design-block super-admin-theme-block" data-theme-block="<?php echo htmlspecialchars($themeKey, ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars($themeLabel, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="super-admin-design-block-head">
                <h3><?php echo htmlspecialchars($themeLabel, ENT_QUOTES, 'UTF-8'); ?></h3>
                <p>Update palette tokens for <?php echo $themeKey === 'dark' ? 'dark mode' : 'light mode'; ?>.</p>
            </div>

            <?php foreach ($paletteGroups as $groupKey => $group): ?>
            <div class="super-admin-token-group">
                <h4><?php echo htmlspecialchars((string)$group['label'], ENT_QUOTES, 'UTF-8'); ?></h4>
                <div class="super-admin-token-grid">
                    <?php foreach ($group['tokens'] as $tokenKey => $tokenLabel): ?>
                        <?php $path = $themeKey . '.' . $groupKey . '.' . $tokenKey; ?>
                        <label class="super-admin-token-card" for="token-<?php echo htmlspecialchars($path, ENT_QUOTES, 'UTF-8'); ?>">
                            <input
                                class="super-admin-token-swatch super-admin-token-picker"
                                type="color"
                                data-token-swatch="<?php echo htmlspecialchars($path, ENT_QUOTES, 'UTF-8'); ?>"
                                data-design-picker="<?php echo htmlspecialchars($path, ENT_QUOTES, 'UTF-8'); ?>"
                                aria-label="<?php echo htmlspecialchars((string)$tokenLabel . ' color picker', ENT_QUOTES, 'UTF-8'); ?>"
                            >
                            <span class="super-admin-token-name"><?php echo htmlspecialchars((string)$tokenLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                            <input
                                id="token-<?php echo htmlspecialchars($path, ENT_QUOTES, 'UTF-8'); ?>"
                                class="super-admin-token-input"
                                type="text"
                                inputmode="text"
                                spellcheck="false"
                                autocomplete="off"
                                data-design-token="<?php echo htmlspecialchars($path, ENT_QUOTES, 'UTF-8'); ?>"
                                placeholder="#000000"
                            >
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </section>
        <?php endforeach; ?>

        <div class="super-admin-form-actions">
            <button type="button" class="super-admin-btn super-admin-btn-primary" id="superAdminApplyDesign">Apply Design System</button>
            <button type="button" class="super-admin-btn super-admin-btn-secondary" id="superAdminResetDesign">Reset</button>
        </div>

        <p id="superAdminDesignStatus" class="super-admin-status" role="status" aria-live="polite">Design settings ready.</p>
    </article>

    <article class="card super-admin-panel super-admin-panel-full">
        <div class="super-admin-panel-head">
            <h2>Responsive UI Preview</h2>
            <p>Preview viewport behavior using your active breakpoints and color tokens.</p>
        </div>

        <div class="super-admin-preview-controls" role="group" aria-label="Preview size controls">
            <button type="button" class="super-admin-btn super-admin-btn-secondary is-active" data-preview-size="mobile">Mobile</button>
            <button type="button" class="super-admin-btn super-admin-btn-secondary" data-preview-size="tablet">Tablet</button>
            <button type="button" class="super-admin-btn super-admin-btn-secondary" data-preview-size="desktop">Desktop</button>
        </div>

        <div id="superAdminPreviewFrame" class="super-admin-preview-frame" data-preview-size="mobile">
            <div class="super-admin-preview-shell">
                <header class="preview-header">
                    <h3>Live Theme Preview</h3>
                    <p>Current workspace styling for cards and controls.</p>
                </header>
                <section class="preview-grid">
                    <article class="preview-card">
                        <h4>Queue Summary</h4>
                        <p>Pending: <strong>24</strong></p>
                        <p>Overdue: <strong>5</strong></p>
                    </article>
                    <article class="preview-card">
                        <h4>User Activity</h4>
                        <p>Online Users: <strong>18</strong></p>
                        <p>Traffic (5m): <strong>37</strong></p>
                    </article>
                    <article class="preview-card">
                        <h4>Action Buttons</h4>
                        <div class="preview-actions">
                            <button type="button">Primary Action</button>
                            <button type="button" class="secondary">Secondary</button>
                        </div>
                    </article>
                </section>
            </div>
        </div>
    </article>
</section>

<script>
(function () {
    const designApi = window.DTMISDesignSystem;

    const fallbackDefaults = {
        appearance: { themeMode: 'system', densityMode: 'comfortable' },
        layout: { radius: 16, sidebarWidth: 280, contentWidth: 1280 },
        breakpoints: { mobile: 479, tablet: 767, tabletLandscape: 991 },
        roleAccents: {
            light: {
                CENRO_ADMIN_RECORD: '#2f8f83',
                PASU_OFFICER: '#3c8c78',
                PENRO_ADMIN_RECORD: '#3f7fb1',
                RECORDS_UNIT: '#9b7a4f',
                PACDO: '#9b7a4f',
                ORED: '#7a6ab3',
                DIVISION_CHIEF: '#8a6f66',
                SECTION_STAFF: '#5f8596',
                ARD_TS: '#4f7fb8',
                ARD_MS: '#4f7fb8',
                SUPER_ADMIN: '#2a8f84'
            },
            dark: {
                CENRO_ADMIN_RECORD: '#2f8f83',
                PASU_OFFICER: '#3c8c78',
                PENRO_ADMIN_RECORD: '#3f7fb1',
                RECORDS_UNIT: '#9b7a4f',
                PACDO: '#9b7a4f',
                ORED: '#7a6ab3',
                DIVISION_CHIEF: '#8a6f66',
                SECTION_STAFF: '#5f8596',
                ARD_TS: '#4f7fb8',
                ARD_MS: '#4f7fb8',
                SUPER_ADMIN: '#2a8f84'
            }
        },
        palettes: {
            light: {
                brand: { primary: '#3b63f2', secondary: '#2746b7', tertiary: '#e8eeff', alternate: '#c9d6ff' },
                utility: { primaryText: '#1a2340', secondaryText: '#4b5a86', primaryBg: '#f6f8ff', secondaryBg: '#ffffff' },
                accents: { accent1: '#3b63f233', accent2: '#60a5fa26', accent3: '#a78bfa22', accent4: '#f59e0b24' },
                semantic: { success: '#22c55e', error: '#ff5d73', warning: '#f59e0b', info: '#3ba7ff' }
            },
            dark: {
                brand: { primary: '#4f7cff', secondary: '#2d4ea2', tertiary: '#1a2745', alternate: '#2b3a63' },
                utility: { primaryText: '#f4f8ff', secondaryText: '#a9b7d5', primaryBg: '#0a1224', secondaryBg: '#101a31' },
                accents: { accent1: '#4f7cff33', accent2: '#22d3ee24', accent3: '#a78bfa22', accent4: '#f59e0b24' },
                semantic: { success: '#22c55e', error: '#ff5d73', warning: '#f59e0b', info: '#60a5fa' }
            }
        }
    };

    function clone(value) {
        return JSON.parse(JSON.stringify(value));
    }

    function isHexColor(value) {
        return /^#([\da-fA-F]{3}|[\da-fA-F]{4}|[\da-fA-F]{6}|[\da-fA-F]{8})$/.test(String(value || '').trim());
    }

    function sanitizeColor(value, fallback) {
        const raw = String(value || '').trim();
        return isHexColor(raw) ? raw.toLowerCase() : String(fallback || '#000000').toLowerCase();
    }

    function toSixDigitHex(value) {
        const raw = String(value || '').trim().toLowerCase();
        if (!isHexColor(raw)) {
            return '#000000';
        }
        if (raw.length === 4 || raw.length === 5) {
            const r = raw.charAt(1);
            const g = raw.charAt(2);
            const b = raw.charAt(3);
            return ('#' + r + r + g + g + b + b).toLowerCase();
        }
        if (raw.length === 9) {
            return raw.slice(0, 7);
        }
        if (raw.length === 7) {
            return raw;
        }
        return '#000000';
    }

    function getAlphaHex(value) {
        const raw = String(value || '').trim().toLowerCase();
        if (!isHexColor(raw)) {
            return '';
        }
        if (raw.length === 5) {
            const a = raw.charAt(4);
            return (a + a).toLowerCase();
        }
        if (raw.length === 9) {
            return raw.slice(7, 9);
        }
        return '';
    }

    function normalize(settings) {
        if (designApi && typeof designApi.normalize === 'function') {
            return designApi.normalize(settings);
        }

        const output = clone(fallbackDefaults);
        if (!settings || typeof settings !== 'object') {
            return output;
        }

        if (settings.appearance && typeof settings.appearance === 'object') {
            output.appearance.themeMode = String(settings.appearance.themeMode || output.appearance.themeMode);
            output.appearance.densityMode = String(settings.appearance.densityMode || output.appearance.densityMode);
        }

        if (settings.layout && typeof settings.layout === 'object') {
            output.layout.radius = Number(settings.layout.radius || output.layout.radius);
            output.layout.sidebarWidth = Number(settings.layout.sidebarWidth || output.layout.sidebarWidth);
            output.layout.contentWidth = Number(settings.layout.contentWidth || output.layout.contentWidth);
        }

        if (settings.breakpoints && typeof settings.breakpoints === 'object') {
            output.breakpoints.mobile = Number(settings.breakpoints.mobile || output.breakpoints.mobile);
            output.breakpoints.tablet = Number(settings.breakpoints.tablet || output.breakpoints.tablet);
            output.breakpoints.tabletLandscape = Number(settings.breakpoints.tabletLandscape || output.breakpoints.tabletLandscape);
        }

        if (settings.roleAccents && typeof settings.roleAccents === 'object') {
            ['light', 'dark'].forEach(function (themeKey) {
                const themeSource = settings.roleAccents[themeKey] && typeof settings.roleAccents[themeKey] === 'object'
                    ? settings.roleAccents[themeKey]
                    : settings.roleAccents;
                Object.keys(output.roleAccents[themeKey]).forEach(function (roleKey) {
                    output.roleAccents[themeKey][roleKey] = sanitizeColor(themeSource[roleKey], output.roleAccents[themeKey][roleKey]);
                });
            });
        }

        ['light', 'dark'].forEach(function (themeKey) {
            const themeSettings = settings.palettes && settings.palettes[themeKey] ? settings.palettes[themeKey] : null;
            if (!themeSettings) {
                return;
            }
            ['brand', 'utility', 'accents', 'semantic'].forEach(function (groupKey) {
                const groupSettings = themeSettings[groupKey] || {};
                Object.keys(output.palettes[themeKey][groupKey]).forEach(function (tokenKey) {
                    output.palettes[themeKey][groupKey][tokenKey] = sanitizeColor(groupSettings[tokenKey], output.palettes[themeKey][groupKey][tokenKey]);
                });
            });
        });

        return output;
    }

    function loadSettings() {
        if (designApi && typeof designApi.load === 'function') {
            return designApi.load();
        }
        try {
            const raw = localStorage.getItem('DTMIS_super_admin_design_tokens_v2') || localStorage.getItem('DTMIS_super_admin_design_tokens');
            if (!raw) {
                return clone(fallbackDefaults);
            }
            return normalize(JSON.parse(raw));
        } catch (error) {
            return clone(fallbackDefaults);
        }
    }

    function saveSettings(settings) {
        if (designApi && typeof designApi.save === 'function') {
            return designApi.save(settings);
        }
        const normalized = normalize(settings);
        try {
            const payload = JSON.stringify(normalized);
            localStorage.setItem('DTMIS_super_admin_design_tokens_v2', payload);
            localStorage.setItem('DTMIS_super_admin_design_tokens', payload);
        } catch (error) {
            // Ignore write errors.
        }
        return normalized;
    }

    function applySettings(settings) {
        const normalized = normalize(settings);
        if (designApi && typeof designApi.apply === 'function') {
            designApi.apply(normalized);
        }
        return normalized;
    }

    const themeModeInput = document.getElementById('superAdminThemeMode');
    const densityInput = document.getElementById('superAdminDensityMode');
    const radiusInput = document.getElementById('superAdminRadius');
    const sidebarWidthInput = document.getElementById('superAdminSidebarWidth');
    const contentWidthInput = document.getElementById('superAdminContentWidth');

    const radiusValue = document.getElementById('superAdminRadiusValue');
    const sidebarValue = document.getElementById('superAdminSidebarValue');
    const contentValue = document.getElementById('superAdminContentValue');

    const bpMobileRange = document.getElementById('superAdminBpMobileRange');
    const bpTabletRange = document.getElementById('superAdminBpTabletRange');
    const bpLandscapeRange = document.getElementById('superAdminBpLandscapeRange');
    const bpMobileValue = document.getElementById('superAdminBpMobileValue');
    const bpTabletValue = document.getElementById('superAdminBpTabletValue');
    const bpLandscapeValue = document.getElementById('superAdminBpLandscapeValue');
    const bpSummary = document.getElementById('superAdminBreakpointSummary');

    const applyButton = document.getElementById('superAdminApplyDesign');
    const resetButton = document.getElementById('superAdminResetDesign');
    const statusNode = document.getElementById('superAdminDesignStatus');

    const previewFrame = document.getElementById('superAdminPreviewFrame');
    const previewButtons = Array.from(document.querySelectorAll('[data-preview-size]'));

    const tokenInputs = Array.from(document.querySelectorAll('[data-design-token]'));
    const tokenPickers = Array.from(document.querySelectorAll('[data-design-picker]'));
    const roleAccentInputs = Array.from(document.querySelectorAll('[data-role-accent]'));
    const roleAccentPickers = Array.from(document.querySelectorAll('[data-role-accent-picker]'));

    let settings = loadSettings();

    function setStatus(message) {
        if (!statusNode) {
            return;
        }
        statusNode.textContent = String(message || '');
    }

    function readNumber(input, fallback) {
        const parsed = Number(input && input.value);
        return Number.isFinite(parsed) ? parsed : fallback;
    }

    function setBreakpointLimits() {
        const mobile = readNumber(bpMobileRange, 479);
        const tablet = readNumber(bpTabletRange, 767);

        if (bpTabletRange) {
            bpTabletRange.min = String(mobile + 1);
        }
        if (bpTabletValue) {
            bpTabletValue.min = String(mobile + 1);
        }
        if (bpLandscapeRange) {
            bpLandscapeRange.min = String(tablet + 1);
        }
        if (bpLandscapeValue) {
            bpLandscapeValue.min = String(tablet + 1);
        }

        if (bpMobileRange) {
            bpMobileRange.max = String(Math.max(900, tablet - 1));
        }
        if (bpMobileValue) {
            bpMobileValue.max = String(Math.max(900, tablet - 1));
        }
        if (bpTabletRange) {
            const landscapeCurrent = readNumber(bpLandscapeRange, 991);
            bpTabletRange.max = String(Math.max(1200, landscapeCurrent - 1));
        }
        if (bpTabletValue) {
            const landscapeCurrent = readNumber(bpLandscapeRange, 991);
            bpTabletValue.max = String(Math.max(1200, landscapeCurrent - 1));
        }
    }

    function updateBreakpointSummary() {
        if (!bpSummary) {
            return;
        }
        const mobile = readNumber(bpMobileRange, settings.breakpoints.mobile);
        const tablet = readNumber(bpTabletRange, settings.breakpoints.tablet);
        const landscape = readNumber(bpLandscapeRange, settings.breakpoints.tabletLandscape);
        bpSummary.textContent = 'Mobile <= ' + mobile + 'px, Tablet <= ' + tablet + 'px, Landscape <= ' + landscape + 'px, Desktop > ' + landscape + 'px';
    }

    function updateRangeLabels() {
        if (radiusValue) {
            radiusValue.textContent = String(readNumber(radiusInput, settings.layout.radius)) + 'px';
        }
        if (sidebarValue) {
            sidebarValue.textContent = String(readNumber(sidebarWidthInput, settings.layout.sidebarWidth)) + 'px';
        }
        if (contentValue) {
            contentValue.textContent = String(readNumber(contentWidthInput, settings.layout.contentWidth)) + 'px';
        }
    }

    function getTokenValue(path, fallback) {
        const segments = String(path || '').split('.');
        if (segments.length !== 3) {
            return fallback;
        }
        const theme = segments[0];
        const group = segments[1];
        const token = segments[2];
        if (!settings.palettes || !settings.palettes[theme] || !settings.palettes[theme][group]) {
            return fallback;
        }
        const value = settings.palettes[theme][group][token];
        return typeof value === 'string' && value.trim() !== '' ? value.trim() : fallback;
    }

    function setTokenValue(path, value) {
        const segments = String(path || '').split('.');
        if (segments.length !== 3) {
            return;
        }
        const theme = segments[0];
        const group = segments[1];
        const token = segments[2];
        if (!settings.palettes || !settings.palettes[theme] || !settings.palettes[theme][group]) {
            return;
        }
        settings.palettes[theme][group][token] = String(value || '').trim();
    }

    function getRoleAccentValue(path, fallback) {
        const segments = String(path || '').split('.');
        if (segments.length !== 2) {
            return fallback;
        }
        const theme = segments[0];
        const roleKey = segments[1];
        if (!settings.roleAccents || typeof settings.roleAccents !== 'object') {
            return fallback;
        }
        if (!settings.roleAccents[theme] || typeof settings.roleAccents[theme] !== 'object') {
            return fallback;
        }
        const value = settings.roleAccents[theme][roleKey];
        return typeof value === 'string' && value.trim() !== '' ? value.trim() : fallback;
    }

    function setRoleAccentValue(path, value) {
        const segments = String(path || '').split('.');
        if (segments.length !== 2) {
            return;
        }
        const theme = segments[0];
        const roleKey = segments[1];
        if (!settings.roleAccents || typeof settings.roleAccents !== 'object') {
            settings.roleAccents = {};
        }
        if (!settings.roleAccents[theme] || typeof settings.roleAccents[theme] !== 'object') {
            settings.roleAccents[theme] = {};
        }
        settings.roleAccents[theme][roleKey] = String(value || '').trim();
    }

    function updateTokenSwatches() {
        tokenInputs.forEach(function (input) {
            const path = String(input.getAttribute('data-design-token') || '');
            const swatch = document.querySelector('[data-token-swatch="' + path + '"]');
            const value = String(input.value || '').trim();
            const valid = isHexColor(value);

            input.classList.toggle('is-invalid', !valid);
            if (swatch) {
                if (swatch.matches('input[type="color"]')) {
                    swatch.value = toSixDigitHex(valid ? value : '#000000');
                } else {
                    swatch.style.background = valid ? value : 'repeating-linear-gradient(135deg, #2a3442 0 10px, #1b2430 10px 20px)';
                }
                swatch.classList.toggle('is-invalid', !valid);
            }
        });
    }

    function updateRoleAccentSwatches() {
        roleAccentInputs.forEach(function (input) {
            const roleKey = String(input.getAttribute('data-role-accent') || '');
            const swatch = document.querySelector('[data-role-accent-swatch="' + roleKey + '"]');
            const value = String(input.value || '').trim();
            const valid = isHexColor(value);

            input.classList.toggle('is-invalid', !valid);
            if (!swatch) {
                return;
            }
            swatch.classList.toggle('is-invalid', !valid);
            if (swatch.matches('input[type="color"]')) {
                swatch.value = toSixDigitHex(valid ? value : '#000000');
            }
        });
    }

    function hydrateForm() {
        const normalized = normalize(settings);
        settings = normalized;

        if (themeModeInput) {
            themeModeInput.value = normalized.appearance.themeMode;
        }
        if (densityInput) {
            densityInput.value = normalized.appearance.densityMode;
        }
        if (radiusInput) {
            radiusInput.value = String(normalized.layout.radius);
        }
        if (sidebarWidthInput) {
            sidebarWidthInput.value = String(normalized.layout.sidebarWidth);
        }
        if (contentWidthInput) {
            contentWidthInput.value = String(normalized.layout.contentWidth);
        }

        if (bpMobileRange) { bpMobileRange.value = String(normalized.breakpoints.mobile); }
        if (bpMobileValue) { bpMobileValue.value = String(normalized.breakpoints.mobile); }
        if (bpTabletRange) { bpTabletRange.value = String(normalized.breakpoints.tablet); }
        if (bpTabletValue) { bpTabletValue.value = String(normalized.breakpoints.tablet); }
        if (bpLandscapeRange) { bpLandscapeRange.value = String(normalized.breakpoints.tabletLandscape); }
        if (bpLandscapeValue) { bpLandscapeValue.value = String(normalized.breakpoints.tabletLandscape); }

        tokenInputs.forEach(function (input) {
            const path = String(input.getAttribute('data-design-token') || '');
            input.value = getTokenValue(path, '#000000');
        });

        tokenPickers.forEach(function (picker) {
            const path = String(picker.getAttribute('data-design-picker') || '');
            const tokenInput = tokenInputs.find(function (item) {
                return String(item.getAttribute('data-design-token') || '') === path;
            });
            const colorValue = tokenInput ? String(tokenInput.value || '').trim() : '#000000';
            picker.value = toSixDigitHex(colorValue);
        });

        roleAccentInputs.forEach(function (input) {
            const roleKey = String(input.getAttribute('data-role-accent') || '');
            input.value = getRoleAccentValue(roleKey, '#000000');
        });

        roleAccentPickers.forEach(function (picker) {
            const roleKey = String(picker.getAttribute('data-role-accent-picker') || '');
            const roleInput = roleAccentInputs.find(function (item) {
                return String(item.getAttribute('data-role-accent') || '') === roleKey;
            });
            const colorValue = roleInput ? String(roleInput.value || '').trim() : '#000000';
            picker.value = toSixDigitHex(colorValue);
        });

        setBreakpointLimits();
        updateRangeLabels();
        updateBreakpointSummary();
        updateTokenSwatches();
        updateRoleAccentSwatches();
    }

    function updateSettingsFromControls() {
        settings.appearance.themeMode = String(themeModeInput ? themeModeInput.value : settings.appearance.themeMode || 'system');
        settings.appearance.densityMode = String(densityInput ? densityInput.value : settings.appearance.densityMode || 'comfortable');

        settings.layout.radius = readNumber(radiusInput, settings.layout.radius);
        settings.layout.sidebarWidth = readNumber(sidebarWidthInput, settings.layout.sidebarWidth);
        settings.layout.contentWidth = readNumber(contentWidthInput, settings.layout.contentWidth);

        settings.breakpoints.mobile = readNumber(bpMobileRange, settings.breakpoints.mobile);
        settings.breakpoints.tablet = readNumber(bpTabletRange, settings.breakpoints.tablet);
        settings.breakpoints.tabletLandscape = readNumber(bpLandscapeRange, settings.breakpoints.tabletLandscape);

        tokenInputs.forEach(function (input) {
            const path = String(input.getAttribute('data-design-token') || '');
            setTokenValue(path, input.value);
        });

        roleAccentInputs.forEach(function (input) {
            const roleKey = String(input.getAttribute('data-role-accent') || '');
            setRoleAccentValue(roleKey, input.value);
        });

        settings = normalize(settings);
    }

    function applyPreview() {
        updateSettingsFromControls();
        hydrateForm();
        applySettings(settings);
        setStatus('Preview updated. Click Apply Design System to save.');
    }

    function persistSettings() {
        updateSettingsFromControls();
        hydrateForm();

        const normalized = saveSettings(settings);
        settings = applySettings(normalized);

        if (designApi && typeof designApi.applyThemeModePreference === 'function') {
            designApi.applyThemeModePreference(settings);
        } else {
            try {
                localStorage.setItem('DTMIS_theme', settings.appearance.themeMode);
            } catch (error) {
                // Ignore storage restrictions.
            }
        }

        setStatus('Design system settings saved and applied globally.');
    }

    function connectBreakpointPair(rangeInput, numberInput) {
        if (!rangeInput || !numberInput) {
            return;
        }
        rangeInput.addEventListener('input', function () {
            numberInput.value = rangeInput.value;
            applyPreview();
        });
        numberInput.addEventListener('input', function () {
            rangeInput.value = numberInput.value;
            applyPreview();
        });
    }

    function updatePreviewSize(previewSize) {
        const normalized = String(previewSize || 'mobile').toLowerCase();
        if (previewFrame) {
            previewFrame.setAttribute('data-preview-size', normalized);
        }

        previewButtons.forEach(function (button) {
            const target = String(button.getAttribute('data-preview-size') || '').toLowerCase();
            button.classList.toggle('is-active', target === normalized);
        });

        settings.previewSize = normalized;
    }

    if (radiusInput) {
        radiusInput.addEventListener('input', applyPreview);
    }
    if (sidebarWidthInput) {
        sidebarWidthInput.addEventListener('input', applyPreview);
    }
    if (contentWidthInput) {
        contentWidthInput.addEventListener('input', applyPreview);
    }
    if (themeModeInput) {
        themeModeInput.addEventListener('change', applyPreview);
    }
    if (densityInput) {
        densityInput.addEventListener('change', applyPreview);
    }

    connectBreakpointPair(bpMobileRange, bpMobileValue);
    connectBreakpointPair(bpTabletRange, bpTabletValue);
    connectBreakpointPair(bpLandscapeRange, bpLandscapeValue);

    tokenInputs.forEach(function (input) {
        input.addEventListener('input', function () {
            updateTokenSwatches();
            const candidate = String(input.value || '').trim();
            if (isHexColor(candidate)) {
                const path = String(input.getAttribute('data-design-token') || '');
                const picker = tokenPickers.find(function (item) {
                    return String(item.getAttribute('data-design-picker') || '') === path;
                });
                if (picker) {
                    picker.value = toSixDigitHex(candidate);
                }
                applyPreview();
            } else {
                setStatus('Enter a valid hex color like #3b63f2 or #4f7cff33.');
            }
        });
        input.addEventListener('change', applyPreview);
    });

    tokenPickers.forEach(function (picker) {
        picker.addEventListener('input', function () {
            const path = String(picker.getAttribute('data-design-picker') || '');
            const tokenInput = tokenInputs.find(function (item) {
                return String(item.getAttribute('data-design-token') || '') === path;
            });
            if (!tokenInput) {
                return;
            }
            const existingAlpha = getAlphaHex(tokenInput.value);
            tokenInput.value = String(picker.value || '#000000').toLowerCase() + (existingAlpha !== '' ? existingAlpha : '');
            updateTokenSwatches();
            applyPreview();
        });
    });

    roleAccentInputs.forEach(function (input) {
        input.addEventListener('input', function () {
            updateRoleAccentSwatches();
            const candidate = String(input.value || '').trim();
            if (isHexColor(candidate)) {
                const roleKey = String(input.getAttribute('data-role-accent') || '');
                const picker = roleAccentPickers.find(function (item) {
                    return String(item.getAttribute('data-role-accent-picker') || '') === roleKey;
                });
                if (picker) {
                    picker.value = toSixDigitHex(candidate);
                }
                persistSettings();
            } else {
                setStatus('Enter a valid role accent hex color like #1b6da8.');
            }
        });
        input.addEventListener('change', persistSettings);
    });

    roleAccentPickers.forEach(function (picker) {
        picker.addEventListener('input', function () {
            const roleKey = String(picker.getAttribute('data-role-accent-picker') || '');
            const roleInput = roleAccentInputs.find(function (item) {
                return String(item.getAttribute('data-role-accent') || '') === roleKey;
            });
            if (!roleInput) {
                return;
            }
            roleInput.value = String(picker.value || '#000000').toLowerCase();
            updateRoleAccentSwatches();
            persistSettings();
        });
    });

    previewButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const previewSize = String(button.getAttribute('data-preview-size') || 'mobile');
            updatePreviewSize(previewSize);
        });
    });

    if (applyButton) {
        applyButton.addEventListener('click', persistSettings);
    }

    if (resetButton) {
        resetButton.addEventListener('click', function () {
            settings = clone(designApi && designApi.defaults ? designApi.defaults : fallbackDefaults);
            settings = saveSettings(settings);
            hydrateForm();
            applySettings(settings);

            if (designApi && typeof designApi.applyThemeModePreference === 'function') {
                designApi.applyThemeModePreference(settings);
            }

            setStatus('Design system reset to defaults.');
        });
    }

    settings = normalize(settings);
    hydrateForm();
    applySettings(settings);
    updatePreviewSize(String(settings.previewSize || 'mobile'));
})();
</script>
