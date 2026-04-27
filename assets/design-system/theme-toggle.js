(function () {
    var STORAGE_KEY = "edats_theme";
    var THEMES = ["light", "dark", "system"];

    function normalizeTheme(value) {
        return THEMES.indexOf(value) !== -1 ? value : "system";
    }

    function systemTheme() {
        try {
            return window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
        } catch (error) {
            return "light";
        }
    }

    function applyTheme(themeValue) {
        var normalized = normalizeTheme(themeValue);
        var resolved = normalized === "system" ? systemTheme() : normalized;
        document.documentElement.setAttribute("data-theme", resolved);
        if (document.body) {
            document.body.setAttribute("data-theme", resolved);
        }
        try {
            document.dispatchEvent(new CustomEvent("edats:theme-changed", { detail: { theme: resolved } }));
        } catch (error) {
            // Ignore environments without CustomEvent support.
        }
        return resolved;
    }

    function getResolvedThemeFromDom() {
        var current = String(document.documentElement.getAttribute("data-theme") || "").toLowerCase();
        return current === "dark" ? "dark" : "light";
    }

    function syncBodyThemeFromRoot() {
        if (!document.body) {
            return;
        }
        var rootTheme = document.documentElement.getAttribute("data-theme") || "light";
        document.body.setAttribute("data-theme", rootTheme);
    }

    function getStoredTheme() {
        try {
            return normalizeTheme(localStorage.getItem(STORAGE_KEY) || "system");
        } catch (error) {
            return "system";
        }
    }

    function setStoredTheme(themeValue) {
        var normalized = normalizeTheme(themeValue);
        try {
            localStorage.setItem(STORAGE_KEY, normalized);
        } catch (error) {
            // Ignore storage write errors in restricted environments.
        }
        return applyTheme(normalized);
    }

    function updateToggleButtons(resolvedTheme) {
        var safeResolvedTheme = resolvedTheme === "dark" ? "dark" : "light";
        var nextTheme = safeResolvedTheme === "dark" ? "light" : "dark";
        var toggles = document.querySelectorAll("[data-theme-toggle]");
        toggles.forEach(function (toggle) {
            toggle.setAttribute("aria-label", "Switch to " + nextTheme + " theme");
            toggle.setAttribute("title", "Switch to " + nextTheme + " theme");
        });
    }

    function bindToggleButtons() {
        var toggles = document.querySelectorAll("[data-theme-toggle]");
        toggles.forEach(function (toggle) {
            toggle.addEventListener("click", function () {
                var current = getResolvedThemeFromDom();
                var next = current === "dark" ? "light" : "dark";
                var resolved = setStoredTheme(next);
                updateToggleButtons(resolved);
            });
        });
    }

    function initTheme() {
        var resolvedTheme = applyTheme(getStoredTheme());
        syncBodyThemeFromRoot();
        bindToggleButtons();
        updateToggleButtons(resolvedTheme);

        document.addEventListener("edats:theme-changed", function (event) {
            var detailTheme = event && event.detail && event.detail.theme ? String(event.detail.theme).toLowerCase() : "";
            var resolved = detailTheme === "dark" || detailTheme === "light" ? detailTheme : getResolvedThemeFromDom();
            updateToggleButtons(resolved);
        });

        try {
            var bodyThemeObserver = new MutationObserver(function () {
                syncBodyThemeFromRoot();
            });
            bodyThemeObserver.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ["data-theme"]
            });
        } catch (error) {
            // Ignore environments without MutationObserver support.
        }

        try {
            window.matchMedia("(prefers-color-scheme: dark)").addEventListener("change", function () {
                if (getStoredTheme() === "system") {
                    var updatedResolvedTheme = applyTheme("system");
                    updateToggleButtons(updatedResolvedTheme);
                }
            });
        } catch (error) {
            // Older browsers may not support change listeners.
        }

        window.addEventListener("storage", function (event) {
            if (!event || event.key !== STORAGE_KEY) {
                return;
            }
            var updatedResolvedTheme = applyTheme(getStoredTheme());
            updateToggleButtons(updatedResolvedTheme);
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initTheme);
    } else {
        initTheme();
    }

    window.EDATSTheme = {
        get: function () { return getStoredTheme(); },
        set: function (themeValue) { return setStoredTheme(themeValue); },
        apply: function (themeValue) { return applyTheme(themeValue); }
    };
})();
