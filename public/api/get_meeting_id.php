<?php
require_once '../../config/database.php'; // Adjust path as needed
$conn = include '../../config/database.php'; // Use the same method as schedule_appointment.php

$appointment_id = $_GET['appointment_id'];
$user_id = $_GET['user_id'];

// Check if the database connection is established
if (!$conn) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Query to get the meeting_id for this appointment if it's an online consultation
$sql = "SELECT meeting_id FROM appointments WHERE appointment_id = ? AND service_id = 1 AND (patient_id = ? OR doctor_id = ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $appointment_id, $user_id, $user_id);
$stmt->execute();
$stmt->bind_result($meeting_id);
$stmt->fetch();

if ($meeting_id) {
    echo json_encode(['meeting_id' => $meeting_id]);
} else {
    echo json_encode(['meeting_id' => null, 'error' => 'Meeting ID not found or unauthorized']);
}

$stmt->close();
$conn->close();
