<?php
require_once '../../config/database.php';

$db = include '../../config/database.php';

// Get total patients count
$patientCount = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'patient'")->fetch_assoc()['total'];

// Get total doctors count
$doctorCount = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'doctor'")->fetch_assoc()['total'];

// Get pending verifications count
$pendingVerifications = $db->query("SELECT COUNT(*) as total FROM doctor_verifications WHERE status = 'pending'")->fetch_assoc()['total'];

echo json_encode([
    'totalPatients' => $patientCount,
    'totalDoctors' => $doctorCount,
    'pendingVerifications' => $pendingVerifications
]);
