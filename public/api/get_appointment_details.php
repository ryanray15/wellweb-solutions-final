<?php
session_start();
require_once '../../config/database.php';
$db = include '../../config/database.php';

$appointment_id = $_GET['appointment_id'];

$stmt = $db->prepare("SELECT doctor_id, patient_id FROM appointments WHERE appointment_id = ?");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $appointment = $result->fetch_assoc();
    echo json_encode(['success' => true, 'doctor_id' => $appointment['doctor_id'], 'patient_id' => $appointment['patient_id']]);
} else {
    echo json_encode(['success' => false, 'message' => 'Appointment not found.']);
}

$stmt->close();
$db->close();
