<?php

require_once '../../src/autoload.php';
require_once '../../vendor/autoload.php'; // Point to the correct Composer autoloader
require_once '../../src/controllers/AuthController.php';
require_once '../../config/database.php';
require_once '../../secrets.php';

header('Content-Type: application/json');

$db = include '../../config/database.php';
$auth = new AuthController($db);

$data = json_decode(file_get_contents("php://input"));

$stripe = new \Stripe\StripeClient('sk_test_51Q0mWz08GrFUpp2baKJ76Qx92QtXyK8Yd0WCgvmKgONsI81AV0zrbACPouftbwP9uRUyDJZ6qwOViw1yUT1ZpNhq00IoE3Zn2L');

// Process user registration...
$first_name = $data->first_name;
$middle_initial = $data->middle_initial;
$last_name = $data->last_name;
$contact_number = $data->contact_number;
$address = $data->address;
$email = $data->email;
$password = $data->password;
$role = $data->role;
$gender = $data->gender ?? '';
$specializations = $data->specializations ?? [];

$response = $auth->register($first_name, $middle_initial, $last_name, $contact_number, $address, $email, $password, $role, $gender, $specializations);

// Assuming the user registration was successful, now create a Stripe account
if ($role === 'doctor' && isset($response['user_id'])) {
    try {
        $account = $stripe->accounts->create([
            'controller' => [
                'stripe_dashboard' => [
                    'type' => 'express',
                ],
                'fees' => [
                    'payer' => 'application'
                ],
                'losses' => [
                    'payments' => 'application'
                ],
            ],
        ]);

        $stripe_account_id = $account->id;

        // Save the Stripe account ID in the database
        if ($auth->saveStripeAccountId($response['user_id'], $stripe_account_id)) {
            echo json_encode([
                'status' => true,
                'message' => 'Doctor registered and Stripe account created',
                'stripe_account_id' => $stripe_account_id,
            ]);
        } else {
            throw new Exception("Failed to save Stripe account ID in the database.");
        }
    } catch (Exception $e) {
        error_log("An error occurred when calling the Stripe API to create an account: {$e->getMessage()}");
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    // If the role is not a doctor, skip Stripe account creation
    echo json_encode($response);
}
