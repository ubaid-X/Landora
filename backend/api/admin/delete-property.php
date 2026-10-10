<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../middleware/require-admin.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Property.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST requests are allowed.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON request.']);
    exit;
}

$propertyId = isset($input['property_id']) ? (int) $input['property_id'] : 0;

if ($propertyId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'property_id is required and must be a positive integer.']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $property = new Property($pdo);

    $existing = $property->findById($propertyId);

    if ($existing === null) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Property not found.']);
        exit;
    }

    // 1. Collect image paths BEFORE the delete cascades them away
    $pathsStmt = $pdo->prepare("SELECT image_path FROM property_images WHERE property_id = :id");
    $pathsStmt->execute([':id' => $propertyId]);
    $imagePaths = $pathsStmt->fetchAll(PDO::FETCH_COLUMN);

    // 2. Delete the property (cascades to property_images rows)
    $ok = $property->deleteById($propertyId);

    if (!$ok) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Unable to delete property.']);
        exit;
    }

    // 3. Best-effort delete physical files + the property's folder
    $projectRoot = realpath(__DIR__ . '/../../..');
    $deletedFiles = 0;

    if ($projectRoot !== false) {
        foreach ($imagePaths as $relPath) {
            if (strpos($relPath, 'http://') === 0 || strpos($relPath, 'https://') === 0) {
                continue;
            }

            $abs = $projectRoot . DIRECTORY_SEPARATOR . $relPath;
            if (is_file($abs) && @unlink($abs)) {
                $deletedFiles++;
            }
        }

        // Remove the property directory itself
        $dir = $projectRoot . DIRECTORY_SEPARATOR . 'uploads'
             . DIRECTORY_SEPARATOR . 'property_images'
             . DIRECTORY_SEPARATOR . $propertyId;

        if (is_dir($dir)) {
            $files = glob($dir . DIRECTORY_SEPARATOR . '*');
            if ($files) {
                foreach ($files as $f) {
                    if (is_file($f)) @unlink($f);
                }
            }
            @rmdir($dir);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Property deleted successfully.',
        'property_id' => $propertyId,
        'files_deleted' => $deletedFiles
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to delete property.']);
}