<?php
require_once '../../vendor/autoload.php';
require_once '../../config/database.php';

// Set your secret key from Stripe
\Stripe\Stripe::setApiKey('sk_test_51Q0mWz08GrFUpp2baKJ76Qx92QtXyK8Yd0WCgvmKgONsI81AV0zrbACPouftbwP9uRUyDJZ6qwOViw1yUT1ZpNhq00IoE3Zn2L');

// This function fetches the dynamic ngrok URL
function getNgrokPublicUrl()
{
    $ngrokApiUrl = 'http://127.0.0.1:4040/api/tunnels';
    $ngrokApiResponse = @file_get_contents($ngrokApiUrl);

    if ($ngrokApiResponse === FALSE) {
        // If there's an error fetching the ngrok URL
        error_log("Could not retrieve ngrok URL");
        return null;
    }

    $ngrokData = json_decode($ngrokApiResponse, true);

    // Extract public URL from the ngrok API response
    if (isset($ngrokData['tunnels'][0]['public_url'])) {
        return $ngrokData['tunnels'][0]['public_url'];
    } else {
        error_log("ngrok public URL not found in API response");
        return null;
    }
}

// Fetch the ngrok public URL
$ngrokPublicUrl = getNgrokPublicUrl();

if ($ngrokPublicUrl === null) {
    //die("Error: Could not get ngrok public URL. Please ensure ngrok is running.");
}

// Capture the request payload
$input = json_decode(file_get_contents('php://input'), true);

// Extract and validate necessary data
$patientId = $input['patient_id'] ?? null;
$doctorId = $input['doctor_id'] ?? null;
$serviceId = $input['service_id'] ?? null;
$appointmentDate = $input['date'] ?? null;
$appointmentStartTime = $input['start_time'] ?? null;
$appointmentEndTime = $input['end_time'] ?? null;
$referrer = $input['referrer'] ?? null;

if (!$patientId || !$doctorId || !$serviceId || !$appointmentDate || !$appointmentStartTime || !$appointmentEndTime) {
    error_log("Invalid input data for creating checkout session");
    echo json_encode(['error' => 'Invalid input data']);
    exit();
}

// Get the database connection
$db = include '../../config/database.php';

// Fetch doctor's Stripe account ID from the database
$query = $db->prepare("SELECT stripe_account_id FROM users WHERE user_id = ?");
$query->bind_param("i", $doctorId);
$query->execute();
$result = $query->get_result();
$doctor = $result->fetch_assoc();

if (!$doctor || empty($doctor['stripe_account_id'])) {
    error_log("No Stripe account ID found for this doctor");
    echo json_encode(['error' => 'No Stripe account ID found for this doctor']);
    exit();
}

$stripeAccountId = $doctor['stripe_account_id'];

// Define services (could come from a database)
$services = [
    '1' => ['name' => 'Online Consultation', 'price' => 100000], // Price in cents
    '2' => ['name' => 'Physical Consultation', 'price' => 100000], // Price in cents
];

// Check if the service ID exists
if (!isset($services[$serviceId])) {
    error_log("Invalid service selected: $serviceId");
    echo json_encode(['error' => 'Invalid service selected']);
    exit();
}

try {
    // Create a new Stripe Checkout session
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
            'application_fee_amount' => 10000,  // Example platform fee (PHP 100)
            'transfer_data' => [
                'destination' => $stripeAccountId,
            ],
        ],
        'metadata' => [
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'service_id' => $serviceId,
            'date' => $appointmentDate,
            'start_time' => $appointmentStartTime,
            'end_time' => $appointmentEndTime,
        ],
        'mode' => 'payment',
        'success_url' => $ngrokPublicUrl . '/dashboard.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => $referrer,
    ]);

    // // Create a new Stripe Checkout session
    // $checkout_session = \Stripe\Checkout\Session::create([
    //     'payment_method_types' => ['card'],
    //     'line_items' => [[
    //         'price_data' => [
    //             'currency' => 'php',
    //             'product_data' => [
    //                 'name' => $services[$serviceId]['name'],
    //             ],
    //             'unit_amount' => $services[$serviceId]['price'],
    //         ],
    //         'quantity' => 1,
    //     ]],
    //     'payment_intent_data' => [
    //         'application_fee_amount' => 10000,  // Example platform fee (PHP 100)
    //         'transfer_data' => [
    //             'destination' => $stripeAccountId,
    //         ],
    //     ],
    //     'metadata' => [
    //         'patient_id' => $patientId,
    //         'doctor_id' => $doctorId,
    //         'service_id' => $serviceId,
    //         'date' => $appointmentDate,
    //         'start_time' => $appointmentStartTime,
    //         'end_time' => $appointmentEndTime,
    //     ],
    //     'mode' => 'payment',
    //     'success_url' => '/dashboard.php?session_id={CHECKOUT_SESSION_ID}',
    //     'cancel_url' => $referrer,
    // ]);

    // Return the checkout session URL as a JSON response
    echo json_encode(['checkout_url' => $checkout_session->url]);
} catch (Exception $e) {
    error_log("Error creating Stripe Checkout session: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
