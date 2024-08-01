<?php
require_once '../../src/autoload.php';
require_once '../../config/database.php';

$db = include '../../config/database.php';
$appointmentController = new AppointmentController($db);

$data = json_decode(file_get_contents("php://input"));

$patient_id = $data->patient_id ?? '';
$doctor_id = $data->doctor_id ?? '';
$service_id = $data->service_id ?? '';
$date = $data->date ?? '';
$time = $data->time ?? '';

// Log the input data
error_log("Scheduling appointment with patient_id: $patient_id, doctor_id: $doctor_id, service_id: $service_id, date: $date, time: $time");

// Verify existence of patient, doctor, and service
$patientExists = $db->query("SELECT * FROM users WHERE user_id = $patient_id AND role = 'patient'")->num_rows > 0;
$doctorExists = $db->query("SELECT * FROM users WHERE user_id = $doctor_id AND role = 'doctor'")->num_rows > 0;
$serviceExists = $db->query("SELECT * FROM services WHERE service_id = $service_id")->num_rows > 0;

error_log("Patient exists: " . ($patientExists ? "Yes" : "No"));
error_log("Doctor exists: " . ($doctorExists ? "Yes" : "No"));
error_log("Service exists: " . ($serviceExists ? "Yes" : "No"));

if (!$patientExists || !$doctorExists || !$serviceExists) {
    echo json_encode(['status' => false, 'message' => 'Invalid patient, doctor, or service ID']);
    exit;
}

$response = $appointmentController->schedule($patient_id, $doctor_id, $service_id, $date, $time);

echo json_encode($response);
?>
