<?php
require_once '../../config/database.php';

$db = include '../../config/database.php';

// Fetch all users (except admins)
$result = $db->query("SELECT user_id, first_name, middle_initial, last_name, email, role FROM users WHERE role != 'admin'");

$users = [];
while ($row = $result->fetch_assoc()) {
    // Concatenate first name, middle initial, and last name
    $row['name'] = $row['first_name'] . ' ' . $row['middle_initial'] . ' ' . $row['last_name'];
    unset($row['first_name'], $row['middle_initial'], $row['last_name']);
    $users[] = $row;
}

echo json_encode($users);
