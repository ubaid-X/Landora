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

$dealerId = (int) $_SESSION['dealer_id'];

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

// Required fields
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

// Validate price
if (!is_numeric($price) || (float) $price <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Price must be a positive number.'
    ]);
    exit;
}

// Validate area_size
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

    $propertyId = $property->create($dealerId, [
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

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Property added successfully.',
        'property_id' => $propertyId,
        'dealer_id' => $dealerId,
        'property_status' => 'unsold'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to add property.'
    ]);
}