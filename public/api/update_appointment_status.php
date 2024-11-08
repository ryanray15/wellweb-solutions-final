<?php
require_once '../../config/database.php';

header("Content-Type: application/json");

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
    exit();
}

// Decode the JSON payload
$data = json_decode(file_get_contents("php://input"), true);
$appointment_id = $data['appointment_id'] ?? null;
$status = $data['status'] ?? null;

// Validate inputs
if (!$appointment_id || !in_array($status, ['completed', 'no show'])) {
    echo json_encode(["success" => false, "message" => "Invalid input parameters."]);
    exit();
}

// Connect to the database
$db = include '../../config/database.php';

// Prepare and execute the SQL statement to update the appointment status
$query = "UPDATE appointments SET status = ? WHERE appointment_id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("si", $status, $appointment_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Appointment status updated successfully."]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to update appointment status."]);
}

$stmt->close();
$db->close();
