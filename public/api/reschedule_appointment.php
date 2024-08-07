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

// Extract input data
$appointment_id = $data->appointment_id ?? '';
$new_date = $data->new_date ?? '';
$new_time = $data->new_time ?? '';

// Log input data
error_log("Rescheduling appointment ID: $appointment_id to new date: $new_date at new time: $new_time");

// Verify the existence of the appointment
$appointmentQuery = $db->prepare("SELECT * FROM appointments WHERE appointment_id = ?");
$appointmentQuery->bind_param("i", $appointment_id);
$appointmentQuery->execute();
$appointment = $appointmentQuery->get_result()->fetch_assoc();

if (!$appointment) {
    echo json_encode(['status' => false, 'message' => 'Appointment does not exist']);
    exit();
}

$doctor_id = $appointment['doctor_id'];

// Check if the new time slot is available
$availabilityQuery = $db->prepare("
    SELECT * FROM doctor_availability 
    WHERE doctor_id = ? 
    AND date = ? 
    AND (
        (start_time <= ? AND end_time > ? AND status = 'Available') 
        OR 
        (start_time IS NULL AND end_time IS NULL AND status = 'Available')
    )
");
$availabilityQuery->bind_param("isss", $doctor_id, $new_date, $new_time, $new_time);
$availabilityQuery->execute();
$availableSlot = $availabilityQuery->get_result()->fetch_assoc();

if (!$availableSlot) {
    echo json_encode(['status' => false, 'message' => 'The selected time slot is not available.']);
    exit();
}

// Update the appointment
$response = $appointmentController->reschedule($appointment_id, $new_date, $new_time);

if ($response['status']) {
    // Update doctor's availability to reflect the rescheduled appointment
    $start_time = $availableSlot['start_time'];
    $end_time = $availableSlot['end_time'];

    // Adjust the available slots
    if ($start_time < $new_time) {
        // Insert a new availability record for the time before the appointment
        $newStartQuery = $db->prepare("
            INSERT INTO doctor_availability (doctor_id, date, start_time, end_time, status)
            VALUES (?, ?, ?, ?, 'Available')
        ");
        $newStartQuery->bind_param("isss", $doctor_id, $new_date, $start_time, $new_time);
        $newStartQuery->execute();
    }

    if ($end_time > $new_time) {
        // Update the existing availability to start after the rescheduled appointment
        $updateAvailabilityQuery = $db->prepare("
            UPDATE doctor_availability 
            SET start_time = ?
            WHERE availability_id = ?
        ");
        $updateAvailabilityQuery->bind_param("si", $new_time, $availableSlot['availability_id']);
        $updateAvailabilityQuery->execute();
    }

    echo json_encode(['status' => true, 'message' => 'Appointment rescheduled successfully.']);
} else {
    echo json_encode(['status' => false, 'message' => 'Failed to reschedule appointment.']);
}
