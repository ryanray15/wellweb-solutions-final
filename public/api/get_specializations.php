<?php
require_once '../../config/database.php';
$db = include '../../config/database.php';

$specializations = $db->query("SELECT * FROM specializations")->fetch_all(MYSQLI_ASSOC);

echo json_encode($specializations);
