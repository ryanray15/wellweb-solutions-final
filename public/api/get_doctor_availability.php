<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => false, 'message' => 'Unauthorized']);
    exit();
}

$doctor_id = $_SESSION['user_id'];

$query = $db->prepare("SELECT * FROM doctor_availability WHERE doctor_id = ?");
$query->bind_param("i", $doctor_id);
$query->execute();
$result = $query->get_result();

$availabilities = [];

while ($row = $result->fetch_assoc()) {
    $availabilities[] = [
        'id' => $row['availability_id'],
        'title' => 'Available',
        'start' => $row['date'] . 'T' . $row['start_time'],
        'end' => $row['date'] . 'T' . $row['end_time'],
        'backgroundColor' => '#28a745', // Green color for available slots
        'borderColor' => '#28a745'
    ];
}

echo json_encode($availabilities);
