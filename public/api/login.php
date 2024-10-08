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

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['role'] = $user['role'];
    error_log("User logged in with ID: " . $_SESSION['user_id']);
    error_log("Session ID: " . session_id());
    error_log("Session Data: " . print_r($_SESSION, true));

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
    echo json_encode(['status' => false, 'message' => 'Invalid email or password']);
}
