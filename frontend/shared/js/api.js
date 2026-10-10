/* =========================================================
   Property Dealing Platform — API Helper
   ---------------------------------------------------------
   Every page includes this file.
   Provides:
     - API.base(path)      → builds full URL
     - API.get(path)       → GET with credentials
     - API.post(path, body)→ POST JSON with credentials
     - API.postForm(path, formData) → POST multipart (file upload)
     - API.getQuery(name)  → reads ?param= from current URL
     - API.redirect(path)  → navigate
     - API.escapeHTML(str) → safe HTML output
     - API.formatPrice(str)→ "12000000.00" → "Rs. 1,20,00,000"
     - API.imageUrl(path)  → prepends "/" to stored path
     - API.showToast(msg, type) → simple toast notification
     - API.renderImageFallback(container, type)
     - API.requireAuth(role) → redirects to login if session missing
     - API.logout()        → calls logout endpoint and redirects
   ========================================================= */

(function (global) {
    'use strict';

    // ---------------------------------------------------------
    // Constants
    // ---------------------------------------------------------
    const BACKEND_BASE = '/backend/api';

    const ADMIN_LOGIN_PAGE = '/frontend/admin/login.html';
    const DEALER_LOGIN_PAGE = '/frontend/dealer/login.html';
    const PUBLIC_HOME_PAGE = '/frontend/public/index.html';

    // ---------------------------------------------------------
    // Core fetch helpers
    // ---------------------------------------------------------

    /**
     * Build a full backend API URL from a short path.
     * @param {string} path  e.g. "/public/list-properties.php"
     * @returns {string}
     */
    function base(path) {
        if (!path.startsWith('/')) path = '/' + path;
        return BACKEND_BASE + path;
    }

    /**
     * GET request with session credentials.
     * @param {string} path
     * @returns {Promise<Object>} parsed JSON
     */
    async function get(path) {
        const res = await fetch(base(path), {
            method: 'GET',
            credentials: 'include',
            headers: { 'Accept': 'application/json' }
        });
        return parseResponse(res);
    }

    /**
     * POST JSON request with session credentials.
     * @param {string} path
     * @param {Object} body
     * @returns {Promise<Object>} parsed JSON
     */
    async function post(path, body) {
        const res = await fetch(base(path), {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(body || {})
        });
        return parseResponse(res);
    }

    /**
     * POST multipart form-data (for file uploads).
     * @param {string} path
     * @param {FormData} formData
     * @returns {Promise<Object>} parsed JSON
     */
    async function postForm(path, formData) {
        const res = await fetch(base(path), {
            method: 'POST',
            credentials: 'include',
            headers: { 'Accept': 'application/json' },
            body: formData
        });
        return parseResponse(res);
    }

    /**
     * Parse the response into JSON while preserving status code.
     * Never throws for non-2xx — caller checks `result.success`.
     */
    async function parseResponse(res) {
        let data = null;
        try {
            data = await res.json();
        } catch (_) {
            data = {
                success: false,
                message: 'Unexpected server response. Please try again.'
            };
        }
        data.httpStatus = res.status;
        return data;
    }

    // ---------------------------------------------------------
    // URL & navigation helpers
    // ---------------------------------------------------------

    /**
     * Read a query string parameter from the current page URL.
     * @param {string} name
     * @returns {string|null}
     */
    function getQuery(name) {
        const params = new URLSearchParams(window.location.search);
        return params.get(name);
    }

    /**
     * Redirect the browser to a path.
     */
    function redirect(path) {
        window.location.href = path;
    }

    // ---------------------------------------------------------
    // Rendering helpers
    // ---------------------------------------------------------

    /**
     * Escape user-supplied text before inserting into HTML.
     */
    function escapeHTML(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /**
     * Format a numeric string into Pakistani numbering with "Rs." prefix.
     * "12000000.00" → "Rs. 1,20,00,000"
     */
    function formatPrice(value) {
        if (value === null || value === undefined || value === '') return 'Rs. 0';
        const num = Number(value);
        if (isNaN(num)) return 'Rs. 0';

        const formatted = new Intl.NumberFormat('en-IN', {
            maximumFractionDigits: 0
        }).format(num);

        return 'Rs. ' + formatted;
    }

    /**
     * Convert a stored image path into a usable URL.
     * "uploads/property_images/2/abc.png" → "/uploads/property_images/2/abc.png"
     */
    function imageUrl(path) {
        if (!path) return '';

        // If it's already a full URL (ImgBB), return it as-is
        if (path.startsWith('http://') || path.startsWith('https://')) {
            return path;
        }

        // Otherwise, treat it as a local path
        if (path.startsWith('/')) return path;
        return '/' + path;
    }
    /**
     * Render an image fallback inside a container.
     * @param {HTMLElement} container
     * @param {string} propertyType  e.g. "House", "Plot"
     */
    function renderImageFallback(container, propertyType) {
        if (!container) return;

        const iconMap = {
            'House': 'fa-house',
            'Plot': 'fa-mountain-sun',
            'Shop': 'fa-store',
            'Office': 'fa-building',
            'Building': 'fa-city',
            'Agricultural': 'fa-wheat-awn'
        };
        const icon = iconMap[propertyType] || 'fa-image';

        container.innerHTML =
            '<div class="img-fallback">' +
            '<i class="fa-solid ' + icon + '"></i>' +
            '<span>No image available</span>' +
            '</div>';
    }

    /**
     * Show a lightweight toast at the top of the viewport.
     * @param {string} message
     * @param {'success'|'error'|'info'|'warning'} type
     */
    function showToast(message, type) {
        const colorMap = {
            success: '#16825D',
            error: '#D93025',
            warning: '#A76014',
            info: '#006AFF'
        };
        const bg = colorMap[type] || colorMap.info;

        let toast = document.getElementById('pd-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'pd-toast';
            toast.style.cssText = [
                'position:fixed',
                'top:20px',
                'right:20px',
                'z-index:9999',
                'color:#fff',
                'padding:12px 18px',
                'border-radius:8px',
                'font-size:14px',
                'font-weight:600',
                'box-shadow:0 4px 12px rgba(0,0,0,.15)',
                'max-width:320px',
                'display:none'
            ].join(';');
            document.body.appendChild(toast);
        }

        toast.style.backgroundColor = bg;
        toast.textContent = message;
        toast.style.display = 'block';

        clearTimeout(toast._hideTimer);
        toast._hideTimer = setTimeout(function () {
            toast.style.display = 'none';
        }, 3000);
    }

    // ---------------------------------------------------------
    // Session helpers
    // ---------------------------------------------------------

    /**
     * Ensure the current visitor is logged in with the expected role.
     * Calls the profile endpoint; if unauthorized, redirects to login.
     *
     * @param {'dealer'|'admin'} role
     * @returns {Promise<Object|null>} profile object or null after redirect
     */
    async function requireAuth(role) {
        const endpoint = role === 'admin'
            ? '/admin/my-profile.php'
            : '/dealer/my-profile.php';

        const loginPage = role === 'admin'
            ? ADMIN_LOGIN_PAGE
            : DEALER_LOGIN_PAGE;

        try {
            const result = await get(endpoint);

            if (!result.success || result.httpStatus === 401) {
                redirect(loginPage);
                return null;
            }

            return result;
        } catch (_) {
            redirect(loginPage);
            return null;
        }
    }

    /**
     * Log out and redirect based on role.
     * @param {'dealer'|'admin'} role
     */
    async function logout(role) {
        try {
            await post('/auth/logout.php', {});
        } catch (_) { /* ignore */ }

        if (role === 'admin') {
            redirect(ADMIN_LOGIN_PAGE);
        } else if (role === 'dealer') {
            redirect(DEALER_LOGIN_PAGE);
        } else {
            redirect(PUBLIC_HOME_PAGE);
        }
    }

    // ---------------------------------------------------------
    // Form helpers
    // ---------------------------------------------------------

    /**
     * Mark a field invalid and show the message below it.
     * Uses Bootstrap 5 .is-invalid class.
     */
    function setFieldError(inputEl, message) {
        if (!inputEl) return;
        inputEl.classList.add('is-invalid');

        let feedback = inputEl.parentElement.querySelector('.invalid-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            inputEl.parentElement.appendChild(feedback);
        }
        feedback.textContent = message;
    }

    /**
     * Clear the error state from a field.
     */
    function clearFieldError(inputEl) {
        if (!inputEl) return;
        inputEl.classList.remove('is-invalid');
        const feedback = inputEl.parentElement.querySelector('.invalid-feedback');
        if (feedback) feedback.textContent = '';
    }

    /**
     * Clear all field errors inside a form.
     */
    function clearFormErrors(formEl) {
        if (!formEl) return;
        formEl.querySelectorAll('.is-invalid').forEach(function (el) {
            el.classList.remove('is-invalid');
        });
        formEl.querySelectorAll('.invalid-feedback').forEach(function (el) {
            el.textContent = '';
        });
    }

    // ---------------------------------------------------------
    // Expose API globally
    // ---------------------------------------------------------
    global.API = {
        base: base,
        get: get,
        post: post,
        postForm: postForm,

        getQuery: getQuery,
        redirect: redirect,

        escapeHTML: escapeHTML,
        formatPrice: formatPrice,
        imageUrl: imageUrl,
        renderImageFallback: renderImageFallback,
        showToast: showToast,

        requireAuth: requireAuth,
        logout: logout,

        setFieldError: setFieldError,
        clearFieldError: clearFieldError,
        clearFormErrors: clearFormErrors,

        // Constants
        PAGES: {
            PUBLIC_HOME: PUBLIC_HOME_PAGE,
            DEALER_LOGIN: DEALER_LOGIN_PAGE,
            ADMIN_LOGIN: ADMIN_LOGIN_PAGE
        }
    };

})(window);