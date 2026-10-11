<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../middleware/require-dealer.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Property.php';

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

$ext = $allowedMimes[$mime];

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $property = new Property($pdo);

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

        // Build the project root and upload paths
    $projectRoot = dirname(__DIR__, 3);
    $uploadsRoot = $projectRoot . DIRECTORY_SEPARATOR . 'uploads';
    $baseDir     = $uploadsRoot . DIRECTORY_SEPARATOR . 'property_images';

    // Create the base folder if it doesn't exist (survives fresh deployments)
    if (!is_dir($baseDir)) {
        if (!@mkdir($baseDir, 0775, true)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to create upload directory.']);
            exit;
        }
    }

    $propertyDir = $baseDir . DIRECTORY_SEPARATOR . $propertyId;

    if (!is_dir($propertyDir)) {
        if (!mkdir($propertyDir, 0775, true)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to create upload directory.']);
            exit;
        }
    }

    $filename   = bin2hex(random_bytes(8)) . '.' . $ext;
    $targetPath = $propertyDir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Unable to save uploaded image.']);
        exit;
    }

    $relativePath = 'uploads/property_images/' . $propertyId . '/' . $filename;

    $stmt = $pdo->prepare("
        INSERT INTO property_images (property_id, image_path)
        VALUES (:property_id, :image_path)
    ");
    $stmt->execute([
        ':property_id' => $propertyId,
        ':image_path'  => $relativePath,
    ]);

    http_response_code(201);
    echo json_encode([
        'success'    => true,
        'message'    => 'Image uploaded successfully.',
        'image_id'   => (int) $pdo->lastInsertId(),
        'property_id'=> $propertyId,
        'image_path' => $relativePath
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to upload image.']);
}