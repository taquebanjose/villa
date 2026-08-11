<?php
session_start();
include 'db/connection.php';

// 1. Security check
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// 2. Fetch Detailed Reservation Data (Updated to include total_price)
$stmt = $conn->prepare("SELECT r.*, u.name, u.email 
                        FROM reservations r 
                        JOIN users u ON r.user_id = u.id 
                        WHERE r.id = ? AND r.user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$reservation = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$reservation) {
    die("Reservation not found.");
}

// 3. Status Badge Color Logic - Dynamic adjustment handled via CSS Variables
$status = strtolower($reservation['status']);

// 4. Logic for Stay Session Display
$time_val = $reservation['time'];
$display_session = "";

if ($time_val == "08:00:00") {
    $display_session = "☀️ Day Session";
} elseif ($time_val == "19:00:00") {
    $display_session = "🌙 Night Session";
} elseif ($time_val == "08:00:01") {
    $display_session = "⏳ 22 Hours Booking";
} else {
    $display_session = !empty($time_val) ? date("h:i A", strtotime($time_val)) : "02:00 PM";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Ticket - Villa Marciana</title>
    
    <!-- Prevent Light/Dark Mode Flash on Page Load -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <link rel="stylesheet" href="css/style.css">
    <style>
        /* --- DYNAMIC THEME SYSTEM (CSS VARIABLES) --- */
        :root {
            --bg-color: #fcfbfa;
            --text-main: #111111;
            --text-sub: #555555;
            --card-bg: rgba(255, 255, 255, 0.9);
            --card-border: rgba(212, 175, 55, 0.2);
            --nav-bg: rgba(255, 255, 255, 0.8);
            --border-subtle: rgba(0, 0, 0, 0.05);
            --shadow-color: rgba(212, 175, 55, 0.08);
            --gold-accent: #d4af37;
            --gold-text: #b8931d;
            --item-bg: #ffffff;
            --badge-pending: #b8931d;
            --badge-confirmed: #2e7d32;
            --badge-cancelled: #c62828;
        }

        [data-theme="dark"] {
            --bg-color: #0f0e0d;
            --text-main: #f1ede1;
            --text-sub: #b0b5c0;
            --card-bg: rgba(25, 24, 22, 0.85);
            --card-border: rgba(255, 255, 255, 0.08);
            --nav-bg: rgba(15, 14, 13, 0.85);
            --border-subtle: rgba(255, 255, 255, 0.08);
            --shadow-color: rgba(0, 0, 0, 0.5);
            --gold-accent: #d4af37;
            --gold-text: #e6c65c;
            --item-bg: rgba(35, 33, 30, 0.9);
            --badge-pending: #e6c65c;
            --badge-confirmed: #4caf50;
            --badge-cancelled: #ef5350;
        }

        body, html { 
            margin: 0; 
            padding: 0; 
            height: 100%; 
            overflow-x: hidden; 
            background-color: var(--bg-color);
            color: var(--text-main);
            font-family: 'Montserrat', sans-serif;
            transition: background-color 0.4s ease, color 0.4s ease;
        }

        .centered-wrapper {
            display: flex; justify-content: center; align-items: center; 
            min-height: 100vh; padding: 100px 20px 40px 20px; box-sizing: border-box;
            background: radial-gradient(circle at top left, rgba(212, 175, 55, 0.03), transparent 40%), var(--bg-color);
        }

        .modern-nav { 
            position: absolute; top: 0; left: 0; width: 100%; z-index: 100; 
            background: var(--nav-bg) !important; 
            backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-subtle);
        }
        
        .nav-title {
            color: var(--gold-accent);
            font-family: 'Cinzel', serif;
            font-weight: 500;
        }

        /* --- THEME TOGGLE BUTTON --- */
        .theme-toggle-btn {
            background: var(--card-bg);
            border: 1px solid var(--border-subtle);
            color: var(--text-main);
            border-radius: 50px;
            padding: 8px 16px;
            font-size: 0.9rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(15px);
            transition: all 0.3s ease;
        }
        .theme-toggle-btn:hover {
            border-color: var(--gold-accent);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px var(--shadow-color);
        }

        /* --- PREMIUM TICKET CARD --- */
        .ticket-card {
            background: var(--card-bg); 
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 32px; overflow: hidden;
            max-width: 450px; width: 100%; 
            border: 1px solid var(--card-border);
            box-shadow: 0 30px 70px var(--shadow-color), 0 10px 30px rgba(0, 0, 0, 0.02);
            animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        /* Elegant Gradient Header */
        .ticket-header { 
            background: linear-gradient(135deg, #d4af37, #b8931d); 
            padding: 30px 25px; text-align: center; color: #ffffff; 
        }
        .ticket-header h2 {
            margin: 0; font-family: 'Cinzel', serif; font-weight: 400; letter-spacing: 2px; font-size: 1.4rem;
        }

        .ticket-body { padding: 35px 30px; }

        /* Clean Card Frame for QR Code & Prominent Reference Number */
        .qr-section {
            text-align: center;
            margin-bottom: 25px;
        }
        .qr-container { 
            background: var(--item-bg); padding: 18px; border-radius: 20px; width: 160px; 
            margin: 0 auto 15px; 
            box-shadow: 0 15px 35px var(--shadow-color);
            border: 1px solid var(--card-border);
        }
        .qr-container img { width: 100%; display: block; border-radius: 8px; }

        .ticket-reference-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--text-sub);
            font-weight: 700;
            margin-bottom: 4px;
        }

        .ticket-reference-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--gold-accent);
            letter-spacing: 1.5px;
            font-family: 'Cinzel', serif;
        }

        .info-row { display: flex; justify-content: space-between; align-items: center; margin: 15px 0; font-size: 0.9rem; }
        .info-label { color: var(--text-sub); font-weight: 500; }
        .info-value { color: var(--text-main); font-weight: 700; text-align: right; }
        
        /* Dashed Ticket Divider with Circular Cutouts */
        .ticket-divider { border-top: 2px dashed rgba(212, 175, 55, 0.3); margin: 30px 0; position: relative; }
        .ticket-divider::before, .ticket-divider::after {
            content: ''; position: absolute; width: 24px; height: 24px;
            background: var(--bg-color); border-radius: 50%; top: -13px;
            border: 1px solid var(--card-border);
        }
        .ticket-divider::before { left: -43px; box-shadow: inset -6px 0 8px rgba(0,0,0,0.01); }
        .ticket-divider::after { right: -43px; box-shadow: inset 6px 0 8px rgba(0,0,0,0.01); }

        /* Premium Badge Styling */
        .status-badge {
            background: var(--item-bg);
            padding: 6px 16px; 
            border-radius: 50px; font-size: 0.72rem; font-weight: 800;
            letter-spacing: 1px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.02);
        }
        .status-pending { color: var(--badge-pending); border: 1px solid var(--badge-pending); }
        .status-confirmed { color: var(--badge-confirmed); border: 1px solid var(--badge-confirmed); }
        .status-cancelled { color: var(--badge-cancelled); border: 1px solid var(--badge-cancelled); }

        /* Gold Action Button */
        .print-btn {
            background: var(--gold-accent); color: #0f0e0d; width: 100%; padding: 16px;
            border-radius: 50px; border: none; font-weight: 800;
            text-transform: uppercase; letter-spacing: 1.5px; cursor: pointer;
            margin-bottom: 20px; transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.2);
        }
        .print-btn:hover {
            background: var(--gold-text);
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(212, 175, 55, 0.3);
        }

        @media print {
            body { background: white !important; color: black !important; }
            .modern-nav, .no-print { display: none !important; }
            .centered-wrapper { background: none !important; padding: 0 !important; display: block; }
            .ticket-card { box-shadow: none; border: 1px solid #d4af37; margin: 20px auto; background: #fff !important; color: #000 !important; }
            .ticket-divider::before, .ticket-divider::after { display: none; }
            .info-value, .info-label, .ticket-reference-value { color: #000 !important; }
        }
    </style>
</head>
<body>

    <nav class="modern-nav">
        <div class="nav-container" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 20px; max-width: 1200px; margin: 0 auto;">
            <h1 class="nav-title" style="cursor:pointer; margin:0; font-size: 1.2rem;" onclick="location.href='index.php'">Villa Marciana</h1>
            <div style="display: flex; align-items: center; gap: 12px;">
                <!-- Theme Toggle Button -->
                <button class="theme-toggle-btn" id="themeToggle" onclick="toggleTheme()">
                    <span id="themeIcon">🌙</span> <span id="themeText">Dark</span>
                </button>
                <a href="myreservations.php" class="nav-btn-alt">My Bookings</a>
            </div>
        </div>
    </nav>

    <div class="centered-wrapper">
        <div class="ticket-card">
            <div class="ticket-header">
                <h2>RESERVATION TICKET</h2>
                <p style="margin:5px 0 0; opacity: 0.9; font-size: 0.72rem; font-weight: 800; letter-spacing: 2px;">VILLA MARCIANA RESORT</p>
            </div>

            <div class="ticket-body">
                <!-- QR Code & Larger Reference Number Section -->
                <div class="qr-section">
                    <div class="qr-container">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=VERIFY-VM-<?= $reservation['id']; ?>" alt="Access QR">
                    </div>
                    <div class="ticket-reference-label">Reference Number</div>
                    <div class="ticket-reference-value">#VM-<?= str_pad($reservation['id'], 5, '0', STR_PAD_LEFT); ?></div>
                </div>

                <div class="info-row">
                    <span class="info-label">Guest Name</span>
                    <span class="info-value"><?= htmlspecialchars($reservation['name']); ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Check-in Date</span>
                    <span class="info-value"><?= date("M d, Y", strtotime($reservation['date'])); ?></span>
                </div>

                <div class="info-row">
                    <span class="info-label">Stay Session</span>
                    <span class="info-value"><?= $display_session; ?></span>
                </div>

                <div class="info-row">
                    <span class="info-label">Total Amount</span>
                    <span class="info-value" style="color: var(--gold-text); font-size: 1.1rem; font-weight: 800;">₱<?= number_format($reservation['total_price'], 2); ?></span>
                </div>

                <div class="info-row">
                    <span class="info-label">Status</span>
                    <?php 
                        $badgeClass = 'status-pending';
                        if($status == 'confirmed') $badgeClass = 'status-confirmed';
                        if($status == 'cancelled') $badgeClass = 'status-cancelled';
                    ?>
                    <span class="status-badge <?= $badgeClass; ?>"><?= strtoupper($reservation['status']); ?></span>
                </div>

                <div class="ticket-divider"></div>

                <div style="text-align: center;">
                    <p style="color: var(--text-sub); font-size: 0.75rem; margin-bottom: 25px; line-height: 1.6;">
                        Issued on: <?= date("M d, Y h:i A", strtotime($reservation['created_at'])); ?>
                    </p>
                    
                    <div class="no-print">
                        <button class="print-btn" onclick="window.print()">
                            Print / Save Ticket
                        </button>
                        <a href="index.php" style="color: var(--gold-text); text-decoration: none; font-size: 0.85rem; font-weight: 700; letter-spacing: 0.5px;">Return to Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // --- LIGHT / DARK MODE UI SYNC ---
        const activeTheme = document.documentElement.getAttribute('data-theme') || 'light';
        updateThemeUI(activeTheme);

        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeUI(newTheme);
        }

        function updateThemeUI(theme) {
            const themeIcon = document.getElementById('themeIcon');
            const themeText = document.getElementById('themeText');
            if (theme === 'dark') {
                themeIcon.innerText = '☀️';
                themeText.innerText = 'Light';
            } else {
                themeIcon.innerText = '🌙';
                themeText.innerText = 'Dark';
            }
        }
    </script>
</body>
</html>