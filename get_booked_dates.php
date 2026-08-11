<?php
include 'db/connection.php';

// We fetch both 'confirmed' and 'pending' to prevent double-booking 
// while the admin is still reviewing a request.
$sql = "SELECT date FROM reservations WHERE status IN ('confirmed', 'pending')";
$result = $conn->query($sql);

$bookedDates = [];

if ($result) {
    while($row = $result->fetch_assoc()) {
        // Ensure the date is in YYYY-MM-DD format for Flatpickr
        $bookedDates[] = $row['date'];
    }
}

// Set header to JSON so the JavaScript frontend can read it correctly
header('Content-Type: application/json');

// If no dates are found, it returns an empty array [] instead of an error
echo json_encode($bookedDates);