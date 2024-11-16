<?php
session_start();
require_once '../../config/database.php';
$db = include '../../config/database.php';

$doctor_id = $_GET['doctor_id'];
$user_id = $_SESSION['user_id'];
$consultation_type = $_GET['consultation_type'] ?? null; // Expected to be 'online' or 'physical'

$response = ["status" => false, "data" => []];

// Prepare the base query with consultation type and status filters
$queryString = "
    SELECT a.appointment_id, a.date, a.start_time, a.end_time, s.name AS service_name, a.status
    FROM appointments a
    JOIN services s ON a.service_id = s.service_id
    WHERE a.doctor_id = ? 
      AND a.patient_id = ? 
      AND a.status IN ('pending', 'no show')
";

// Map consultation type to service name
if ($consultation_type === 'online') {
    $service_name = 'Online Consultation';
} elseif ($consultation_type === 'physical') {
    $service_name = 'Physical Consultation';
} else {
    $service_name = null; // No filter if consultation type is not specified
}

// Add service name condition if specified
if ($service_name) {
    $queryString .= " AND s.name = ?";
}

$query = $db->prepare($queryString);

// Bind parameters based on whether service name is provided
if ($service_name) {
    $query->bind_param("iis", $doctor_id, $user_id, $service_name);
} else {
    $query->bind_param("ii", $doctor_id, $user_id);
}

// Execute the query
$query->execute();
$result = $query->get_result();

// Process the results
while ($row = $result->fetch_assoc()) {
    $response["data"][] = [
        "appointment_id" => $row["appointment_id"],
        "details" => "{$row['service_name']} on {$row['date']} from {$row['start_time']} to {$row['end_time']}"
    ];
}

$response["status"] = true;
echo json_encode($response);
