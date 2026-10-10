<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../middleware/require-dealer.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Property.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Only GET requests are allowed.'
    ]);
    exit;
}

$dealerId = (int) $_SESSION['dealer_id'];

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $property = new Property($pdo);
    $rows = $property->findByDealer($dealerId);

    // Attach images per property
    $imageStmt = $pdo->prepare("
        SELECT image_id, property_id, image_path
        FROM property_images
        WHERE property_id = :property_id
        ORDER BY image_id ASC
    ");

    foreach ($rows as &$row) {
        $row['property_id'] = (int) $row['property_id'];
        $row['dealer_id']   = (int) $row['dealer_id'];

        $imageStmt->execute([':property_id' => $row['property_id']]);
        $images = $imageStmt->fetchAll();

        foreach ($images as &$img) {
            $img['image_id']    = (int) $img['image_id'];
            $img['property_id'] = (int) $img['property_id'];
        }
        unset($img);

        $row['images'] = $images;
    }
    unset($row);

    echo json_encode([
        'success'   => true,
        'dealer_id' => $dealerId,
        'count'     => count($rows),
        'properties'=> $rows
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to fetch properties.'
    ]);
}