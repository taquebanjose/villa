<?php
session_start();
include '../db/connection.php';

// Strict Admin Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$res_id = $_GET['id'];
$displayName = isset($_SESSION['name']) ? explode(' ', $_SESSION['name'])[0] : 'Admin';

// Fetch specific reservation details with user info
$sql = "SELECT r.*, u.name as customer_name, u.email as customer_email 
        FROM reservations r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $res_id);
$stmt->execute();
$reservation = $stmt->get_result()->fetch_assoc();

if (!$reservation) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Booking | Villa Marciana</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dark">

    <nav class="modern-nav">
        <div class="nav-container">
            <h1 class="nav-title" onclick="location.href='../index.php'">Villa Marciana</h1>
            <div class="nav-buttons">
                <a href="dashboard.php" style="color: #fff; text-decoration: none; font-size: 0.9rem;">← Back to Dashboard</a>
                <div class="account-dropdown">
                    <button class="account-toggle" onclick="toggleMenu()">👤 <?= $displayName ?></button>
                    <div id="accountMenu" class="account-menu">
                        <a href="../myreservations.php">My Bookings</a>
                        <a href="../logout.php" style="color: #ff4444;">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="hero-wrapper">
        <div class="hero-content">
            <div style="text-align: left; margin-bottom: 25px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px;">
                <h2 style="color: #00ff88; margin: 0;">Reservation Details</h2>
                <p style="color: rgba(255,255,255,0.5); font-size: 0.85rem;">Booking ID: #VM-<?= $reservation['id'] ?></p>
            </div>

            <div style="text-align: left; display: flex; flex-direction: column; gap: 15px;">
                <div>
                    <label>Guest Name</label>
                    <div style="font-size: 1.1rem; color: #fff;"><?= htmlspecialchars($reservation['customer_name']) ?></div>
                </div>

                <div>
                    <label>Stay Date & Session</label>
                    <div style="font-size: 1.1rem; color: #fff;">
                        <?= date("F d, Y", strtotime($reservation['date'])) ?> <br>
                        <span style="color: #00bdff; font-size: 0.9rem;">
                            <?= $reservation['time'] == '08:00' ? '☀️ Day Session' : '🌙 Night Session' ?>
                        </span>
                    </div>
                </div>

                <div>
                    <label>Payment Method</label>
                    <div style="color: #fff;"><?= $reservation['payment_type'] ?></div>
                </div>

                <div>
                    <label>Current Status</label> <br>
                    <span class="status-pill <?= $reservation['status'] ?>">
                        <?= str_replace('_', ' ', strtoupper($reservation['status'])) ?>
                    </span>
                </div>
            </div>

            <div style="margin-top: 35px; display: flex; flex-direction: column; gap: 10px;">
                <form action="update_status.php" method="POST">
                    <input type="hidden" name="res_id" value="<?= $reservation['id'] ?>">
                    
                    <?php if($reservation['status'] !== 'confirmed'): ?>
                        <button type="submit" name="status" value="confirmed" class="nav-btn" style="width: 100%; margin-bottom: 10px;">Approve Reservation</button>
                    <?php endif; ?>

                    <?php if($reservation['status'] !== 'cancelled'): ?>
                        <button type="submit" name="status" value="cancelled" class="nav-btn" style="width: 100%; background: transparent; border: 1px solid #ff4444; color: #ff4444;">Cancel Booking</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </main>

    <script>
        function toggleMenu() {
            document.getElementById('accountMenu').classList.toggle('show');
        }
        window.onclick = function(e) {
            if (!e.target.matches('.account-toggle')) {
                const menu = document.getElementById('accountMenu');
                if (menu && menu.classList.contains('show')) menu.classList.remove('show');
            }
        }
    </script>
</body>
</html>