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

// Get the doctor ID from session
$doctor_id = $_SESSION['user_id'];

$query = $db->prepare("SELECT a.appointment_id, 
                        CONCAT(u.first_name, ' ', u.middle_initial, ' ', u.last_name) AS patient_name, 
                              s.name AS service_name, 
                              a.date, a.time, a.status
                        FROM appointments a
                        JOIN users u ON a.patient_id = u.user_id
                        JOIN services s ON a.service_id = s.service_id
                        WHERE a.doctor_id = ?");
$query->bind_param("i", $doctor_id);
$query->execute();
$result = $query->get_result();

$appointments = [];
while ($row = $result->fetch_assoc()) {
    $appointments[] = $row;
}

echo json_encode(['status' => true, 'appointments' => $appointments]);
