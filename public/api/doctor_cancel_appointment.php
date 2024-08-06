<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $appointment_id = $input['appointment_id'] ?? null;
    $doctor_id = $_SESSION['user_id'];

    if ($appointment_id) {
        $query = $db->prepare("UPDATE appointments SET status = 'canceled' WHERE appointment_id = ? AND doctor_id = ?");
        $query->bind_param("ii", $appointment_id, $doctor_id);

        if ($query->execute()) {
            echo json_encode(['status' => true, 'message' => 'Appointment cancelled']);
        } else {
            echo json_encode(['status' => false, 'message' => 'Failed to cancel appointment']);
        }
    } else {
        echo json_encode(['status' => false, 'message' => 'Invalid appointment ID']);
    }
}
