<?php
session_start(); // Start the session

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once '../../src/autoload.php';
require_once '../../config/database.php';

$db = include '../../config/database.php';
$appointmentController = new AppointmentController($db);

$data = json_decode(file_get_contents("php://input"));

$appointment_id = $data->appointment_id ?? '';

$response = $appointmentController->cancel($appointment_id);

echo json_encode($response);
