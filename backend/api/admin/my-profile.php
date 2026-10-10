<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../middleware/require-admin.php';
require_once __DIR__ . '/../../classes/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Only GET requests are allowed.'
    ]);
    exit;
}

$adminId = (int) $_SESSION['admin_id'];

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $sql = "
        SELECT
            admin_id,
            username,
            created_at
        FROM admins
        WHERE admin_id = :id
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $adminId]);
    $admin = $stmt->fetch();

    if (!$admin) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Admin profile not found.'
        ]);
        exit;
    }

    $admin['admin_id'] = (int) $admin['admin_id'];
    $admin['role'] = 'admin';

    echo json_encode([
        'success' => true,
        'admin' => $admin
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to fetch admin profile.'
    ]);
}