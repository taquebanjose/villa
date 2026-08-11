<?php
session_start();
include 'db/connection.php';

// Fetch User Data for Nav if logged in
$user_name = "Guest";
if (isset($_SESSION['user_id'])) {
    /** @var mysqli $conn */
    $stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if ($user && !empty($user['name'])) {
        $user_name = $user['name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rental Agreement &amp; Policies | Villa Marciana</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body.light {
            background: #fcfbfa;
            font-family: 'Montserrat', sans-serif;
            margin: 0; padding: 0;
            color: #444;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .policy-container {
            max-width: 850px;
            width: 90%;
            margin: 40px auto;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.9);
            border-radius: 32px;
            padding: 40px;
            box-shadow: 0 30px 70px rgba(212, 175, 55, 0.05), 0 10px 30px rgba(0, 0, 0, 0.02);
            box-sizing: border-box;
        }

        .policy-header {
            text-align: center;
            border-bottom: 1px solid rgba(0,0,0,0.08);
            padding-bottom: 25px;
            margin-bottom: 25px;
        }

        .policy-header h1 {
            font-family: 'Cinzel', serif;
            color: #111;
            font-size: 1.6rem;
            margin-bottom: 8px;
        }

        .policy-header p {
            font-size: 0.82rem;
            color: #666;
            line-height: 1.5;
            margin: 4px 0;
        }

        /* --- SCROLLABLE POLICY BOX --- */
        .scrollable-policy-box {
            height: 380px;
            overflow-y: auto;
            padding-right: 15px;
            border: 1px solid rgba(0, 0, 0, 0.06);
            background: rgba(252, 251, 250, 0.5);
            border-radius: 20px;
            padding: 20px;
            box-sizing: border-box;
        }

        /* Custom scrollbar styling for elegance */
        .scrollable-policy-box::-webkit-scrollbar {
            width: 6px;
        }
        .scrollable-policy-box::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.02);
            border-radius: 10px;
        }
        .scrollable-policy-box::-webkit-scrollbar-thumb {
            background: rgba(212, 175, 55, 0.4);
            border-radius: 10px;
        }
        .scrollable-policy-box::-webkit-scrollbar-thumb:hover {
            background: rgba(212, 175, 55, 0.7);
        }

        h2.section-title {
            font-family: 'Cinzel', serif;
            font-size: 1.05rem;
            color: #b8931d;
            margin-top: 15px;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        ol.terms-list {
            padding-left: 20px;
            line-height: 1.8;
            font-size: 0.9rem;
            color: #444;
            margin-bottom: 0;
        }

        ol.terms-list li {
            margin-bottom: 12px;
        }

        .dress-code-box {
            background: rgba(212, 175, 55, 0.04);
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 16px;
            padding: 20px;
            margin-top: 25px;
        }

        .dress-code-box h3 {
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

        .action-footer {
            margin-top: 30px;
            text-align: center;
        }

        .proceed-btn {
            background: #d4af37;
            color: #fff;
            width: 100%;
            padding: 16px;
            border-radius: 50px;
            border: none;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
            letter-spacing: 1.5px;
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.2);
        }

        .proceed-btn:hover:not(:disabled) {
            background: #b8931d;
            transform: translateY(-2px);
        }

        .proceed-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
            opacity: 0.6;
        }

        .scroll-hint {
            font-size: 0.78rem;
            color: #888;
            margin-bottom: 12px;
            font-style: italic;
        }
    </style>
</head>
<body class="light">

<div class="policy-container">
    <div class="policy-header">
        <h1>Villa Marciana Resort</h1>
        <p>No. 61 Main Avenue, Blk. 22 Lot 24, Pacita 2 San Pedro City, Laguna</p>
        <p>Proprietor: Kent S. Lagasca | Mobile: 0917 678 0804 / 0936 043 4197</p>
        <h2 style="font-family: 'Cinzel', serif; margin-top: 20px; font-size: 1.2rem; color: #d4af37;">Rental Agreement &amp; Policies</h2>
    </div>

    <div class="scroll-hint" id="scrollHint">↓ Please scroll down the terms completely to unlock the main page button.</div>

    <!-- Scrollable Window -->
    <div class="scrollable-policy-box" id="policyScrollBox">
        <h2 class="section-title">Terms and Conditions</h2>
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
            <h3>Proper Swimming Attire Guidelines</h3>
            <ul>
                <li><strong>GIRLS:</strong> Swimsuits, Shorts &amp; Tops, Rash Guards</li>
                <li><strong>BOYS:</strong> Swimming Trunks, Shorts, Sando, Rash Guards</li>
            </ul>
        </div>
    </div>

    <div class="action-footer">
        <form action="index.php" method="GET">
            <button type="submit" id="proceedBtn" class="proceed-btn" disabled>Proceed to Main Page</button>
        </form>
    </div>
</div>

<script>
    const scrollBox = document.getElementById('policyScrollBox');
    const proceedBtn = document.getElementById('proceedBtn');
    const scrollHint = document.getElementById('scrollHint');

    scrollBox.addEventListener('scroll', function() {
        // Check if user has scrolled to the bottom (with a small 10px threshold tolerance)
        const isAtBottom = scrollBox.scrollHeight - scrollBox.scrollTop <= scrollBox.clientHeight + 10;

        if (isAtBottom) {
            proceedBtn.disabled = false;
            scrollHint.innerText = "✓ You have reviewed all terms and policies.";
            scrollHint.style.color = "#b8931d";
        }
    });
</script>

</body>
</html>