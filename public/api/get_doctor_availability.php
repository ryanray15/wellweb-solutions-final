<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $doctor_id = $_SESSION['user_id'];

    $query = $db->prepare("SELECT date, availability_status FROM doctor_availability WHERE doctor_id = ?");
    $query->bind_param("i", $doctor_id);
    $query->execute();
    $result = $query->get_result();

    $events = [];

    while ($row = $result->fetch_assoc()) {
        $events[] = [
            'title' => $row['availability_status'] === 'available' ? 'Available' : 'Unavailable',
            'start' => $row['date'],
            'end' => $row['date'],
            'color' => $row['availability_status'] === 'available' ? 'green' : 'red'
        ];
    }

    echo json_encode($events);
}
