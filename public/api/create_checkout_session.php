<?php
require_once '../../vendor/autoload.php';
require_once '../../config/database.php';

// Set your secret key
\Stripe\Stripe::setApiKey('sk_test_51Q0mWz08GrFUpp2baKJ76Qx92QtXyK8Yd0WCgvmKgONsI81AV0zrbACPouftbwP9uRUyDJZ6qwOViw1yUT1ZpNhq00IoE3Zn2L');  // Replace with your secret key

// Capture the request payload
$input = json_decode(file_get_contents('php://input'), true);

// Extract necessary data
$patientId = $input['patient_id'];
$doctorId = $input['doctor_id'];
$serviceId = $input['service_id'];
$appointmentDate = $input['date'];
$appointmentTime = $input['time'];
$referrer = $input['referrer'];

// Validate the inputs, and query the database for doctor details
$query = $mysqli->prepare("SELECT stripe_account_id FROM users WHERE user_id = ?");
$query->bind_param("i", $doctorId);
$query->execute();
$result = $query->get_result();
$doctor = $result->fetch_assoc();

if (!$doctor || empty($doctor['stripe_account_id'])) {
    echo json_encode(['error' => 'No Stripe account ID found for this doctor']);
    exit();
}

$stripeAccountId = $doctor['stripe_account_id'];

// Define services (this should ideally come from your database)
$services = [
    '1' => ['name' => 'Online Consultation', 'price' => 100000],
    '2' => ['name' => 'Physical Consultation', 'price' => 100000],
];

// Check if the service ID exists
if (!isset($services[$serviceId])) {
    echo json_encode(['error' => 'Invalid service selected']);
    exit();
}

// Create a new Stripe Checkout session
try {
    $checkout_session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'php',
                'product_data' => [
                    'name' => $services[$serviceId]['name'],
                ],
                'unit_amount' => $services[$serviceId]['price'],
            ],
            'quantity' => 1,
        ]],
        'payment_intent_data' => [
            'application_fee_amount' => 10000,  // Example platform fee
            'transfer_data' => [
                'destination' => $stripeAccountId,
            ],
        ],
        'metadata' => [
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'service_id' => $serviceId,
            'date' => $appointmentDate,
            'time' => $appointmentTime,
        ],
        'mode' => 'payment',
        'success_url' => 'http://localhost/dashboard.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => $referrer,
    ]);

    // Return the checkout session URL as a JSON response
    echo json_encode(['checkout_url' => $checkout_session->url]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
