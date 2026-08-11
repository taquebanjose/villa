<?php
session_start();
include '../db/connection.php';
include 'functions.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    exit("Unauthorized");
}

$today = date('Y-m-d');

// Logic: Archive everything that has passed OR is already cancelled
// This keeps the "Active Reservations" list focused only on upcoming stays
$query = "UPDATE reservations SET is_archived = 1 WHERE date < ? OR status = 'cancelled'";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $today);

if ($stmt->execute()) {
    logActivity($conn, 'CLEANUP', "System moved past/cancelled bookings to archive.");
    $stmt->close(); // Clean up the statement
    header("Location: dashboard.php?msg=cleanup_done");
} else {
    header("Location: dashboard.php?msg=error");
}
exit();