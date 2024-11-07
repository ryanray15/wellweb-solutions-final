<?php
require_once '../../src/autoload.php';
require_once '../../config/database.php';

$db = include '../../config/database.php';
$patient_id = $_GET['patient_id'];
$type = $_GET['type'] ?? 'all'; // Default to 'all' if no type is provided

// Base query
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

// Modify the query based on the appointment type
if ($type === 'online') {
    $query .= " AND a.service_id = 1"; // Online Consultation
} elseif ($type === 'physical') {
    $query .= " AND a.service_id = 2"; // Physical Consultation
}

$stmt = $db->prepare($query);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();

$appointments = [];
while ($row = $result->fetch_assoc()) {
    $appointments[] = $row;
}

echo json_encode($appointments);
