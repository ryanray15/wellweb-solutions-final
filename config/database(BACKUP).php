<?php
// config/database.php

$host = 'localhost';
$db = 'capstone_system_final';
$user = 'root'; // Change this if you have a different MySQL user
$pass = ''; // Change this if your MySQL user has a password

$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_error) {
    die('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}

return $mysqli;
?>
