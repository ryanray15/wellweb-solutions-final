<?php
session_start();
require_once '../../config/database.php';
$db = include '../../config/database.php';

$doctor_id = $_GET['doctor_id'];
$user_id = $_SESSION['user_id'];

$response = ["status" => false, "data" => []];

$query = $db->prepare("
    SELECT a.appointment_id, a.date, a.start_time, a.end_time, s.name AS service_name
    FROM appointments a
    JOIN services s ON a.service_id = s.service_id
    WHERE a.doctor_id = ? AND a.patient_id = ?
");
$query->bind_param("ii", $doctor_id, $user_id);
$query->execute();
$result = $query->get_result();

while ($row = $result->fetch_assoc()) {
    $response["data"][] = [
        "appointment_id" => $row["appointment_id"],
        "details" => "{$row['service_name']} on {$row['date']} from {$row['start_time']} to {$row['end_time']}"
    ];
}
$response["status"] = true;

echo json_encode($response);
