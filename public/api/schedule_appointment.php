<?php
session_start(); // Start the session

// Log the session ID and session data for debugging
error_log("Session ID: " . session_id());
error_log("Session Data: " . print_r($_SESSION, true));

// Check if the request is a webhook request from Stripe
$isWebhookRequest = isset($_SERVER['HTTP_STRIPE_SIGNATURE']);
error_log("Is Webhook Request: " . ($isWebhookRequest ? "true" : "false"));

// Only enforce session authentication if it's not a webhook request
// if (!isset($_SESSION['user_id']) && !$isWebhookRequest) {
//     error_log("Unauthorized access - No valid session or webhook signature.");
//     echo json_encode(['status' => false, 'message' => 'Unauthorized access']);
//     exit();
// }

require_once '../../src/autoload.php';
require_once '../../config/database.php';

$db = include '../../config/database.php';
$appointmentController = new AppointmentController($db);

// Log the payload received
$data = json_decode(file_get_contents("php://input"));

// Extract consultation_type
$consultation_type = $data->consultation_type ?? '';  // Ensure consultation type is passed

// Log the input data for debugging
error_log("Scheduling appointment with patient_id: $patient_id, doctor_id: $doctor_id, service_id: $service_id, consultation_type: $consultation_type, date: $date, time: $time");

error_log("Received Data: " . print_r($data, true));

// Log the Stripe signature header to ensure it's being sent
if ($isWebhookRequest) {
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
    error_log("Stripe Signature Header: " . $sig_header);
    error_log("Stripe Signature: " . print_r($_SERVER['HTTP_STRIPE_SIGNATURE'], true));
}

// Validate the input data
$patient_id = $data->patient_id ?? '';
$doctor_id = $data->doctor_id ?? '';
$service_id = $data->service_id ?? '';
$date = $data->date ?? '';
$time = $data->time ?? ''; // Assume this is in 'HH:MM' format
$appointment_duration = 30; // Duration of appointment in minutes
$consultation_type = $data->consultation_type ?? ''; // Ensure consultation type is passed

// Log the input data for debugging
error_log("Scheduling appointment with patient_id: $patient_id, doctor_id: $doctor_id, service_id: $service_id, date: $date, time: $time");

// Verify existence of patient, doctor, and service
$patientExists = $db->query("SELECT * FROM users WHERE user_id = $patient_id AND role = 'patient'")->num_rows > 0;
$doctorExists = $db->query("SELECT * FROM users WHERE user_id = $doctor_id AND role = 'doctor'")->num_rows > 0;
$serviceExists = $db->query("SELECT * FROM services WHERE service_id = $service_id")->num_rows > 0;

if (!$patientExists || !$doctorExists || !$serviceExists) {
    error_log("Invalid patient, doctor, or service ID");
    echo json_encode(['status' => false, 'message' => 'Invalid patient, doctor, or service ID']);
    exit;
}

error_log("Patient, doctor, and service validated. Proceeding...");

// Fetch doctor name for notifications
$query = "SELECT CONCAT(first_name, ' ', middle_initial, ' ', last_name) as doctor_name FROM users WHERE user_id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param('i', $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$doctor = $result->fetch_assoc();
$doctorName = $doctor['doctor_name'] ?? '';

// Calculate end time of the appointment
$appointmentEndTime = date('H:i:s', strtotime("+$appointment_duration minutes", strtotime($time)));

error_log("Consultation Type: " . $consultation_type);

// Check doctor availability
$availabilityQuery = $db->prepare("
    SELECT * FROM doctor_availability 
    WHERE doctor_id = ? 
    AND date = ? 
    AND status = 'Available'
    AND start_time <= ? 
    AND end_time >= ?
    AND consultation_type = ?
");
$start_time_check = $time;
$end_time_check = $appointmentEndTime;
$availabilityQuery->bind_param("issss", $doctor_id, $date, $start_time_check, $end_time_check, $consultation_type);
$availabilityQuery->execute();
$availableSlot = $availabilityQuery->get_result()->fetch_assoc();

if (!$availableSlot) {
    error_log("The selected time slot is not available.");
    echo json_encode(['status' => false, 'message' => 'The selected time slot is not available.']);
    exit;
}

// Begin transaction
$db->begin_transaction();

try {
    // Schedule appointment
    $response = $appointmentController->schedule($patient_id, $doctor_id, $service_id, $date, $time, $consultation_type);
    error_log("Schedule response: " . print_r($response, true));

    if (!$response['status']) {
        throw new Exception('Appointment scheduling failed');
    }

    // Insert notification
    $query = "INSERT INTO notifications (patient_id, message, type) VALUES (?, ?, 'appointment')";
    $message = "Your appointment with Dr. $doctorName on $date at $time has been scheduled.";
    $stmt = $db->prepare($query);
    $stmt->bind_param("is", $patient_id, $message);
    $stmt->execute();

    // Adjust doctor's availability
    $start_time = $availableSlot['start_time'];
    $end_time = $availableSlot['end_time'];

    error_log("Original availability slot: " . print_r($availableSlot, true));

    // Split availability into new slots around the booked time
    if ($start_time < $time) {
        $preBookingQuery = $db->prepare("
            INSERT INTO doctor_availability (doctor_id, date, start_time, end_time, status, consultation_type) 
            VALUES (?, ?, ?, ?, 'Available', ?)
        ");
        $preBookingQuery->bind_param("issss", $doctor_id, $date, $start_time, $time, $consultation_type);
        $preBookingQuery->execute();
    }

    if ($end_time > $appointmentEndTime) {
        $postBookingQuery = $db->prepare("
            INSERT INTO doctor_availability (doctor_id, date, start_time, end_time, status, consultation_type) 
            VALUES (?, ?, ?, ?, 'Available', ?)
        ");
        $postBookingQuery->bind_param("issss", $doctor_id, $date, $appointmentEndTime, $end_time, $consultation_type);
        $postBookingQuery->execute();
    }

    // Insert booked slot
    $bookingQuery = $db->prepare("
        INSERT INTO doctor_availability (doctor_id, date, start_time, end_time, status) 
        VALUES (?, ?, ?, ?, 'Booked')
    ");
    $bookingQuery->bind_param("isss", $doctor_id, $date, $time, $appointmentEndTime);
    $bookingQuery->execute();

    // Remove original available slot
    $deleteOriginalQuery = $db->prepare("
        DELETE FROM doctor_availability 
        WHERE availability_id = ?
    ");
    $deleteOriginalQuery->bind_param("i", $availableSlot['availability_id']);
    $deleteOriginalQuery->execute();

    // Commit transaction
    $db->commit();
    echo json_encode(['status' => true, 'message' => 'Appointment scheduled successfully']);
} catch (Exception $e) {
    // Rollback transaction
    $db->rollback();
    error_log("Transaction Error: " . $e->getMessage());
    echo json_encode(['status' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
