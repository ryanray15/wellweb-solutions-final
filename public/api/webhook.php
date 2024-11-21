<?php
require_once '../../vendor/autoload.php';
require_once '../../config/database.php';

$db = include '../../config/database.php';

// Set your secret key from Stripe dashboard
\Stripe\Stripe::setApiKey('sk_test_51Q0mWz08GrFUpp2baKJ76Qx92QtXyK8Yd0WCgvmKgONsI81AV0zrbACPouftbwP9uRUyDJZ6qwOViw1yUT1ZpNhq00IoE3Zn2L');

// Retrieve the raw body from Stripe
$payload = @file_get_contents('php://input');

// Check for Stripe signature
if (!isset($_SERVER['HTTP_STRIPE_SIGNATURE'])) {
    http_response_code(400); // Bad request
    echo "Invalid request. No Stripe signature detected.";
    exit();
}

$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];

// The webhook secret (from the Stripe dashboard)
$endpoint_secret = 'whsec_pLtl0ALs32GdaIqWeRPXbp7pau63aXOR';

try {
    // Verify the event came from Stripe
    $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
} catch (\UnexpectedValueException $e) {
    error_log('Invalid payload');
    http_response_code(400);
    exit();
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    error_log('Invalid signature');
    http_response_code(400);
    exit();
}

// Log the payload and signature header for debugging
file_put_contents('webhook_payload.log', $payload, FILE_APPEND);
file_put_contents('sig_header.log', $sig_header, FILE_APPEND);

// Handle different event types
switch ($event['type']) {
    case 'checkout.session.completed':
        $session = $event['data']['object'];

        // Retrieve appointment details from session metadata
        $appointmentData = [
            'patient_id' => $session->metadata['patient_id'],
            'doctor_id' => $session->metadata['doctor_id'],
            'service_id' => $session->metadata['service_id'],
            'availability_id' => $session->metadata['availability_id'],
            'date' => $session->metadata['date'],
            'start_time' => $session->metadata['start_time'],
            'end_time' => $session->metadata['end_time'],
        ];

        // Log the appointment data for debugging
        file_put_contents('appointment_data.log', print_r($appointmentData, true), FILE_APPEND);

        // Send appointment data to schedule API
        $ch = curl_init('http://localhost/api/schedule_appointment.php');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($appointmentData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Log the response
        error_log("Appointment API Response: $response");
        error_log("HTTP Status Code: $httpcode");

        curl_close($ch);

        if ($httpcode === 200) {
            error_log('Appointment scheduled successfully.');
        } else {
            error_log('Failed to schedule appointment. HTTP code: ' . $httpcode);
        }
        break;

    case 'account.updated':
        $account = $event['data']['object'];

        // Check if the account is fully onboarded
        if ($account->charges_enabled && $account->payouts_enabled) {
            $stripeAccountId = $account->id;

            // Update the doctor's onboarding success in the database
            $query = $db->prepare("
            UPDATE doctor_verifications dv
            JOIN users u ON dv.doctor_id = u.user_id
            SET dv.onboarding_success = 1
            WHERE u.stripe_account_id = ?
        ");
            $query->bind_param("s", $stripeAccountId);
            $query->execute();

            if ($query->affected_rows > 0) {
                error_log("Onboarding success updated for Stripe account: $stripeAccountId");
            } else {
                error_log("Failed to update onboarding success for Stripe account: $stripeAccountId");
            }
        }
        break;

    default:
        error_log("Received unknown event type: " . $event['type']);
        break;
}

http_response_code(200); // Acknowledge receipt of the event to Stripe
