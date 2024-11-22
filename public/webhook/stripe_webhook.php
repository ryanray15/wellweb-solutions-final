<?php
require_once '../../src/controllers/StripeSecret.php';

// Retrieve the request's body and parse it as JSON
$payload = @file_get_contents('php://input');
$event = null;

try {
    $event = json_decode($payload, true);
} catch (Exception $e) {
    http_response_code(400); // Invalid payload
    exit();
}

// Handle the event
switch ($event['type']) {
    case 'account.updated':
        // Handle account updates
        $account = $event['data']['object'];
        // Check if account onboarding is complete
        if ($account['details_submitted']) {
            // Update your database to reflect the account's status
        }
        break;

    case 'checkout.session.completed':
        // Handle successful payment
        $session = $event['data']['object'];
        // Fulfill the purchase...
        break;

        // ... handle other event types
    default:
        echo 'Received unknown event type ' . $event['type'];
}

http_response_code(200); // Acknowledge receipt of the event
