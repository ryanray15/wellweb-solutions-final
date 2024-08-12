<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? null;
$doctor_id = $_POST['doctor_id'] ?? null;

if ($action && $doctor_id) {
    if ($action == 'approve') {
        // Approve doctor verification
        $query = $db->prepare("UPDATE users SET verification_status = 'verified' WHERE user_id = ?");
        $query->bind_param("i", $doctor_id);
        $query->execute();
        echo json_encode(['status' => true, 'message' => 'Doctor verified successfully']);
    } elseif ($action == 'reject') {
        $reason = $_POST['reason'] ?? 'Not specified';
        // Reject doctor verification
        $query = $db->prepare("UPDATE users SET verification_status = 'rejected' WHERE user_id = ?");
        $query->bind_param("i", $doctor_id);
        $query->execute();
        echo json_encode(['status' => true, 'message' => 'Doctor verification rejected']);
    }
} else {
    // Fetch all pending verifications
    $result = $db->query("SELECT user_id, name, email FROM users WHERE role = 'doctor' AND verification_status = 'pending'");
    $doctors = [];
    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }
    echo json_encode(['status' => true, 'doctors' => $doctors]);
}
