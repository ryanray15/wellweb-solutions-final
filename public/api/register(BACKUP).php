<?php
require_once '../../src/controllers/AuthController.php';

$auth = new AuthController();

$data = json_decode(file_get_contents("php://input"));

$name = $data->name;
$email = $data->email;
$password = $data->password;
$role = $data->role;

$response = $auth->register($name, $email, $password, $role);

echo json_encode($response);
