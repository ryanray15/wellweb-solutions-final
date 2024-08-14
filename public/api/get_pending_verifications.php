<?php
require_once '../../config/database.php';

$db = include '../../config/database.php';

$result = $db->query("
    SELECT dv.id as verification_id, dv.doctor_id, 
           CONCAT(u.first_name, ' ', u.middle_initial, ' ', u.last_name) as doctor_name, 
           dv.status, dv.document_path
    FROM doctor_verifications dv
    JOIN users u ON dv.doctor_id = u.user_id
    WHERE dv.status = 'pending'
");

$verifications = [];
while ($row = $result->fetch_assoc()) {
    $verifications[] = $row;
}

echo json_encode($verifications);
