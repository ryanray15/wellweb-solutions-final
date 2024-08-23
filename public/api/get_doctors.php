<?php
require_once '../../src/autoload.php';
require_once '../../config/database.php';

$db = include '../../config/database.php';

$specialization_id = $_GET['specialization_id'] ?? null;

// Correct the JOIN condition to link the doctor_specializations with specializations correctly
$query = "SELECT u.user_id, CONCAT(u.first_name, ' ', u.middle_initial, ' ', u.last_name) as name, u.address, s.name as specialization 
          FROM users u 
          JOIN doctor_specializations ds ON u.user_id = ds.doctor_id 
          JOIN specializations s ON ds.specialization_id = s.id 
          WHERE u.role = 'doctor'";

if ($specialization_id) {
    // Ensure specialization filter is applied correctly
    $query .= " AND ds.specialization_id = " . intval($specialization_id);
}

$result = $db->query($query);

if (!$result) {
    // Error handling
    echo json_encode(["error" => $db->error]);
    exit;
}

$doctors = [];
while ($row = $result->fetch_assoc()) {
    $doctors[] = $row;
}

echo json_encode($doctors);
