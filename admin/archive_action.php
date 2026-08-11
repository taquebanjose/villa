<?php
session_start();
include '../db/connection.php'; // Ensure path to connection is correct

// 1. Security check: Only Admins allowed
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if (isset($_GET['id'])) {
    $res_id = $_GET['id'];
    $action = ($_GET['action'] === 'restore') ? 0 : 1;

    // 2. Update the archive status
    $stmt = $conn->prepare("UPDATE reservations SET is_archived = ? WHERE id = ?");
    $stmt->bind_param("ii", $action, $res_id);
    $stmt->execute();
}

// 3. Go back to the dashboard
header("Location: dashboard.php?msg=archive_success");
exit();