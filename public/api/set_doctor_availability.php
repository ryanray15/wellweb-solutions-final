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

$doctor_id = $_SESSION['user_id'];
$availability = $data->availability ?? [];

$db->query("DELETE FROM doctor_availability WHERE doctor_id = $doctor_id");

$stmt = $db->prepare("INSERT INTO doctor_availability (doctor_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");

foreach ($availability as $day) {
    $stmt->bind_param("isss", $doctor_id, $day['day'], $day['start_time'], $day['end_time']);
    $stmt->execute();
}

echo json_encode(['status' => true, 'message' => 'Availability updated successfully']);
