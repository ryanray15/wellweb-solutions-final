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
$new_date = $data->date ?? '';
$new_time = $data->time ?? '';
$appointment_duration = 30; // Duration of appointment in minutes

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

// Calculate new end time of the appointment
$new_end_time = date('H:i:s', strtotime("+$appointment_duration minutes", strtotime($new_time)));

// Check doctor availability for the new time slot
$availabilityQuery = $db->prepare("
    SELECT * FROM doctor_availability 
    WHERE doctor_id = ? 
    AND date = ? 
    AND status = 'Available'
    AND start_time <= ? 
    AND end_time >= ?
");

$availabilityQuery->bind_param("isss", $doctor_id, $new_date, $new_time, $new_end_time);
$availabilityQuery->execute();
$availableSlot = $availabilityQuery->get_result()->fetch_assoc();

if (!$availableSlot) {
    echo json_encode(['status' => false, 'message' => 'The selected time slot is not available.']);
    exit();
}

// Begin transaction
$db->begin_transaction();

try {
    // Reschedule the appointment
    $response = $appointmentController->reschedule($appointment_id, $new_date, $new_time);
    if (!$response['status']) {
        throw new Exception('Appointment rescheduling failed');
    }

    // Adjust doctor's availability
    $start_time = $availableSlot['start_time'];
    $end_time = $availableSlot['end_time'];

    // Split availability into new slots around the rescheduled time
    if ($start_time < $new_time) {
        // Create an available slot before the appointment
        $preBookingQuery = $db->prepare("
            INSERT INTO doctor_availability (doctor_id, date, start_time, end_time, status) 
            VALUES (?, ?, ?, ?, 'Available')
        ");
        $pre_start_time = $start_time;
        $pre_end_time = $new_time;
        $preBookingQuery->bind_param("isss", $doctor_id, $new_date, $pre_start_time, $pre_end_time);
        $preBookingQuery->execute();
    }

    if ($end_time > $new_end_time) {
        // Create an available slot after the appointment
        $postBookingQuery = $db->prepare("
            INSERT INTO doctor_availability (doctor_id, date, start_time, end_time, status) 
            VALUES (?, ?, ?, ?, 'Available')
        ");
        $post_start_time = $new_end_time;
        $post_end_time = $end_time;
        $postBookingQuery->bind_param("isss", $doctor_id, $new_date, $post_start_time, $post_end_time);
        $postBookingQuery->execute();
    }

    // Insert the booked slot for the rescheduled time
    $bookingQuery = $db->prepare("
        INSERT INTO doctor_availability (doctor_id, date, start_time, end_time, status) 
        VALUES (?, ?, ?, ?, 'Booked')
    ");
    $booked_start_time = $new_time;
    $booked_end_time = $new_end_time;
    $bookingQuery->bind_param("isss", $doctor_id, $new_date, $booked_start_time, $booked_end_time);
    $bookingQuery->execute();

    // Remove original available slot
    $deleteOriginalQuery = $db->prepare("
        DELETE FROM doctor_availability 
        WHERE availability_id = ?
    ");
    $original_availability_id = $availableSlot['availability_id'];
    $deleteOriginalQuery->bind_param("i", $original_availability_id);
    $deleteOriginalQuery->execute();

    // Commit transaction
    $db->commit();

    echo json_encode(['status' => true, 'message' => 'Appointment rescheduled successfully']);
} catch (Exception $e) {
    // Rollback transaction
    $db->rollback();
    echo json_encode(['status' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
