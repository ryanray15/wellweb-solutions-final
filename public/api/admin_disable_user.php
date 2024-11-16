<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

$db = include '../../config/database.php';
$user_id = $_GET['user_id'] ?? null;

if ($user_id) {
    $query = $db->prepare("UPDATE users SET active_status = 'inactive' WHERE user_id = ?");
    $query->bind_param("i", $user_id);

    if ($query->execute()) {
        echo json_encode(['status' => true, 'message' => 'User has been disabled successfully.']);
    } else {
        echo json_encode(['status' => false, 'message' => 'Failed to disable user.']);
    }
} else {
    echo json_encode(['status' => false, 'message' => 'Invalid user ID.']);
}
