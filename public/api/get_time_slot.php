<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

$doctorId = $_GET['doctor_id'] ?? null;
$availabilityId = $_GET['availability_id'] ?? null;

if ($doctorId && $availabilityId) {
    $query = $mysqli->prepare("
        SELECT date, start_time, end_time, consultation_type
        FROM doctor_availability
        WHERE doctor_id = ? AND availability_id = ?
    ");
    $query->bind_param("ii", $doctorId, $availabilityId);
    $query->execute();
    $result = $query->get_result();
    $availability = $result->fetch_assoc();

    if ($availability) {
        echo json_encode([
            'status' => 'success',
            'data' => $availability
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Time slot not found']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
}
