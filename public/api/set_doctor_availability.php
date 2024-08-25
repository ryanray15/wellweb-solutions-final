<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$doctor_id = $input['doctor_id'] ?? null;
$consultation_type = $input['consultation_type'] ?? null;
$consultation_duration = $input['consultation_duration'] ?? null;
$date = $input['date'] ?? null;
$start_time = $input['start_time'] ?? null;
$end_time = $input['end_time'] ?? null;
$status = $input['status'] ?? 'Available';

if ($doctor_id && $date && $start_time && $end_time && $consultation_type && $consultation_duration) {
    $query = $db->prepare("
        INSERT INTO doctor_availability (doctor_id, consultation_type, consultation_duration, date, start_time, end_time, status)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE start_time = VALUES(start_time), end_time = VALUES(end_time), status = VALUES(status)
    ");
    $query->bind_param("issssss", $doctor_id, $consultation_type, $consultation_duration, $date, $start_time, $end_time, $status);

    if ($query->execute()) {
        echo json_encode(['status' => true, 'message' => 'Availability set successfully']);
    } else {
        echo json_encode(['status' => false, 'message' => 'Failed to set availability']);
    }
} else {
    echo json_encode(['status' => false, 'message' => 'Missing required fields']);
}
