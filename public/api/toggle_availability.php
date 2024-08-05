<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = $_SESSION['user_id'];
    $data = json_decode(file_get_contents("php://input"), true);
    $date = $data['date'] ?? null;

    if (!$date) {
        echo json_encode(['status' => false, 'message' => 'Invalid date']);
        exit();
    }

    $query = $db->prepare("SELECT * FROM doctor_availability WHERE doctor_id = ? AND date = ?");
    $query->bind_param("is", $doctor_id, $date);
    $query->execute();
    $availability = $query->get_result()->fetch_assoc();

    if ($availability) {
        // Toggle availability status
        $newStatus = $availability['availability_status'] === 'available' ? 'unavailable' : 'available';
        $updateQuery = $db->prepare("UPDATE doctor_availability SET availability_status = ? WHERE doctor_id = ? AND date = ?");
        $updateQuery->bind_param("sis", $newStatus, $doctor_id, $date);
        $updateQuery->execute();
        echo json_encode(['status' => true, 'message' => "Date $date toggled to $newStatus."]);
    } else {
        $insertQuery = $db->prepare("INSERT INTO doctor_availability (doctor_id, date, availability_status) VALUES (?, ?, 'available')");
        $insertQuery->bind_param("is", $doctor_id, $date);
        $insertQuery->execute();
        echo json_encode(['status' => true, 'message' => "Date $date set to available."]);
    }
}
