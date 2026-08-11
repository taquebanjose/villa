<?php
session_start();
include 'db/connection.php';

// Check if ID and Action (archive/restore) are provided
if (isset($_GET['id']) && isset($_SESSION['user_id'])) {
    $res_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];
    $new_status = ($_GET['action'] === 'restore') ? 0 : 1;

    // Security check: only update if the reservation belongs to the logged-in user
    $stmt = $conn->prepare("UPDATE reservations SET is_archived = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("iii", $new_status, $res_id, $user_id);
    $stmt->execute();
}

// Go back to the reservations page
header("Location: myreservations.php?msg=success");
exit();