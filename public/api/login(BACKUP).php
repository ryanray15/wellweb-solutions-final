<?php
require_once '../../src/controllers/AuthController.php';

$auth = new AuthController();

$data = json_decode(file_get_contents("php://input"));

$email = $data->email;
$password = $data->password;

$response = $auth->login($email, $password);

echo json_encode($response);
