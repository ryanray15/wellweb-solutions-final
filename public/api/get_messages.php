<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';

$user_id = $_SESSION['user_id'];
$with_user_id = $_GET['with_user_id'];

$query = $db->prepare("
    SELECT * 
    FROM messages 
    WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
    ORDER BY timestamp ASC
");
$query->bind_param("iiii", $user_id, $with_user_id, $with_user_id, $user_id);
$query->execute();
$result = $query->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

echo json_encode(['messages' => $messages]);
