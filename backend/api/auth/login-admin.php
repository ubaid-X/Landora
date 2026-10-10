<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Admin.php';


// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Only POST requests are allowed.'
    ]);

    exit;
}


// Read JSON body
$input = json_decode(file_get_contents('php://input'), true);


// Validate JSON
if (!is_array($input)) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON request.'
    ]);

    exit;
}


// Get input values
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';


// Required fields
if ($username === '' || $password === '') {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Username and password are required.'
    ]);

    exit;
}


try {

    $database = new Database();
    $pdo = $database->getConnection();

    $admin = new Admin($pdo);

    // Find admin
    $adminRow = $admin->findByUsername($username);


    // Case 1: Not found
    if ($adminRow === null) {

        // Same delay as wrong password — prevents user enumeration via timing
        sleep(1);
        
        http_response_code(404);

        echo json_encode([
            'success' => false,
            'status' => 'not_registered',
            'message' => 'No admin account found with that username.'
        ]);

        exit;
    }

        // Case 2: Wrong password
    if (!$admin->verifyPassword($password, $adminRow['password'])) {

        // Slow down brute-force attempts
        sleep(1);

        http_response_code(401);

        echo json_encode([
            'success' => false,
            'status' => 'invalid_credentials',
            'message' => 'Invalid username or password.'
        ]);

        exit;
    }

    // Case 3: Approved admin → create session
    session_start();

    // Prevent session fixation
    session_regenerate_id(true);

    $_SESSION['admin_id']       = (int) $adminRow['admin_id'];
    $_SESSION['admin_username'] = $adminRow['username'];
    $_SESSION['role']           = 'admin';

    http_response_code(200);

    echo json_encode([
        'success' => true,
        'status' => 'approved',
        'message' => 'Admin login successful.',
        'admin' => [
            'admin_id' => (int) $adminRow['admin_id'],
            'username' => $adminRow['username']
        ]
    ]);

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to process admin login.'
    ]);
}