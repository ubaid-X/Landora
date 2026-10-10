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
$newStatus = trim($input['verification_status'] ?? '');

$allowedStatuses = ['pending', 'approved', 'rejected'];

if ($dealerId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'dealer_id is required and must be a positive integer.'
    ]);
    exit;
}

if (!in_array($newStatus, $allowedStatuses, true)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'verification_status must be one of: pending, approved, rejected.'
    ]);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Check dealer exists
    $check = $pdo->prepare("SELECT dealer_id, verification_status FROM dealers WHERE dealer_id = :id LIMIT 1");
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

    $update = $pdo->prepare("
        UPDATE dealers
        SET verification_status = :status
        WHERE dealer_id = :id
    ");
    $update->execute([
        ':status' => $newStatus,
        ':id' => $dealerId
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Dealer status updated.',
        'dealer_id' => $dealerId,
        'previous_status' => $dealer['verification_status'],
        'verification_status' => $newStatus
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to update dealer status.'
    ]);
}