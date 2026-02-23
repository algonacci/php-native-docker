/**
 * Network Safety Guard
 * Automatically detects network connectivity and disables UI controls when offline
 * Prevents failed transactions due to network issues
 */

(function () {
    'use strict';

    // Configuration
    const CONFIG = {
        CHECK_URL: 'https://connectivitycheck.gstatic.com/generate_204',
        CHECK_INTERVAL: 5000,       // Check every 5 seconds (more responsive)
        GRACE_PERIOD: 2000,         // 2 second delay before disabling
        STATUS_KEY: 'networkGuardStatus' // LocalStorage key
    };

    // State
    let gracePeriodTimer = null;
    let checkIntervalTimer = null;
    let lastKnownStatus = 'CHECKING';

    // DOM Elements
    const elements = {
        overlay: null,
        indicator: null
    };

    /**
     * Initialize the Network Safety Guard
     */
    function init() {
        // Ensure DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () {
                createStatusIndicator();
                createOverlay();
                startMonitoring();
            });
        } else {
            createStatusIndicator();
            createOverlay();
            startMonitoring();
        }
    }

    /**
     * Start monitoring after UI elements are created
     */
    function startMonitoring() {
        // Initial check
        checkInternet();

        // Start periodic checking
        checkIntervalTimer = setInterval(checkInternet, CONFIG.CHECK_INTERVAL);

        // Listen to browser online/offline events
        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);
    }

    /**
     * Create the network status indicator (traffic light style)
     */
    function createStatusIndicator() {
        const indicator = document.createElement('div');
        indicator.id = 'network-status-indicator';
        indicator.className = 'network-status-indicator online';
        indicator.innerHTML = `
            <span class="light-dot"></span>
            <span class="status-label">Online</span>
        `;

        // Find navbar and insert indicator
        const navbarActions = document.querySelector('.d-flex.align-items-center.gap-2');

        if (navbarActions) {
            navbarActions.insertBefore(indicator, navbarActions.firstChild);
        } else {
            // Fallback: insert after navbar brand
            const navbarBrand = document.querySelector('.navbar-brand');
            if (navbarBrand) {
                navbarBrand.parentNode.insertBefore(indicator, navbarBrand.nextSibling);
            } else {
                document.body.insertBefore(indicator, document.body.firstChild);
            }
        }

        elements.indicator = indicator;
    }

    /**
     * Create full-page overlay
     */
    function createOverlay() {
        const overlay = document.createElement('div');
        overlay.id = 'network-overlay';
        overlay.innerHTML = `
            <div class="network-overlay-content">
                <div class="network-overlay-icon">
                    <i class="fas fa-wifi-slash"></i>
                </div>
                <h2>Koneksi Terputus</h2>
                <p>Tombol dan input dinonaktifkan untuk mencegah transaksi gagal.</p>
                <p class="network-overlay-hint">Sistem akan otomatis aktif kembali saat koneksi pulih.</p>
                <div class="network-overlay-spinner">
                    <div class="spinner-border text-warning" role="status">
                        <span class="visually-hidden">Memeriksa...</span>
                    </div>
                </div>
            </div>
        `;
        overlay.style.cssText = `
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9998;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            color: white;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        `;
        document.body.appendChild(overlay);
        elements.overlay = overlay;

        // Add overlay styles
        const overlayStyles = document.createElement('style');
        overlayStyles.textContent = `
            #network-overlay.show {
                display: flex;
            }
            #network-overlay .network-overlay-icon {
                font-size: 64px;
                margin-bottom: 20px;
                color: #f39c12;
            }
            #network-overlay h2 {
                font-size: 28px;
                font-weight: 600;
                margin-bottom: 10px;
            }
            #network-overlay p {
                font-size: 16px;
                opacity: 0.9;
                margin-bottom: 8px;
            }
            #network-overlay .network-overlay-hint {
                font-size: 14px;
                opacity: 0.7;
                font-style: italic;
            }
            #network-overlay .network-overlay-spinner {
                margin-top: 20px;
            }
        `;
        document.head.appendChild(overlayStyles);
    }

    /**
     * Check internet connectivity using fetch
     */
    async function checkInternet() {
        // Fast path: browser already knows it's offline
        if (navigator.onLine === false) {
            setOffline();
            return;
        }

        const wasOffline = lastKnownStatus === 'OFFLINE' || lastKnownStatus === 'CHECKING_GRACE';

        // Update internal state
        lastKnownStatus = 'CHECKING';

        // If we were offline, show checking UI to indicate we're trying to reconnect
        if (wasOffline) {
            updateIndicator('checking');
        }

        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 5000);

            await fetch(CONFIG.CHECK_URL, {
                method: 'HEAD',
                mode: 'no-cors',
                signal: controller.signal
            });

            clearTimeout(timeoutId);

            // If we get here, we're online
            setOnline();

        } catch (error) {
            // Network error - definitively offline or transient drop
            setOffline();
        }
    }

    /**
     * Handle browser going online
     */
    function handleOnline() {
        // Clear any pending grace period
        if (gracePeriodTimer) {
            clearTimeout(gracePeriodTimer);
            gracePeriodTimer = null;
        }
        setOnline();
    }

    /**
     * Handle browser going offline
     */
    function handleOffline() {
        // Start grace period
        lastKnownStatus = 'CHECKING_GRACE';
        updateIndicator('checking');

        gracePeriodTimer = setTimeout(() => {
            setOffline();
        }, CONFIG.GRACE_PERIOD);
    }

    /**
     * Set online status
     */
    function setOnline() {
        lastKnownStatus = 'ONLINE';
        localStorage.setItem(CONFIG.STATUS_KEY, 'ONLINE');
        updateIndicator('ONLINE');
        toggleButtons('ONLINE');
        hideOverlay();
    }

    /**
     * Set offline status
     */
    function setOffline() {
        lastKnownStatus = 'OFFLINE';
        localStorage.setItem(CONFIG.STATUS_KEY, 'OFFLINE');
        updateIndicator('OFFLINE');
        toggleButtons('OFFLINE');
        showOverlay();
    }

    /**
     * Toggle button/input states based on network status
     * @param {string} status - 'ONLINE', 'OFFLINE', or 'CHECKING'
     */
    function toggleButtons(status) {
        const selectors = [
            'button:not(.ignore-network-guard)',
            'input:not(.ignore-network-guard)',
            'select:not(.ignore-network-guard)',
            'textarea:not(.ignore-network-guard)'
        ].join(', ');

        const elements = document.querySelectorAll(selectors);

        elements.forEach(el => {
            if (status === 'OFFLINE' || status === 'CHECKING') {
                // Store original state
                if (!el.dataset.originalDisabled) {
                    el.dataset.originalDisabled = el.disabled;
                }
                el.disabled = true;
                el.style.opacity = '0.5';
                el.style.cursor = 'not-allowed';

                // Add visual indicator class
                el.classList.add('network-guarded');

            } else {
                // Restore original state
                if (el.dataset.originalDisabled) {
                    el.disabled = el.dataset.originalDisabled === 'true';
                } else {
                    el.disabled = false;
                }
                el.style.opacity = '';
                el.style.cursor = '';
                el.classList.remove('network-guarded');
            }
        });

        // Add global style for network-guarded elements
        if (!document.getElementById('network-guard-styles')) {
            const style = document.createElement('style');
            style.id = 'network-guard-styles';
            style.textContent = `
                .network-guarded {
                    opacity: 0.5 !important;
                    cursor: not-allowed !important;
                    pointer-events: none !important;
                }
                .network-guarded:hover {
                    opacity: 0.5 !important;
                }
            `;
            document.head.appendChild(style);
        }
    }

    /**
     * Update status indicator (traffic light)
     */
    function updateIndicator(status) {
        let indicator = elements.indicator;

        // If indicator not found, try to find it
        if (!indicator) {
            indicator = document.getElementById('network-status-indicator');
            if (indicator) {
                elements.indicator = indicator;
            } else {
                return;
            }
        }

        // Remove all status classes
        indicator.classList.remove('online', 'offline', 'checking');

        // Add the appropriate status class (normalize status to lowercase)
        const normalizedStatus = status ? status.toLowerCase() : '';

        if (normalizedStatus === 'checking' || normalizedStatus === 'checking_grace') {
            indicator.classList.add('checking');
        } else if (normalizedStatus === 'offline') {
            indicator.classList.add('offline');
        } else {
            indicator.classList.add('online');
        }

        // Update label
        const label = indicator.querySelector('.status-label');
        if (label) {
            if (normalizedStatus === 'checking' || normalizedStatus === 'checking_grace') {
                label.textContent = 'Checking...';
            } else if (normalizedStatus === 'offline') {
                label.textContent = 'Offline';
            } else {
                label.textContent = 'Online';
            }
        }
    }

    /**
     * Show overlay
     */
    function showOverlay() {
        if (elements.overlay) {
            elements.overlay.classList.add('show');
        }
    }

    /**
     * Hide overlay
     */
    function hideOverlay() {
        if (elements.overlay) {
            elements.overlay.classList.remove('show');
        }
    }

    /**
     * Get current network status
     */
    function getStatus() {
        return lastKnownStatus;
    }

    /**
     * Manually trigger a connectivity check
     */
    function forceCheck() {
        checkInternet();
    }

    // Expose API globally
    window.NetworkGuard = {
        init: init,
        getStatus: getStatus,
        forceCheck: forceCheck,
        toggleButtons: toggleButtons,
        updateIndicator: updateIndicator
    };

    // Auto-initialize when navbar is ready (defer to ensure DOM is ready)
    function deferredInit() {
        // Wait for DOM to be fully ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () {
                setTimeout(init, 100);
            });
        } else {
            setTimeout(init, 100);
        }
    }

    deferredInit();

})();
