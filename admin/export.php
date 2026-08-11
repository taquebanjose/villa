<?php
session_start();
include '../db/connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    exit("Denied");
}

// 1. Set headers to force download as CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Villa_Marciana_Archive_' . date('Y-m-d') . '.csv');

// 2. Open the output stream
$output = fopen('php://output');

// 3. Set the column headers for the Excel file
fputcsv($output, array('Reservation ID', 'Guest Name', 'Stay Date', 'Time', 'Payment Type', 'Status'));

// 4. Fetch the data from the database
$query = "SELECT r.id, u.name, r.date, r.time, r.payment_type, r.status 
          FROM reservations r 
          JOIN users u ON r.user_id = u.id 
          WHERE r.status = 'archived' 
          ORDER BY r.date DESC";

$result = $conn->query($query);

// 5. Loop through the data and add to CSV
while ($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}

fclose($output);
exit();