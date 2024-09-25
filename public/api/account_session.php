<?php
// account_session.php

require_once '../../vendor/autoload.php';
require_once '../../secrets.php'; // Assuming this file contains your Stripe secret key.

header('Content-Type: application/json');

$stripe = new \Stripe\StripeClient('sk_test_51Q0mWz08GrFUpp2baKJ76Qx92QtXyK8Yd0WCgvmKgONsI81AV0zrbACPouftbwP9uRUyDJZ6qwOViw1yUT1ZpNhq00IoE3Zn2L');

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json);

    $account_session = $stripe->accountSessions->create([
        'account' => $data->account,
        'components' => [
            'account_onboarding' => [
                'enabled' => true,
            ],
        ],
    ]);

    echo json_encode(array(
        'client_secret' => $account_session->client_secret
    ));
} catch (Exception $e) {
    error_log("An error occurred when calling the Stripe API to create an account session: {$e->getMessage()}");
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
