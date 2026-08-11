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

    // Update to 'pending_cancel' so Admin can approve the cancellation
    $stmt = $conn->prepare("UPDATE reservations SET status = 'pending_cancel' WHERE id = ? AND user_id = ? AND (status = 'confirmed' OR status = 'pending')");
    $stmt->bind_param("ii", $res_id, $user_id);

    if ($stmt->execute()) {
        // Redirect with the specific message for the animation
        header("Location: myreservations.php?msg=request_sent");
    } else {
        header("Location: myreservations.php?msg=error");
    }
    $stmt->close();
} else {
    header("Location: myreservations.php");
}
exit();
?>