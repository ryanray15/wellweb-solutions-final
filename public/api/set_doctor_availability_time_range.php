<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = $_SESSION['user_id'];
    $date = $_POST['date'] ?? null;
    $start_time = $_POST['start_time'] ?? null;
    $end_time = $_POST['end_time'] ?? null;
    $status = $_POST['status'] ?? 'Available';
    $consultation_type = $_POST['consultation_type'] ?? 'both';
    $consultation_duration = $_POST['consultation_duration'] ?? 30;

    try {
        if ($date && $start_time && $end_time) {
            $query = $db->prepare("
                INSERT INTO doctor_availability (doctor_id, date, start_time, end_time, status, consultation_type, consultation_duration)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE start_time = VALUES(start_time), end_time = VALUES(end_time), status = VALUES(status),
                                       consultation_type = VALUES(consultation_type), consultation_duration = VALUES(consultation_duration)
            ");
            $query->bind_param("isssssi", $doctor_id, $date, $start_time, $end_time, $status, $consultation_type, $consultation_duration);

            if ($query->execute()) {
                echo json_encode(['status' => true, 'message' => 'Schedule updated successfully.']);
            } else {
                throw new Exception('Failed to update schedule.');
            }
        } else {
            throw new Exception('Date, start time, and end time are required.');
        }
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'message' => $e->getMessage()]);
    }
}
