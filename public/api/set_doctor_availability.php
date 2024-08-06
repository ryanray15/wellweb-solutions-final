<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = $_SESSION['user_id'];
    $data = json_decode(file_get_contents("php://input"), true);
    $start = $data['start'];
    $end = $data['end'];
    $allDay = $data['allDay'];
    $status = $data['status'];

    // Insert availability based on whether it's all day or a specific time range
    $query = $db->prepare(
        "INSERT INTO doctor_availability (doctor_id, date, start_time, end_time, status)
        VALUES (?, ?, ?, ?, ?)"
    );

    if ($allDay) {
        // If it's an all-day event, set start and end times to NULL
        $query->bind_param("issss", $doctor_id, $start, NULL, NULL, $status);
    } else {
        // Parse the start and end datetime strings to extract date and time
        $startDate = new DateTime($start);
        $endDate = new DateTime($end);
        $date = $startDate->format('Y-m-d');
        $startTime = $startDate->format('H:i:s');
        $endTime = $endDate->format('H:i:s');
        $query->bind_param("issss", $doctor_id, $date, $startTime, $endTime, $status);
    }

    if ($query->execute()) {
        echo json_encode(['status' => true, 'message' => 'Availability set successfully.']);
    } else {
        echo json_encode(['status' => false, 'message' => 'Failed to set availability.']);
    }
}
