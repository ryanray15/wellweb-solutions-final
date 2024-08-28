<?php
require_once '../../config/database.php';

$db = include '../../config/database.php';

$query = $_GET['query'] ?? '';

if ($query) {
    // Prepare the SQL statement to select distinct doctor names
    $stmt = $db->prepare("
        SELECT DISTINCT u.user_id, CONCAT(u.first_name, ' ', u.middle_initial, ' ', u.last_name) as name
        FROM users u
        JOIN doctor_specializations ds ON u.user_id = ds.doctor_id
        JOIN specializations s ON ds.specialization_id = s.id
        WHERE u.role = 'doctor' AND (u.first_name LIKE ? OR u.last_name LIKE ?)
    ");

    $likeQuery = "%" . $db->real_escape_string($query) . "%";
    $stmt->bind_param('ss', $likeQuery, $likeQuery);
    $stmt->execute();
    $result = $stmt->get_result();

    $doctors = [];
    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }

    echo json_encode($doctors);
} else {
    echo json_encode([]);
}
