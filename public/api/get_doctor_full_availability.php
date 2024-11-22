<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';
header('Content-Type: application/json');

$doctor_id = $_GET['doctor_id'] ?? null;
$consultation_type = $_GET['consultation_type'] ?? null;

if ($doctor_id && $consultation_type) {
    // Fetch only slots that are 'Available' and match the consultation type
    $query = $db->prepare("
        SELECT da.availability_id AS id, da.date, da.start_time, da.end_time, da.status, da.consultation_type
        FROM doctor_availability da
        WHERE da.doctor_id = ? 
        AND da.consultation_type = ? 
        AND da.status = 'Available'
    ");
    $query->bind_param("is", $doctor_id, $consultation_type);
    $query->execute();
    $result = $query->get_result();

    $events = [];
    while ($row = $result->fetch_assoc()) {
        // Filter out any unavailable slots
        if ($row['status'] !== 'Available') {
            continue;
        }

        $color = $row['consultation_type'] === 'online' ? 'blue' : 'green'; // Blue for online, green for physical

        $events[] = [
            'id' => $row['id'], // Availability ID
            'start' => "{$row['date']}T{$row['start_time']}",
            'end' => "{$row['date']}T{$row['end_time']}",
            'title' => ucfirst($row['consultation_type']) . ' Consultation',
            'color' => $color,
            'textColor' => 'white',
        ];
    }

    echo json_encode($events);
} else {
    echo json_encode([]); // Return empty array if required parameters are missing
}
