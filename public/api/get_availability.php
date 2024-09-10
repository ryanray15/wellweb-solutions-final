<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';
header('Content-Type: application/json');

$doctor_id = $_GET['doctor_id'] ?? null;
$consultation_type = $_GET['consultation_type'] ?? null;
$specialization_id = $_GET['specialization_id'] ?? null;

if ($doctor_id && $specialization_id) {
    // Fetch all availability for the selected doctor and specialization
    $query = $db->prepare("
        SELECT da.availability_id AS id, da.date, da.start_time, da.end_time, da.status, da.consultation_type
        FROM doctor_availability da
        JOIN doctor_specializations ds ON da.doctor_id = ds.doctor_id
        WHERE da.doctor_id = ? 
        AND ds.specialization_id = ?
    ");
    $query->bind_param("ii", $doctor_id, $specialization_id);
    $query->execute();
    $result = $query->get_result();

    $events = [];
    while ($row = $result->fetch_assoc()) {
        // Set event color based on consultation type and status
        $color = ($row['status'] == 'Available') ?
            (($row['consultation_type'] == 'online') ? 'blue' : 'green') : 'red';

        $event = [
            'id' => $row['id'],
            'start' => $row['start_time'] ? $row['date'] . 'T' . $row['start_time'] : $row['date'],
            'end' => $row['end_time'] ? $row['date'] . 'T' . $row['end_time'] : $row['date'],
            'allDay' => !$row['start_time'],
            'title' => ucfirst($row['consultation_type']) . ' Consultation - ' . $row['status'],
            'color' => $color,  // Use consultation type and status to determine color
            'textColor' => 'white'
        ];
        $events[] = $event;
    }

    echo json_encode(['events' => $events]); // Output all events (Available & Not Available)
} else {
    echo json_encode(['error' => 'Missing required parameters']);
}
