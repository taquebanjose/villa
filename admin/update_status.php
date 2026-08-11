<?php
session_start();
include '../db/connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SESSION['role'] === 'admin') {
    $id = $_POST['res_id'];
    $new_status = $_POST['status'];

    $sql = "UPDATE reservations SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $new_status, $id);
    
    if ($stmt->execute()) {
        header("Location: dashboard.php?msg=updated");
    } else {
        header("Location: dashboard.php?msg=error");
    }
}
?>