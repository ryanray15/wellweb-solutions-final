<?php
require_once '../../src/controllers/AuthController.php';

$auth = new AuthController();

$data = json_decode(file_get_contents("php://input"));

$email = $data->email;
$new_password = $data->new_password;

$response = $auth->reset_password($email, $new_password);

echo json_encode($response);
