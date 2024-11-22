<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (isset($_SESSION['user_id']) && isset($data['rate'], $data['openTime'], $data['closeTime'])) {
    $doctor_id = $_SESSION['user_id'];
    $rate = $data['rate'] * 100; // Multiply rate by 100 to store as cents
    $openTime = $data['openTime'];
    $closeTime = $data['closeTime'];

    // Save consultation rate
    $rateQuery = $db->prepare("
    INSERT INTO doctor_rates (doctor_id, consultation_rate)
    VALUES (?, ?)
    ON DUPLICATE KEY UPDATE
    consultation_rate = VALUES(consultation_rate)
    ");
    $rateQuery->bind_param("id", $doctor_id, $rate);
    $rateQuery->execute();


    // Save clinic hours
    $hoursQuery = $db->prepare("
        INSERT INTO doctor_clinic_hours (doctor_id, clinic_open_time, clinic_close_time)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE
        clinic_open_time = VALUES(clinic_open_time),
        clinic_close_time = VALUES(clinic_close_time)
    ");
    $hoursQuery->bind_param("iss", $doctor_id, $openTime, $closeTime);
    $hoursQuery->execute();

    if ($rateQuery->affected_rows > 0 || $hoursQuery->affected_rows > 0) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'failed']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
}
