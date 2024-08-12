<?php
require_once '../../config/database.php';

$db = include '../../config/database.php';

$user_id = $_GET['user_id'] ?? null;

if ($user_id) {
    $stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $status = $stmt->execute();

    if ($status) {
        echo json_encode(['status' => true, 'message' => 'User deleted successfully']);
    } else {
        echo json_encode(['status' => false, 'message' => 'Failed to delete user']);
    }
} else {
    echo json_encode(['status' => false, 'message' => 'No user ID provided']);
}
