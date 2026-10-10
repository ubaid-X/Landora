<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../middleware/require-admin.php';
require_once __DIR__ . '/../../classes/Database.php';

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

$dealerId = isset($input['dealer_id']) ? (int) $input['dealer_id'] : 0;

if ($dealerId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'dealer_id is required and must be a positive integer.']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // 1. Verify dealer exists
    $check = $pdo->prepare("SELECT dealer_id, username FROM dealers WHERE dealer_id = :id LIMIT 1");
    $check->execute([':id' => $dealerId]);
    $dealer = $check->fetch();

    if (!$dealer) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Dealer not found.']);
        exit;
    }

    // 2. Collect image paths + property IDs BEFORE the delete cascades them away
    $pathsStmt = $pdo->prepare("
        SELECT pi.image_path
        FROM property_images pi
        INNER JOIN properties p ON p.property_id = pi.property_id
        WHERE p.dealer_id = :dealer_id
    ");
    $pathsStmt->execute([':dealer_id' => $dealerId]);
    $imagePaths = $pathsStmt->fetchAll(PDO::FETCH_COLUMN);

    $propsStmt = $pdo->prepare("SELECT property_id FROM properties WHERE dealer_id = :dealer_id");
    $propsStmt->execute([':dealer_id' => $dealerId]);
    $propertyIds = $propsStmt->fetchAll(PDO::FETCH_COLUMN);

    // 3. Delete the dealer (cascades to properties + property_images rows)
    $delete = $pdo->prepare("DELETE FROM dealers WHERE dealer_id = :id");
    $delete->execute([':id' => $dealerId]);

    // 4. Best-effort delete physical files from disk
    $projectRoot = realpath(__DIR__ . '/../../..');
    $deletedFiles = 0;

    if ($projectRoot !== false) {
        // Delete individual files (skip ImgBB URLs starting with http)
        foreach ($imagePaths as $relPath) {
            if (strpos($relPath, 'http://') === 0 || strpos($relPath, 'https://') === 0) {
                continue; // Image lives on ImgBB — nothing to unlink locally
            }

            $abs = $projectRoot . DIRECTORY_SEPARATOR . $relPath;
            if (is_file($abs) && @unlink($abs)) {
                $deletedFiles++;
            }
        }

        // Remove the property directories (they should be empty now)
        $baseUploadDir = $projectRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'property_images';
        foreach ($propertyIds as $pid) {
            $dir = $baseUploadDir . DIRECTORY_SEPARATOR . $pid;
            if (is_dir($dir)) {
                // Clean any leftover files (safety)
                $files = glob($dir . DIRECTORY_SEPARATOR . '*');
                if ($files) {
                    foreach ($files as $f) {
                        if (is_file($f)) @unlink($f);
                    }
                }
                @rmdir($dir);
            }
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Dealer deleted successfully.',
        'dealer_id' => $dealerId,
        'username' => $dealer['username'],
        'files_deleted' => $deletedFiles
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to delete dealer.']);
}