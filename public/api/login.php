<?php
session_start();
require_once '../../src/autoload.php';
require_once '../../config/database.php';

$db = include '../../config/database.php';

$data = json_decode(file_get_contents("php://input"));

$email = $data->email ?? '';
$password = $data->password ?? '';

$query = $db->prepare("SELECT * FROM users WHERE email = ?");
$query->bind_param("s", $email);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['role'] = $user['role'];
    echo json_encode(['status' => true, 'message' => 'Login successful!']);
} else {
    echo json_encode(['status' => false, 'message' => 'Invalid email or password']);
}
