<?php
require_once '../../src/autoload.php';
require_once '../../config/database.php';

$db = include '../../config/database.php';
$patient_id = $_GET['patient_id'];

$result = $db->query("SELECT appointment_id, date, time FROM appointments WHERE patient_id = $patient_id AND status != 'cancelled'");
$appointments = [];
while ($row = $result->fetch_assoc()) {
    $appointments[] = $row;
}

echo json_encode($appointments);
