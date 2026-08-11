<?php
session_start();
include 'db/connection.php';

// 1. Security: Only logged-in users can see their own confirmed receipts
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    die("Unauthorized access.");
}

$booking_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];
$display_name = $_SESSION['name'] ?? 'Valued Guest'; 

$query = "SELECT * FROM reservations WHERE id = ? AND user_id = ? AND status = 'confirmed'";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) { 
    die("Booking not found or not yet confirmed by Admin."); 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Villa_Marciana_Ticket_<?= $booking_id; ?></title>
    <style>
        /* Modern reset for the printer */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body { 
            font-family: 'Times New Roman', serif; 
            background: #f0f0f0; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            min-height: 100vh;
            padding: 40px 0;
        }

        /* The Ticket Design */
        .ticket {
            background: #fff;
            width: 550px; /* Fixed width for perfect symmetry */
            padding: 50px;
            border: 2px solid #111;
            outline: 12px solid #fff;
            box-shadow: 0 0 0 14px #111;
            text-align: center;
            position: relative;
        }

        .header h1 {
            text-transform: uppercase;
            letter-spacing: 6px;
            font-size: 2.5rem;
            margin-bottom: 5px;
        }

        .header p {
            color: #666;
            font-size: 0.9rem;
            letter-spacing: 2px;
            margin-bottom: 20px;
        }

        .divider {
            height: 2px;
            background: #111;
            margin: 0 auto 25px auto;
            width: 60%;
        }

        .status-badge {
            display: inline-block;
            border: 2px solid #111;
            padding: 8px 25px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.9rem;
            margin-bottom: 40px;
        }

        /* Information Alignment */
        .info-container {
            width: 100%;
            margin-bottom: 30px;
        }

        .info-row {
            display: flex;
            justify-content: center; /* Centers the whole row */
            margin-bottom: 12px;
        }

        .label {
            text-align: right;
            width: 140px;
            color: #888;
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: bold;
            padding-right: 15px;
        }

        .value {
            text-align: left;
            width: 180px;
            font-weight: bold;
            color: #000;
        }

        .footer-text {
            margin-top: 40px;
            font-size: 0.85rem;
            color: #555;
            line-height: 1.5;
        }

        .qr-mock {
            margin-top: 30px;
            border: 1px dashed #bbb;
            width: 90px;
            height: 90px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.6rem;
            color: #bbb;
        }

        /* PRINT SETTINGS */
        .no-print-area {
            margin-bottom: 30px;
            text-align: center;
        }

        .btn-print {
            background: #111;
            color: #fff;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            font-size: 1rem;
        }

        @media print {
            /* This tells the browser to NOT print the page title/date/URL */
            @page { margin: 0; }
            
            body { 
                background: #fff; 
                padding: 0; 
                margin: 0; 
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
            }

            .no-print-area { display: none; }

            .ticket { 
                box-shadow: none; 
                outline: none; 
                border: 2px solid #000; 
                margin: auto; 
            }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print-area">
        <button class="btn-print" onclick="window.print()">PRINT OFFICIAL TICKET</button>
        <p style="margin-top: 10px; color: #666; font-size: 0.8rem;">Note: Browser headers and footers are automatically hidden.</p>
    </div>

    <div class="ticket">
        <div class="header">
            <h1>VILLA MARCIANA</h1>
            <p>ESTABLISHED 2026</p>
        </div>

        <div class="divider"></div>
        <div class="status-badge">Confirmed Booking</div>

        <div class="info-container">
            <div class="info-row">
                <span class="label">Reference:</span>
                <span class="value">#VM-<?= str_pad($data['id'], 5, '0', STR_PAD_LEFT); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Guest:</span>
                <span class="value"><?= htmlspecialchars($display_name); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Date:</span>
                <span class="value"><?= date("M d, Y", strtotime($data['date'])); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Session:</span>
                <span class="value"><?= $data['time']; ?></span>
            </div>
            <div class="info-row">
                <span class="label">Payment:</span>
                <span class="value"><?= htmlspecialchars($data['payment_type']); ?></span>
            </div>
        </div>

        <div class="qr-mock">
            OFFICIAL TICKET
        </div>

        <div class="footer-text">
            <p><strong>Thank you for choosing Villa Marciana.</strong></p>
            <p>Please present this confirmation at the gate.</p>
            <p style="font-style: italic; margin-top: 10px;">Your escape to paradise awaits.</p>
        </div>
    </div>

</body>
</html>