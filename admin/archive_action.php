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

    // 2. Update the archive status (Fixed to use $pdo and array execution)
    $stmt = $pdo->prepare("UPDATE reservations SET is_archived = ? WHERE id = ?");
    $stmt->execute([$action, $res_id]);
}

// 3. Go back to the dashboard
header("Location: dashboard.php?msg=archive_success");
exit();