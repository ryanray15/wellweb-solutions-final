<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = $_SESSION['user_id'];
    $date = $_POST['date'] ?? null;
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;

    $availability_status = 'available'; // or 'unavailable'

    // Set default response
    $response = ['status' => false, 'message' => 'Invalid request'];

    if ($date) {
        $query = $db->prepare("SELECT * FROM doctor_availability WHERE doctor_id = ? AND date = ?");
        $query->bind_param("is", $doctor_id, $date);
        $query->execute();
        $existingAvailability = $query->get_result()->fetch_assoc();

        if ($existingAvailability) {
            // Toggle availability status
            $availability_status = $existingAvailability['availability_status'] === 'available' ? 'unavailable' : 'available';

            $updateQuery = $db->prepare("UPDATE doctor_availability SET availability_status = ? WHERE doctor_id = ? AND date = ?");
            $updateQuery->bind_param("sis", $availability_status, $doctor_id, $date);
            $updateQuery->execute();
            $response = ['status' => true, 'message' => 'Date toggled successfully.'];
        } else {
            $insertQuery = $db->prepare("INSERT INTO doctor_availability (doctor_id, date, availability_status) VALUES (?, ?, ?)");
            $insertQuery->bind_param("iss", $doctor_id, $date, $availability_status);
            $insertQuery->execute();
            $response = ['status' => true, 'message' => 'Date set successfully.'];
        }
    } elseif ($start_date && $end_date) {
        $period = new DatePeriod(
            new DateTime($start_date),
            new DateInterval('P1D'),
            (new DateTime($end_date))->modify('+1 day')
        );

        foreach ($period as $date) {
            $currentDate = $date->format('Y-m-d');
            $query = $db->prepare("SELECT * FROM doctor_availability WHERE doctor_id = ? AND date = ?");
            $query->bind_param("is", $doctor_id, $currentDate);
            $query->execute();
            $existingAvailability = $query->get_result()->fetch_assoc();

            if ($existingAvailability) {
                $availability_status = $existingAvailability['availability_status'] === 'available' ? 'unavailable' : 'available';
                $updateQuery = $db->prepare("UPDATE doctor_availability SET availability_status = ? WHERE doctor_id = ? AND date = ?");
                $updateQuery->bind_param("sis", $availability_status, $doctor_id, $currentDate);
                $updateQuery->execute();
            } else {
                $insertQuery = $db->prepare("INSERT INTO doctor_availability (doctor_id, date, availability_status) VALUES (?, ?, ?)");
                $insertQuery->bind_param("iss", $doctor_id, $currentDate, $availability_status);
                $insertQuery->execute();
            }
        }

        $response = ['status' => true, 'message' => 'Availability updated successfully.'];
    }

    echo json_encode($response);
}
