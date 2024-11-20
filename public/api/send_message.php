<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);
$sender_id = $_SESSION['user_id'];
$receiver_id = $data['receiver_id'];
$content = $data['content'];

$query = $db->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
$query->bind_param("iis", $sender_id, $receiver_id, $content);

if ($query->execute()) {
    echo json_encode(['status' => true]);
} else {
    echo json_encode(['status' => false, 'message' => 'Failed to send message']);
}
