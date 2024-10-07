<?php
require_once '../../vendor/autoload.php';
require_once '../../config/database.php';

// Set your secret key from Stripe dashboard
\Stripe\Stripe::setApiKey('sk_test_51Q0mWz08GrFUpp2baKJ76Qx92QtXyK8Yd0WCgvmKgONsI81AV0zrbACPouftbwP9uRUyDJZ6qwOViw1yUT1ZpNhq00IoE3Zn2L');

// Retrieve the raw body from Stripe
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];

// The webhook secret (from the Stripe dashboard)
$endpoint_secret = 'whsec_9b3a4d7331b3c23633a41051a138023172d70a9fdbb34bde4277fc499ebe28c9'; // Replace with your webhook secret

// Log the payload and signature header for debugging
file_put_contents('webhook_payload.log', $payload, FILE_APPEND);
file_put_contents('sig_header.log', $sig_header, FILE_APPEND);

// Verify that the event came from Stripe
try {
    $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
} catch (\UnexpectedValueException $e) {
    // Invalid payload
    error_log('Invalid payload');
    http_response_code(400);
    exit();
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    // Invalid signature
    error_log('Invalid signature');
    http_response_code(400);
    exit();
}

// Handle the checkout.session.completed event
if ($event['type'] == 'checkout.session.completed') {
    $session = $event['data']['object'];

    // Retrieve appointment details from session metadata
    $appointmentData = [
        'patient_id' => $session->metadata['patient_id'],
        'doctor_id' => $session->metadata['doctor_id'],
        'service_id' => $session->metadata['service_id'],
        'date' => $session->metadata['date'],
        'time' => $session->metadata['time'],
    ];

    // Log the appointment data for debugging
    file_put_contents('appointment_data.log', print_r($appointmentData, true), FILE_APPEND);

    // Make a request to the existing appointment scheduling API
    $ch = curl_init('http://localhost/api/schedule_appointment.php'); // Adjust the path accordingly
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
}

http_response_code(200); // Acknowledge receipt of the event to Stripe
