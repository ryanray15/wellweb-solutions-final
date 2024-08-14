<?php
require_once '../../src/autoload.php';
require_once '../../config/database.php';

$db = include '../../config/database.php';

$result = $db->query("SELECT user_id, CONCAT(first_name, ' ', middle_initial, ' ', last_name) as name FROM users WHERE role = 'doctor'");
$doctors = [];
while ($row = $result->fetch_assoc()) {
    $doctors[] = $row;
}

echo json_encode($doctors);
