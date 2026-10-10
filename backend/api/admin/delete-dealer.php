<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../middleware/require-admin.php';
require_once __DIR__ . '/../../classes/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Only POST requests are allowed.'
    ]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON request.'
    ]);
    exit;
}

$dealerId = isset($input['dealer_id']) ? (int) $input['dealer_id'] : 0;

if ($dealerId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'dealer_id is required and must be a positive integer.'
    ]);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Check dealer exists
    $check = $pdo->prepare("SELECT dealer_id, username FROM dealers WHERE dealer_id = :id LIMIT 1");
    $check->execute([':id' => $dealerId]);
    $dealer = $check->fetch();

    if (!$dealer) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Dealer not found.'
        ]);
        exit;
    }

    // ON DELETE CASCADE handles properties + property_images
    $delete = $pdo->prepare("DELETE FROM dealers WHERE dealer_id = :id");
    $delete->execute([':id' => $dealerId]);

    echo json_encode([
        'success' => true,
        'message' => 'Dealer deleted successfully.',
        'dealer_id' => $dealerId,
        'username' => $dealer['username']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to delete dealer.'
    ]);
}