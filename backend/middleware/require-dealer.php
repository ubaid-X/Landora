<?php

require_once __DIR__ . '/../config/session.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'dealer' ||
    !isset($_SESSION['dealer_id'])
) {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Dealer login required.'
    ]);

    exit;
}