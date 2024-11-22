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

$data = json_decode(file_get_contents("php://input"), true);

// Validate and extract input data
$appointment_id = $data['appointment_id'] ?? '';
$availability_id = $data['availability_id'] ?? '';
$new_date = $data['date'] ?? '';
$new_start_time = $data['start_time'] ?? '';
$new_end_time = $data['end_time'] ?? '';

// Validate the existence of the appointment
$appointmentQuery = $db->prepare("SELECT * FROM appointments WHERE appointment_id = ?");
$appointmentQuery->bind_param("i", $appointment_id);
$appointmentQuery->execute();
$appointment = $appointmentQuery->get_result()->fetch_assoc();

if (!$appointment) {
    echo json_encode(['status' => false, 'message' => 'Appointment does not exist']);
    exit();
}

$doctor_id = $appointment['doctor_id'];
$patient_id = $appointment['patient_id'];
$service_id = $appointment['service_id'];
$old_availability_id = $appointment['availability_id']; // Get the old availability ID

// Validate the availability slot
$availabilityQuery = $db->prepare("
    SELECT availability_id, status 
    FROM doctor_availability 
    WHERE availability_id = ? 
    AND doctor_id = ? 
    AND date = ? 
    AND start_time = ? 
    AND end_time = ? 
    AND status = 'Available'
");
$availabilityQuery->bind_param("iisss", $availability_id, $doctor_id, $new_date, $new_start_time, $new_end_time);
$availabilityQuery->execute();
$availableSlot = $availabilityQuery->get_result()->fetch_assoc();

if (!$availableSlot) {
    echo json_encode(['status' => false, 'message' => 'The selected time slot is not available']);
    exit();
}

// Begin transaction
$db->begin_transaction();

try {
    // Update the `availability_id`, `date`, `start_time`, and `end_time` in the appointments table
    $updateAppointmentQuery = $db->prepare("
        UPDATE appointments 
        SET availability_id = ?, date = ?, start_time = ?, end_time = ?, status = 'rescheduled'
        WHERE appointment_id = ?
    ");
    $updateAppointmentQuery->bind_param("isssi", $availability_id, $new_date, $new_start_time, $new_end_time, $appointment_id);
    $updateAppointmentQuery->execute();

    // Mark the old availability slot as 'Available' again
    $oldAvailabilityQuery = $db->prepare("UPDATE doctor_availability SET status = 'Available' WHERE availability_id = ?");
    $oldAvailabilityQuery->bind_param("i", $old_availability_id);
    $oldAvailabilityQuery->execute();

    // Mark the new availability slot as 'Not Available'
    $updateAvailabilityQuery = $db->prepare("UPDATE doctor_availability SET status = 'Not Available' WHERE availability_id = ?");
    $updateAvailabilityQuery->bind_param("i", $availability_id);
    $updateAvailabilityQuery->execute();

    // Notify the patient about the rescheduled appointment
    $doctorQuery = $db->prepare("SELECT CONCAT(first_name, ' ', middle_initial, ' ', last_name) as name FROM users WHERE user_id = ?");
    $doctorQuery->bind_param("i", $doctor_id);
    $doctorQuery->execute();
    $doctorResult = $doctorQuery->get_result()->fetch_assoc();
    $doctorName = $doctorResult['name'];

    $notificationQuery = $db->prepare("
        INSERT INTO notifications (patient_id, message, type) 
        VALUES (?, ?, 'appointment')
    ");
    $notificationMessage = "Your appointment with Dr. $doctorName has been rescheduled to $new_date from $new_start_time to $new_end_time.";
    $notificationQuery->bind_param("is", $patient_id, $notificationMessage);
    $notificationQuery->execute();

    // Commit transaction
    $db->commit();
    echo json_encode(['status' => true, 'message' => 'Appointment rescheduled successfully']);
} catch (Exception $e) {
    // Rollback transaction in case of error
    $db->rollback();
    error_log("Transaction Error: " . $e->getMessage());
    echo json_encode(['status' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
