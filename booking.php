<?php
session_start();
include 'db/connection.php';

// 1. Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. Fetch User Data for Nav
/** @var mysqli $conn */
$stmt = $pdo->prepare("SELECT name, image FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$userName = !empty($user['name']) ? $user['name'] : 'Guest';
$displayName = explode(' ', $userName)[0];
$user_image = !empty($user['image']) ? 'uploads/' . $user['image'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserve &amp; Policies | Villa Marciana</title>
    
    <!-- Flatpickr (Premium Calendar Library) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <link rel="stylesheet" href="css/style.css">
    <style>
        body.light {
            background: #fcfbfa;
            font-family: 'Montserrat', sans-serif;
            margin: 0; padding: 0;
            color: #444;
        }

        .modern-nav { 
            position: absolute; top: 0; left: 0; width: 100%; z-index: 100; 
            background: rgba(255, 255, 255, 0.8) !important; 
            backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            display: flex; justify-content: space-between; align-items: center; padding: 20px 40px; box-sizing: border-box;
        }

        .nav-brand {
            font-family: 'Cinzel', serif; font-size: 1.2rem; color: #d4af37; text-decoration: none;
        }

        .nav-links {
            display: flex; gap: 20px; align-items: center;
        }

        .nav-links a {
            text-decoration: none; color: #444; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #d4af37;
        }

        /* --- PRESTIGE LAYOUT --- */
        .page-wrapper {
            max-width: 1100px;
            width: 90%;
            margin: 120px auto 50px;
        }

        .booking-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 40px;
            margin-bottom: 50px;
        }

        @media (max-width: 900px) {
            .booking-grid { grid-template-columns: 1fr; }
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(25px) saturate(180%);
            -webkit-backdrop-filter: blur(25px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 32px;
            padding: 40px;
            box-shadow: 0 30px 70px rgba(212, 175, 55, 0.05), 0 10px 30px rgba(0, 0, 0, 0.02);
        }

        /* --- FLATPICKR OVERRIDES --- */
        .flatpickr-calendar {
            background: rgba(255, 255, 255, 0.95) !important;
            border: 1px solid rgba(212, 175, 55, 0.2) !important;
            box-shadow: 0 15px 40px rgba(212, 175, 55, 0.08) !important;
            border-radius: 20px !important;
        }
        .flatpickr-day.selected {
            background: #d4af37 !important;
            border-color: #d4af37 !important;
        }

        .modern-input {
            width: 100%;
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 16px;
            font-size: 0.95rem;
            color: #111;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }
        .modern-input:focus {
            outline: none;
            border-color: #d4af37;
            box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.1);
            background: #fff;
        }

        /* --- SESSION OPTIONS (CARD SELECTOR) --- */
        .session-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 15px;
        }

        .session-card {
            background: rgba(255, 255, 255, 0.6);
            border: 1px solid rgba(0,0,0,0.06);
            border-radius: 20px;
            padding: 20px 10px;
            text-align: center;
            cursor: pointer;
            pointer-events: auto !important;
            position: relative;
            z-index: 10;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .session-card h4 { margin: 0 0 5px; color: #111; font-size: 0.95rem; pointer-events: none; }
        .session-card p { margin: 0; color: #666; font-size: 0.75rem; pointer-events: none; }

        .session-card:hover:not(.disabled) {
            transform: translateY(-3px);
            border-color: #d4af37;
            background: rgba(212, 175, 55, 0.02);
        }

        .session-card.selected {
            background: #d4af37 !important;
            border-color: #d4af37 !important;
            color: #fff !important;
            box-shadow: 0 10px 25px rgba(212, 175, 55, 0.25);
        }
        .session-card.selected h4, .session-card.selected p { color: #fff; }

        .session-card.disabled {
            opacity: 0.4;
            cursor: not-allowed;
            pointer-events: none !important;
            background: rgba(0,0,0,0.05);
            border-color: transparent;
        }

        /* --- DYNAMIC PRICING PANEL --- */
        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 0.9rem;
            color: #555;
        }
        .price-total {
            border-top: 1px solid rgba(0,0,0,0.08);
            padding-top: 15px;
            display: flex;
            justify-content: space-between;
            font-size: 1.2rem;
            font-weight: bold;
            color: #111;
        }

        .policy-notice {
            font-size: 0.78rem;
            color: #777;
            text-align: center;
            margin-top: 20px;
            line-height: 1.5;
        }

        .policy-notice a {
            color: #d4af37;
            text-decoration: none;
            font-weight: 600;
        }
        .policy-notice a:hover { text-decoration: underline; }

        .reserve-btn {
            background: #d4af37; color: #fff; width: 100%; padding: 18px;
            border-radius: 50px; border: none; font-weight: 800; text-transform: uppercase;
            font-size: 0.9rem; cursor: pointer; transition: all 0.3s ease; letter-spacing: 1.5px;
            margin-top: 15px; box-shadow: 0 8px 25px rgba(212, 175, 55, 0.2);
        }
        .reserve-btn:hover { background: #b8931d; transform: translateY(-2px); }
        .reserve-btn:disabled { background: #ccc; cursor: not-allowed; box-shadow: none; transform: none; }

        /* --- POLICY SECTION INTEGRATION --- */
        .policy-section-box {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.9);
            border-radius: 32px;
            padding: 50px;
            box-shadow: 0 30px 70px rgba(212, 175, 55, 0.05), 0 10px 30px rgba(0, 0, 0, 0.02);
            margin-top: 40px;
        }

        .policy-header {
            text-align: center;
            border-bottom: 1px solid rgba(0,0,0,0.08);
            padding-bottom: 25px;
            margin-bottom: 30px;
        }

        .policy-header h2 {
            font-family: 'Cinzel', serif;
            color: #111;
            font-size: 1.5rem;
            margin-bottom: 8px;
        }

        .policy-header p {
            font-size: 0.82rem;
            color: #666;
            margin: 4px 0;
        }

        h3.section-title {
            font-family: 'Cinzel', serif;
            font-size: 1rem;
            color: #b8931d;
            margin-top: 25px;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        ol.terms-list {
            padding-left: 20px;
            line-height: 1.8;
            font-size: 0.9rem;
            color: #444;
        }

        ol.terms-list li {
            margin-bottom: 12px;
        }

        .dress-code-box {
            background: rgba(212, 175, 55, 0.04);
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 20px;
            padding: 20px;
            margin-top: 30px;
        }

        .dress-code-box h4 {
            font-family: 'Cinzel', serif;
            font-size: 0.95rem;
            color: #111;
            margin-top: 0;
            margin-bottom: 10px;
        }

        .dress-code-box ul {
            margin: 0;
            padding-left: 20px;
            font-size: 0.88rem;
            line-height: 1.6;
            color: #555;
        }
    </style>
</head>
<body class="light">

<nav class="modern-nav">
    <a href="index.php" class="nav-brand">Villa Marciana</a>
    <div class="nav-links">
        <a href="booking.php" style="color: #d4af37;">Reserve</a>
        <a href="#policies">Policies</a>
        <a href="myreservations.php">My Bookings</a>
        <a href="profile.php">Profile</a>
        <a href="logout.php">Sign Out</a>
    </div>
</nav>

<div class="page-wrapper">
    <div class="booking-grid">
        <!-- Left: Setup and Config Form -->
        <div class="glass-panel">
            <h2 style="font-family: 'Cinzel', serif; font-weight: 400; margin-top: 0;">Reserve Your Stay</h2>
            <p style="color: #666; font-size: 0.9rem; margin-bottom: 30px;">Select your date and preferred session parameters.</p>

            <form id="bookingForm" action="process_booking.php" method="POST">
                <!-- Hidden inputs to submit selected options -->
                <input type="hidden" name="booking_date" id="selectedDateInput" required>
                <input type="hidden" name="session_type" id="selectedSessionInput" required>

                <div class="form-group">
                    <label style="display: block; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #444; margin-bottom: 8px;">1. Select Booking Date</label>
                    <input type="text" id="datePicker" class="modern-input" placeholder="Choose a date" readonly>
                </div>

                <div class="form-group" style="margin-top: 30px;">
                    <label style="display: block; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #444; margin-bottom: 8px;">2. Available Sessions</label>
                    <div class="session-options" id="sessionContainer">
                        <div class="session-card" data-session="day">
                            <h4>Day</h4>
                            <p>8 AM - 5 PM</p>
                        </div>
                        <div class="session-card" data-session="night">
                            <h4>Night</h4>
                            <p>7 PM - 6 AM</p>
                        </div>
                        <div class="session-card" data-session="22hr">
                            <h4>22-Hour</h4>
                            <p>8 AM - 6 AM</p>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Right: Summary and Dynamic Bill -->
        <div class="glass-panel" style="display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <h3 style="font-family: 'Cinzel', serif; font-weight: 400; margin-top: 0; color: #b8931d;">Fare Breakdown</h3>
                <p style="color: #777; font-size: 0.85rem; margin-bottom: 25px;">Live fare summary based on seasonal rates.</p>

                <div id="priceSummary" style="display: none;">
                    <div class="price-row">
                        <span>Base Rate (<span id="summarySession">Day</span>)</span>
                        <span id="summaryBasePrice">₱0.00</span>
                    </div>
                    <div class="price-row" id="weekendPremiumRow" style="display: none;">
                        <span>Weekend Surcharge (+15%)</span>
                        <span id="summaryWeekendPrice">₱0.00</span>
                    </div>
                    <div class="price-total">
                        <span>Estimated Total</span>
                        <span id="summaryTotal">₱0.00</span>
                    </div>
                </div>

                <div id="summaryPlaceholder" style="text-align: center; color: #999; padding: 40px 0; font-size: 0.9rem;">
                    Select a date and session to estimate pricing details.
                </div>
            </div>

            <div>
                <div class="policy-notice">
                    By clicking Request Reservation, you agree to Villa Marciana's <a href="#policies">Rental Agreement &amp; Policies</a> below.
                </div>
                <button type="submit" form="bookingForm" id="submitBtn" class="reserve-btn" disabled>
                    Request Reservation
                </button>
            </div>
        </div>
    </div>

    <!-- Integrated Policy & Terms Section -->
    <div class="policy-section-box" id="policies">
        <div class="policy-header">
            <h2>Villa Marciana Resort</h2>
            <p>No. 61 Main Avenue, Blk. 22 Lot 24, Pacita 2 San Pedro City, Laguna</p>
            <p>Proprietor: Kent S. Lagasca | Mobile: 0917 678 0804 / 0936 043 4197</p>
            <h3 style="font-family: 'Cinzel', serif; margin-top: 20px; font-size: 1.1rem; color: #d4af37;">Rental Agreement &amp; Policies</h3>
        </div>

        <h3 class="section-title">Terms and Conditions</h3>
        <ol class="terms-list">
            <li>A rate of ₱8,500 morning shift / ₱9,500 for evening shift for 10 hours plus ₱500.00 for each succeeding hour.</li>
            <li>Reservation is confirmed upon payment of a 50% non-refundable deposit.</li>
            <li>Use of two (2) air-conditioned rooms.</li>
            <li>Villa Marciana supplies soft drinks and liquor requirements. A corkage fee of ₱100 will be charged for every bottle of liquor or one (1) case of beer or soft drinks if clients bring their own (applicable only to available soft drinks, beer, and liquor brands).</li>
            <li><strong>Decoration Policy:</strong> Taping, sticking, or adhering any decorations directly onto walls, posts, or surfaces is strictly prohibited. Please tie decorations instead. Confetti, glitter, and balloons containing glitter inside are strictly banned as they create heavy mess and damage the pool filtration system.</li>
            <li>In compliance with local city ordinances regarding noise levels, all music and videoke systems must be completely turned off by 10:00 PM. Excessive noise after this hour is strictly prohibited.</li>
            <li>Any damage done to the premises or its belongings caused by the deliberate or unintentional acts of the guest(s) will be charged to the host/client account.</li>
            <li>Management of Villa Marciana will not be held liable for any loss or damage to valuables or belongings of the guest(s) during the rental period, including vehicles parked within the premises.</li>
            <li>Minors and children are not allowed inside the premises without adult supervision. As a private venue, Villa Marciana Resort is held free from liability regarding any untoward incidents related to this matter.</li>
            <li>Outside caterers are strictly prohibited. Villa Marciana features an exclusive in-house caterer.</li>
            <li>Guests are required to take a shower before swimming and must wear proper swimming attire at all times.</li>
        </ol>

        <div class="dress-code-box">
            <h4>Proper Swimming Attire Guidelines</h4>
            <ul>
                <li><strong>GIRLS:</strong> Swimsuits, Shorts &amp; Tops, Rash Guards</li>
                <li><strong>BOYS:</strong> Swimming Trunks, Shorts, Sando, Rash Guards</li>
            </ul>
        </div>
    </div>
</div>

<script>
    const rates = {
        'day': 8500,
        'night': 9500,
        '22hr': 16000
    };

    let selectedDate = null;

    // Initialize Flatpickr calendar
    flatpickr("#datePicker", {
        minDate: "today",
        dateFormat: "Y-m-d",
        onChange: function(selectedDates, dateStr) {
            selectedDate = dateStr;
            document.getElementById('selectedDateInput').value = dateStr;
            checkAvailability(dateStr);
        }
    });

    // Handle clicks globally using event delegation
    document.addEventListener('click', function(event) {
        const card = event.target.closest('.session-card');
        if (!card) return;

        if (card.classList.contains('disabled')) {
            return;
        }

        // Remove selection from all cards
        document.querySelectorAll('.session-card').forEach(c => c.classList.remove('selected'));
        
        // Select clicked card
        card.classList.add('selected');
        const sessionType = card.getAttribute('data-session');
        document.getElementById('selectedSessionInput').value = sessionType;

        // Enable Submit button and update price summary
        document.getElementById('submitBtn').disabled = false;
        updateSummary(sessionType);
    });

    // Check Availability dynamic call
    function checkAvailability(dateStr) {
        // Force-enable all cards first so they are never stuck
        document.querySelectorAll('.session-card').forEach(card => {
            card.classList.remove('disabled', 'selected');
        });
        document.getElementById('selectedSessionInput').value = '';
        document.getElementById('submitBtn').disabled = true;
        updateSummary(null);

        // Then fetch database constraints to lock only booked slots
        fetch(`check_availability.php?date=${dateStr}`)
            .then(res => res.json())
            .then(data => {
                const cards = document.querySelectorAll('.session-card');
                cards.forEach(card => {
                    const session = card.getAttribute('data-session');
                    if (data[session] === false) {
                        card.classList.add('disabled');
                    }
                });
            })
            .catch(err => console.error("Error checking availability:", err));
    }

    function updateSummary(session) {
        const placeholder = document.getElementById('summaryPlaceholder');
        const summaryBox = document.getElementById('priceSummary');

        if (!session || !selectedDate) {
            placeholder.style.display = 'block';
            summaryBox.style.display = 'none';
            return;
        }

        placeholder.style.display = 'none';
        summaryBox.style.display = 'block';

        let basePrice = rates[session];
        
        const dateObj = new Date(selectedDate);
        const dayOfWeek = dateObj.getDay(); 
        const isWeekend = (dayOfWeek === 5 || dayOfWeek === 6);

        let weekendSurcharge = 0;
        if (isWeekend) {
            weekendSurcharge = basePrice * 0.15;
            document.getElementById('weekendPremiumRow').style.display = 'flex';
        } else {
            document.getElementById('weekendPremiumRow').style.display = 'none';
        }

        let total = basePrice + weekendSurcharge;

        document.getElementById('summarySession').innerText = session.toUpperCase();
        document.getElementById('summaryBasePrice').innerText = '₱' + basePrice.toLocaleString();
        document.getElementById('summaryWeekendPrice').innerText = '₱' + weekendSurcharge.toLocaleString();
        document.getElementById('summaryTotal').innerText = '₱' + total.toLocaleString();
    }
</script>
</body>
</html>