<?php
session_start(); // Start the session
require_once '../../src/autoload.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

$db = include '../../config/database.php';

// Decode the JSON input
$data = json_decode(file_get_contents("php://input"));

$email = $data->email ?? '';
$password = $data->password ?? '';

// Prepare and execute the query
$query = $db->prepare("SELECT * FROM users WHERE email = ?");
$query->bind_param("s", $email);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();

// Check if the user exists, if the password matches, and if the account is active
if ($user && password_verify($password, $user['password']) && $user['active_status'] === 'active') {
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['role'] = $user['role'];
    error_log("User logged in with ID: " . $_SESSION['user_id']);
    error_log("Session ID: " . session_id());
    error_log("Session Data: " . print_r($_SESSION, true));

    // Check email verification status (exclude admin role)
    if ($user['role'] !== 'admin') {
        $verificationQuery = $db->prepare("
            SELECT is_otp_verified 
            FROM user_verifications 
            WHERE email = ?
        ");
        $verificationQuery->bind_param("s", $email);
        $verificationQuery->execute();
        $verificationResult = $verificationQuery->get_result();

        // If not verified or no record exists in user_verifications
        if ($verificationResult->num_rows === 0 || !$verificationResult->fetch_assoc()['is_otp_verified']) {
            // Send response with redirect to email verification page
            echo json_encode([
                'status' => false,
                'message' => 'Email verification required.',
                'redirect' => '/verify_email.php'
            ]);
            exit(); // Stop further execution
        }
    }

    // Determine the redirect URL based on the user's role
    $redirectUrl = '/dashboard.php'; // Default to patient dashboard
    if ($user['role'] === 'doctor') {
        $redirectUrl = '/dashboard.php'; // Redirect to doctor dashboard
    } elseif ($user['role'] === 'admin') {
        $redirectUrl = '/dashboard.php'; // Redirect to admin dashboard
    }

    // Return a successful login response with redirect URL
    echo json_encode([
        'status' => true,
        'message' => 'Login successful!',
        'user_id' => $_SESSION['user_id'],
        'role' => $_SESSION['role'],
        'redirect' => $redirectUrl
    ]);
} else {
    // Return an error response
    echo json_encode(['status' => false, 'message' => 'Invalid email, password, or account inactive']);
}
