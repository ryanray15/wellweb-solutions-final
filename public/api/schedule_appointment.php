<?php
session_start(); // Start the session

require_once '../../src/autoload.php';
require_once '../../config/database.php';
include __DIR__ . '/../../config/config.php';

$db = include '../../config/database.php';
$appointmentController = new AppointmentController($db);

// Log the payload received
$data = json_decode(file_get_contents("php://input"), true);

// Validate and extract the input data
$patient_id = $data['patient_id'] ?? '';
$doctor_id = $data['doctor_id'] ?? '';
$service_id = $data['service_id'] ?? '';
$availability_id = $data['availability_id'] ?? '';
$date = $data['date'] ?? '';
$start_time = $data['start_time'] ?? '';
$end_time = $data['end_time'] ?? '';
$meeting_id = null; // Initialize meeting_id as null for non-online consultations

// Generate a meeting_id if the service is for an online consultation (service_id = 1)
if ($service_id == 1) {
    $meeting_id = createVideoSDKRoom();
}

function createVideoSDKRoom()
{
    $url = "https://api.videosdk.live/v2/rooms";
    $options = [
        "http" => [
            "header" => "Authorization: " . VIDEOSDK_TOKEN . "\r\nContent-Type: application/json\r\n",
            "method" => "POST",
        ]
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    $result = json_decode($response, true);

    if (isset($result['roomId'])) {
        return $result['roomId'];
    } else {
        error_log("Error generating VideoSDK roomId: " . json_encode($result));
        return null;
    }
}


// Validate the existence of patient, doctor, service, and availability slot
$patientExists = $db->query("SELECT 1 FROM users WHERE user_id = $patient_id AND role = 'patient'")->num_rows > 0;
$doctorExists = $db->query("SELECT 1 FROM users WHERE user_id = $doctor_id AND role = 'doctor'")->num_rows > 0;
$serviceExists = $db->query("SELECT 1 FROM services WHERE service_id = $service_id")->num_rows > 0;
$availabilityQuery = $db->prepare("
    SELECT availability_id, status FROM doctor_availability 
    WHERE availability_id = ? AND doctor_id = ? AND date = ? 
    AND start_time = ? AND end_time = ? AND status = 'Available'
");
$availabilityQuery->bind_param("iisss", $availability_id, $doctor_id, $date, $start_time, $end_time);
$availabilityQuery->execute();
$availableSlot = $availabilityQuery->get_result()->fetch_assoc();

if (!$patientExists || !$doctorExists || !$serviceExists || !$availableSlot) {
    echo json_encode(['status' => false, 'message' => 'Invalid patient, doctor, service, or availability slot']);
    exit;
}

// Begin transaction
$db->begin_transaction();

try {
    // Schedule appointment using AppointmentController with the selected availability_id
    $response = $appointmentController->schedule($patient_id, $doctor_id, $service_id, $availability_id, $date, $start_time, $end_time, $meeting_id);

    if (!$response['status']) {
        throw new Exception('Appointment scheduling failed');
    }

    // Update doctor_availability to mark the slot as 'Not Available'
    $updateAvailabilityQuery = $db->prepare("UPDATE doctor_availability SET status = 'Not Available' WHERE availability_id = ?");
    $updateAvailabilityQuery->bind_param("i", $availability_id);
    $updateAvailabilityQuery->execute();

    // Insert notification for the patient
    $query = "INSERT INTO notifications (patient_id, message, type) VALUES (?, ?, 'appointment')";
    $message = "Your appointment with Dr. $doctorName on $date at $start_time and ends at $end_time has been scheduled.";
    $stmt = $db->prepare($query);
    $stmt->bind_param("is", $patient_id, $message);
    $stmt->execute();

    // Commit transaction
    $db->commit();
    echo json_encode(['status' => true, 'message' => 'Appointment scheduled successfully']);
} catch (Exception $e) {
    // Rollback transaction
    $db->rollback();
    error_log("Transaction Error: " . $e->getMessage());
    echo json_encode(['status' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
