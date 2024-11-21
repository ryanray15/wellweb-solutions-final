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

try {
    // Check if the OTP is valid
    $query = $db->prepare("
        SELECT * 
        FROM OTPs 
        WHERE email = ? AND otp = ? AND expires_at > NOW()
    ");
    $query->bind_param("ss", $email, $otp);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        // Mark OTP as used (optional but recommended)
        $updateQuery = $db->prepare("
            DELETE FROM OTPs 
            WHERE email = ? AND otp = ?
        ");
        $updateQuery->bind_param("ss", $email, $otp);
        $updateQuery->execute();

        // Update or Insert into user_verifications table
        $verificationQuery = $db->prepare("
            INSERT INTO user_verifications (email, is_otp_verified, created_at, updated_at)
            VALUES (?, 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE 
                is_otp_verified = 1,
                updated_at = NOW()
        ");
        $verificationQuery->bind_param("s", $email);
        $verificationQuery->execute();

        if ($verificationQuery->affected_rows > 0) {
            echo json_encode(['status' => 200, 'message' => 'OTP verified successfully']);
        } else {
            throw new Exception("Failed to update verification status.");
        }
    } else {
        echo json_encode(['status' => 400, 'message' => 'Invalid or expired OTP']);
    }
} catch (Exception $e) {
    error_log("Error verifying OTP: " . $e->getMessage());
    echo json_encode(['status' => 500, 'message' => 'Internal server error']);
} catch (Throwable $t) {
    error_log("Unexpected error: " . $t->getMessage());
    echo json_encode(['status' => 500, 'message' => 'Unexpected server error']);
}
