<?php
include 'db/connection.php';

// We fetch both 'confirmed' and 'pending' to prevent double-booking 
// while the admin is still reviewing a request using PDO.
$sql = "SELECT date FROM reservations WHERE status IN ('confirmed', 'pending')";
$stmt = $pdo->query($sql);

$bookedDates = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Ensure the date is in YYYY-MM-DD format for Flatpickr
    $bookedDates[] = $row['date'];
}

// Set header to JSON so the JavaScript frontend can read it correctly
header('Content-Type: application/json');

// If no dates are found, it returns an empty array [] instead of an error
echo json_encode($bookedDates);