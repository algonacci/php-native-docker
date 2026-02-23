(function () {
    'use strict';

    const STORAGE_KEY = 'darkMode';
    const DARK_CLASS = 'dark-mode';

    function init() {
        const savedMode = localStorage.getItem(STORAGE_KEY);
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        const isDark = savedMode === 'dark' || (!savedMode && prefersDark);
        
        if (isDark) {
            document.body.classList.add(DARK_CLASS);
        }
        
        updateToggleIcon(isDark);
        setupEventListeners();
    }

    function toggleDarkMode() {
        const isDark = document.body.classList.toggle(DARK_CLASS);
        localStorage.setItem(STORAGE_KEY, isDark ? 'dark' : 'light');
        updateToggleIcon(isDark);
        
        return isDark;
    }

    function updateToggleIcon(isDark) {
        const toggles = document.querySelectorAll('.theme-toggle i');
        toggles.forEach(icon => {
            if (isDark) {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            }
        });
    }

    function setupEventListeners() {
        document.querySelectorAll('.theme-toggle').forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                e.preventDefault();
                toggleDarkMode();
            });
        });

        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem(STORAGE_KEY)) {
                const isDark = e.matches;
                document.body.classList.toggle(DARK_CLASS, isDark);
                updateToggleIcon(isDark);
            }
        });
    }

    function isDarkMode() {
        return document.body.classList.contains(DARK_CLASS);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.DarkMode = {
        toggle: toggleDarkMode,
        isDark: isDarkMode
    };
})();
