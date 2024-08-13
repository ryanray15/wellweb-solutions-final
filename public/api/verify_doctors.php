<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';
header('Content-Type: application/json');

// Decode the JSON body sent in the request
$data = json_decode(file_get_contents('php://input'), true);

$action = $data['action'] ?? null;
$doctor_id = $data['doctor_id'] ?? null;

if ($action && $doctor_id) {
    if ($action == 'approve') {
        // Approve doctor verification
        $query = $db->prepare("UPDATE doctor_verifications SET status = 'verified', reviewed_at = NOW(), reviewed_by = ? WHERE doctor_id = ?");
        $admin_id = $_SESSION['user_id']; // Assuming the admin's ID is stored in session
        $query->bind_param("ii", $admin_id, $doctor_id);
        $query->execute();

        // Update is_verified in users table
        $query = $db->prepare("UPDATE users SET is_verified = 1 WHERE user_id = ?");
        $query->bind_param("i", $doctor_id);
        $query->execute();

        echo json_encode(['status' => true, 'message' => 'Doctor verified successfully']);
    } elseif ($action == 'reject') {
        // Reject doctor verification
        $query = $db->prepare("UPDATE doctor_verifications SET status = 'rejected', reviewed_at = NOW(), reviewed_by = ? WHERE doctor_id = ?");
        $admin_id = $_SESSION['user_id']; // Assuming the admin's ID is stored in session
        $query->bind_param("ii", $admin_id, $doctor_id);
        $query->execute();

        echo json_encode(['status' => true, 'message' => 'Doctor verification rejected']);
    }
} else {
    echo json_encode(['status' => false, 'message' => 'Invalid request']);
}
