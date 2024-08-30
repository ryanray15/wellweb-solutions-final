<?php
require_once '../../src/autoload.php';
require_once '../../config/database.php';

$db = include '../../config/database.php';
$patient_id = $_GET['patient_id'];

$query = "
    SELECT 
        a.appointment_id, 
        a.date, 
        a.time, 
        a.service_id,   /* Include service_id in the selection */
        u.user_id as doctor_id,   /* Include doctor_id in the selection */
        CONCAT(u.first_name, ' ', u.middle_initial, ' ', u.last_name) as doctor_name
    FROM appointments a
    JOIN users u ON a.doctor_id = u.user_id
    WHERE a.patient_id = ? AND a.status != 'canceled'
";

$stmt = $db->prepare($query);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();

$appointments = [];
while ($row = $result->fetch_assoc()) {
    $appointments[] = $row;
}

echo json_encode($appointments);
