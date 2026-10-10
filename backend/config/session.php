<?php

// ------------------------------------------------------------
// Session security hardening
//
// Include this file BEFORE calling session_start().
// Every request that needs a session must load this first.
// ------------------------------------------------------------

if (session_status() === PHP_SESSION_NONE) {

    // Cookie is not readable by JavaScript (mitigates XSS-based session theft)
    ini_set('session.cookie_httponly', 1);

    // Cookie is not sent on cross-site requests (CSRF mitigation)
    ini_set('session.cookie_samesite', 'Lax');

    // Reject uninitialized session IDs (session fixation mitigation)
    ini_set('session.use_strict_mode', 1);

    // Never allow session ID to be passed via URL
    ini_set('session.use_only_cookies', 1);

    // Send cookie only over HTTPS when the site is served over HTTPS
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', 1);
    }
}