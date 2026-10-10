/* =========================================================
   Property Dealing — Partial injector
   Injects navbar + footer into every page from shared HTML.
   Page markup requirement:
     <div id="navbar" data-navbar="public"></div>
     <div id="footer"></div>
   data-navbar accepts: "public" | "dealer" | "admin"
   ========================================================= */

(function () {
    'use strict';

    const PARTIALS_BASE = '/frontend/shared/partials/';

    async function injectNavbar() {
        const slot = document.getElementById('navbar');
        if (!slot) return;

        const type = slot.getAttribute('data-navbar') || 'public';
        const file = PARTIALS_BASE + 'navbar-' + type + '.html';

        try {
            const res = await fetch(file, { cache: 'no-cache' });
            if (!res.ok) return;
            slot.innerHTML = await res.text();

            highlightActiveNav();
            attachLogoutHandlers(type);
        } catch (_) { /* silent */ }
    }

    async function injectFooter() {
        const slot = document.getElementById('footer');
        if (!slot) return;

        try {
            const res = await fetch(PARTIALS_BASE + 'footer.html', { cache: 'no-cache' });
            if (!res.ok) return;
            slot.innerHTML = await res.text();
        } catch (_) { /* silent */ }
    }

    function highlightActiveNav() {
        const path = window.location.pathname;
        const currentFile = path.substring(path.lastIndexOf('/') + 1);

        document.querySelectorAll('.site-navbar .nav-link[data-nav]').forEach(function (link) {
            const href = link.getAttribute('href') || '';
            const linkFile = href.substring(href.lastIndexOf('/') + 1);

            if (linkFile && currentFile && linkFile === currentFile) {
                link.classList.add('active');
            }
        });
    }

    function attachLogoutHandlers(type) {
        if (type === 'dealer') {
            const btn = document.getElementById('dealerLogoutBtn');
            if (btn) {
                btn.addEventListener('click', function () {
                    if (window.API && typeof window.API.logout === 'function') {
                        window.API.logout('dealer');
                    } else {
                        window.location.href = '/frontend/dealer/login.html';
                    }
                });
            }
        } else if (type === 'admin') {
            const btn = document.getElementById('adminLogoutBtn');
            if (btn) {
                btn.addEventListener('click', function () {
                    if (window.API && typeof window.API.logout === 'function') {
                        window.API.logout('admin');
                    } else {
                        window.location.href = '/frontend/admin/login.html';
                    }
                });
            }
        }
    }

    function init() {
        injectNavbar();
        injectFooter();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();