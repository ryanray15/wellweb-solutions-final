<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';
header('Content-Type: application/json');

$doctor_id = $_GET['doctor_id'] ?? null;

if ($doctor_id) {
    // Fetch all availability for the selected doctor, including consultation types and statuses, clinic open and close times
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
    ");
    $query->bind_param("i", $doctor_id);
    $query->execute();
    $result = $query->get_result();

    $events = [];
    $slotTimes = null;

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

        // Capture slot min and max times for the calendar
        $slotTimes = [
            'slotMinTime' => $row['slot_min_time'],
            'slotMaxTime' => $row['slot_max_time']
        ];
    }

    // Return events and slot times for calendar rendering
    echo json_encode([
        'events' => $events,
        'slotTimes' => $slotTimes
    ]);
} else {
    echo json_encode([]); // Return empty if doctor ID is not provided
}
