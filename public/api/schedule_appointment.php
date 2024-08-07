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
$time = $data->time ?? ''; // Assume this is in 'HH:MM' format
$appointment_duration = 30; // Duration of appointment in minutes

// Log the input data
error_log("Scheduling appointment with patient_id: $patient_id, doctor_id: $doctor_id, service_id: $service_id, date: $date, time: $time");

// Verify existence of patient, doctor, and service
$patientExists = $db->query("SELECT * FROM users WHERE user_id = $patient_id AND role = 'patient'")->num_rows > 0;
$doctorExists = $db->query("SELECT * FROM users WHERE user_id = $doctor_id AND role = 'doctor'")->num_rows > 0;
$serviceExists = $db->query("SELECT * FROM services WHERE service_id = $service_id")->num_rows > 0;

if (!$patientExists || !$doctorExists || !$serviceExists) {
    echo json_encode(['status' => false, 'message' => 'Invalid patient, doctor, or service ID']);
    exit;
}

// Calculate end time of the appointment
$appointmentEndTime = date('H:i:s', strtotime("+$appointment_duration minutes", strtotime($time)));

// Check doctor availability
$availabilityQuery = $db->prepare("
    SELECT * FROM doctor_availability 
    WHERE doctor_id = ? 
    AND date = ? 
    AND status = 'Available'
    AND start_time <= ? 
    AND end_time >= ?
");

// Ensure variables are used
$start_time_check = $time;
$end_time_check = $appointmentEndTime;
$availabilityQuery->bind_param("isss", $doctor_id, $date, $start_time_check, $end_time_check);
$availabilityQuery->execute();
$availableSlot = $availabilityQuery->get_result()->fetch_assoc();

if (!$availableSlot) {
    echo json_encode(['status' => false, 'message' => 'The selected time slot is not available.']);
    exit;
}

// Begin transaction
$db->begin_transaction();

try {
    // Insert the appointment
    $response = $appointmentController->schedule($patient_id, $doctor_id, $service_id, $date, $time);
    if (!$response['status']) {
        throw new Exception('Appointment scheduling failed');
    }

    // Adjust doctor's availability
    $start_time = $availableSlot['start_time'];
    $end_time = $availableSlot['end_time'];

    // Split availability into new slots around the booked time
    if ($start_time < $time) {
        // Create an available slot before the appointment
        $preBookingQuery = $db->prepare("
            INSERT INTO doctor_availability (doctor_id, date, start_time, end_time, status) 
            VALUES (?, ?, ?, ?, 'Available')
        ");
        $pre_start_time = $start_time;
        $pre_end_time = $time;
        $preBookingQuery->bind_param("isss", $doctor_id, $date, $pre_start_time, $pre_end_time);
        $preBookingQuery->execute();
    }

    if ($end_time > $appointmentEndTime) {
        // Create an available slot after the appointment
        $postBookingQuery = $db->prepare("
            INSERT INTO doctor_availability (doctor_id, date, start_time, end_time, status) 
            VALUES (?, ?, ?, ?, 'Available')
        ");
        $post_start_time = $appointmentEndTime;
        $post_end_time = $end_time;
        $postBookingQuery->bind_param("isss", $doctor_id, $date, $post_start_time, $post_end_time);
        $postBookingQuery->execute();
    }

    // Insert the booked slot
    $bookingQuery = $db->prepare("
        INSERT INTO doctor_availability (doctor_id, date, start_time, end_time, status) 
        VALUES (?, ?, ?, ?, 'Booked')
    ");
    $booked_start_time = $time;
    $booked_end_time = $appointmentEndTime;
    $bookingQuery->bind_param("isss", $doctor_id, $date, $booked_start_time, $booked_end_time);
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

    echo json_encode(['status' => true, 'message' => 'Appointment scheduled successfully']);
} catch (Exception $e) {
    // Rollback transaction
    $db->rollback();
    echo json_encode(['status' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
