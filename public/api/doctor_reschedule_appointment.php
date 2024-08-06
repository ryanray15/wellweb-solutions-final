<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $appointment_id = $input['appointment_id'] ?? null;
    $new_date = $input['new_date'] ?? null;
    $new_time = $input['new_time'] ?? null;
    $doctor_id = $_SESSION['user_id'] ?? null;  // Ensure session variable is set

    if ($appointment_id && $new_date && $new_time && $doctor_id) {  // Check all variables are valid
        $query = $db->prepare("UPDATE appointments SET date = ?, time = ?, status = 'rescheduled' WHERE appointment_id = ? AND doctor_id = ?");
        $query->bind_param("ssii", $new_date, $new_time, $appointment_id, $doctor_id);

        if ($query->execute()) {
            echo json_encode(['status' => true, 'message' => 'Appointment rescheduled']);
        } else {
            echo json_encode(['status' => false, 'message' => 'Failed to reschedule appointment']);
        }
    } else {
        echo json_encode(['status' => false, 'message' => 'Invalid input or session.']);
    }
}
