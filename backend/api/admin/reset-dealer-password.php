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

$dealerId        = isset($input['dealer_id']) ? (int) $input['dealer_id'] : 0;
$newPassword     = $input['new_password'] ?? '';
$confirmPassword = $input['confirm_password'] ?? '';

if ($dealerId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'dealer_id is required and must be a positive integer.']);
    exit;
}

if ($newPassword === '' || $confirmPassword === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'New password and confirmation are required.']);
    exit;
}

if (strlen($newPassword) < 8) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']);
    exit;
}

if ($newPassword !== $confirmPassword) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Confirm dealer exists
    $check = $pdo->prepare("SELECT dealer_id, username FROM dealers WHERE dealer_id = :id LIMIT 1");
    $check->execute([':id' => $dealerId]);
    $dealer = $check->fetch();

    if (!$dealer) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Dealer not found.']);
        exit;
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

    $update = $pdo->prepare("UPDATE dealers SET password = :hash WHERE dealer_id = :id");
    $update->execute([':hash' => $newHash, ':id' => $dealerId]);

    echo json_encode([
        'success'   => true,
        'message'   => 'Password reset successfully. Tell the dealer the new password.',
        'dealer_id' => $dealerId,
        'username'  => $dealer['username']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to reset password.']);
}