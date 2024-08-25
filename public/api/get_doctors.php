<?php
require_once '../../src/autoload.php';
require_once '../../config/database.php';

$db = include '../../config/database.php';

$specialization_id = $_GET['specialization_id'] ?? null;
$consultation_type = $_GET['consultation_type'] ?? null; // Get consultation type from request

$query = "SELECT u.user_id, CONCAT(u.first_name, ' ', u.middle_initial, ' ', u.last_name) as name, u.address, s.name as specialization, da.consultation_duration 
          FROM users u 
          JOIN doctor_specializations ds ON u.user_id = ds.doctor_id 
          JOIN specializations s ON ds.specialization_id = s.id 
          LEFT JOIN doctor_availability da ON u.user_id = da.doctor_id 
          WHERE u.role = 'doctor'";

if ($specialization_id) {
    // Ensure specialization filter is applied correctly
    $query .= " AND ds.specialization_id = " . intval($specialization_id);
}

if ($consultation_type) {
    // Adjust the consultation type logic to match the service selected by the patient
    if ($consultation_type == 1) { // Assuming 1 corresponds to "Online Consultation"
        $query .= " AND (da.consultation_type = 'online' OR da.consultation_type = 'both')";
    } elseif ($consultation_type == 2) { // Assuming 2 corresponds to "Physical Consultation"
        $query .= " AND (da.consultation_type = 'physical' OR da.consultation_type = 'both')";
    }
}

$result = $db->query($query);

if (!$result) {
    echo json_encode(["error" => $db->error]);
    exit;
}

$doctors = [];
while ($row = $result->fetch_assoc()) {
    $doctors[] = $row;
}

echo json_encode($doctors);
