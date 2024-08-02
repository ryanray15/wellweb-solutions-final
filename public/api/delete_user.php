<?php
require_once '../../src/autoload.php';
require_once '../../src/controllers/UserController.php';
require_once '../../config/database.php';

$db = include '../../config/database.php';
$userController = new UserController($db);

$data = json_decode(file_get_contents("php://input"));

$user_id = $data->user_id ?? '';

$response = $userController->delete($user_id);

echo json_encode($response);
