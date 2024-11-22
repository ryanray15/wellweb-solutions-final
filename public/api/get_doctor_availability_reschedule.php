<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';
header('Content-Type: application/json');

$doctor_id = $_GET['doctor_id'] ?? null;
$consultation_type = $_GET['consultation_type'] ?? null;

if ($doctor_id && $consultation_type) { // Check both doctor_id and consultation_type
    $query = $db->prepare("
        SELECT 
            da.availability_id AS id, 
            da.date, 
            da.start_time, 
            da.end_time, 
            da.status, 
            da.consultation_type,
            dch.clinic_open_time AS slot_min_time,
            dch.clinic_close_time AS slot_max_time
        FROM doctor_availability da
        JOIN doctor_clinic_hours dch ON da.doctor_id = dch.doctor_id
        WHERE da.doctor_id = ?
        AND da.consultation_type = ?
        AND da.status = 'Available';
    ");
    $query->bind_param("is", $doctor_id, $consultation_type);
    $query->execute();
    $result = $query->get_result();

    $events = [];
    $slotTimes = null;

    while ($row = $result->fetch_assoc()) {
        $events[] = [
            'id' => $row['id'],
            'start' => $row['date'] . 'T' . $row['start_time'],
            'end' => $row['date'] . 'T' . $row['end_time'],
            'title' => ucfirst($row['consultation_type']) . ' Consultation - ' . $row['status'],
            'color' => $row['consultation_type'] === 'online' ? 'blue' : 'green',
            'textColor' => 'white',
        ];

        // Capture slot times for response (only once, as they are the same for all rows)
        if ($slotTimes === null) {
            $slotTimes = [
                'slotMinTime' => $row['slot_min_time'],
                'slotMaxTime' => $row['slot_max_time']
            ];
        }
    }

    // Return events and slot times for calendar rendering
    echo json_encode([
        'events' => $events,
        'slotTimes' => $slotTimes
    ]);
} else {
    echo json_encode([]); // Return empty array if doctor ID or consultation type is not provided
}
