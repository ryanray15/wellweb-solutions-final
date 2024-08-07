<?php
session_start(); // Start the session

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once '../../src/autoload.php';
require_once '../../config/database.php';

$db = include '../../config/database.php';
$appointmentController = new AppointmentController($db);

$data = json_decode(file_get_contents("php://input"));

$patient_id = $data->patient_id ?? '';
$doctor_id = $data->doctor_id ?? '';
$service_id = $data->service_id ?? '';
$date = $data->date ?? '';
$time = $data->time ?? '';

// Log the input data
error_log("Scheduling appointment with patient_id: $patient_id, doctor_id: $doctor_id, service_id: $service_id, date: $date, time: $time");

// Verify existence of patient, doctor, and service
$patientExists = $db->query("SELECT * FROM users WHERE user_id = $patient_id AND role = 'patient'")->num_rows > 0;
$doctorExists = $db->query("SELECT * FROM users WHERE user_id = $doctor_id AND role = 'doctor'")->num_rows > 0;
$serviceExists = $db->query("SELECT * FROM services WHERE service_id = $service_id")->num_rows > 0;

error_log("Patient exists: " . ($patientExists ? "Yes" : "No"));
error_log("Doctor exists: " . ($doctorExists ? "Yes" : "No"));
error_log("Service exists: " . ($serviceExists ? "Yes" : "No"));

if (!$patientExists || !$doctorExists || !$serviceExists) {
    echo json_encode(['status' => false, 'message' => 'Invalid patient, doctor, or service ID']);
    exit;
}

// Check doctor availability
$availabilityQuery = $db->prepare("
    SELECT * FROM doctor_availability 
    WHERE doctor_id = ? 
    AND date = ? 
    AND (
        (start_time <= ? AND end_time > ? AND status = '') 
        OR 
        (status = '' AND start_time IS NULL AND end_time IS NULL)
    )
");

// Adjusted the parameter type definition and variables to match
$availabilityQuery->bind_param("isss", $doctor_id, $date, $time, $time);
$availabilityQuery->execute();
$unavailableSlot = $availabilityQuery->get_result()->fetch_assoc();

if ($unavailableSlot) {
    echo json_encode(['status' => false, 'message' => 'The selected time slot is not available.']);
    exit();
}

$response = $appointmentController->schedule($patient_id, $doctor_id, $service_id, $date, $time);

echo json_encode($response);
