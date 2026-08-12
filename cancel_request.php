<?php
session_start();
include 'db/connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $res_id = (int)$_GET['id'];
    $user_id = $_SESSION['user_id'];

    // Fixed: Changed $conn to $pdo and updated execution for PDO syntax
    $stmt = $pdo->prepare("UPDATE reservations SET status = 'pending_cancel' WHERE id = ? AND user_id = ? AND (status = 'confirmed' OR status = 'pending')");
    
    if ($stmt->execute([$res_id, $user_id])) {
        // Redirect with the specific message for the animation
        header("Location: myreservations.php?msg=request_sent");
    } else {
        header("Location: myreservations.php?msg=error");
    }
} else {
    header("Location: myreservations.php");
}
exit();
?>