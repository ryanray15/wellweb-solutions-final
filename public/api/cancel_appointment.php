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

$appointment_id = $data->appointment_id ?? '';

// Verify the existence of the appointment
$appointmentQuery = $db->prepare("SELECT * FROM appointments WHERE appointment_id = ?");
$appointmentQuery->bind_param("i", $appointment_id);
$appointmentQuery->execute();
$appointment = $appointmentQuery->get_result()->fetch_assoc();

if (!$appointment) {
  echo json_encode(['status' => false, 'message' => 'Appointment does not exist']);
  exit();
}

$patient_id = $appointment['patient_id'];
$doctor_id = $appointment['doctor_id'];

$response = $appointmentController->cancel($appointment_id);

if ($response['status']) {
  // After successfully canceling an appointment
  $doctorQuery = $db->prepare("SELECT CONCAT(first_name, ' ', middle_initial, ' ', last_name) as name FROM users WHERE user_id = ?");
  $doctorQuery->bind_param("i", $doctor_id);
  $doctorQuery->execute();
  $doctorResult = $doctorQuery->get_result()->fetch_assoc();
  $doctorName = $doctorResult['name'];

  $query = "INSERT INTO notifications (patient_id, message, type) VALUES (?, ?, 'appointment')";
  $message = "Your appointment with Dr. $doctorName on " . $appointment['date'] . " at " . $appointment['time'] . " has been canceled.";
  $stmt = $db->prepare($query);
  $stmt->bind_param(
    "is",
    $patient_id,
    $message
  );
  $stmt->execute();
}

echo json_encode($response);
