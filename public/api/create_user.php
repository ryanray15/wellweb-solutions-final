<?php
require_once '../../src/autoload.php';
require_once '../../src/controllers/UserController.php';
require_once '../../config/database.php';

$db = include '../../config/database.php';
$userController = new UserController($db);

$data = json_decode(file_get_contents("php://input"));

$name = $data->name ?? '';
$email = $data->email ?? '';
$password = $data->password ?? '';
$role = $data->role ?? '';

$response = $userController->create($name, $email, $password, $role);

echo json_encode($response);
?>
