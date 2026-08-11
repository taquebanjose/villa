<?php
session_start();
include 'db/connection.php';

// 1. Security: Redirect to login if session is not active
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Guest';

// 2. Fetch User's Reservations
$query = "SELECT * FROM reservations WHERE user_id = ? ORDER BY date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// 3. Fetch the closest upcoming confirmed reservation for the timer
$upcoming_query = "SELECT date, time FROM reservations 
                  WHERE user_id = ? AND date >= CURDATE() AND status = 'confirmed' 
                  ORDER BY date ASC LIMIT 1";
$up_stmt = $conn->prepare($upcoming_query);
$up_stmt->bind_param("i", $user_id);
$up_stmt->execute();
$upcoming = $up_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Portal | Villa Marciana</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Layout Adjustments */
        .portal-wrapper { padding-top: 120px; max-width: 1100px; margin: 0 auto; padding-inline: 20px; }
        
        /* Countdown Styling */
        .timer-card {
            background: rgba(0, 255, 136, 0.05);
            border: 1px solid rgba(0, 255, 136, 0.2);
            border-radius: 30px;
            padding: 30px;
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }
        .countdown-display { display: flex; gap: 15px; }
        .time-unit { 
            background: rgba(0, 0, 0, 0.3); 
            padding: 15px; 
            border-radius: 15px; 
            min-width: 70px; 
            text-align: center;
            border: 1px solid rgba(255,255,255,0.05);
        }
        .time-unit span { display: block; font-size: 1.8rem; font-weight: 900; color: #00ff88; }
        .time-unit label { font-size: 0.6rem; text-transform: uppercase; color: #888; }

        /* Table Design */
        .table-container {
            background: rgba(20, 20, 20, 0.6);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .print-link {
            color: #00ff88;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: bold;
            transition: 0.3s;
        }
        .print-link:hover { text-shadow: 0 0 10px rgba(0, 255, 136, 0.5); }
    </style>
</head>
<body class="dark">

<nav class="modern-nav">
    <div class="nav-container">
        <h1 class="nav-title" onclick="location.href='index.php'">Villa Marciana</h1>
        
        <div class="nav-buttons">
            <a href="index.php" class="nav-btn-alt">Back to Home</a>
            
            <div class="account-toggle">
                👤 <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Guest'); ?>
            </div>
            
            <a href="reserve.php" class="nav-btn">✨ New Reservation</a>
            
            <a href="logout.php" class="nav-btn logout-btn">🚪 Logout</a>
        </div>
    </div>
</nav>

<main class="portal-wrapper">
    <div style="margin-bottom: 40px;">
        <h1 style="font-size: 2.5rem; margin: 0;">Welcome, <?php echo htmlspecialchars($user_name); ?>!</h1>
        <p style="color: #888;">Manage your bookings and check-in details below.</p>
    </div>

    <?php if ($upcoming): ?>
    <div class="timer-card">
        <div>
            <h3 style="margin: 0; color: #fff;">Your Next Stay</h3>
            <p style="color: #888; margin-top: 5px;"><?php echo date("F j, Y", strtotime($upcoming['date'])); ?> (<?php echo $upcoming['time']; ?>)</p>
        </div>
        <div class="countdown-display">
            <div class="time-unit"><span id="days">00</span><label>Days</label></div>
            <div class="time-unit"><span id="hours">00</span><label>Hours</label></div>
            <div class="time-unit"><span id="mins">00</span><label>Mins</label></div>
        </div>
    </div>
    <?php endif; ?>

    <div class="table-container">
        <h3 style="margin-top: 0; margin-bottom: 25px; color: #00ff88;">Booking History</h3>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Ref ID</th>
                        <th>Date</th>
                        <th>Session</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td style="color: #666; font-family: monospace;">#VM-<?php echo $row['id']; ?></td>
                        <td><strong><?php echo date("M d, Y", strtotime($row['date'])); ?></strong></td>
                        <td><?php echo $row['time']; ?></td>
                        <td>
                            <span class="status-pill <?php echo strtolower($row['status']); ?>">
                                <?php echo ucfirst($row['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if(strtolower($row['status']) == 'confirmed'): ?>
                                <a href="print_receipt.php?id=<?php echo $row['id']; ?>" target="_blank" class="print-link">
                                    🖨️ Print Receipt
                                </a>
                            <?php else: ?>
                                <span style="color: #444; font-size: 0.85rem;">Pending Approval</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php if ($upcoming): ?>
<script>
    const target = new Date("<?php echo $upcoming['date']; ?>").getTime();
    function updateCountdown() {
        const now = new Date().getTime();
        const gap = target - now;
        if (gap > 0) {
            document.getElementById('days').innerText = Math.floor(gap / (1000 * 60 * 60 * 24));
            document.getElementById('hours').innerText = Math.floor((gap % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            document.getElementById('mins').innerText = Math.floor((gap % (1000 * 60 * 60)) / (1000 * 60));
        }
    }
    setInterval(updateCountdown, 1000);
    updateCountdown();
</script>
<?php endif; ?>

</body>
</html>