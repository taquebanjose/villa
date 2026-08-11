<?php
session_start();
require_once 'db/connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $date = $_POST['booking_date']; 
    $selected_val = $_POST['specific_time'];
    $payment = $_POST['payment_method'];

    try {
        $pdo->beginTransaction();

        $check_stmt = $pdo->prepare("SELECT specific_time FROM reservations WHERE date = ? AND status IN ('confirmed', 'pending') FOR UPDATE");
        $check_stmt->execute([$date]);
        $reservations = $check_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $taken_slots = [];
        foreach ($reservations as $row) {
            $taken_slots[] = date("H:i:s", strtotime($row['specific_time']));
        }

        $is_available = true;

        if (in_array('08:00:01', $taken_slots)) {
            $is_available = false;
        } elseif ($selected_val == "08:00:01" && count($taken_slots) > 0) {
            $is_available = false;
        } elseif (in_array($selected_val, $taken_slots)) {
            $is_available = false;
        }

        if (!$is_available) {
            throw new Exception("slot_taken");
        }

        $total_price = 0;
        $time_label = "";

        if ($selected_val == "08:00:00") {
            $total_price = 8500.00;
            $time_label = "Day Session";
        } elseif ($selected_val == "19:00:00") {
            $total_price = 9500.00;
            $time_label = "Night Session";
        } elseif ($selected_val == "08:00:01") {
            $total_price = 18000.00;
            $time_label = "22 Hours Stay";
        }

        $stmt = $pdo->prepare("INSERT INTO reservations (user_id, date, time, specific_time, payment_type, total_price, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $success = $stmt->execute([$user_id, $date, $time_label, $selected_val, $payment, $total_price]);

        if (!$success) {
            throw new Exception("db_error");
        }

        $new_id = trim($pdo->lastInsertId());
        
        // Ensure absolutely no line breaks can leak into the header string
        $safe_id = preg_replace('/[\r\n]+/', '', $new_id);
        
        $pdo->commit();
        header("Location: confirmation.php?id=" . $safe_id);
        exit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $safe_error = urlencode(trim($e->getMessage()));
        header("Location: booking.php?error=" . $safe_error);
        exit();
    }
}
?>