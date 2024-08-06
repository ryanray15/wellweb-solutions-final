<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = $_SESSION['user_id'];
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $status = $_POST['status'] ?? 'Available';

    try {
        if ($start_date && $end_date) {
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);

            // Correct loop logic to prevent off-by-one error
            while ($start < $end) {
                $date = $start->format('Y-m-d');
                $query = $db->prepare("
                    INSERT INTO doctor_availability (doctor_id, date, status)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE status = VALUES(status)
                ");
                $query->bind_param("iss", $doctor_id, $date, $status);
                $query->execute();

                $start->modify('+1 day');
            }

            echo json_encode(['status' => true, 'message' => 'Date range availability updated successfully.']);
        } else {
            throw new Exception('Start date and end date are required.');
        }
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'message' => $e->getMessage()]);
    }
}
