<?php
session_start();
require_once '../../config/database.php';
$db = include '../../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);
$meeting_id = $data['meeting_id'];
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$action = $data['action']; // 'start' or 'end'

// Identify doctor and patient based on the role
if ($role === 'doctor') {
    $doctor_id = $user_id;
    $patient_id = $data['patient_id'];
} else {
    $doctor_id = $data['doctor_id'];
    $patient_id = $user_id;
}

// Step 1: Check if both doctor_id and patient_id exist in the users table
$ids = [$doctor_id, $patient_id];
$stmtCheckUsers = $db->prepare("SELECT user_id FROM users WHERE user_id IN (?, ?)");
$stmtCheckUsers->bind_param("ii", $ids[0], $ids[1]);
$stmtCheckUsers->execute();
$resultUsers = $stmtCheckUsers->get_result();

if ($resultUsers->num_rows < 2) {
    // If both doctor and patient are not found, we cannot proceed
    echo json_encode([
        'status' => false,
        'message' => 'Doctor or Patient does not exist in the users table.'
    ]);
    exit();
}

$stmtCheckUsers->close();

// Step 2: Set start or end time based on the action
if ($action === 'start') {
    // Insert a new record when the call starts
    $stmt = $db->prepare("INSERT INTO video_call_history (meeting_id, doctor_id, patient_id, start_time, status) VALUES (?, ?, ?, NOW(), 'ongoing')");
    $stmt->bind_param("sii", $meeting_id, $doctor_id, $patient_id);
} else if ($action === 'end') {
    // Update the record to mark the call as completed
    $stmt = $db->prepare("UPDATE video_call_history SET end_time = NOW(), status = 'completed' WHERE meeting_id = ? AND doctor_id = ? AND patient_id = ? AND end_time IS NULL");
    $stmt->bind_param("sii", $meeting_id, $doctor_id, $patient_id);
}

// Step 3: Execute the statement and handle response
if ($stmt->execute()) {
    echo json_encode(['status' => true]);
} else {
    echo json_encode(['status' => false, 'message' => $stmt->error]);
}
$stmt->close();
