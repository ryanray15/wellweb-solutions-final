<?php
session_start();
require_once '../../config/database.php';
$db = include '../../config/database.php';

$doctor_id = $_GET['doctor_id'];
$response = ["status" => false, "data" => []];

// Check if doctor_id is set and is an integer
if (!isset($doctor_id) || !is_numeric($doctor_id)) {
    $response["message"] = "Invalid doctor ID.";
    echo json_encode($response);
    exit();
}

$query = $db->prepare("
    SELECT availability_id, date, start_time, end_time, status 
    FROM doctor_availability 
    WHERE doctor_id = ?
");

$query->bind_param("i", $doctor_id);
$query->execute();
$result = $query->get_result();

// Check if data exists
if ($result->num_rows === 0) {
    $response["message"] = "No availability found for this doctor ID.";
} else {
    while ($row = $result->fetch_assoc()) {
        $status_color = ($row["status"] === "Available" ? "green" : "red");
        $status_title = ($row["status"] === "Available" ? "Available" : "Booked");

        $response["data"][] = [
            "id" => $row["availability_id"],
            "title" => $status_title,
            "start" => "{$row['date']}T{$row['start_time']}",
            "end" => "{$row['date']}T{$row['end_time']}",
            "color" => $status_color
        ];
    }
    $response["status"] = true;
}

echo json_encode($response);
