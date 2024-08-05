<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = $_SESSION['user_id'];
    $data = json_decode(file_get_contents("php://input"), true);
    $start_date = $data['start_date'] ?? null;
    $end_date = $data['end_date'] ?? null;

    if (!$start_date || !$end_date) {
        echo json_encode(['status' => false, 'message' => 'Invalid date range']);
        exit();
    }

    $period = new DatePeriod(
        new DateTime($start_date),
        new DateInterval('P1D'),
        (new DateTime($end_date))
    );

    foreach ($period as $date) {
        $currentDate = $date->format('Y-m-d');
        $query = $db->prepare("SELECT * FROM doctor_availability WHERE doctor_id = ? AND date = ?");
        $query->bind_param("is", $doctor_id, $currentDate);
        $query->execute();
        $availability = $query->get_result()->fetch_assoc();

        if ($availability) {
            $newStatus = $availability['availability_status'] === 'available' ? 'unavailable' : 'available';
            $updateQuery = $db->prepare("UPDATE doctor_availability SET availability_status = ? WHERE doctor_id = ? AND date = ?");
            $updateQuery->bind_param("sis", $newStatus, $doctor_id, $currentDate);
            $updateQuery->execute();
        } else {
            $insertQuery = $db->prepare("INSERT INTO doctor_availability (doctor_id, date, availability_status) VALUES (?, ?, 'available')");
            $insertQuery->bind_param("is", $doctor_id, $currentDate);
            $insertQuery->execute();
        }
    }

    echo json_encode(['status' => true, 'message' => 'Availability updated successfully.']);
}
