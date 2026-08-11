<?php
$host = '127.0.0.1'; // .
$user = 'root';
$pass = '';
$dbname = 'villa_db';
$port = 3306;

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("Database failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
date_default_timezone_set('Asia/Manila');
?>