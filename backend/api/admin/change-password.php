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

$adminId = (int) $_SESSION['admin_id'];

$currentPassword = $input['current_password'] ?? '';
$newPassword     = $input['new_password'] ?? '';
$confirmPassword = $input['confirm_password'] ?? '';

if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'All three fields are required.']);
    exit;
}

if (strlen($newPassword) < 8) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters.']);
    exit;
}

if ($newPassword !== $confirmPassword) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'New passwords do not match.']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $stmt = $pdo->prepare("SELECT password FROM admins WHERE admin_id = :id LIMIT 1");
    $stmt->execute([':id' => $adminId]);
    $row = $stmt->fetch();

    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Admin account not found.']);
        exit;
    }

    if (!password_verify($currentPassword, $row['password'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
        exit;
    }

    if (password_verify($newPassword, $row['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'New password must be different from your current password.']);
        exit;
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

    $update = $pdo->prepare("UPDATE admins SET password = :hash WHERE admin_id = :id");
    $update->execute([':hash' => $newHash, ':id' => $adminId]);

    echo json_encode([
        'success' => true,
        'message' => 'Password changed successfully.'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to change password.']);
}