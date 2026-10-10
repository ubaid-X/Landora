<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../middleware/require-dealer.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Property.php';

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

$dealerId   = (int) $_SESSION['dealer_id'];
$propertyId = isset($input['property_id']) ? (int) $input['property_id'] : 0;
$newStatus  = trim($input['property_status'] ?? '');

if ($propertyId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'property_id is required and must be a positive integer.'
    ]);
    exit;
}

if (!in_array($newStatus, ['sold', 'unsold'], true)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'property_status must be either sold or unsold.'
    ]);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $property = new Property($pdo);

    // Make sure the property exists and belongs to this dealer
    $existing = $property->findById($propertyId);

    if ($existing === null || (int) $existing['dealer_id'] !== $dealerId) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Property not found or does not belong to you.'
        ]);
        exit;
    }

    $result = $property->toggleStatus($propertyId, $dealerId, $newStatus);

    if ($result === null) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Unable to update property status.'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Property status updated.',
        'property_id' => $propertyId,
        'property_status' => $result
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to update property status.'
    ]);
}