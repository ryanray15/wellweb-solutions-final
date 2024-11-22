<?php
session_start();
require_once '../../config/database.php';
$db = include '../../config/database.php';

$user_id = $_SESSION['user_id'];
$response = ["status" => false, "data" => []];

$query = $db->prepare("
    SELECT DISTINCT d.user_id AS doctor_id, CONCAT(d.first_name, ' ', d.middle_initial, ' ', d.last_name) AS doctor_name
    FROM appointments a
    JOIN users d ON a.doctor_id = d.user_id
    WHERE a.patient_id = ?
");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();

while ($row = $result->fetch_assoc()) {
    $response["data"][] = $row;
}
$response["status"] = true;

echo json_encode($response);
