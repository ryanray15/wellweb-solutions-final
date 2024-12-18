<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';
header('Content-Type: application/json');

$doctor_id = $_GET['doctor_id'] ?? null;
$consultation_type = $_GET['consultation_type'] ?? null;
$specialization_id = $_GET['specialization_id'] ?? null;

if ($doctor_id && $specialization_id) {
    // Convert consultation_type from integer to string
    if ($consultation_type == 1) {
        $consultation_type = 'online';
    } elseif ($consultation_type == 2) {
        $consultation_type = 'physical';
    }

    // Fetch only the available slots for the specific consultation type
    $query = $db->prepare("
        SELECT da.availability_id AS id, da.date, da.start_time, da.end_time, da.status, da.consultation_type
        FROM doctor_availability da
        JOIN doctor_specializations ds ON da.doctor_id = ds.doctor_id
        WHERE da.doctor_id = ? 
        AND da.consultation_type = ? 
        AND ds.specialization_id = ? 
        AND da.status = 'Available'
    ");
    $query->bind_param("iss", $doctor_id, $consultation_type, $specialization_id);
    $query->execute();
    $result = $query->get_result();

    $events = [];
    while ($row = $result->fetch_assoc()) {
        // Set event color based on consultation type
        $color = ($row['consultation_type'] == 'online') ? 'blue' : 'green';

        $event = [
            'id' => $row['id'],
            'start' => $row['start_time'] ? $row['date'] . 'T' . $row['start_time'] : $row['date'],
            'end' => $row['end_time'] ? $row['date'] . 'T' . $row['end_time'] : $row['date'],
            'allDay' => !$row['start_time'],
            'title' => ucfirst($row['consultation_type']) . ' Consultation',
            'color' => $color,  // Use consultation type to determine color
            'textColor' => 'white'
        ];
        $events[] = $event;
    }

    echo json_encode(['events' => $events]); // Output only available slots
} else {
    echo json_encode(['error' => 'Missing required parameters']);
}
