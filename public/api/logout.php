<?php
session_start();

// Destroy the entire session to log out the user
session_unset();
session_destroy();

// Return a JSON response to indicate success
echo json_encode(['status' => true, 'message' => 'Logged out successfully']);
exit();
