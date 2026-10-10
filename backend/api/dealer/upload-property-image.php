<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../middleware/require-dealer.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Property.php';
require_once __DIR__ . '/../../config/config.php'; // Loads ImgBB key

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST requests are allowed.']);
    exit;
}

$dealerId   = (int) $_SESSION['dealer_id'];
$propertyId = isset($_POST['property_id']) ? (int) $_POST['property_id'] : 0;

if ($propertyId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'property_id is required.']);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Image file is required.']);
    exit;
}

$file = $_FILES['image'];

// Max 5 MB
if ($file['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Image size must be 5 MB or less.']);
    exit;
}

// Validate MIME type
$allowedMimes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($file['tmp_name']);

if (!isset($allowedMimes[$mime])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, and WEBP images are allowed.']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $property = new Property($pdo);

    // Check property belongs to dealer
    $existing = $property->findById($propertyId);
    if ($existing === null || (int) $existing['dealer_id'] !== $dealerId) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Property not found or does not belong to you.']);
        exit;
    }

    // Limit: max 10 images per property
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM property_images WHERE property_id = :id");
    $countStmt->execute([':id' => $propertyId]);
    $existingCount = (int) $countStmt->fetchColumn();

    if ($existingCount >= 10) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Maximum of 10 images per property. Please remove an existing image before uploading a new one.'
        ]);
        exit;
    }

    // Encode image to base64 for ImgBB
    $imageData = base64_encode(file_get_contents($file['tmp_name']));

    // Prepare cURL request to ImgBB
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.imgbb.com/1/upload?key=' . IMGBB_API_KEY);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'image' => $imageData,
        'name'  => uniqid() . '_property',
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
            $response  = curl_exec($ch);
    $curlError = curl_error($ch);

    if ($curlError) {
        throw new Exception('Image upload service unavailable: ' . $curlError);
    }

    $responseData = json_decode($response, true);

    if (!isset($responseData['data']['url'])) {
        throw new Exception('Image upload failed. Invalid response from ImgBB.');
    }

    // ImgBB returns a direct URL to the image
    $imageUrl = $responseData['data']['url'];

    // Store the full URL in the database
    $stmt = $pdo->prepare("
        INSERT INTO property_images (property_id, image_path)
        VALUES (:property_id, :image_path)
    ");
    $stmt->execute([
        ':property_id' => $propertyId,
        ':image_path'  => $imageUrl,
    ]);

    http_response_code(201);
    echo json_encode([
        'success'    => true,
        'message'    => 'Image uploaded successfully.',
        'image_id'   => (int) $pdo->lastInsertId(),
        'property_id'=> $propertyId,
        'image_url'  => $imageUrl
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to upload image: ' . $e->getMessage()
    ]);
}