<?php
require_once '../../config/database.php';

$db = include '../../config/database.php';
$patient_id = $_GET['patient_id'];

$query = "
    SELECT id, message, type, is_read, created_at 
    FROM notifications 
    WHERE patient_id = ? 
    ORDER BY created_at DESC
";

$stmt = $db->prepare($query);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

echo json_encode($notifications);
