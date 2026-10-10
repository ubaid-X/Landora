<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../middleware/require-admin.php';
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

$propertyId = isset($input['property_id']) ? (int) $input['property_id'] : 0;

if ($propertyId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'property_id is required and must be a positive integer.'
    ]);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $property = new Property($pdo);

    $existing = $property->findById($propertyId);

    if ($existing === null) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Property not found.'
        ]);
        exit;
    }

    $ok = $property->deleteById($propertyId);

    if (!$ok) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Unable to delete property.'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Property deleted successfully.',
        'property_id' => $propertyId
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to delete property.'
    ]);
}