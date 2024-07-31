<?php
require_once '../../src/autoload.php';
require_once '../../src/controllers/AuthController.php';
require_once '../../config/database.php';

$db = include '../../config/database.php';
$auth = new AuthController($db);

$data = json_decode(file_get_contents("php://input"));

$email = $data->email;
$new_password = $data->new_password;

$response = $auth->reset_password($email, $new_password);

echo json_encode($response);
?>
