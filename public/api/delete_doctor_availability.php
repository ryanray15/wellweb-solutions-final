<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $availability_id = $data['id'];

    // Prepare delete statement
    $query = $db->prepare("DELETE FROM doctor_availability WHERE availability_id = ?");
    $query->bind_param("i", $availability_id);

    if ($query->execute()) {
        echo json_encode(['status' => true, 'message' => 'Schedule updated successfully.']);
    } else {
        echo json_encode(['status' => false, 'message' => 'Failed to update schedule.']);
    }
}
