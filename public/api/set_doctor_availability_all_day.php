<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = $_SESSION['user_id'];
    $date = $_POST['date'] ?? null;
    $status = $_POST['status'] ?? 'Available';

    try {
        if ($date) {
            $query = $db->prepare("
                INSERT INTO doctor_availability (doctor_id, date, status)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE status = VALUES(status)
            ");
            $query->bind_param("iss", $doctor_id, $date, $status);

            if ($query->execute()) {
                echo json_encode(['status' => true, 'message' => 'All-day availability updated successfully.']);
            } else {
                throw new Exception('Failed to update availability.');
            }
        } else {
            throw new Exception('Date is required.');
        }
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'message' => $e->getMessage()]);
    }
}
