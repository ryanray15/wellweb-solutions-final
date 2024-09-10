<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';
header('Content-Type: application/json');

$doctor_id = $_GET['doctor_id'] ?? null;
$consultation_type = $_GET['consultation_type'] ?? null;  // Get consultation type from request
$specialization_id = $_GET['specialization_id'] ?? null;

if ($doctor_id && $consultation_type && $specialization_id) {
    // Fetch availability for the selected doctor, consultation type, and specialization
    $query = $db->prepare("
        SELECT da.availability_id AS id, da.date, da.start_time, da.end_time, da.status, da.consultation_type
        FROM doctor_availability da
        JOIN doctor_specializations ds ON da.doctor_id = ds.doctor_id
        WHERE da.doctor_id = ? 
        AND da.consultation_type = ?  -- Filter based on consultation type
        AND ds.specialization_id = ?
    ");
    $query->bind_param("isi", $doctor_id, $consultation_type, $specialization_id);
    $query->execute();
    $result = $query->get_result();

    $events = [];
    while ($row = $result->fetch_assoc()) {
        // Set event color based on consultation type
        $color = ($row['consultation_type'] == 'online') ? 'lightblue' : 'green';

        $event = [
            'id' => $row['id'],
            'start' => $row['start_time'] ? $row['date'] . 'T' . $row['start_time'] : $row['date'],
            'end' => $row['end_time'] ? $row['date'] . 'T' . $row['end_time'] : $row['date'],
            'allDay' => !$row['start_time'],
            'title' => ucfirst($row['consultation_type']) . ' Consultation - ' . $row['status'],
            'color' => $color,  // Use consultation type to determine color
            'textColor' => 'white'
        ];
        $events[] = $event;
    }

    echo json_encode(['events' => $events]); // Output a valid JSON response
} else {
    echo json_encode(['error' => 'Missing required parameters']);
}
