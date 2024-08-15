<?php
require_once '../../src/autoload.php';
require_once '../../config/database.php';

$db = include '../../config/database.php';

$specialization_id = $_GET['specialization_id'] ?? null;

$query = "SELECT u.user_id, CONCAT(u.first_name, ' ', u.middle_initial, ' ', u.last_name) as name
          FROM users u
          JOIN doctor_specializations ds ON u.user_id = ds.doctor_id
          WHERE u.role = 'doctor'";

if ($specialization_id) {
    $query .= " AND ds.specialization_id = " . intval($specialization_id);
}

$result = $db->query($query);
$doctors = [];
while ($row = $result->fetch_assoc()) {
    $doctors[] = $row;
}

echo json_encode($doctors);
