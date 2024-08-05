<?php
session_start();

require_once '../../config/database.php';

$db = include '../../config/database.php';

$doctor_id = $_SESSION['user_id'];

// Prepare the query to fetch available days and unavailable specific dates
$query = $db->prepare("
    SELECT day_of_week, unavailable_dates 
    FROM doctor_availability 
    WHERE doctor_id = ?
");
$query->bind_param("i", $doctor_id);
$query->execute();
$result = $query->get_result();

$availability = [
    'days_of_week' => [],
    'unavailable_dates' => []
];

while ($row = $result->fetch_assoc()) {
    if (!empty($row['day_of_week'])) {
        $availability['days_of_week'][] = $row['day_of_week'];
    }
    if (!empty($row['unavailable_dates'])) {
        $availability['unavailable_dates'][] = $row['unavailable_dates'];
    }
}

echo json_encode($availability);
