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
$start_time = $data->time ?? '';

// Calculate end time (let's assume the appointment lasts 30 minutes)
$end_time = date("H:i:s", strtotime('+30 minutes', strtotime($start_time)));

// Log the input data
error_log("Scheduling appointment with patient_id: $patient_id, doctor_id: $doctor_id, service_id: $service_id, date: $date, start_time: $start_time, end_time: $end_time");

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

// Check if the time slot is available
$availabilityQuery = $db->prepare("
    SELECT * FROM doctor_availability 
    WHERE doctor_id = ? 
    AND date = ? 
    AND start_time <= ? 
    AND end_time >= ? 
    AND status = 'Available'
");
$availabilityQuery->bind_param("isss", $doctor_id, $date, $start_time, $end_time);
$availabilityQuery->execute();
$availabilitySlot = $availabilityQuery->get_result()->fetch_assoc();

if (!$availabilitySlot) {
    echo json_encode(['status' => false, 'message' => 'The selected time slot is not available.']);
    exit;
}

// Update the existing availability slot
$original_start_time = $availabilitySlot['start_time'];
$original_end_time = $availabilitySlot['end_time'];

// If the appointment time matches the availability time exactly
if ($original_start_time == $start_time && $original_end_time == $end_time) {
    // Update the availability slot to "Not Available"
    $updateAvailabilityQuery = $db->prepare("
        UPDATE doctor_availability 
        SET status = 'Not Available'
        WHERE availability_id = ?
    ");
    $updateAvailabilityQuery->bind_param("i", $availabilitySlot['availability_id']);
    $updateAvailabilityQuery->execute();
} else {
    // Split the availability slot into before and after the appointment time
    $updateAvailabilityQuery = $db->prepare("
        UPDATE doctor_availability 
        SET end_time = ?
        WHERE availability_id = ?
    ");
    $updateAvailabilityQuery->bind_param("si", $start_time, $availabilitySlot['availability_id']);
    $updateAvailabilityQuery->execute();

    // Insert a new record for the remaining time after the appointment
    $insertNewSlotQuery = $db->prepare("
        INSERT INTO doctor_availability (doctor_id, date, start_time, end_time, status)
        VALUES (?, ?, ?, ?, 'Available')
    ");
    $insertNewSlotQuery->bind_param("isss", $doctor_id, $date, $end_time, $original_end_time);
    $insertNewSlotQuery->execute();
}

// Schedule the appointment
$response = $appointmentController->schedule($patient_id, $doctor_id, $service_id, $date, $start_time);

echo json_encode($response);
