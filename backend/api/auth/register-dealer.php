<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Dealer.php';


// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Only POST requests are allowed.'
    ]);

    exit;
}


// Read JSON request body
$input = json_decode(file_get_contents('php://input'), true);


// Check valid JSON
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
$email = trim($input['email'] ?? '');

$password = $input['password'] ?? '';
$confirmPassword = $input['confirm_password'] ?? '';

$businessName = trim($input['business_name'] ?? '');
$ownerName = trim($input['owner_name'] ?? '');

$phone = trim($input['phone'] ?? '');
$whatsapp = trim($input['whatsapp'] ?? '');

$fullAddress = trim($input['full_address'] ?? '');
$province = trim($input['province'] ?? '');
$city = trim($input['city'] ?? '');


// Required fields
$requiredFields = [
    'username' => $username,
    'email' => $email,
    'password' => $password,
    'confirm_password' => $confirmPassword,
    'business_name' => $businessName,
    'owner_name' => $ownerName,
    'phone' => $phone,
    'full_address' => $fullAddress,
    'province' => $province,
    'city' => $city
];


foreach ($requiredFields as $field => $value) {

    if ($value === '') {

        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required.'
        ]);

        exit;
    }
}


// Validate username
if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Username must contain 3-50 characters and may only contain letters, numbers, and underscores.'
    ]);

    exit;
}


// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Please provide a valid email address.'
    ]);

    exit;
}


// Validate password
if (strlen($password) < 8) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Password must be at least 8 characters long.'
    ]);

    exit;
}


// Confirm password
if ($password !== $confirmPassword) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Passwords do not match.'
    ]);

    exit;
}


try {

    // Create database connection
    $database = new Database();

    $pdo = $database->getConnection();

    // Create Dealer object
    $dealer = new Dealer($pdo);


    // Check username
    if ($dealer->usernameExists($username)) {

        http_response_code(409);

        echo json_encode([
            'success' => false,
            'message' => 'Username is already registered.'
        ]);

        exit;
    }


    // Check email
    if ($dealer->emailExists($email)) {

        http_response_code(409);

        echo json_encode([
            'success' => false,
            'message' => 'Email is already registered.'
        ]);

        exit;
    }


    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);


    // Dealer data
    $dealerData = [
        'username' => $username,
        'email' => $email,
        'password' => $hashedPassword,
        'business_name' => $businessName,
        'owner_name' => $ownerName,
        'phone' => $phone,
        'whatsapp' => $whatsapp !== '' ? $whatsapp : null,
        'full_address' => $fullAddress,
        'province' => $province,
        'city' => $city
    ];


    // Create dealer
    $dealerId = $dealer->create($dealerData);


    // Success response
    http_response_code(201);

    echo json_encode([
        'success' => true,
        'message' => 'Dealer registration submitted successfully. Your account is pending admin approval.',
        'dealer_id' => $dealerId,
        'verification_status' => 'pending'
    ]);

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to complete dealer registration.'
    ]);
}