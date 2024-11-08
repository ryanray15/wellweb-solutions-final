<?php
require_once '../../src/autoload.php';
require_once '../../config/database.php';

$db = include '../../config/database.php';
$patient_id = $_GET['patient_id'] ?? null;

$query = "
    SELECT 
        a.appointment_id, 
        a.date, 
        a.time,
        a.service_id, 
        u.user_id AS doctor_id,
        CONCAT(u.first_name, ' ', u.middle_initial, ' ', u.last_name) AS doctor_name,
        r.status AS refund_status
    FROM appointments a
    JOIN users u ON a.doctor_id = u.user_id
    LEFT JOIN refund_requests r ON r.appointment_id = a.appointment_id
    WHERE a.patient_id = ? AND a.status = 'canceled'
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
