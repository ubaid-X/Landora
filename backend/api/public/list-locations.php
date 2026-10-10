<?php

header('Content-Type: application/json');

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

    $provinceStmt = $pdo->query("
        SELECT DISTINCT p.province
        FROM properties p
        INNER JOIN dealers d ON d.dealer_id = p.dealer_id
        WHERE d.verification_status = 'approved'
          AND p.province <> ''
        ORDER BY p.province ASC
    ");
    $provinces = $provinceStmt->fetchAll(PDO::FETCH_COLUMN);

    $cityStmt = $pdo->query("
        SELECT DISTINCT p.city
        FROM properties p
        INNER JOIN dealers d ON d.dealer_id = p.dealer_id
        WHERE d.verification_status = 'approved'
          AND p.city <> ''
        ORDER BY p.city ASC
    ");
    $cities = $cityStmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success'   => true,
        'provinces' => $provinces,
        'cities'    => $cities
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to fetch locations.'
    ]);
}