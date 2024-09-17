<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';
header('Content-Type: application/json');

$doctor_id = $_GET['doctor_id'] ?? null;

if ($doctor_id) {
    // Fetch all availability for the selected doctor, including consultation types and statuses
    $query = $db->prepare("
        SELECT da.availability_id AS id, da.date, da.start_time, da.end_time, da.status, da.consultation_type,
               MIN(start_time) OVER() AS min_start_time,
               MAX(end_time) OVER() AS max_end_time,
               MAX(consultation_duration) OVER() AS consultation_duration
        FROM doctor_availability da
        WHERE da.doctor_id = ?
    ");
    $query->bind_param("i", $doctor_id);
    $query->execute();
    $result = $query->get_result();

    $events = [];
    $minStartTime = null;
    $maxEndTime = null;
    $consultationDuration = null;

    while ($row = $result->fetch_assoc()) {
        // Set event color based on consultation type and status
        $color = $row['status'] === 'Available' ? ($row['consultation_type'] === 'online' ? 'blue' : 'green') : ($row['status'] === 'Not Available' ? 'red' : 'gray'); // Gray for 'Not Available'

        $event = [
            'id' => $row['id'],
            'start' => $row['start_time'] ? $row['date'] . 'T' . $row['start_time'] : $row['date'],
            'end' => $row['end_time'] ? $row['date'] . 'T' . $row['end_time'] : $row['date'],
            'allDay' => !$row['start_time'],
            'title' => ($row['status'] === 'Not Available') ? 'Not Available' : ucfirst($row['consultation_type']) . ' Consultation - ' . $row['status'],
            'color' => $color,
            'textColor' => 'white'
        ];
        $events[] = $event;

        // Capture the overall start and end time, and consultation duration
        $minStartTime = $row['min_start_time'];
        $maxEndTime = $row['max_end_time'];
        $consultationDuration = $row['consultation_duration'];
    }

    echo json_encode($events); // Output all events (Available, Booked, Not Available)
} else {
    echo json_encode([]); // Return empty if doctor ID is not provided
}
