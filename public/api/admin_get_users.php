<?php
require_once '../../config/database.php';

$db = include '../../config/database.php';

// Fetch all users (except admins)
$result = $db->query("SELECT user_id, name, email, role FROM users WHERE role != 'admin'");

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode($users);
