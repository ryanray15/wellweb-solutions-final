<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = $_POST['appointment_id'] ?? null;
    $new_date = $_POST['new_date'] ?? null;
    $new_time = $_POST['new_time'] ?? null;
    $doctor_id = $_SESSION['user_id'];

    if ($appointment_id && $new_date && $new_time) {
        $query = $db->prepare("UPDATE appointments SET date = ?, time = ?, status = 'rescheduled' WHERE appointment_id = ? AND doctor_id = ?");
        $query->bind_param("ssii", $new_date, $new_time, $appointment_id, $doctor_id);

        if ($query->execute()) {
            echo json_encode(['status' => true, 'message' => 'Appointment rescheduled']);
        } else {
            echo json_encode(['status' => false, 'message' => 'Failed to reschedule appointment']);
        }
    } else {
        echo json_encode(['status' => false, 'message' => 'Invalid input']);
    }
}
