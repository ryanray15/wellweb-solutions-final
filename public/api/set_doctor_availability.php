<?php
session_start();

require_once '../../config/database.php';

$db = include '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = $_SESSION['user_id'];
    $daysOfWeek = $_POST['days_of_week'] ?? [];
    $unavailableDates = $_POST['unavailable_dates'] ?? [];

    // Delete old availability entries
    $deleteQuery = $db->prepare("DELETE FROM doctor_availability WHERE doctor_id = ?");
    $deleteQuery->bind_param("i", $doctor_id);
    $deleteQuery->execute();

    // Insert new availability days
    foreach ($daysOfWeek as $day) {
        $insertDayQuery = $db->prepare("INSERT INTO doctor_availability (doctor_id, day_of_week) VALUES (?, ?)");
        $insertDayQuery->bind_param("is", $doctor_id, $day);
        $insertDayQuery->execute();
    }

    // Insert unavailable specific dates
    foreach ($unavailableDates as $date) {
        $insertDateQuery = $db->prepare("INSERT INTO doctor_availability (doctor_id, unavailable_dates) VALUES (?, ?)");
        $insertDateQuery->bind_param("is", $doctor_id, $date);
        $insertDateQuery->execute();
    }

    echo json_encode(['status' => true, 'message' => 'Availability updated successfully.']);
}
