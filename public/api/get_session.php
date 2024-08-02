<?php
session_start();
header('Content-Type: application/json');

// Check if user_id is set in session
if (isset($_SESSION['user_id'])) {
    // Return the user_id and role
    echo json_encode([
        'user_id' => $_SESSION['user_id'],
        'role' => $_SESSION['role'],
        'status' => true
    ]);
} else {
    // Return null for user_id and role if not logged in
    echo json_encode([
        'user_id' => null,
        'role' => null,
        'status' => false,
        'message' => 'User not logged in'
    ]);
}
