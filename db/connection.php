<?php
$host = 'dpg-d9tel0h42hec7381noug-a';
$port = '5432';
$dbname = 'villa_db_7x7e';
$user = 'villa_db_7x7e_user';
$password = 'Ok68wkiOIHU7cTbjO3i368QsNyH7oSrU';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
date_default_timezone_set('Asia/Manila');
?>