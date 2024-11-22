<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$user_id = $_SESSION['user_id']; // Fetch current user from session
$with_user_id = $data['with_user_id'];

if (!$with_user_id) {
    echo json_encode(['status' => false, 'message' => 'Invalid input']);
    exit();
}

// Mark messages as read
$query = $db->prepare("
    UPDATE messages
    SET is_read = 1
    WHERE receiver_id = ? AND sender_id = ? AND is_read = 0
");
$query->bind_param("ii", $user_id, $with_user_id);
if ($query->execute()) {
    echo json_encode(['status' => true, 'message' => 'Messages marked as read']);
} else {
    echo json_encode(['status' => false, 'message' => 'Failed to mark messages as read']);
}
