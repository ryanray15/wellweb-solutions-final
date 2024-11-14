<?php
require_once '../../config/database.php';
require_once '../../src/autoload.php';

$db = include '../../config/database.php';
$data = json_decode(file_get_contents("php://input"), true);
$doctor_id = $data['doctor_id'];
$time_ranges = $data['time_ranges'] ?? [];

$controller = new DoctorAvailabilityController($db);
$response = ["status" => false, "message" => ""];

// Pass the entire array of time ranges to setAvailability
$response = $controller->setAvailability($doctor_id, $time_ranges);

// Output response
echo json_encode($response);
