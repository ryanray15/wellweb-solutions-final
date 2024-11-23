<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$email = $input['email'] ?? null;
$newPassword = $input['new_password'] ?? null;

if (!$email || !$newPassword) {
    echo json_encode(['status' => 400, 'message' => 'Email and new password are required']);
    exit();
}

$db = include '../../config/database.php';

try {
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

    $query = $db->prepare("
        UPDATE users 
        SET password = ? 
        WHERE email = ?
    ");
    $query->bind_param("ss", $hashedPassword, $email);
    $query->execute();

    if ($query->affected_rows > 0) {
        echo json_encode(['status' => 200, 'message' => 'Password reset successfully']);
    } else {
        echo json_encode(['status' => 400, 'message' => 'Failed to reset password. Please try again.']);
    }
} catch (Exception $e) {
    error_log("Error resetting password for email {$email}: " . $e->getMessage());
    echo json_encode(['status' => 500, 'message' => 'Internal server error']);
}
