<?php
require_once '../../src/autoload.php';
require_once '../../config/database.php';

$db = include '../../config/database.php';
$appointmentController = new AppointmentController($db);

$data = json_decode(file_get_contents("php://input"));

$appointment_id = $data->appointment_id ?? '';
$date = $data->date ?? '';
$time = $data->time ?? '';

$response = $appointmentController->reschedule($appointment_id, $date, $time);

echo json_encode($response);
?>
