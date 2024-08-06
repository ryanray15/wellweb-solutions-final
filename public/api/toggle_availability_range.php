<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = $_SESSION['user_id'];
    $data = json_decode(file_get_contents("php://input"), true);
    $start = $data['start'] ?? null;
    $end = $data['end'] ?? null;
    $status = $data['status'] ?? null;

    if (!$start || !$end) {
        echo json_encode(['status' => false, 'message' => 'Invalid time range']);
        exit();
    }

    $startDate = date('Y-m-d', strtotime($start));
    $startTime = date('H:i:s', strtotime($start));
    $endTime = date('H:i:s', strtotime($end));

    // Insert or update availability for a specific time range
    $query = $db->prepare("INSERT INTO doctor_availability (doctor_id, date, start_time, end_time, status) VALUES (?, ?, ?, ?, 'available') ON DUPLICATE KEY UPDATE availability_status = 'available'");
    $query->bind_param("isss", $doctor_id, $startDate, $startTime, $endTime);
    $query->execute();

    echo json_encode(['status' => true, 'message' => 'Availability set for the selected time range.']);
}
