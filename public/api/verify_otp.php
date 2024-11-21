<?php
date_default_timezone_set('Asia/Manila'); // Set the correct timezone
require_once '../../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);

$email = $input['email'] ?? null;
$otp = $input['otp'] ?? null;

if (!$email || !$otp) {
    echo json_encode(['status' => 400, 'message' => 'Email and OTP are required']);
    exit();
}

// Database connection
$db = include '../../config/database.php';

$query = $db->prepare("
    SELECT * 
    FROM OTPs 
    WHERE email = ? AND otp = ? AND expires_at > NOW()
");
$query->bind_param("ss", $email, $otp);
$query->execute();
$result = $query->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['status' => 200, 'message' => 'OTP verified successfully']);
} else {
    echo json_encode(['status' => 400, 'message' => 'Invalid or expired OTP']);
}
