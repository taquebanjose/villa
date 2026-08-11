<?php
session_start();
include 'db/connection.php';

// --- SECURITY CHECK ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- NEW CALENDAR LOGIC: FETCH SLOTS PER DATE ---
$availabilityData = [];
try {
    // Pull specific_time from your reservations table using PDO
    $stmt = $pdo->prepare("SELECT date, specific_time FROM reservations WHERE status IN ('confirmed', 'pending')");
    $stmt->execute();
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($reservations as $row) {
        $date = $row['date'];
        // NORMALIZATION: Ensure '08:00' becomes '08:00:00' to match the HTML values exactly
        $time = date("H:i:s", strtotime($row['specific_time']));
        
        if (!isset($availabilityData[$date])) {
            $availabilityData[$date] = [];
        }
        $availabilityData[$date][] = $time;
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
}

// TERMINATION RULE: Identify dates that are 100% full
$fully_booked_dates = [];
foreach ($availabilityData as $date => $slots) {
    // A date is disabled if '22 Hours' (08:00:01) is taken OR both Day (08:00:00) and Night (19:00:00) are taken
    if (in_array('08:00:01', $slots) || (in_array('08:00:00', $slots) && in_array('19:00:00', $slots))) {
        $fully_booked_dates[] = $date;
    }
}

$availability_json = json_encode($availabilityData);
$disabled_dates_json = json_encode($fully_booked_dates);

// FETCH USER IMAGE & NAME (Original Logic)
try {
    $stmt = $pdo->prepare("SELECT name, image FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);

    $userName = !empty($res['name']) ? explode(' ', $res['name'])[0] : 'Guest';
    $user_image = !empty($res['image']) ? 'uploads/' . $res['image'] : null;
} catch (PDOException $e) {
    $userName = 'Guest';
    $user_image = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserve Your Stay | Villa Marciana</title>
    
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
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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
            --input-bg: rgba(255, 255, 255, 0.9);
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
            --input-bg: rgba(15, 18, 24, 0.8);
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            transition: background-color 0.4s ease, color 0.4s ease;
            margin: 0;
            font-family: 'Montserrat', sans-serif;
            overflow-x: hidden;
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

        /* --- UPGRADED BOOKING CARD WITH VISIBLE OVERFLOW FOR CALENDAR --- */
        .hero-wrapper {
            overflow: visible !important;
        }

        .reserve-card {
            background: var(--card-bg);
            backdrop-filter: blur(30px) saturate(180%);
            -webkit-backdrop-filter: blur(30px) saturate(180%);
            border: 1px solid var(--card-border);
            box-shadow: 0 30px 70px var(--shadow-color), 0 10px 30px rgba(0, 0, 0, 0.02);
            border-radius: 32px; padding: 40px; max-width: 500px; width: 95%; margin: auto;
            animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
            overflow: visible !important;
        }

        .form-group {
            overflow: visible !important;
        }

        .form-group label {
            font-size: 0.72rem; text-transform: uppercase; letter-spacing: 1.5px;
            color: var(--gold-text); font-weight: 800; display: block; margin-bottom: 12px;
        }

        input#calendar {
            background: var(--input-bg); border: 1px solid var(--border-subtle);
            border-radius: 16px; color: var(--text-main) !important; padding: 16px; width: 100%;
            font-size: 0.95rem; margin-bottom: 25px; outline: none; box-sizing: border-box;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            box-sizing: border-box;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.01);
            cursor: pointer;
        }
        input#calendar:focus {
            border-color: var(--gold-accent);
            background: var(--card-hover-bg);
            box-shadow: 0 10px 30px rgba(212, 175, 55, 0.12);
        }

        /* --- ANIMATED OPTIONS GRID (FIXED LAYOUT) --- */
        .option-item { 
            width: 100%; 
            transition: opacity 0.4s ease, transform 0.4s ease, max-height 0.5s cubic-bezier(0.16, 1, 0.3, 1), margin-bottom 0.5s ease;
            opacity: 1;
            transform: scale(1);
            max-height: 160px;
            margin-bottom: 12px;
            overflow: hidden;
            display: block;
        }
        
        .option-item.slot-hidden {
            opacity: 0;
            transform: scale(0.95);
            max-height: 0;
            margin-bottom: 0 !important;
            pointer-events: none;
        }

        .option-label {
            display: flex; justify-content: space-between; align-items: center;
            padding: 18px 24px; background: var(--input-bg);
            border: 1px solid var(--border-subtle); border-radius: 20px;
            cursor: pointer; transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); 
            font-size: 0.9rem; color: var(--text-main);
        }
        .option-label:hover {
            background: var(--card-hover-bg);
            border-color: rgba(212, 175, 55, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.08);
        }

        .option-item input[type="radio"]:checked + .option-label {
            background: rgba(212, 175, 55, 0.08); 
            border-color: var(--gold-accent); 
            box-shadow: 0 8px 30px rgba(212, 175, 55, 0.12);
            transform: translateY(-2px);
        }

        /* --- TOTAL DISPLAY ANIMATION --- */
        #total-display {
            background: rgba(212, 175, 55, 0.06); border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 24px; padding: 24px; margin-top: 25px; text-align: center;
            transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
            transform: scale(0.95); opacity: 0; display: none;
        }
        #total-display.show {
            display: block;
            transform: scale(1);
            opacity: 1;
        }

        .confirm-btn {
            background: var(--gold-accent); color: #ffffff; width: 100%; padding: 18px;
            border-radius: 50px; border: none; font-weight: 800;
            text-transform: uppercase; letter-spacing: 1.5px; cursor: pointer;
            margin-top: 20px; transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 10px 30px rgba(212, 175, 55, 0.2);
        }
        .confirm-btn:hover:not(:disabled) {
            background: #b8931d;
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(212, 175, 55, 0.3);
        }
        .confirm-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            box-shadow: none;
        }

        /* --- FLATPICKR ADAPTIVE THEME & POSITIONING OVERRIDES --- */
        .flatpickr-calendar { 
            background: var(--card-hover-bg) !important; 
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(212, 175, 55, 0.3) !important; 
            box-shadow: 0 25px 65px rgba(0,0,0,0.3) !important;
            border-radius: 24px !important;
            padding: 16px !important;
            width: 330px !important;
            z-index: 99999 !important;
        }
        .flatpickr-days { width: 300px !important; }
        .dayContainer { width: 300px !important; min-width: 300px !important; max-width: 300px !important; }
        .flatpickr-day { 
            color: var(--text-main) !important; 
            border-radius: 12px !important; 
            font-weight: 500;
            transition: all 0.2s ease;
            max-width: 38px !important;
            height: 38px !important;
            line-height: 38px !important;
        }
        .flatpickr-day:hover { background: rgba(212, 175, 55, 0.15) !important; border-color: transparent !important; }
        .flatpickr-day.selected { 
            background: var(--gold-accent) !important; 
            border-color: var(--gold-accent) !important;
            color: #ffffff !important; 
            box-shadow: 0 6px 15px rgba(212,175,55,0.3) !important;
            font-weight: 700;
        }
        .flatpickr-day.flatpickr-disabled, .flatpickr-day.flatpickr-disabled:hover { 
            color: var(--text-sub) !important; 
            opacity: 0.4;
            background: transparent !important;
            text-decoration: line-through;
        }
        .flatpickr-months .flatpickr-month { color: var(--text-main) !important; fill: var(--text-main) !important; }
        .flatpickr-current-month .flatpickr-monthDropdown-months { font-weight: 700 !important; color: var(--text-main) !important; }
        .flatpickr-weekday { color: var(--gold-text) !important; font-weight: 600 !important; }

        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

<nav class="modern-nav">
    <div class="nav-container">
        <h1 class="nav-title" onclick="location.href='index.php'" style="color:var(--gold-accent); cursor:pointer; font-family: 'Cinzel', serif;">Villa Marciana</h1>
        <div class="nav-buttons">
            <button class="theme-toggle-btn" id="themeToggle" onclick="toggleTheme()">
                <span id="themeIcon">🌙</span> <span id="themeText">Dark</span>
            </button>

            <a href="index.php" class="nav-btn-alt">← Back Home</a>
            <div class="account-dropdown">
                <button class="account-toggle" onclick="togglePremiumMenu(event)">
                    <?php if ($user_image): ?><img src="<?= htmlspecialchars($user_image, ENT_QUOTES, 'UTF-8') ?>" class="nav-avatar"><?php else: ?><div class="nav-avatar" style="display:flex; align-items:center; justify-content:center; background: rgba(0,0,0,0.03); color: #b8931d;">👤</div><?php endif; ?>
                    <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>
                </button>
                <div class="account-menu" id="userMenu">
                    <div style="padding: 10px 15px; font-size: 0.75rem; color: var(--gold-text); text-transform: uppercase; font-weight: 800; letter-spacing: 1px;">Member Portal</div>
                    <a href="myreservations.php">📅 My Reservations</a>
                    <a href="profile.php">⚙️ Account Settings</a>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="admin/dashboard.php" style="color: #ffa000; font-weight: bold;">🛠️ Admin Dashboard</a>
                    <?php endif; ?>
                    <hr style="border: 0; border-top: 1px solid var(--border-subtle); margin: 5px 0;">
                    <a href="logout.php" style="color: #ff3b30;">🚪 Logout</a>
                </div>
            </div>
        </div>
    </div>
</nav>

<main class="hero-wrapper" style="display: flex; align-items: center; min-height: 90vh; padding-top: 100px; box-sizing: border-box;">
    <div class="reserve-card">
        <div style="text-align: left; margin-bottom: 30px;">
            <h2 style="color: var(--text-main); margin: 0; font-size: 1.8rem; font-family: 'Cinzel', serif; font-weight: 400;">Secure Your Stay</h2>
            <p style="color: var(--text-sub); font-size: 0.85rem; margin-top: 5px;">Select your preferred dates and session below.</p>
        </div>

        <form action="process_booking.php" method="POST">
            <div class="form-group">
                <label>📅 Select Date</label>
                <input type="text" name="booking_date" id="calendar" placeholder="Click to check availability..." readonly required>
            </div>

            <div class="form-group" style="margin-bottom: 25px;">
                <label>⏰ Stay Type / Check-In Time</label>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    
                    <div class="option-item" id="container_day">
                        <input type="radio" id="time_day" name="specific_time" value="08:00:00" required onclick="updatePrice(8500)" style="display:none;">
                        <label for="time_day" class="option-label">
                            <div style="display: flex; flex-direction: column; gap: 2px;">
                                <span style="font-weight: 700; color: var(--text-main);">☀️ Day Session</span>
                                <span style="font-size: 0.75rem; color: var(--text-sub);">8:00 AM — 5:00 PM</span>
                            </div>
                            <span style="color: var(--gold-text); font-weight: 800;">₱8,500</span>
                        </label>
                    </div>

                    <div class="option-item" id="container_night">
                        <input type="radio" id="time_night" name="specific_time" value="19:00:00" onclick="updatePrice(9500)" style="display:none;">
                        <label for="time_night" class="option-label">
                            <div style="display: flex; flex-direction: column; gap: 2px;">
                                <span style="font-weight: 700; color: var(--text-main);">🌙 Night Session</span>
                                <span style="font-size: 0.75rem; color: var(--text-sub);">7:00 PM — 5:00 AM</span>
                            </div>
                            <span style="color: var(--gold-text); font-weight: 800;">₱9,500</span>
                        </label>
                    </div>

                    <div class="option-item" id="container_24">
                        <input type="radio" id="time_24" name="specific_time" value="08:00:01" onclick="updatePrice(18000)" style="display:none;">
                        <label for="time_24" class="option-label">
                            <div style="display: flex; flex-direction: column; gap: 2px;">
                                <span style="font-weight: 700; color: var(--text-main);">⏳ 22 Hours Stay</span>
                                <span style="font-size: 0.75rem; color: var(--text-sub);">Check-in: 08:00 AM (Next Day Checkout)</span>
                            </div>
                            <span style="color: var(--gold-text); font-weight: 800;">₱18,000</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>💳 Payment Method</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="option-item">
                        <input type="radio" id="gcash" name="payment_method" value="GCash" required style="display:none;">
                        <label for="gcash" class="option-label" style="justify-content: center; flex-direction: column; gap: 5px;">
                            <span style="font-size: 1.2rem;">📱</span>
                            <span style="color: var(--text-main); font-weight: 700; font-size: 0.9rem;">G-Cash</span>
                        </label>
                    </div>
                    <div class="option-item">
                        <input type="radio" id="cash" name="payment_method" value="Cash" style="display:none;">
                        <label for="cash" class="option-label" style="justify-content: center; flex-direction: column; gap: 5px;">
                            <span style="font-size: 1.2rem;">💵</span>
                            <span style="color: var(--text-main); font-weight: 700; font-size: 0.9rem;">Cash</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- GCash QR Code Container (Hidden by default) -->
            <div id="gcash-container" style="display: none; background: var(--input-bg); border: 1px solid var(--border-subtle); border-radius: 20px; padding: 20px; text-align: center; margin-top: 20px;">
                <p style="color: var(--gold-text); font-weight: 700; font-size: 0.85rem; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px;">Scan to Pay via GCash</p>
                <img src="images/gcash-qr.jpg" alt="GCash QR Code" style="width: 180px; height: 180px; object-fit: contain; border-radius: 12px; margin-bottom: 10px; border: 1px solid var(--border-subtle);">
                <p style="color: var(--text-main); font-size: 0.85rem; margin: 0; font-weight: 600;">Account Name: Villa Marciana</p>
                <p style="color: var(--text-sub); font-size: 0.8rem; margin: 5px 0 0 0;">Number: 0912-345-6789</p>
            </div>

            <div id="total-display">
                <label style="color: var(--gold-text); margin-bottom: 5px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">Total Amount</label>
                <div style="font-size: 2.2rem; color: var(--text-main); font-weight: 800;" id="price-tag">₱0</div>
            </div>

            <button type="submit" class="confirm-btn" id="confirmBtn" disabled>Confirm Reservation</button>
        </form>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
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

    const availability = <?= $availability_json ?>;
    const fullyBooked = <?= $disabled_dates_json ?>;
    
    let currentPrice = 0;

    // --- FORM VALIDATION FOR CONFIRM BUTTON ---
    function validateForm() {
        const calendarInput = document.getElementById('calendar').value;
        const selectedTime = document.querySelector('input[name="specific_time"]:checked');
        const selectedPayment = document.querySelector('input[name="payment_method"]:checked');
        const confirmBtn = document.getElementById('confirmBtn');

        if (calendarInput && selectedTime && selectedPayment) {
            confirmBtn.disabled = false;
        } else {
            confirmBtn.disabled = true;
        }
    }

    // --- SMOOTH LUXURY PRICE TICKER ---
    function updatePrice(targetAmount) {
        const display = document.getElementById('total-display');
        const tag = document.getElementById('price-tag');
        
        display.style.display = 'block';
        requestAnimationFrame(() => {
            display.classList.add('show');
        });

        let start = currentPrice;
        let duration = 350; 
        let startTime = null;

        function animatePrice(timestamp) {
            if (!startTime) startTime = timestamp;
            let progress = timestamp - startTime;
            let progressPercent = Math.min(progress / duration, 1);
            let currentVal = Math.floor(start + (targetAmount - start) * progressPercent);
            
            tag.innerText = '₱' + currentVal.toLocaleString();

            if (progressPercent < 1) {
                requestAnimationFrame(animatePrice);
            } else {
                tag.innerText = '₱' + targetAmount.toLocaleString();
                currentPrice = targetAmount;
            }
        }
        requestAnimationFrame(animatePrice);
        validateForm();
    }

    // --- SLIDING TRANSITION SLOT FILTERING ---
    function filterSlots(selectedDate) {
        const daySlot = document.getElementById('container_day');
        const nightSlot = document.getElementById('container_night');
        const fullSlot = document.getElementById('container_24');
        const radios = document.getElementsByName('specific_time');

        [daySlot, nightSlot, fullSlot].forEach(el => {
            el.classList.remove('slot-hidden');
        });
        radios.forEach(r => r.checked = false);

        const display = document.getElementById('total-display');
        display.classList.remove('show');
        setTimeout(() => {
            if (!display.classList.contains('show')) {
                display.style.display = 'none';
            }
        }, 500);
        currentPrice = 0; 

        if (availability[selectedDate]) {
            const taken = availability[selectedDate];

            if (taken.includes('08:00:01')) {
                [daySlot, nightSlot, fullSlot].forEach(el => el.classList.add('slot-hidden'));
                validateForm();
                return;
            }

            if (taken.length > 0) {
                fullSlot.classList.add('slot-hidden');
            }

            if (taken.includes('08:00:00')) { daySlot.classList.add('slot-hidden'); }
            if (taken.includes('19:00:00')) { nightSlot.classList.add('slot-hidden'); }
        }
        validateForm();
    }

    flatpickr("#calendar", {
        dateFormat: "Y-m-d",
        minDate: "today",
        disable: fullyBooked,
        disableMobile: "true",
        static: false,
        position: "auto",
        onChange: function(selectedDates, dateStr) {
            filterSlots(dateStr);
            validateForm();
        }
    });

    // --- GCASH QR CODE TOGGLE & VALIDATION LOGIC ---
    const gcashRadio = document.getElementById('gcash');
    const cashRadio = document.getElementById('cash');
    const gcashContainer = document.getElementById('gcash-container');

    function handlePaymentToggle() {
        if (gcashRadio.checked) {
            gcashContainer.style.display = 'block';
        } else {
            gcashContainer.style.display = 'none';
        }
        validateForm();
    }

    if (gcashRadio && cashRadio && gcashContainer) {
        gcashRadio.addEventListener('change', handlePaymentToggle);
        cashRadio.addEventListener('change', handlePaymentToggle);
    }

    function togglePremiumMenu(event) {
        event.stopPropagation();
        const menu = document.getElementById('userMenu');
        if (menu.style.display === 'flex') { closePremiumMenu(); } 
        else {
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
        if (!event.target.closest('.account-dropdown')) {
            closePremiumMenu();
        }
    }
</script>
</body>
</html>