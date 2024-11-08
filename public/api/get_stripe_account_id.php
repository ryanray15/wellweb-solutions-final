<?php
// get_stripe_account_id.php

require_once '../../config/database.php';
require_once '../../src/controllers/AuthController.php';

header('Content-Type: application/json');

// Assuming user is authenticated and you can access their user_id from session or token
session_start();
$user_id = $_SESSION['user_id'];  // This would be set when the user logs in

$db = include '../../config/database.php';
$auth = new AuthController($db);

// Fetch Stripe account ID from the database based on the user ID
$stripe_account_id = $auth->getStripeAccountId($user_id);

if ($stripe_account_id) {
    echo json_encode(['stripe_account_id' => $stripe_account_id]);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Stripe account not found']);
}
