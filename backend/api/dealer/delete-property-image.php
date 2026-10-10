<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../middleware/require-dealer.php';
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

$dealerId = (int) $_SESSION['dealer_id'];
$imageId  = isset($input['image_id']) ? (int) $input['image_id'] : 0;

if ($imageId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'image_id is required and must be a positive integer.']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Fetch image and confirm it belongs to a property owned by this dealer
    $sql = "
        SELECT pi.image_id, pi.property_id, pi.image_path
        FROM property_images pi
        INNER JOIN properties p ON p.property_id = pi.property_id
        WHERE pi.image_id = :id AND p.dealer_id = :dealer_id
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $imageId, ':dealer_id' => $dealerId]);
    $img = $stmt->fetch();

    if (!$img) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Image not found or does not belong to you.']);
        exit;
    }

    // Delete DB row
    $del = $pdo->prepare("DELETE FROM property_images WHERE image_id = :id");
    $del->execute([':id' => $imageId]);

    // Best-effort: delete the file from disk
    $absPath = realpath(__DIR__ . '/../../../') . DIRECTORY_SEPARATOR . $img['image_path'];
    if (is_file($absPath)) {
        @unlink($absPath);
    }

    echo json_encode([
        'success'  => true,
        'message'  => 'Image deleted.',
        'image_id' => $imageId
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to delete image.']);
}