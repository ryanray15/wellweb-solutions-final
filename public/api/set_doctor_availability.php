<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';
header('Content-Type: application/json');

// Capture the input data
$input = json_decode(file_get_contents('php://input'), true);

$doctor_id = $input['doctor_id'] ?? null;
$consultation_type = $input['consultation_type'] ?? null;
$consultation_duration = $input['consultation_duration'] ?? null;
$date = $input['date'] ?? null;
$status = $input['status'] ?? 'Available';
$time_ranges = $input['time_ranges'] ?? []; // The array of time ranges

// Ensure all required data is present
if ($doctor_id && $consultation_type && $consultation_duration && $date && !empty($time_ranges)) {

    // Loop through each time range provided
    foreach ($time_ranges as $range) {
        $start_time = $range['start_time'];
        $end_time = $range['end_time'];

        // Insert each time range directly into the database
        $insertQuery = $db->prepare("
            INSERT INTO doctor_availability 
            (doctor_id, consultation_type, consultation_duration, date, start_time, end_time, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $insertQuery->bind_param("issssss", $doctor_id, $consultation_type, $consultation_duration, $date, $start_time, $end_time, $status);
        $insertQuery->execute();
    }

    // Return success message after processing all time ranges
    echo json_encode(['status' => true, 'message' => 'Availability set successfully']);
} else {
    // Handle the case where required fields are missing
    echo json_encode(['status' => false, 'message' => 'Missing required fields or no time ranges provided']);
}
