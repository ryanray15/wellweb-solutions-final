<?php
require_once '../../src/autoload.php';
require_once '../../config/database.php';

$db = include '../../config/database.php';
$doctor_id = $_GET['doctor_id'];
$type = $_GET['type'] ?? 'all'; // Default to 'all' if no type is provided

$query = "
    SELECT 
        a.appointment_id, 
        a.date, 
        a.time,
        a.status, 
        a.service_id,   /* Include service_id in the selection */
        u.user_id as patient_id,   /* Include patient_id in the selection */
        CONCAT(u.first_name, ' ', u.middle_initial, ' ', u.last_name) as patient_name
    FROM appointments a
    JOIN users u ON a.patient_id = u.user_id
    WHERE a.doctor_id = ? AND a.status != 'canceled' AND a.status != 'completed'
";

// Modify the query based on the appointment type
if ($type === 'online') {
    $query .= " AND a.service_id = 1"; // Online Consultation
} elseif ($type === 'physical') {
    $query .= " AND a.service_id = 2"; // Physical Consultation
}

$stmt = $db->prepare($query);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

$appointments = [];
while ($row = $result->fetch_assoc()) {
    $appointments[] = $row;
}

echo json_encode($appointments);
