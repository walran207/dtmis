(function () {
    'use strict';

    var STORAGE_KEY = 'edats_super_admin_design_tokens_v2';
    var LEGACY_STORAGE_KEY = 'edats_super_admin_design_tokens';
    var BREAKPOINT_STYLE_ID = 'edats-runtime-breakpoints-style';
    var ROLE_ACCENT_STYLE_ID = 'edats-runtime-role-accents-style';

    var ROLE_ACCENT_DEFAULTS = {
        CENRO_ADMIN_RECORD: '#2f8f83',
        PENRO_ADMIN_RECORD: '#3f7fb1',
        PAMO_ADMIN: '#3c8c78',
        PASU: '#4a8f6a',
        PASU_OFFICER: '#3c8c78',
        PAMO_UNIT: '#3c8c78',
        RECORDS_UNIT: '#9b7a4f',
        PACDO: '#9b7a4f',
        ORED: '#7a6ab3',
        DIVISION_CHIEF: '#8a6f66',
        SECTION_STAFF: '#5f8596',
        ARD_TS: '#4f7fb8',
        ARD_MS: '#4f7fb8',
        SUPER_ADMIN: '#2a8f84'
    };

    var DEFAULTS = {
        appearance: {
            themeMode: 'system',
            densityMode: 'comfortable'
        },
        layout: {
            radius: 16,
            sidebarWidth: 280,
            contentWidth: 1280
        },
        breakpoints: {
            mobile: 479,
            tablet: 767,
            tabletLandscape: 991
        },
        roleAccents: {
            light: {
                CENRO_ADMIN_RECORD: '#2f8f83',
                PENRO_ADMIN_RECORD: '#3f7fb1',
                PAMO_ADMIN: '#3c8c78',
                PASU: '#4a8f6a',
                PASU_OFFICER: '#3c8c78',
                PAMO_UNIT: '#3c8c78',
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
                PENRO_ADMIN_RECORD: '#3f7fb1',
                PAMO_ADMIN: '#3c8c78',
                PASU: '#4a8f6a',
                PASU_OFFICER: '#3c8c78',
                PAMO_UNIT: '#3c8c78',
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
                brand: {
                    primary: '#3b63f2',
                    secondary: '#2746b7',
                    tertiary: '#e8eeff',
                    alternate: '#c9d6ff'
                },
                utility: {
                    primaryText: '#1a2340',
                    secondaryText: '#4b5a86',
                    primaryBg: '#f6f8ff',
                    secondaryBg: '#ffffff'
                },
                accents: {
                    accent1: '#3b63f233',
                    accent2: '#60a5fa26',
                    accent3: '#a78bfa22',
                    accent4: '#f59e0b24'
                },
                semantic: {
                    success: '#22c55e',
                    error: '#ff5d73',
                    warning: '#f59e0b',
                    info: '#3ba7ff'
                }
            },
            dark: {
                brand: {
                    primary: '#4f7cff',
                    secondary: '#2d4ea2',
                    tertiary: '#1a2745',
                    alternate: '#2b3a63'
                },
                utility: {
                    primaryText: '#f4f8ff',
                    secondaryText: '#a9b7d5',
                    primaryBg: '#0a1224',
                    secondaryBg: '#101a31'
                },
                accents: {
                    accent1: '#4f7cff33',
                    accent2: '#22d3ee24',
                    accent3: '#a78bfa22',
                    accent4: '#f59e0b24'
                },
                semantic: {
                    success: '#22c55e',
                    error: '#ff5d73',
                    warning: '#f59e0b',
                    info: '#60a5fa'
                }
            }
        }
    };

    function clone(value) {
        return JSON.parse(JSON.stringify(value));
    }

    function isObject(value) {
        return value && typeof value === 'object' && !Array.isArray(value);
    }

    function mergeDeep(base, override) {
        var output = clone(base);
        if (!isObject(override)) {
            return output;
        }

        Object.keys(override).forEach(function (key) {
            var baseValue = output[key];
            var overrideValue = override[key];
            if (isObject(baseValue) && isObject(overrideValue)) {
                output[key] = mergeDeep(baseValue, overrideValue);
                return;
            }
            output[key] = overrideValue;
        });

        return output;
    }

    function clamp(number, min, max) {
        return Math.min(Math.max(number, min), max);
    }

    function toNumber(value, fallback) {
        var parsed = Number(value);
        return Number.isFinite(parsed) ? parsed : fallback;
    }

    function normalizeHex(value, fallback) {
        var raw = String(value || '').trim();
        if (raw === '') {
            return fallback;
        }

        if (!/^#([\da-fA-F]{3}|[\da-fA-F]{4}|[\da-fA-F]{6}|[\da-fA-F]{8})$/.test(raw)) {
            return fallback;
        }

        if (raw.length === 4 || raw.length === 5) {
            var expanded = '#';
            var source = raw.slice(1).split('');
            source.forEach(function (character) {
                expanded += character + character;
            });
            return expanded.toLowerCase();
        }

        return raw.toLowerCase();
    }

    function normalizeThemeMode(value) {
        var mode = String(value || 'system').toLowerCase();
        return mode === 'light' || mode === 'dark' || mode === 'system' ? mode : 'system';
    }

    function normalizeDensityMode(value) {
        return String(value || '').toLowerCase() === 'compact' ? 'compact' : 'comfortable';
    }

    function normalizePalette(source, defaults) {
        var palette = mergeDeep(defaults, source || {});

        Object.keys(palette.brand).forEach(function (key) {
            palette.brand[key] = normalizeHex(palette.brand[key], defaults.brand[key]);
        });
        Object.keys(palette.utility).forEach(function (key) {
            palette.utility[key] = normalizeHex(palette.utility[key], defaults.utility[key]);
        });
        Object.keys(palette.accents).forEach(function (key) {
            palette.accents[key] = normalizeHex(palette.accents[key], defaults.accents[key]);
        });
        Object.keys(palette.semantic).forEach(function (key) {
            palette.semantic[key] = normalizeHex(palette.semantic[key], defaults.semantic[key]);
        });

        return palette;
    }

    function normalizeRoleAccents(source) {
        var input = isObject(source) ? source : {};
        var roleAccents = { light: {}, dark: {} };

        ['light', 'dark'].forEach(function (themeKey) {
            var themeSource = isObject(input[themeKey]) ? input[themeKey] : input;
            Object.keys(ROLE_ACCENT_DEFAULTS).forEach(function (roleKey) {
                roleAccents[themeKey][roleKey] = normalizeHex(themeSource[roleKey], ROLE_ACCENT_DEFAULTS[roleKey]);
            });
        });

        return roleAccents;
    }

    function normalize(settings) {
        var merged = mergeDeep(DEFAULTS, settings || {});

        var mobile = clamp(Math.round(toNumber(merged.breakpoints.mobile, DEFAULTS.breakpoints.mobile)), 320, 900);
        var tablet = clamp(Math.round(toNumber(merged.breakpoints.tablet, DEFAULTS.breakpoints.tablet)), mobile + 1, 1200);
        var tabletLandscape = clamp(Math.round(toNumber(merged.breakpoints.tabletLandscape, DEFAULTS.breakpoints.tabletLandscape)), tablet + 1, 1800);

        return {
            appearance: {
                themeMode: normalizeThemeMode(merged.appearance.themeMode),
                densityMode: normalizeDensityMode(merged.appearance.densityMode)
            },
            layout: {
                radius: clamp(Math.round(toNumber(merged.layout.radius, DEFAULTS.layout.radius)), 6, 30),
                sidebarWidth: clamp(Math.round(toNumber(merged.layout.sidebarWidth, DEFAULTS.layout.sidebarWidth)), 220, 420),
                contentWidth: clamp(Math.round(toNumber(merged.layout.contentWidth, DEFAULTS.layout.contentWidth)), 960, 2200)
            },
            breakpoints: {
                mobile: mobile,
                tablet: tablet,
                tabletLandscape: tabletLandscape
            },
            roleAccents: normalizeRoleAccents(merged.roleAccents),
            palettes: {
                light: normalizePalette(merged.palettes.light, DEFAULTS.palettes.light),
                dark: normalizePalette(merged.palettes.dark, DEFAULTS.palettes.dark)
            }
        };
    }

    function loadRaw() {
        var raw = '';
        try {
            raw = localStorage.getItem(STORAGE_KEY) || localStorage.getItem(LEGACY_STORAGE_KEY) || '';
        } catch (error) {
            raw = '';
        }

        if (!raw) {
            return null;
        }

        try {
            return JSON.parse(raw);
        } catch (error) {
            return null;
        }
    }

    function load() {
        return normalize(loadRaw());
    }

    function save(settings) {
        var normalized = normalize(settings);
        try {
            var payload = JSON.stringify(normalized);
            localStorage.setItem(STORAGE_KEY, payload);
            localStorage.setItem(LEGACY_STORAGE_KEY, payload);
        } catch (error) {
            // Ignore storage restrictions.
        }
        return normalized;
    }

    function getResolvedTheme() {
        var rootTheme = String(document.documentElement.getAttribute('data-theme') || '').toLowerCase();
        if (rootTheme === 'dark') {
            return 'dark';
        }
        return 'light';
    }

    function getActivePalette(settings) {
        var mode = getResolvedTheme();
        return settings.palettes[mode] || settings.palettes.light;
    }

    function applyVariables(settings) {
        var palette = getActivePalette(settings);
        var root = document.documentElement;

        root.style.setProperty('--super-admin-radius', String(settings.layout.radius) + 'px');
        root.style.setProperty('--super-admin-sidebar-width', String(settings.layout.sidebarWidth) + 'px');
        root.style.setProperty('--super-admin-content-max', String(settings.layout.contentWidth) + 'px');

        root.style.setProperty('--super-admin-primary', palette.brand.primary);
        root.style.setProperty('--super-admin-secondary', palette.brand.secondary);

        root.style.setProperty('--theme-bg', palette.utility.primaryBg);
        root.style.setProperty('--theme-surface', palette.utility.secondaryBg);
        root.style.setProperty('--theme-surface-soft', palette.brand.tertiary);
        root.style.setProperty('--theme-border', palette.brand.alternate);
        root.style.setProperty('--theme-text', palette.utility.primaryText);
        root.style.setProperty('--theme-text-muted', palette.utility.secondaryText);
        root.style.setProperty('--theme-link', palette.brand.secondary);
        root.style.setProperty('--theme-focus', palette.accents.accent2);
        root.style.setProperty('--theme-primary', palette.brand.primary);
        root.style.setProperty('--theme-secondary', palette.brand.secondary);

        root.style.setProperty('--bg', palette.utility.primaryBg);
        root.style.setProperty('--surface', palette.utility.secondaryBg);
        root.style.setProperty('--card', palette.utility.secondaryBg);
        root.style.setProperty('--line', palette.brand.alternate);
        root.style.setProperty('--text', palette.utility.primaryText);
        root.style.setProperty('--muted', palette.utility.secondaryText);
        root.style.setProperty('--primary', palette.brand.primary);
        root.style.setProperty('--secondary', palette.brand.secondary);

        root.style.setProperty('--color-success', palette.semantic.success);
        root.style.setProperty('--color-warning', palette.semantic.warning);
        root.style.setProperty('--color-danger', palette.semantic.error);
        root.style.setProperty('--color-info', palette.semantic.info);
        root.style.setProperty('--semantic-success', palette.semantic.success);
        root.style.setProperty('--semantic-warning', palette.semantic.warning);
        root.style.setProperty('--semantic-danger', palette.semantic.error);
        root.style.setProperty('--semantic-info', palette.semantic.info);

        root.style.setProperty('--edats-accent-1', palette.accents.accent1);
        root.style.setProperty('--edats-accent-2', palette.accents.accent2);
        root.style.setProperty('--edats-accent-3', palette.accents.accent3);
        root.style.setProperty('--edats-accent-4', palette.accents.accent4);
        root.style.setProperty('--accent-1', palette.accents.accent1);
        root.style.setProperty('--accent-2', palette.accents.accent2);
        root.style.setProperty('--accent-3', palette.accents.accent3);
        root.style.setProperty('--accent-4', palette.accents.accent4);

        if (document.body) {
            document.body.classList.toggle('super-admin-density-compact', settings.appearance.densityMode === 'compact');
        }
    }

    function buildRoleAccentCss(roleAccents) {
        var tokens = isObject(roleAccents) ? roleAccents : {};
        var lines = [];
        ['light', 'dark'].forEach(function (themeKey) {
            var themeTokens = isObject(tokens[themeKey]) ? tokens[themeKey] : tokens;
            Object.keys(ROLE_ACCENT_DEFAULTS).forEach(function (roleKey) {
                var accent = normalizeHex(themeTokens[roleKey], ROLE_ACCENT_DEFAULTS[roleKey]);
                lines.push('body[data-theme="' + themeKey + '"][data-role-key="' + roleKey + '"], html[data-theme="' + themeKey + '"] body[data-role-key="' + roleKey + '"] { --role-accent: ' + accent + '; }');
            });
        });
        return lines.join('\n');
    }

    function applyRoleAccents(settings) {
        var styleNode = document.getElementById(ROLE_ACCENT_STYLE_ID);
        if (!styleNode) {
            styleNode = document.createElement('style');
            styleNode.id = ROLE_ACCENT_STYLE_ID;
            document.head.appendChild(styleNode);
        }
        styleNode.textContent = buildRoleAccentCss(settings && settings.roleAccents ? settings.roleAccents : {});
    }

    function buildBreakpointCss(breakpoints) {
        var mobile = Number(breakpoints.mobile || DEFAULTS.breakpoints.mobile);
        var tablet = Number(breakpoints.tablet || DEFAULTS.breakpoints.tablet);
        var landscape = Number(breakpoints.tabletLandscape || DEFAULTS.breakpoints.tabletLandscape);
        var tabletMin = tablet + 1;

        return [
            ':root { --edats-bp-mobile: ' + mobile + 'px; --edats-bp-tablet: ' + tablet + 'px; --edats-bp-tablet-landscape: ' + landscape + 'px; }',
            '@media (max-width: ' + landscape + 'px) { .hide-tablet-down { display: none !important; } }',
            '@media (max-width: ' + tablet + 'px) { .hide-mobile { display: none !important; } .flex-col-tablet { flex-direction: column !important; align-items: flex-start !important; } }',
            '@media (min-width: ' + tabletMin + 'px) { .show-mobile { display: none !important; } }',
            '@media (max-width: ' + mobile + 'px) { .hide-small { display: none !important; } .m-0-mobile { margin: 0 !important; } .p-0-mobile { padding: 0 !important; } .mb-2-mobile { margin-bottom: 8px !important; } .mb-4-mobile { margin-bottom: 16px !important; } .text-center-mobile { text-align: center !important; } .text-left-mobile { text-align: left !important; } }',
            '@media (max-width: ' + landscape + 'px) { .mobile-nav-toggle { display: inline-flex; } .dashboard-shell { min-height: 100vh; min-height: 100dvh; height: auto; grid-template-columns: 1fr; gap: 0; padding: 0; overflow: visible; } .sidebar-host { position: fixed; top: 0; left: 0; height: 100%; height: 100dvh; width: 272px; z-index: 500; transform: translateX(-100%); transition: transform 0.3s cubic-bezier(0.32, 0.72, 0, 1); align-self: auto; } .sidebar-host.is-open { transform: translateX(0); } .sidebar { height: 100%; height: 100dvh; border-radius: 0 18px 18px 0; border-left: none; border-right: 1px solid var(--sidebar-border); border-bottom: none; position: relative; box-shadow: 8px 0 32px rgba(12, 24, 40, 0.22); display: grid; overflow: hidden; } .sidebar-toggle, .sidebar-resize-handle { display: none; } .content { min-height: auto; overflow: visible; width: 100%; padding: 10px 16px 20px; } body[data-role-key="SUPER_ADMIN"] .super-admin-panel, body[data-role-key="SUPER_ADMIN"] .network-block { grid-column: span 12; } body[data-role-key="SUPER_ADMIN"] .super-admin-network-grid { grid-template-columns: 1fr; } body[data-role-key="SUPER_ADMIN"] .super-admin-network-cards { grid-template-columns: repeat(2, minmax(0, 1fr)); } }',
            '@media (max-width: ' + tablet + 'px) { .sidebar-host { width: 260px; } .content { padding: 10px 12px 18px; } .dashboard-footer { flex-direction: column; align-items: flex-start; gap: 8px; } body[data-role-key="SUPER_ADMIN"] .super-admin-network-cards { grid-template-columns: 1fr; } body[data-role-key="SUPER_ADMIN"] .super-admin-form-actions { flex-direction: column; } body[data-role-key="SUPER_ADMIN"] .super-admin-btn { width: 100%; } }',
            '@media (max-width: ' + mobile + 'px) { .sidebar-host { width: 248px; } .content { padding: 8px 10px 16px; } }'
        ].join('\n');
    }

    function applyBreakpoints(settings) {
        var styleNode = document.getElementById(BREAKPOINT_STYLE_ID);
        if (!styleNode) {
            styleNode = document.createElement('style');
            styleNode.id = BREAKPOINT_STYLE_ID;
            document.head.appendChild(styleNode);
        }
        styleNode.textContent = buildBreakpointCss(settings.breakpoints);
    }

    function apply(settings) {
        var normalized = normalize(settings);
        applyVariables(normalized);
        applyBreakpoints(normalized);
        applyRoleAccents(normalized);
        return normalized;
    }

    function update(patch) {
        var merged = mergeDeep(load(), patch || {});
        var saved = save(merged);
        return apply(saved);
    }

    function applyThemeModePreference(settings) {
        if (!settings || !settings.appearance) {
            return;
        }
        if (window.EDATSTheme && typeof window.EDATSTheme.set === 'function') {
            window.EDATSTheme.set(settings.appearance.themeMode);
            return;
        }
        try {
            localStorage.setItem('edats_theme', settings.appearance.themeMode);
        } catch (error) {
            // Ignore storage restrictions.
        }
    }

    function bootstrap() {
        apply(load());

        document.addEventListener('edats:theme-changed', function () {
            apply(load());
        });

        window.addEventListener('storage', function (event) {
            if (!event) {
                return;
            }
            if (event.key === STORAGE_KEY || event.key === LEGACY_STORAGE_KEY) {
                apply(load());
            }
        });
    }

    window.EDATSDesignSystem = {
        key: STORAGE_KEY,
        legacyKey: LEGACY_STORAGE_KEY,
        defaults: clone(DEFAULTS),
        normalize: normalize,
        load: load,
        save: save,
        apply: apply,
        update: update,
        applyThemeModePreference: applyThemeModePreference
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootstrap);
    } else {
        bootstrap();
    }
})();
