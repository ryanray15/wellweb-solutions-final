<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';
header('Content-Type: application/json');

$doctor_id = $_GET['doctor_id'] ?? null;

if ($doctor_id) {
    // Fetch availability for the selected doctor
    $query = $db->prepare("
        SELECT availability_id AS id, date, start_time, end_time, status,
               MIN(start_time) OVER() AS min_start_time,
               MAX(end_time) OVER() AS max_end_time,
               MAX(consultation_duration) OVER() AS consultation_duration
        FROM doctor_availability 
        WHERE doctor_id = ?
    ");
    $query->bind_param("i", $doctor_id);
    $query->execute();
    $result = $query->get_result();

    $events = [];
    $minStartTime = null;
    $maxEndTime = null;
    $consultationDuration = null;

    while ($row = $result->fetch_assoc()) {
        $event = [
            'id' => $row['id'],
            'start' => $row['start_time'] ? $row['date'] . 'T' . $row['start_time'] : $row['date'],
            'end' => $row['end_time'] ? $row['date'] . 'T' . $row['end_time'] : $row['date'],
            'allDay' => !$row['start_time'],
            'title' => $row['status'],
            'color' => $row['status'] === 'Available' ? 'green' : ($row['status'] === 'Booked' ? 'red' : 'red'),
            'textColor' => 'white'
        ];
        $events[] = $event;

        // Capture the overall start and end time, and consultation duration
        $minStartTime = $row['min_start_time'];
        $maxEndTime = $row['max_end_time'];
        $consultationDuration = $row['consultation_duration'];
    }

    echo json_encode($events);
} else {
    echo json_encode([]);
}
