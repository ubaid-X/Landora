<?php

// Include at the top of every admin-only endpoint.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin' ||
    !isset($_SESSION['admin_id'])
) {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Admin login required.'
    ]);

    exit;
}