<?php
require_once '../../vendor/autoload.php'; // Load Stripe PHP library
require_once '../../config/database.php'; // Your database connection

\Stripe\Stripe::setApiKey('sk_test_51Q0mWz08GrFUpp2baKJ76Qx92QtXyK8Yd0WCgvmKgONsI81AV0zrbACPouftbwP9uRUyDJZ6qwOViw1yUT1ZpNhq00IoE3Zn2L'); // Replace with your secret key

session_start();

// Ensure the user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    echo json_encode([
        'status' => 403,
        'message' => 'Unauthorized access',
    ]);
    exit();
}

$db = include '../../config/database.php';

try {
    // Fetch the logged-in user's Stripe account ID from the database
    $userId = $_SESSION['user_id'];
    $query = $db->prepare("SELECT stripe_account_id FROM users WHERE user_id = ?");
    $query->bind_param("i", $userId);
    $query->execute();
    $result = $query->get_result();
    $user = $result->fetch_assoc();

    if (!$user || !$user['stripe_account_id']) {
        echo json_encode([
            'status' => 404,
            'message' => 'Stripe account not linked',
        ]);
        exit();
    }

    $stripeAccountId = $user['stripe_account_id'];

    // Generate the Express Dashboard login link
    $loginLink = \Stripe\Account::createLoginLink($stripeAccountId);

    echo json_encode([
        'status' => 200,
        'message' => 'Login link generated successfully',
        'login_url' => $loginLink->url,
    ]);
} catch (\Stripe\Exception\ApiErrorException $e) {
    error_log("Stripe API error: " . $e->getMessage());
    echo json_encode([
        'status' => 500,
        'message' => 'Failed to generate login link',
    ]);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode([
        'status' => 500,
        'message' => 'An error occurred',
    ]);
}
