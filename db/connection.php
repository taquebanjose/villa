<?php
$db_url = getenv('DATABASE_URL');
$dbparts = parse_url($db_url);

$host = $dbparts['host'];
$port = $dbparts['port'];
$user = $dbparts['user'];
$password = $dbparts['pass'];
$dbname = ltrim($dbparts['path'], '/');

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>