<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Only GET requests are allowed.'
    ]);
    exit;
}

$propertyId = isset($_GET['property_id']) ? (int) $_GET['property_id'] : 0;

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

    $sql = "
        SELECT
            p.property_id,
            p.title,
            p.description,
            p.price,
            p.area_size,
            p.area_unit,
            p.property_type,
            p.province,
            p.city,
            p.property_status,
            p.views,
            p.created_at,
            d.business_name AS dealer_business_name
        FROM properties p
        INNER JOIN dealers d ON d.dealer_id = p.dealer_id
        WHERE p.property_id = :id
          AND d.verification_status = 'approved'
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $propertyId]);
    $row = $stmt->fetch();

    if (!$row) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Property not found.'
        ]);
        exit;
    }
           // Increment view counter only if the frontend explicitly signals a new view
    // (?count_view=1). This prevents double counting from browser prefetch.
    if (isset($_GET['count_view']) && $_GET['count_view'] === '1') {
        $pdo->prepare("UPDATE properties SET views = views + 1 WHERE property_id = :id")
            ->execute([':id' => $propertyId]);

        $row['views'] = ((int) $row['views']) + 1;
    }
    $row['property_id'] = (int) $row['property_id'];

    $imgStmt = $pdo->prepare("
        SELECT image_id, property_id, image_path
        FROM property_images
        WHERE property_id = :property_id
        ORDER BY image_id ASC
    ");
    $imgStmt->execute([':property_id' => $propertyId]);
    $images = $imgStmt->fetchAll();

    foreach ($images as &$img) {
        $img['image_id'] = (int) $img['image_id'];
        $img['property_id'] = (int) $img['property_id'];
    }
    unset($img);

    $row['images'] = $images;

    echo json_encode([
        'success' => true,
        'platform_contact' => [
            'phone' => ADMIN_CONTACT_NUMBER,
            'email' => ADMIN_CONTACT_EMAIL,
        ],
        'property' => $row
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to fetch property.'
    ]);
}