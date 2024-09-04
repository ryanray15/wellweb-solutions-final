<?php
// Database configuration
$host = 'localhost';
$db = 'capstone_system_final';
$user = 'root'; // Change this if you have a different MySQL user
$pass = ''; // Change this if your MySQL user has a password

// Create a connection
$mysqli = new mysqli($host, $user, $pass, $db); //test push 123 merge

// Check connection
if ($mysqli->connect_error) {
    die('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}
echo 'Connection successful!';
