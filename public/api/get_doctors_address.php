<?php
header('Content-Type: application/json');
require_once '../../config/database.php';
$db = include '../../config/database.php';

$query = $db->prepare("SELECT CONCAT(first_name, ' ', middle_initial, ' ', last_name) as name, address FROM users WHERE role = 'doctor' AND active_status = 'active';");
$query->execute();
$result = $query->get_result();

$doctors = [];
while ($row = $result->fetch_assoc()) {
    $doctors[] = [
        "name" => $row["name"],
        "address" => $row["address"],
    ];
}

echo json_encode($doctors);
