<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';
header('Content-Type: application/json');

$doctor_id = $_GET['doctor_id'] ?? null;
$consultation_type = $_GET['consultation_type'] ?? null;

if ($doctor_id && $consultation_type) { // Check both doctor_id and consultation_type
    $query = $db->prepare("
        SELECT da.availability_id AS id, da.date, da.start_time, da.end_time, da.status, da.consultation_type,
               MIN(start_time) OVER() AS min_start_time,
               MAX(end_time) OVER() AS max_end_time,
               MAX(consultation_duration) OVER() AS consultation_duration
        FROM doctor_availability da
        WHERE da.doctor_id = ?
        AND da.consultation_type = ?
        AND da.status = 'Available';
    ");
    $query->bind_param("is", $doctor_id, $consultation_type);
    $query->execute();
    $result = $query->get_result();

    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = [
            'id' => $row['id'],
            'start' => $row['date'] . 'T' . $row['start_time'],
            'end' => $row['date'] . 'T' . $row['end_time'],
            'title' => ucfirst($row['consultation_type']) . ' Consultation - ' . $row['status'],
            'color' => $row['consultation_type'] === 'online' ? 'blue' : 'green',
            'textColor' => 'white',
        ];
    }

    echo json_encode($events); // Directly return the events array
} else {
    echo json_encode([]); // Return empty array if doctor ID or consultation type is not provided
}
