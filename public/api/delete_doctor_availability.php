<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = $_SESSION['user_id'];
    $data = json_decode(file_get_contents("php://input"), true);
    $event_id = $data['event_id'] ?? null;

    if (!$event_id) {
        echo json_encode(['status' => false, 'message' => 'Invalid event ID']);
        exit();
    }

    $deleteQuery = $db->prepare("DELETE FROM doctor_availability WHERE availability_id = ? AND doctor_id = ?");
    $deleteQuery->bind_param("ii", $event_id, $doctor_id);
    $deleteQuery->execute();

    echo json_encode(['status' => true, 'message' => 'Availability deleted successfully.']);
}
