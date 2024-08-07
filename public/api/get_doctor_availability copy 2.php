<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';

header('Content-Type: application/json');

$doctor_id = $_GET['doctor_id'] ?? null;  // Fetch doctor_id from GET parameters

// Check if doctor_id is provided
if (!$doctor_id) {
    echo json_encode(['status' => false, 'message' => 'Doctor ID is missing.']);
    exit;
}

// Debugging: Log the doctor_id
error_log("Fetching availability for doctor_id: $doctor_id");

// Fetch availability for the doctor
$query = $db->prepare(
    "SELECT availability_id AS id, date, start_time, end_time, status 
     FROM doctor_availability 
     WHERE doctor_id = ?"
);

$query->bind_param("i", $doctor_id);
$query->execute();
$result = $query->get_result();

// Check if there are any results
if ($result->num_rows === 0) {
    // Debugging: Log that no availability is found
    error_log("No availability found for doctor_id: $doctor_id");
}

// Collect events
$events = [];

while ($row = $result->fetch_assoc()) {
    $event = [
        'id' => $row['id'],
        'start' => $row['start_time'] ? $row['date'] . 'T' . $row['start_time'] : $row['date'],
        'end' => $row['end_time'] ? $row['date'] . 'T' . $row['end_time'] : $row['date'],
        'allDay' => !$row['start_time'],
        'title' => $row['status'],
        'color' => $row['status'] === 'Available' ? 'green' : 'red'
    ];
    $events[] = $event;
}

echo json_encode($events);
