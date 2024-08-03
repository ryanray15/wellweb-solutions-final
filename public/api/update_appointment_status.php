<?php
session_start();
header('Content-Type: application/json');

// Restrict access to logged-in doctors only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    echo json_encode(['status' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once '../../config/database.php';

$db = include '../../config/database.php';

$data = json_decode(file_get_contents("php://input"));

$appointment_id = $data->appointment_id ?? null;
$status = $data->status ?? null;

if (!$appointment_id || !$status) {
    echo json_encode(['status' => false, 'message' => 'Invalid input']);
    exit();
}

$query = $db->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ?");
$query->bind_param("si", $status, $appointment_id);

if ($query->execute()) {
    echo json_encode(['status' => true, 'message' => 'Appointment status updated successfully']);
} else {
    echo json_encode(['status' => false, 'message' => 'Failed to update appointment status']);
}
