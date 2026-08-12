<?php
session_start();
include '../db/connection.php';
include 'functions.php'; // Included for logging functionality if available

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SESSION['role'] === 'admin') {
    $id = $_POST['res_id'];
    $new_status = $_POST['status'];

    $sql = "UPDATE reservations SET status = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$new_status, $id])) {
        if (function_exists('logActivity')) {
            logActivity($pdo, 'UPDATE_STATUS', "Updated reservation ID $id status to $new_status.");
        }
        header("Location: dashboard.php?msg=updated");
    } else {
        header("Location: dashboard.php?msg=error");
    }
}