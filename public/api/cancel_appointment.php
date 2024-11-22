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

// Parse the input data
$data = json_decode(file_get_contents("php://input"));

// Get appointment_id from request data
$appointment_id = $data->appointment_id ?? '';

if (empty($appointment_id)) {
  echo json_encode(['status' => false, 'message' => 'Invalid appointment ID']);
  exit();
}

// Fetch the appointment details to verify it exists and get availability_id
$appointmentQuery = $db->prepare("SELECT * FROM appointments WHERE appointment_id = ?");
$appointmentQuery->bind_param("i", $appointment_id);
$appointmentQuery->execute();
$appointment = $appointmentQuery->get_result()->fetch_assoc();

if (!$appointment) {
  echo json_encode(['status' => false, 'message' => 'Appointment does not exist']);
  exit();
}

// Extract patient_id, doctor_id, and availability_id from the appointment record
$patient_id = $appointment['patient_id'];
$doctor_id = $appointment['doctor_id'];
$availability_id = $appointment['availability_id'];

// Call the cancel method in AppointmentController
$response = $appointmentController->cancel($appointment_id, $availability_id);

if ($response['status']) {
  // Successfully canceled the appointment, create a notification

  // Fetch doctor name for notification message
  $doctorQuery = $db->prepare("SELECT CONCAT(first_name, ' ', middle_initial, ' ', last_name) as name FROM users WHERE user_id = ?");
  $doctorQuery->bind_param("i", $doctor_id);
  $doctorQuery->execute();
  $doctorResult = $doctorQuery->get_result()->fetch_assoc();
  $doctorName = $doctorResult['name'];

  // Create a cancellation notification for the patient
  $query = "INSERT INTO notifications (patient_id, message, type) VALUES (?, ?, 'appointment')";
  $message = "Your appointment with Dr. $doctorName on " . $appointment['date'] . " at " . $appointment['start_time'] . " has been canceled.";
  $stmt = $db->prepare($query);
  $stmt->bind_param("is", $patient_id, $message);
  $stmt->execute();

  echo json_encode(['status' => true, 'message' => 'Appointment canceled successfully']);
} else {
  // Failed to cancel the appointment
  echo json_encode(['status' => false, 'message' => 'Failed to cancel appointment']);
}
