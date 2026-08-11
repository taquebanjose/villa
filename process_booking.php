<?php
session_start();
include 'db/connection.php';
include 'admin/functions.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $date = $_POST['booking_date']; 
    $selected_val = $_POST['specific_time']; // '08:00:00', '19:00:00', or '08:00:01'
    $payment = $_POST['payment_method']; // Captures 'GCash' or 'Cash'

    // Start a Transaction for safe concurrency
    $conn->begin_transaction();

    try {
        // --- 1. DOUBLE-BOOKING PREVENTATIVE CHECK ---
        $check_stmt = $conn->prepare("SELECT specific_time FROM reservations WHERE date = ? AND status IN ('confirmed', 'pending') FOR UPDATE");
        $check_stmt->bind_param("s", $date);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        $taken_slots = [];
        while ($row = $check_result->fetch_assoc()) {
            $taken_slots[] = date("H:i:s", strtotime($row['specific_time']));
        }
        $check_stmt->close();

        $is_available = true;

        // Validation logic
        if (in_array('08:00:01', $taken_slots)) {
            $is_available = false; // Date fully locked by 22-hour stay
        } elseif ($selected_val == "08:00:01" && count($taken_slots) > 0) {
            $is_available = false; // Cannot book 22 hours if any session is taken
        } elseif (in_array($selected_val, $taken_slots)) {
            $is_available = false; // Requested slot occupied
        }

        if (!$is_available) {
            throw new Exception("slot_taken");
        }

        // --- 2. ASSIGN PRICE AND LABEL ---
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

        // --- 3. INSERT RESERVATION ---
        $stmt = $conn->prepare("INSERT INTO reservations (user_id, date, time, specific_time, payment_type, total_price, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("issssd", $user_id, $date, $time_label, $selected_val, $payment, $total_price);

        if (!$stmt->execute()) {
            throw new Exception("db_error");
        }

        $new_id = $conn->insert_id;
        
        // Log activity
        $log_detail = "Booking #$new_id created ($time_label via $payment). Revenue: ₱" . number_format($total_price, 2);
        logActivity($conn, 'BOOKING', $log_detail);

        $conn->commit(); // Save changes
        header("Location: confirmation.php?id=" . $new_id);
        exit();

    } catch (Exception $e) {
        $conn->rollback(); // Undo if any error occurred
        header("Location: booking.php?error=" . $e->getMessage());
        exit();
    }
}
?>