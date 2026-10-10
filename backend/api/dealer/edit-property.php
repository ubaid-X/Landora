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

if ($propertyId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'property_id is required and must be a positive integer.'
    ]);
    exit;
}

$title         = trim($input['title'] ?? '');
$description   = trim($input['description'] ?? '');
$price         = $input['price'] ?? '';
$areaSize      = $input['area_size'] ?? '';
$areaUnit      = trim($input['area_unit'] ?? '');
$propertyType  = trim($input['property_type'] ?? '');
$address       = trim($input['address'] ?? '');
$province      = trim($input['province'] ?? '');
$city          = trim($input['city'] ?? '');
$googleMapUrl  = trim($input['google_map_url'] ?? '');

$required = [
    'title'          => $title,
    'description'    => $description,
    'price'          => $price,
    'area_size'      => $areaSize,
    'area_unit'      => $areaUnit,
    'property_type'  => $propertyType,
    'address'        => $address,
    'province'       => $province,
    'city'           => $city,
    'google_map_url' => $googleMapUrl,
];

foreach ($required as $field => $value) {
    if ($value === '' || $value === null) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required.'
        ]);
        exit;
    }
}

if (!is_numeric($price) || (float) $price <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Price must be a positive number.'
    ]);
    exit;
}

if (!is_numeric($areaSize) || (float) $areaSize <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Area size must be a positive number.'
    ]);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $property = new Property($pdo);

    $existing = $property->findById($propertyId);

    if ($existing === null || (int) $existing['dealer_id'] !== $dealerId) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Property not found or does not belong to you.'
        ]);
        exit;
    }

    $ok = $property->update($propertyId, $dealerId, [
        'title'          => $title,
        'description'    => $description,
        'price'          => $price,
        'area_size'      => $areaSize,
        'area_unit'      => $areaUnit,
        'property_type'  => $propertyType,
        'address'        => $address,
        'province'       => $province,
        'city'           => $city,
        'google_map_url' => $googleMapUrl,
    ]);

    if (!$ok) {
        echo json_encode([
            'success' => true,
            'message' => 'No changes were made to the property.',
            'property_id' => $propertyId
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Property updated successfully.',
        'property_id' => $propertyId
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to update property.'
    ]);
}
