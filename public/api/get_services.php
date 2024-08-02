<?php
require_once '../../src/autoload.php';
require_once '../../config/database.php';

$db = include '../../config/database.php';

$result = $db->query("SELECT service_id, name FROM services");
$services = [];
while ($row = $result->fetch_assoc()) {
    $services[] = $row;
}

echo json_encode($services);
