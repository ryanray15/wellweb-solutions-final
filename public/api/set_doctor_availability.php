<?php
require_once '../../config/database.php';
$db = include '../../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);
$doctor_id = $data['doctor_id'];
$time_ranges = $data['time_ranges'] ?? [];

$response = ["status" => false, "message" => ""];

foreach ($time_ranges as $range) {
    $stmt = $db->prepare("
        INSERT INTO doctor_availability (doctor_id, date, start_time, end_time, status, consultation_type, consultation_duration)
        VALUES (?, ?, ?, ?, 'Available', ?, ?)
        ON DUPLICATE KEY UPDATE 
            start_time = VALUES(start_time),
            end_time = VALUES(end_time),
            status = 'Available',
            consultation_type = VALUES(consultation_type),
            consultation_duration = VALUES(consultation_duration)
    ");

    $date = $range['date'];
    $start_time = $range['start_time'];
    $end_time = $range['end_time'];
    $consultation_type = $range['consultation_type'];
    $consultation_duration = $range['consultation_duration'];

    $stmt->bind_param("issssi", $doctor_id, $date, $start_time, $end_time, $consultation_type, $consultation_duration);
    if ($stmt->execute()) {
        $response["status"] = true;
        $response["message"] = "Availability set successfully.";
    } else {
        $response["message"] = "Error setting availability.";
        break;
    }
    $stmt->close();
}

echo json_encode($response);
