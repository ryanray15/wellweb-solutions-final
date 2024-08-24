<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = $_SESSION['user_id'];
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $status = $_POST['status'] ?? 'Available';
    $consultation_type = $_POST['consultation_type'] ?? 'both';
    $consultation_duration = $_POST['consultation_duration'] ?? 30;

    try {
        if ($start_date && $end_date) {
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);

            // Modify end date for accurate range (exclusive of end)
            $end->modify('-1 day');

            while ($start <= $end) {
                $date = $start->format('Y-m-d');
                $query = $db->prepare("
                    INSERT INTO doctor_availability (doctor_id, date, status, consultation_type, consultation_duration)
                    VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE status = VALUES(status), consultation_type = VALUES(consultation_type), consultation_duration = VALUES(consultation_duration)
                ");
                $query->bind_param("isssi", $doctor_id, $date, $status, $consultation_type, $consultation_duration);
                $query->execute();

                $start->modify('+1 day');
            }

            echo json_encode(['status' => true, 'message' => 'Schedule updated successfully.']);
        } else {
            throw new Exception('Start date and end date are required.');
        }
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'message' => $e->getMessage()]);
    }
}
