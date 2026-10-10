<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../classes/Database.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../config/config.php';


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

    $auth = new Auth($pdo);

    // Find dealer
    $dealer = $auth->findDealerByUsername($username);


    // Case 1: Not registered
    if ($dealer === null) {

        http_response_code(404);

        echo json_encode([
            'success' => false,
            'status' => 'not_registered',
            'message' => "No account found. You haven't registered yet."
        ]);

        exit;
    }


    // Case 2: Wrong password
    if (!$auth->verifyPassword($password, $dealer['password'])) {

        http_response_code(401);

        echo json_encode([
            'success' => false,
            'status' => 'invalid_credentials',
            'message' => 'Invalid username or password.'
        ]);

        exit;
    }


    // Case 3: Pending
    if ($dealer['verification_status'] === 'pending') {

        http_response_code(403);

        echo json_encode([
            'success' => false,
            'status' => 'pending',
            'message' => 'Your account is pending admin approval. Please contact us to request approval.',
            'contact_number' => ADMIN_CONTACT_NUMBER
        ]);

        exit;
    }


    // Case 4: Rejected
    if ($dealer['verification_status'] === 'rejected') {

        http_response_code(403);

        echo json_encode([
            'success' => false,
            'status' => 'rejected',
            'message' => 'Your registration request was rejected by the admin. Please contact us for more information.',
            'contact_number' => ADMIN_CONTACT_NUMBER
        ]);

        exit;
    }


    // Case 5: Approved → create session
    if ($dealer['verification_status'] === 'approved') {

        session_start();

        // Prevent session fixation
        session_regenerate_id(true);

        $_SESSION['dealer_id']       = (int) $dealer['dealer_id'];
        $_SESSION['dealer_username'] = $dealer['username'];
        $_SESSION['role']            = 'dealer';

        http_response_code(200);

        echo json_encode([
            'success' => true,
            'status' => 'approved',
            'message' => 'Login successful.',
            'dealer' => [
                'dealer_id'     => (int) $dealer['dealer_id'],
                'username'      => $dealer['username'],
                'business_name' => $dealer['business_name']
            ]
        ]);

        exit;
    }


    // Fallback: unexpected status value in DB
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unexpected account status.'
    ]);

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to process login.'
    ]);
}