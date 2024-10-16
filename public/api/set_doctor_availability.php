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

    foreach ($time_ranges as $range) {
        $start_time = $range['start_time'];
        $end_time = $range['end_time'];

        // 1. Check for overlapping time ranges in the same date for this doctor
        $query = $db->prepare("
            SELECT availability_id, start_time, end_time, status 
            FROM doctor_availability 
            WHERE doctor_id = ? AND date = ? 
            AND ((start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?))
        ");
        $query->bind_param("isssss", $doctor_id, $date, $end_time, $start_time, $start_time, $end_time);
        $query->execute();
        $result = $query->get_result();

        // 2. Process overlapping ranges: adjust or remove overlaps
        while ($row = $result->fetch_assoc()) {
            // If the new time range fully overlaps an existing one, delete it
            if ($start_time <= $row['start_time'] && $end_time >= $row['end_time']) {
                $deleteQuery = $db->prepare("DELETE FROM doctor_availability WHERE availability_id = ?");
                $deleteQuery->bind_param("i", $row['availability_id']);
                $deleteQuery->execute();
            }
            // If the new time range starts before an existing one and ends during it, adjust the start time of the existing one
            elseif ($start_time <= $row['start_time'] && $end_time < $row['end_time']) {
                $adjustQuery = $db->prepare("UPDATE doctor_availability SET start_time = ? WHERE availability_id = ?");
                $adjustQuery->bind_param("si", $end_time, $row['availability_id']);
                $adjustQuery->execute();
            }
            // If the new time range starts during an existing one and ends after it, adjust the end time of the existing one
            elseif ($start_time > $row['start_time'] && $end_time >= $row['end_time']) {
                $adjustQuery = $db->prepare("UPDATE doctor_availability SET end_time = ? WHERE availability_id = ?");
                $adjustQuery->bind_param("si", $start_time, $row['availability_id']);
                $adjustQuery->execute();
            }
            // If the new time range splits an existing one, adjust the existing one and create a new slot for the remaining time
            elseif ($start_time > $row['start_time'] && $end_time < $row['end_time']) {
                // Adjust the end time of the existing slot
                $adjustQuery = $db->prepare("UPDATE doctor_availability SET end_time = ? WHERE availability_id = ?");
                $adjustQuery->bind_param("si", $start_time, $row['availability_id']);
                $adjustQuery->execute();

                // Create a new slot for the remaining time after the new range
                $insertQuery = $db->prepare("
                    INSERT INTO doctor_availability 
                    (doctor_id, consultation_type, consultation_duration, date, start_time, end_time, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $insertQuery->bind_param("issssss", $doctor_id, $consultation_type, $consultation_duration, $date, $end_time, $row['end_time'], $row['status']);
                $insertQuery->execute();
            }
        }

        // 3. Insert the new time range after resolving any conflicts
        $insertQuery = $db->prepare("
            INSERT INTO doctor_availability 
            (doctor_id, consultation_type, consultation_duration, date, start_time, end_time, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $insertQuery->bind_param("issssss", $doctor_id, $consultation_type, $consultation_duration, $date, $start_time, $end_time, $status);
        $insertQuery->execute();
    }

    echo json_encode(['status' => true, 'message' => 'Availability set successfully']);
} else {
    echo json_encode(['status' => false, 'message' => 'Missing required fields or no time ranges provided']);
}
