<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../middleware/require-admin.php';
require_once __DIR__ . '/../../classes/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Only GET requests are allowed.'
    ]);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $sql = "
        SELECT
            dealer_id,
            username,
            email,
            business_name,
            owner_name,
            phone,
            whatsapp,
            full_address,
            province,
            city,
            verification_status,
            created_at
        FROM dealers
        ORDER BY created_at DESC
    ";

    $stmt = $pdo->query($sql);
    $dealers = $stmt->fetchAll();

    // Cast dealer_id to int for clean JSON
    foreach ($dealers as &$dealer) {
        $dealer['dealer_id'] = (int) $dealer['dealer_id'];
    }
    unset($dealer);

    echo json_encode([
        'success' => true,
        'count' => count($dealers),
        'dealers' => $dealers
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to fetch dealers.'
    ]);
}