<?php
session_start();
include 'db/connection.php';

// 1. Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- FETCH USER IMAGE & NAME ---
$stmt_user = $pdo->prepare("SELECT name, image FROM users WHERE id = ?");
$stmt_user->execute([$user_id]);
$user_res = $stmt_user->fetch(PDO::FETCH_ASSOC);

$userName = !empty($user_res['name']) ? explode(' ', $user_res['name'])[0] : 'Guest';
$user_image = !empty($user_res['image']) ? 'uploads/' . $user_res['image'] : null;
// 2. Fetch User's Reservations (Ordering by newest date first)
$query = "SELECT * FROM reservations WHERE user_id = ? ORDER BY date DESC, time DESC";
$stmt_res = $pdo->prepare($query);
$stmt_res->execute([$user_id]);
$reservations = $stmt_res->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings | Villa Marciana</title>
    
    <!-- Prevent Light/Dark Mode Flash on Page Load -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/style.css">

    <!-- TUQLAS AI CHATBOT EMBED -->
    <script
      src="https://www.tuqlas.com/chatbot.js"
      data-key="tq_live_76df22af5bc6ea5a49ef85cf1d09713975ce0793"
      data-api="https://www.tuqlas.com"
      defer
    ></script>

    <style>
        /* --- DYNAMIC THEME SYSTEM (CSS VARIABLES) --- */
        :root {
            --bg-color: #f8f9fa;
            --text-main: #111111;
            --text-sub: #444444;
            --card-bg: rgba(255, 255, 255, 0.75);
            --card-border: rgba(255, 255, 255, 0.8);
            --card-hover-bg: rgba(255, 255, 255, 0.95);
            --nav-bg: rgba(255, 255, 255, 0.85);
            --border-subtle: rgba(0, 0, 0, 0.06);
            --shadow-color: rgba(212, 175, 55, 0.06);
            --gold-accent: #d4af37;
            --gold-text: #b8931d;
            --item-bg: rgba(255, 255, 255, 0.6);
            --item-hover-bg: rgba(255, 255, 255, 1);
        }

        [data-theme="dark"] {
            --bg-color: #0f1218;
            --text-main: #f0f0f0;
            --text-sub: #b0b5c0;
            --card-bg: rgba(22, 27, 36, 0.75);
            --card-border: rgba(255, 255, 255, 0.08);
            --card-hover-bg: rgba(28, 35, 48, 0.95);
            --nav-bg: rgba(15, 18, 24, 0.85);
            --border-subtle: rgba(255, 255, 255, 0.1);
            --shadow-color: rgba(0, 0, 0, 0.4);
            --gold-accent: #d4af37;
            --gold-text: #e6c65c;
            --item-bg: rgba(15, 18, 24, 0.6);
            --item-hover-bg: rgba(28, 35, 48, 0.95);
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            transition: background-color 0.4s ease, color 0.4s ease;
            margin: 0;
            font-family: 'Montserrat', sans-serif;
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

        .modern-nav { 
            position: absolute; top: 0; left: 0; width: 100%; z-index: 100; 
            background: var(--nav-bg) !important; 
            backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-subtle);
        }

        /* --- PREMIUM NAVIGATION --- */
        .account-dropdown { position: relative; display: inline-block; }
        .account-toggle {
            cursor: pointer; transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); border-radius: 50px;
            background: var(--card-bg); border: 1px solid var(--border-subtle);
            color: var(--text-main); padding: 6px 20px 6px 8px; display: flex; align-items: center; gap: 10px;
            backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
        }
        .nav-avatar { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 1px solid rgba(212, 175, 55, 0.4); }
        .account-toggle:hover { transform: translateY(-2px); background: rgba(212, 175, 55, 0.1); border-color: var(--gold-accent); box-shadow: 0 6px 25px rgba(212, 175, 55, 0.15); }

        .account-menu {
            display: none; position: absolute; right: 0; top: 140%;
            background: var(--card-hover-bg); backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid var(--border-subtle); border-radius: 20px; width: 240px;
            padding: 12px; box-shadow: 0 20px 50px var(--shadow-color); z-index: 2000;
            flex-direction: column; opacity: 0; transform: translateY(-10px) scale(0.98);
            transform-origin: top right; transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .account-menu.active { display: flex; opacity: 1; transform: translateY(0) scale(1); }
        .account-menu a { padding: 12px 18px; color: var(--text-main); text-decoration: none; display: block; border-radius: 12px; font-size: 0.85rem; font-weight: 500; transition: all 0.3s ease; }
        .account-menu a:hover { background: rgba(212, 175, 55, 0.12); color: var(--gold-text); padding-left: 24px; }

        /* --- BOOKING CONTAINER CARD --- */
        .reserve-card {
            background: var(--card-bg);
            backdrop-filter: blur(30px) saturate(180%);
            -webkit-backdrop-filter: blur(30px) saturate(180%);
            border: 1px solid var(--card-border);
            box-shadow: 0 30px 70px var(--shadow-color), 0 10px 30px rgba(0, 0, 0, 0.02);
            border-radius: 32px; padding: 45px 40px; max-width: 600px; width: 95%; text-align: center;
            margin: auto; margin-top: 50px;
            animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        /* --- RESERVATION ITEMS --- */
        .booking-item {
            background: var(--item-bg); 
            border: 1px solid var(--border-subtle);
            border-radius: 24px; padding: 24px; margin-bottom: 16px; text-align: left;
            display: flex; justify-content: space-between; align-items: center;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .booking-item:hover { 
            background: var(--item-hover-bg); 
            border-color: rgba(212, 175, 55, 0.4);
            box-shadow: 0 12px 30px var(--shadow-color);
            transform: translateY(-2px);
        }

        .booking-date { 
            color: var(--text-main) !important; 
            font-weight: 500; 
            font-size: 1.25rem; 
            font-family: 'Cinzel', serif; 
            letter-spacing: 0.5px;
        }
        .booking-details { 
            color: var(--text-sub) !important; 
            font-size: 0.85rem; 
            margin-top: 8px; 
            line-height: 1.6; 
        }
        .booking-details span { color: var(--text-main); font-weight: 600; }

        /* --- PREMIUM STATUS BADGES --- */
        .status-badge { 
            padding: 6px 14px; 
            border-radius: 50px; 
            font-size: 0.68rem; 
            font-weight: 800; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
            display: inline-block; 
        }
        .status-pending { background: rgba(184, 147, 29, 0.08); color: var(--gold-text); border: 1px solid rgba(184, 147, 29, 0.3); }
        .status-confirmed { background: rgba(46, 125, 50, 0.08); color: #4caf50; border: 1px solid rgba(46, 125, 50, 0.3); }
        .status-cancelled { background: rgba(198, 40, 40, 0.08); color: #ff5252; border: 1px solid rgba(198, 40, 40, 0.3); }
        .status-pending_cancel { background: rgba(216, 67, 21, 0.08); color: #ff7043; border: 1px solid rgba(216, 67, 21, 0.3); }

        /* --- INTERACTIVE ACTIONS --- */
        .ticket-link {
            color: var(--gold-text); font-size: 0.82rem; display: block; text-decoration: none; font-weight: 700; margin-bottom: 8px; transition: all 0.3s ease;
        }
        .ticket-link:hover { color: var(--gold-accent); transform: translateX(-2px); }

        .cancel-link {
            color: var(--text-sub); font-size: 0.8rem; display: block; text-decoration: none; transition: color 0.3s ease;
        }
        .cancel-link:hover { color: #ff3b30; text-decoration: underline; }

        /* --- FLOATING TOAST STYLING --- */
        #success-toast {
            position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%) translateY(20px);
            background: var(--card-hover-bg); border: 1px solid var(--gold-accent); color: var(--text-main);
            padding: 16px 32px; border-radius: 50px; backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px);
            box-shadow: 0 15px 40px var(--shadow-color);
            opacity: 0; display: none; z-index: 3000; 
            transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
            font-size: 0.88rem; font-weight: 700;
        }
        #success-toast.reveal {
            opacity: 1;
            display: block;
            transform: translateX(-50%) translateY(0);
        }

        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

<div id="success-toast">✔️ Action Successful</div>

<nav class="modern-nav">
    <div class="nav-container">
        <h1 class="nav-title" onclick="location.href='index.php'" style="cursor:pointer; color: var(--gold-accent); font-family: 'Cinzel', serif;">Villa Marciana</h1>
        <div class="nav-buttons">
            <!-- Theme Toggle Button -->
            <button class="theme-toggle-btn" id="themeToggle" onclick="toggleTheme()">
                <span id="themeIcon">🌙</span> <span id="themeText">Dark</span>
            </button>

            <a href="index.php" class="nav-btn-alt">← Back Home</a>
            <div class="account-dropdown">
                <button class="account-toggle" onclick="togglePremiumMenu(event)">
                    <?php if ($user_image): ?>
                        <img src="<?= htmlspecialchars($user_image, ENT_QUOTES, 'UTF-8') ?>" class="nav-avatar">
                    <?php else: ?>
                        <div class="nav-avatar" style="display:flex; align-items:center; justify-content:center; background: rgba(0,0,0,0.03); color: #b8931d;">👤</div>
                    <?php endif; ?>
                    <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>
                </button>
                <div class="account-menu" id="userMenu">
                    <div style="padding: 10px 15px; font-size: 0.75rem; color: var(--gold-text); text-transform: uppercase; font-weight: 800; letter-spacing: 1px;">Member Portal</div>
                    <a href="reserve.php">✨ Book New Stay</a>
                    <a href="profile.php">⚙️ Account Settings</a>
                    
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="admin/dashboard.php" style="color: #ffa000; font-weight: bold;">🛡️ Admin Dashboard</a>
                    <?php endif; ?>

                    <hr style="border: 0; border-top: 1px solid var(--border-subtle); margin: 5px 0;">
                    <a href="logout.php" style="color: #ff3b30;">🚪 Logout</a>
                </div>
            </div>
        </div>
    </div>
</nav>

<main class="hero-wrapper" style="padding-top: 120px; box-sizing: border-box; display: flex; align-items: center; min-height: 90vh;">
    <div class="reserve-card">
        <h2 style="color: var(--text-main); margin: 0 0 8px 0; font-size: 2rem; font-family: 'Cinzel', serif; font-weight: 400; letter-spacing: 1px;">Your Bookings</h2>
        <p style="color: var(--text-sub); font-size: 0.85rem; margin-bottom: 35px;">Keep track of your luxury visits.</p>

        <div class="bookings-list">
            <?php if ($reservations->num_rows > 0): ?>
                <?php while($row = $reservations->fetch_assoc()): ?>
                    <?php 
                        $time_val = $row['time'];
                        $display_time = "";
                        if ($time_val == "08:00:00") {
                            $display_time = "Day Session (8 AM)";
                        } elseif ($time_val == "19:00:00") {
                            $display_time = "Night Session (7 PM)";
                        } elseif ($time_val == "08:00:01") {
                            $display_time = "Full Day (22H)";
                        } else {
                            $display_time = (!empty($time_val) && $time_val != '00:00:00') ? date("g:i A", strtotime($time_val)) : '02:00 PM';
                        }
                    ?>
                    <div class="booking-item">
                        <div>
                            <div class="booking-date">
                                <?= date("M d, Y", strtotime($row['date'])) ?>
                            </div>
                            <div class="booking-details">
                                ⏰ <span><?= $display_time ?></span> 
                                <br>
                                💳 <span><?= htmlspecialchars($row['payment_type'] ?? 'Cash', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div class="status-badge status-<?= $row['status'] ?>" style="margin-bottom: 12px;">
                                <?= str_replace('_', ' ', $row['status']) ?>
                            </div>
                            
                            <?php if ($row['status'] === 'confirmed' || $row['status'] === 'pending'): ?>
                                <a href="confirmation.php?id=<?= $row['id'] ?>" class="ticket-link">🎟️ View Ticket</a>
                            <?php endif; ?>

                            <?php if ($row['status'] === 'pending' || $row['status'] === 'confirmed'): ?>
                                <a href="cancel_request.php?id=<?= $row['id'] ?>" 
                                   class="cancel-link"
                                   onclick="return confirm('Are you sure you want to request a cancellation for this stay?')">Request Cancel</a>
                            <?php elseif ($row['status'] === 'pending_cancel'): ?>
                                <span style="color: var(--text-sub); font-size: 0.75rem; display: block; font-style: italic;">Awaiting Admin...</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="padding: 60px 20px; color: var(--text-sub);">
                    <p style="font-size: 1rem; font-weight: 500; margin: 0 0 10px 0;">No reservations found.</p>
                    <a href="reserve.php" style="color: var(--gold-text); text-decoration: none; font-weight: 700; display: block; margin-top: 15px; font-size: 0.85rem; letter-spacing: 0.5px;">Book your first stay now →</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

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

    function togglePremiumMenu(event) {
        event.stopPropagation();
        const menu = document.getElementById('userMenu');
        if (menu.style.display === 'flex') {
            closePremiumMenu();
        } else {
            menu.style.display = 'flex';
            requestAnimationFrame(() => { menu.classList.add('active'); });
        }
    }

    function closePremiumMenu() {
        const menu = document.getElementById('userMenu');
        menu.classList.remove('active');
        setTimeout(() => { if (!menu.classList.contains('active')) { menu.style.display = 'none'; } }, 500);
    }

    window.onclick = function(event) {
        if (!event.target.closest('.account-dropdown')) { closePremiumMenu(); }
    }

    window.onload = function() {
        const urlParams = new URLSearchParams(window.location.search);
        const toast = document.getElementById('success-toast');
        
        if (urlParams.get('msg') === 'cancelled') {
            toast.innerHTML = "✔️ Reservation Cancelled Successfully";
            toast.style.display = 'block';
            requestAnimationFrame(() => { toast.classList.add('reveal'); });
            setTimeout(() => { 
                toast.classList.remove('reveal');
                setTimeout(() => { toast.style.display = 'none'; }, 500);
            }, 3000);
        } else if (urlParams.get('msg') === 'request_sent') {
            toast.innerHTML = "📩 Cancellation Request Sent to Admin";
            toast.style.display = 'block';
            requestAnimationFrame(() => { toast.classList.add('reveal'); });
            setTimeout(() => { 
                toast.classList.remove('reveal');
                setTimeout(() => { toast.style.display = 'none'; }, 500);
            }, 4000);
        }
    }
</script>

</body>
</html>