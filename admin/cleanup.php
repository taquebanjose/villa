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
try {
    $query = "UPDATE reservations SET is_archived = 1 WHERE date < ? OR status = 'cancelled'";
    $stmt = $pdo->prepare($query);
    
    if ($stmt->execute([$today])) {
        // Pass $pdo if logActivity expects a database connection, or adjust based on your functions.php
        logActivity($pdo, 'CLEANUP', "System moved past/cancelled bookings to archive.");
        header("Location: dashboard.php?msg=cleanup_done");
    } else {
        header("Location: dashboard.php?msg=error");
    }
} catch (PDOException $e) {
    header("Location: dashboard.php?msg=error");
}
exit();