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
$availabilityId = $input['availability_id'] ?? null;
$appointmentDate = $input['date'] ?? null;
$appointmentStartTime = $input['start_time'] ?? null;
$appointmentEndTime = $input['end_time'] ?? null;
$referrer = $input['referrer'] ?? null;

if (!$patientId || !$doctorId || !$serviceId || !$availabilityId || !$appointmentDate || !$appointmentStartTime || !$appointmentEndTime) {
    error_log("Invalid input data for creating checkout session");
    echo json_encode(['error' => 'Invalid input data']);
    exit();
}

// Get the database connection
$db = include '../../config/database.php';

// Fetch doctor's Stripe account ID and consultation rate from the database
$query = $db->prepare("
    SELECT u.stripe_account_id, dr.consultation_rate
    FROM users u
    LEFT JOIN doctor_rates dr ON u.user_id = dr.doctor_id
    WHERE u.user_id = ?
");
$query->bind_param("i", $doctorId);
$query->execute();
$result = $query->get_result();
$doctor = $result->fetch_assoc();

if (!$doctor || empty($doctor['stripe_account_id']) || $doctor['consultation_rate'] === null) {
    error_log("Doctor information incomplete or missing: Stripe account or consultation rate not found");
    echo json_encode(['error' => 'Doctor information is incomplete']);
    exit();
}

$stripeAccountId = $doctor['stripe_account_id'];
$consultationRate = intval($doctor['consultation_rate']); // Ensure the rate is an integer in cents

// Adjust service price dynamically based on the doctor's consultation rate
$services = [
    '1' => ['name' => 'Online Consultation', 'price' => $consultationRate],
    '2' => ['name' => 'Physical Consultation', 'price' => $consultationRate],
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
                'unit_amount' => $services[$serviceId]['price'], // Use dynamic rate here
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
            'availability_id' => $availabilityId,
            'date' => $appointmentDate,
            'start_time' => $appointmentStartTime,
            'end_time' => $appointmentEndTime,
        ],
        'mode' => 'payment',
        'success_url' => $ngrokPublicUrl . '/dashboard.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => $referrer,
    ]);

    // Return the checkout session URL as a JSON response
    echo json_encode(['checkout_url' => $checkout_session->url]);
} catch (Exception $e) {
    error_log("Error creating Stripe Checkout session: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
