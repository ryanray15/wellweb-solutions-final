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

if ($doctor_id && $consultation_type && $consultation_duration && $date && !empty($time_ranges)) {
    foreach ($time_ranges as $range) {
        $start_time = $range['start_time'];
        $end_time = $range['end_time'];

        // Detect overlaps with existing slots for the same doctor and date
        $query = $db->prepare("
            SELECT availability_id, start_time, end_time, status 
            FROM doctor_availability 
            WHERE doctor_id = ? AND date = ? 
            AND ((start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?))
        ");
        $query->bind_param("isssss", $doctor_id, $date, $end_time, $start_time, $start_time, $end_time);
        $query->execute();
        $result = $query->get_result();

        while ($row = $result->fetch_assoc()) {
            // Handle cases where the new time slot splits an existing one
            if ($start_time > $row['start_time'] && $end_time < $row['end_time']) {
                // Split the existing slot into two
                $query1 = $db->prepare("
                    UPDATE doctor_availability 
                    SET end_time = ? 
                    WHERE availability_id = ?
                ");
                $query1->bind_param("si", $start_time, $row['availability_id']);
                $query1->execute();

                $query2 = $db->prepare("
                    INSERT INTO doctor_availability 
                    (doctor_id, consultation_type, consultation_duration, date, start_time, end_time, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $query2->bind_param("issssss", $doctor_id, $consultation_type, $consultation_duration, $date, $end_time, $row['end_time'], $row['status']);
                $query2->execute();
            }

            // Remove fully overlapped slots
            elseif ($start_time <= $row['start_time'] && $end_time >= $row['end_time']) {
                $query = $db->prepare("
                    DELETE FROM doctor_availability WHERE availability_id = ?
                ");
                $query->bind_param("i", $row['availability_id']);
                $query->execute();
            }

            // Adjust start or end time if partially overlapping
            elseif ($start_time <= $row['start_time'] && $end_time < $row['end_time']) {
                $query = $db->prepare("
                    UPDATE doctor_availability 
                    SET start_time = ? 
                    WHERE availability_id = ?
                ");
                $query->bind_param("si", $end_time, $row['availability_id']);
                $query->execute();
            } elseif ($start_time > $row['start_time'] && $end_time >= $row['end_time']) {
                $query = $db->prepare("
                    UPDATE doctor_availability 
                    SET end_time = ? 
                    WHERE availability_id = ?
                ");
                $query->bind_param("si", $start_time, $row['availability_id']);
                $query->execute();
            }
        }

        // Insert the new time slot if there are no overlaps or after adjustments
        $query = $db->prepare("
            INSERT INTO doctor_availability 
            (doctor_id, consultation_type, consultation_duration, date, start_time, end_time, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $query->bind_param("issssss", $doctor_id, $consultation_type, $consultation_duration, $date, $start_time, $end_time, $status);
        $query->execute();
    }

    echo json_encode(['status' => true, 'message' => 'Availability set successfully']);
} else {
    echo json_encode(['status' => false, 'message' => 'Missing required fields or no time ranges provided']);
}
