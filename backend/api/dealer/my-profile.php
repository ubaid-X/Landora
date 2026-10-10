<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../middleware/require-dealer.php';
require_once __DIR__ . '/../../classes/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Only GET requests are allowed.'
    ]);
    exit;
}

$dealerId = (int) $_SESSION['dealer_id'];

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
            created_at,
            updated_at
        FROM dealers
        WHERE dealer_id = :id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $dealerId]);
    $dealer = $stmt->fetch();

    if (!$dealer) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Dealer profile not found.'
        ]);
        exit;
    }

    $dealer['dealer_id'] = (int) $dealer['dealer_id'];

    echo json_encode([
        'success' => true,
        'dealer' => $dealer
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to fetch dealer profile.'
    ]);
}