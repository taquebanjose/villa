<?php
session_start();
include '../db/connection.php';
include 'functions.php'; // Includes your logActivity function

// 1. Strict Admin Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // 2. Fetch the guest name and reservation date BEFORE deleting
    // This allows the log to be specific (e.g., "Deleted John Doe's reservation")
    $query = "SELECT u.name, r.date 
              FROM reservations r 
              JOIN users u ON r.user_id = u.id 
              WHERE r.id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $guest_name = $row['name'];
        $res_date = date("M d, Y", strtotime($row['date']));

        // 3. Perform the actual deletion
        $del_stmt = $conn->prepare("DELETE FROM reservations WHERE id = ?");
        $del_stmt->bind_param("i", $id);
        
        if ($del_stmt->execute()) {
            // 4. Log the action using your smart logActivity function
            $log_details = "Permanently deleted reservation for $guest_name (Scheduled for $res_date).";
            logActivity($conn, 'DELETE_RESERVATION', $log_details);
            
            header("Location: dashboard.php?msg=deleted");
        } else {
            header("Location: dashboard.php?msg=error");
        }
    } else {
        // Record not found
        header("Location: dashboard.php");
    }
} else {
    // No ID provided
    header("Location: dashboard.php");
}
exit();