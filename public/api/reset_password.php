<?php
session_start();
header('Content-Type: application/json');

// Ensure only logged-in users can access this endpoint
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once '../../src/autoload.php';
require_once '../../src/controllers/AuthController.php';
require_once '../../config/database.php';

try {
    $db = include '../../config/database.php';

    if (!$db) {
        throw new Exception('Failed to connect to the database');
    }

    $auth = new AuthController($db);

    // Get and decode JSON input
    $data = json_decode(file_get_contents("php://input"));

    if (!$data) {
        throw new Exception('Invalid input data');
    }

    // Get the user ID from the session
    $user_id = $_SESSION['user_id'];

    // Retrieve the new password from input
    $new_password = $data->new_password ?? null;

    // Validate input data
    if (empty($new_password)) {
        echo json_encode(['status' => false, 'message' => 'Password cannot be empty']);
        exit();
    }

    // Attempt to reset the password
    $response = $auth->reset_password($user_id, $new_password);

    echo json_encode($response);
} catch (Exception $e) {
    error_log("Exception caught: " . $e->getMessage());
    echo json_encode(['status' => false, 'message' => $e->getMessage()]);
}
