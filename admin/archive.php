<?php
session_start();
include '../db/connection.php';
include 'functions.php'; 

// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$today = date('Y-m-d');
$displayName = isset($_SESSION['name']) ? explode(' ', $_SESSION['name'])[0] : 'Admin';

// --- NEW: SEARCH LOGIC ---
$searchTerm = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// 2. Fetch Archived Reservations with Filter
$query = "SELECT r.id, u.name, r.date, r.status 
          FROM reservations r 
          JOIN users u ON r.user_id = u.id 
          WHERE r.status = 'archived'";

if (!empty($searchTerm)) {
    $query .= " AND (u.name LIKE '%$searchTerm%' OR r.date LIKE '%$searchTerm%')";
}

$query .= " ORDER BY r.date DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive Records | Villa Marciana</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .account-dropdown { position: relative; display: inline-block; }
        .account-menu {
            display: none; position: absolute; right: 0; top: 130%;
            background: rgba(20, 20, 20, 0.9); backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 24px;
            width: 220px; padding: 12px; z-index: 3000; flex-direction: column;
            opacity: 0; transform: translateY(-15px) scale(0.95); transform-origin: top right;
            transition: opacity 0.4s ease, transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .account-menu.active { display: flex; opacity: 1; transform: translateY(0) scale(1); }
        .account-menu a { padding: 12px 15px; color: #ccc; text-decoration: none; border-radius: 12px; transition: 0.2s; font-size: 0.9rem; }
        .account-menu a:hover { background: rgba(255, 255, 255, 0.05); color: #00ff88; padding-left: 20px; }
        .archive-container { max-width: 1000px; margin: 40px auto; padding: 20px; animation: fadeInUp 0.8s ease; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        /* Search Bar Styles */
        .search-box {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 10px 20px;
            border-radius: 50px;
            color: white;
            outline: none;
            width: 300px;
            transition: 0.3s;
        }
        .search-box:focus { border-color: #00ff88; background: rgba(255, 255, 255, 0.06); }

        .archive-pill {
            font-size: 0.65rem; padding: 4px 10px; border-radius: 6px; font-weight: 800;
            text-transform: uppercase; border: 1px solid rgba(255,255,255,0.1);
        }
        .pill-stayed { background: rgba(0, 255, 136, 0.1); color: #00ff88; }
        .pill-cancelled { background: rgba(255, 68, 68, 0.1); color: #ff4444; }
    </style>
</head>
<body class="dark">

    <nav class="modern-nav">
        <div class="nav-container">
            <h1 class="nav-title" onclick="location.href='dashboard.php'">Villa Marciana</h1>
            <div class="nav-buttons">
                <a href="dashboard.php" class="nav-btn-alt">← Dashboard</a>
                <div class="account-dropdown">
                    <div class="account-toggle" style="cursor:pointer;" onclick="togglePremiumMenu(event)">
                        👤 <?= htmlspecialchars($displayName) ?> 
                    </div>
                    <div class="account-menu" id="userMenu">
                        <a href="logs.php">📜 Activity Logs</a>
                        <a href="../profile.php">⚙️ Account Settings</a>
                        <hr style="border: 0; border-top: 1px solid #222; margin: 5px 0;">
                        <a href="../logout.php" style="color: #ff4444;">🚪 Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="archive-container">
        <header style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
            <div>
                <h2 style="color: #fff; margin-bottom: 5px;">Reservation Archive</h2>
                <p style="color: #555; font-size: 0.9rem;">Historical data of past stays and cancellations.</p>
            </div>
            
            <form action="" method="GET" style="display: flex; gap: 10px; align-items: center;">
                <input type="text" name="search" class="search-box" placeholder="Search name or date..." value="<?= htmlspecialchars($searchTerm) ?>">
                <?php if(!empty($searchTerm)): ?>
                    <a href="archive.php" style="color: #ff4444; text-decoration: none; font-size: 0.8rem;">Clear</a>
                <?php endif; ?>
            </header>

        <div style="margin-bottom: 10px; display: flex; justify-content: flex-end;">
            <div style="color: #333; font-size: 0.8rem; font-weight: bold; background: rgba(255,255,255,0.02); padding: 5px 15px; border-radius: 10px;">
                RECORDS FOUND: <?= $result->num_rows ?>
            </div>
        </div>

        <div class="table-responsive" style="background: rgba(20,20,20,0.4); border-radius: 30px; border: 1px solid rgba(255,255,255,0.05); padding: 10px;">
            <table class="glass-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; color: #555; font-size: 0.7rem; text-transform: uppercase;">
                        <th style="padding: 20px;">Guest Name</th>
                        <th>Stay Date</th>
                        <th>History</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr style="border-top: 1px solid rgba(255,255,255,0.03);">
                            <td style="padding: 20px; color: #aaa;"><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                            <td style="color: #888;"><?= formatResortDate($row['date']) ?></td>
                            <td>
                                <?php 
                                    if($row['date'] < $today) {
                                        echo '<span class="archive-pill pill-stayed">✓ Stay Completed</span>';
                                    } else {
                                        echo '<span class="archive-pill pill-cancelled">✕ Cancelled</span>';
                                    }
                                ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 40px; color: #444;">No records match your search.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        function togglePremiumMenu(event) {
            event.stopPropagation();
            const menu = document.getElementById('userMenu');
            menu.classList.toggle('active');
            menu.style.display = menu.classList.contains('active') ? 'flex' : 'none';
        }
        window.onclick = function(event) {
            if (!event.target.closest('.account-dropdown')) { 
                const menu = document.getElementById('userMenu');
                menu.classList.remove('active'); 
                menu.style.display = 'none';
            }
        }
    </script>
</body>
</html>