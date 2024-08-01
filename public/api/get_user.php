<?php
require_once '../../src/autoload.php';
require_once '../../src/controllers/UserController.php';
require_once '../../config/database.php';

$db = include '../../config/database.php';
$userController = new UserController($db);

$user_id = $_GET['user_id'] ?? '';

$response = $userController->get($user_id);

echo json_encode($response);
?>
