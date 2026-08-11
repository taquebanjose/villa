<?php
session_start();
include '../db/connection.php';
include 'functions.php'; 

// 1. Strict Admin Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- FETCH ADMIN DATA (For the Dropdown) ---
$stmt_admin = $pdo->prepare("SELECT name, image FROM users WHERE id = ?");
$stmt_admin->execute([$user_id]);
$admin_data = $stmt_admin->fetch(PDO::FETCH_ASSOC);

$displayName = !empty($admin_data['name']) ? explode(' ', $admin_data['name'])[0] : 'Admin';
$admin_image = !empty($admin_data['image']) ? '../uploads/' . $admin_data['image'] : null;

// --- REVENUE LOGIC ---
$stmt_total = $pdo->query("SELECT SUM(total_price) as total FROM reservations WHERE status = 'confirmed'");
$total_res = $stmt_total->fetch(PDO::FETCH_ASSOC);
$lifetime_revenue = $total_res['total'] ?? 0;

$monthly_query = "SELECT MONTHNAME(date) as month, SUM(total_price) as revenue FROM reservations WHERE status = 'confirmed' AND YEAR(date) = YEAR(CURDATE()) GROUP BY MONTH(date) ORDER BY MONTH(date)";
$monthly_results = $pdo->query($monthly_query);
$months = []; $revenues = [];
while($row = $monthly_results->fetch(PDO::FETCH_ASSOC)) { 
    $months[] = $row['month']; 
    $revenues[] = (float)$row['revenue']; 
}

$session_query = "SELECT time, COUNT(*) as count FROM reservations WHERE status = 'confirmed' GROUP BY time";
$session_results = $pdo->query($session_query);
$session_labels = []; $session_counts = [];
while($row = $session_results->fetch(PDO::FETCH_ASSOC)) {
    $label = ($row['time'] == "08:00:00") ? "☀️ Day" : (($row['time'] == "19:00:00") ? "🌙 Night" : "⏳ 22h");
    $session_labels[] = $label; 
    $session_counts[] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Villa Marciana | Financial Intelligence</title>
    
    <!-- Prevent Light/Dark Mode Flash on Page Load -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    
    <style>
        /* --- DYNAMIC THEME SYSTEM (CSS VARIABLES) --- */
        :root {
            --bg-color: #fcfbfa;
            --text-main: #111111;
            --text-sub: #555555;
            --card-bg: rgba(255, 255, 255, 0.75);
            --card-border: rgba(255, 255, 255, 0.8);
            --card-hover-bg: rgba(28, 35, 48, 0.95);
            --nav-bg: rgba(255, 255, 255, 0.8);
            --border-subtle: rgba(0, 0, 0, 0.06);
            --shadow-color: rgba(212, 175, 55, 0.06);
            --gold-accent: #d4af37;
            --gold-text: #b8931d;
            --item-bg: rgba(255, 255, 255, 0.6);
            --chart-grid: rgba(0, 0, 0, 0.04);
        }

        [data-theme="dark"] {
            --bg-color: #0f0e0d;
            --text-main: #f1ede1;
            --text-sub: #b0b5c0;
            --card-bg: rgba(25, 24, 22, 0.7);
            --card-border: rgba(255, 255, 255, 0.06);
            --card-hover-bg: rgba(20, 19, 17, 0.96);
            --nav-bg: rgba(15, 14, 13, 0.85);
            --border-subtle: rgba(255, 255, 255, 0.08);
            --shadow-color: rgba(0, 0, 0, 0.5);
            --gold-accent: #d4af37;
            --gold-text: #e6c65c;
            --item-bg: rgba(25, 24, 22, 0.7);
            --chart-grid: rgba(255, 255, 255, 0.06);
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            transition: background-color 0.4s ease, color 0.4s ease;
            margin: 0;
            padding: 0;
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

        .account-dropdown { position: relative; display: inline-block; }
        .account-toggle {
            cursor: pointer; transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); border-radius: 50px;
            background: var(--card-bg); border: 1px solid var(--border-subtle);
            color: var(--text-main); padding: 6px 20px 6px 8px; display: flex; align-items: center; gap: 10px;
            backdrop-filter: blur(10px);
        }
        .nav-avatar { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 1px solid rgba(212, 175, 55, 0.4); }
        .account-toggle:hover { transform: translateY(-2px); background: rgba(212, 175, 55, 0.08); border-color: var(--gold-accent); box-shadow: 0 6px 25px rgba(212, 175, 55, 0.15); }

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

        /* Central Glass Panel */
        .glass-panel {
            background: var(--card-bg);
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--card-border);
            border-radius: 32px;
            padding: 40px;
            margin-top: 20px;
            box-shadow: 0 20px 50px var(--shadow-color);
        }

        .stat-box { 
            background: var(--item-bg); 
            padding: 25px; 
            border-radius: 24px; 
            border: 1px solid rgba(212, 175, 55, 0.15);
            box-shadow: 0 10px 30px var(--shadow-color);
        }
        .stat-box label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1.5px; color: var(--gold-text); font-weight: 800; }
        
        .charts-row { display: grid; grid-template-columns: 1.5fr 1fr; gap: 20px; margin-top: 30px; }
        .chart-box { 
            background: var(--item-bg); 
            border: 1px solid var(--border-subtle); 
            padding: 25px; 
            border-radius: 24px; 
        }

        /* Gold Action Buttons */
        .btn-gold { 
            background: var(--gold-accent); color: #0f0e0d; padding: 12px 24px; border-radius: 50px; 
            font-weight: 800; text-transform: uppercase; letter-spacing: 1px; border: none; 
            cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; justify-content: center;
            box-shadow: 0 6px 20px rgba(212, 175, 55, 0.2);
        }
        .btn-gold:hover { background: var(--gold-text); transform: translateY(-2px); box-shadow: 0 10px 25px rgba(212, 175, 55, 0.35); }

        @media (max-width: 850px) {
            .charts-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <nav class="modern-nav">
        <div class="nav-container">
            <h1 class="nav-title" onclick="location.href='dashboard.php'" style="cursor:pointer; color: var(--gold-accent); font-family: 'Cinzel', serif;">
                Villa Marciana <span style="font-size: 0.75rem; color: var(--gold-text); font-family: sans-serif; vertical-align: middle; margin-left: 8px; letter-spacing: 2px; font-weight: 800;">ADMIN</span>
            </h1>
            <div class="nav-buttons" style="display: flex; align-items: center; gap: 12px;">
                <!-- Theme Toggle Button -->
                <button class="theme-toggle-btn" id="themeToggle" onclick="toggleTheme()">
                    <span id="themeIcon">🌙</span> <span id="themeText">Dark</span>
                </button>

                <a href="dashboard.php" class="nav-btn-alt">← Dashboard</a>
                
                <div class="account-dropdown">
                    <button class="account-toggle" onclick="togglePremiumMenu(event)">
                        <?php if ($admin_image): ?>
                            <img src="<?= $admin_image ?>" class="nav-avatar">
                        <?php else: ?>
                            <div class="nav-avatar" style="display:flex; align-items:center; justify-content:center; background: rgba(255,255,255,0.05); color: var(--gold-accent);">👤</div>
                        <?php endif; ?>
                        <?= htmlspecialchars($displayName) ?> 
                    </button>
                    <div class="account-menu" id="userMenu">
                        <div style="padding: 10px 15px; font-size: 0.75rem; color: var(--gold-text); text-transform: uppercase; font-weight: 800; letter-spacing: 1px;">Admin Controls</div>
                        <a href="../profile.php">⚙️ Account Settings</a>
                        <a href="logs.php">📜 Activity Logs</a>
                        <hr style="border: 0; border-top: 1px solid var(--border-subtle); margin: 5px 0;">
                        <a href="../logout.php" style="color: #ff453a;">🚪 Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="hero-wrapper" style="min-height:90vh; padding-top: 100px; box-sizing: border-box;">
        <div class="container wide" style="max-width: 1100px;">
            
            <div class="glass-panel" id="report-content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
                    <div>
                        <h2 style="color: var(--text-main); margin: 0; font-size: 1.8rem; font-family: 'Cinzel', serif; font-weight: 400;">Business Intelligence</h2>
                        <p style="color: var(--text-sub); font-size: 0.85rem;">Financial tracking & session popularity</p>
                    </div>
                    <!-- Unified Premium Centered Button -->
                    <button class="btn-gold" onclick="exportToPDF()">📩 Export PDF</button>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px;">
                    <div class="stat-box">
                        <label>Total Revenue</label>
                        <div style="font-size: 2.8rem; font-weight: 300; color: var(--gold-accent); margin-top: 5px; font-family: 'Cinzel', serif;">₱<?= number_format($lifetime_revenue, 2) ?></div>
                    </div>
                    <div class="stat-box">
                        <label>Top Session</label>
                        <div style="font-size: 2.8rem; font-weight: 300; color: var(--text-main); margin-top: 5px; font-family: 'Cinzel', serif;"><?= !empty($session_labels) ? $session_labels[0] : "N/A" ?></div>
                    </div>
                </div>

                <div class="charts-row">
                    <div class="chart-box">
                        <h4 style="color: var(--gold-text); margin-bottom: 20px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; font-weight: 800;">Monthly Revenue</h4>
                        <canvas id="revenueChart"></canvas>
                    </div>
                    <div class="chart-box">
                        <h4 style="color: var(--gold-text); margin-bottom: 20px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; font-weight: 800;">Popularity Mix</h4>
                        <canvas id="sessionChart"></canvas>
                    </div>
                </div>
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
            
            // Re-render chart grid lines dynamically on theme switch
            revenueChart.options.scales.y.grid.color = newTheme === 'dark' ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.04)';
            revenueChart.update();
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

        // TOGGLE MENU SCRIPT
        function togglePremiumMenu(event) {
            event.stopPropagation();
            const menu = document.getElementById('userMenu');
            if (menu.classList.contains('active')) { closePremiumMenu(); } 
            else {
                menu.style.display = 'flex';
                requestAnimationFrame(() => { menu.classList.add('active'); });
            }
        }
        function closePremiumMenu() {
            const menu = document.getElementById('userMenu');
            menu.classList.remove('active');
            setTimeout(() => { if (!menu.classList.contains('active')) { menu.style.display = 'none'; } }, 400); 
        }
        window.onclick = function(event) { if (!event.target.closest('.account-dropdown')) { closePremiumMenu(); } }

        // CHART CONFIGURATION
        const revCtx = document.getElementById('revenueChart').getContext('2d');
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        
        const revenueChart = new Chart(revCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($months) ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?= json_encode($revenues) ?>,
                    borderColor: '#d4af37',
                    backgroundColor: 'rgba(212, 175, 55, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: { 
                plugins: { legend: { display: false } }, 
                scales: { 
                    y: { grid: { color: currentTheme === 'dark' ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.04)' } }, 
                    x: { grid: { display: false } } 
                } 
            }
        });

        const sessCtx = document.getElementById('sessionChart').getContext('2d');
        new Chart(sessCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($session_labels) ?>,
                datasets: [{
                    data: <?= json_encode($session_counts) ?>,
                    backgroundColor: ['#d4af37', '#8a7322', '#f3e5ab'],
                    borderWidth: 0
                }]
            },
            options: { 
                plugins: { 
                    legend: { 
                        position: 'bottom', 
                        labels: { color: currentTheme === 'dark' ? '#f1ede1' : '#333333', font: { weight: 'bold' } } 
                    } 
                }, 
                cutout: '70%' 
            }
        });

        function exportToPDF() {
            const element = document.getElementById('report-content');
            const opt = {
                margin: 0.5,
                filename: 'Villa-Marciana-Analytics.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, backgroundColor: '#ffffff' },
                jsPDF: { unit: 'in', format: 'letter', orientation: 'landscape' }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>
</html>