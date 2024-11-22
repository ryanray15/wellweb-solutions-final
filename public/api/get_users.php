<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';

$query = $db->prepare("SELECT user_id, CONCAT(first_name, ' ', last_name) AS name FROM users WHERE user_id != ?  AND role = 'doctor';");
$query->bind_param("i", $_SESSION['user_id']);
$query->execute();
$result = $query->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode(['users' => $users]);
