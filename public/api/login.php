<?php
require_once '../../src/autoload.php';
require_once '../../src/controllers/AuthController.php';
require_once '../../config/database.php';

$db = include '../../config/database.php';
$auth = new AuthController($db);

$data = json_decode(file_get_contents("php://input"));

$email = $data->email;
$password = $data->password;

$response = $auth->login($email, $password);

echo json_encode($response);
?>
