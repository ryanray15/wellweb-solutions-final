<?php
session_start(); // Start the session to manage user authentication
require_once '../../src/autoload.php';
require_once '../../config/database.php';

// Connect to the database
$db = include '../../config/database.php';

// Get the JSON data sent from the client
$data = json_decode(file_get_contents("php://input"));

// Extract email and password from the received JSON data
$email = $data->email ?? '';
$password = $data->password ?? '';

// Prepare a SQL statement to select user details based on the provided email
$query = $db->prepare("SELECT * FROM users WHERE email = ?");
$query->bind_param("s", $email);
$query->execute();

// Fetch the result
$result = $query->get_result();
$user = $result->fetch_assoc();

// Verify the password and if it matches, set session variables
if ($user && password_verify($password, $user['password'])) {
    // Set session variables for the logged-in user
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['role'] = $user['role'];

    // Return a successful login response with user details
    echo json_encode([
        'status' => true,
        'message' => 'Login successful!',
        'user_id' => $user['user_id'],
        'role' => $user['role']
    ]);
} else {
    // Return a failure response if the credentials are invalid
    echo json_encode(['status' => false, 'message' => 'Invalid email or password']);
}
?>
