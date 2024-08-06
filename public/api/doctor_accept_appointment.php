<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $appointment_id = $input['appointment_id'] ?? null;
    $doctor_id = $_SESSION['user_id'] ?? null;  // Ensure session variable is set

    // Debugging logs
    error_log("Received appointment_id: " . var_export($appointment_id, true));
    error_log("Received doctor_id from session: " . var_export($doctor_id, true));

    if ($appointment_id && $doctor_id) {  // Check if both variables are valid
        $query = $db->prepare("UPDATE appointments SET status = 'accepted' WHERE appointment_id = ? AND doctor_id = ?");
        $query->bind_param("ii", $appointment_id, $doctor_id);

        if ($query->execute()) {
            echo json_encode(['status' => true, 'message' => 'Appointment accepted']);
        } else {
            echo json_encode(['status' => false, 'message' => 'Failed to accept appointment']);
        }
    } else {
        echo json_encode(['status' => false, 'message' => 'Invalid appointment ID or session.']);
    }
}
