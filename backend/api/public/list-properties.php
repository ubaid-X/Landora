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

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Build dynamic WHERE clause
    $where  = ["d.verification_status = 'approved'"];
    $params = [];

        // Common province abbreviations → full province name
    $provinceAliases = [
        'kpk'         => 'Khyber Pakhtunkhwa',
        'kp'          => 'Khyber Pakhtunkhwa',
        'khyber'      => 'Khyber Pakhtunkhwa',
        'punjab'      => 'Punjab',
        'sindh'       => 'Sindh',
        'balochistan' => 'Balochistan',
        'baluchistan' => 'Balochistan',
        'gb'          => 'Gilgit-Baltistan',
        'gilgit'      => 'Gilgit-Baltistan',
        'ajk'         => 'Azad Jammu and Kashmir',
        'kashmir'     => 'Azad Jammu and Kashmir',
        'ict'         => 'Islamabad Capital Territory',
        'islamabad'   => 'Islamabad Capital Territory',
    ];

    // Search term (comma-separated tokens, AND logic, partial match)
    $q = trim($_GET['q'] ?? '');
    if ($q !== '') {
        $tokens = array_filter(array_map('trim', explode(',', $q)));

        // Expand known province abbreviations to their full names
        $tokens = array_map(function ($token) use ($provinceAliases) {
            $lower = strtolower($token);
            return $provinceAliases[$lower] ?? $token;
        }, $tokens);

        $i = 0;
        foreach ($tokens as $token) {
            $key = ':q' . $i;
            $where[] = "(p.province LIKE {$key} OR p.city LIKE {$key})";
            $params[$key] = '%' . $token . '%';
            $i++;
        }
    }

    // Property type filter
    $type = trim($_GET['type'] ?? '');
    if ($type !== '' && strtolower($type) !== 'all') {
        $where[] = "p.property_type = :type";
        $params[':type'] = $type;
    }

    // Status filter
    $status = trim($_GET['status'] ?? '');
    if ($status !== '' && strtolower($status) !== 'all') {
        $where[] = "p.property_status = :status";
        $params[':status'] = $status;
    }

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
            p.created_at,
            d.business_name AS dealer_business_name
        FROM properties p
        INNER JOIN dealers d ON d.dealer_id = p.dealer_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY p.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $imageStmt = $pdo->prepare("
        SELECT image_id, property_id, image_path
        FROM property_images
        WHERE property_id = :property_id
        ORDER BY image_id ASC
    ");

    foreach ($rows as &$row) {
        $row['property_id'] = (int) $row['property_id'];

        $imageStmt->execute([':property_id' => $row['property_id']]);
        $images = $imageStmt->fetchAll();

        foreach ($images as &$img) {
            $img['image_id'] = (int) $img['image_id'];
            $img['property_id'] = (int) $img['property_id'];
        }
        unset($img);

        $row['images'] = $images;
    }
    unset($row);

    echo json_encode([
        'success' => true,
        'count' => count($rows),
        'platform_contact' => [
            'phone' => ADMIN_CONTACT_NUMBER,
            'email' => ADMIN_CONTACT_EMAIL,
        ],
        'properties' => $rows
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to fetch properties.'
    ]);
}