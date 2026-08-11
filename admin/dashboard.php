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

// --- FETCH ADMIN DATA ---
$stmt_admin = $conn->prepare("SELECT name, image FROM users WHERE id = ?");
$stmt_admin->bind_param("i", $user_id);
$stmt_admin->execute();
$admin_data = $stmt_admin->get_result()->fetch_assoc();

$displayName = !empty($admin_data['name']) ? explode(' ', $admin_data['name'])[0] : 'Admin';
$admin_image = !empty($admin_data['image']) ? '../uploads/' . $admin_data['image'] : null;

// --- ACTION LOGIC: Handle Status Changes ---
if (isset($_GET['action_id']) && isset($_GET['set_status'])) {
    $id = (int)$_GET['action_id'];
    $new_status = $_GET['set_status'];
    
    $res = $conn->query("SELECT u.name FROM reservations r JOIN users u ON r.user_id = u.id WHERE r.id = $id");
    $guest_name = ($res->num_rows > 0) ? $res->fetch_assoc()['name'] : "Unknown Guest";

    $stmt = $conn->prepare("UPDATE reservations SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $id);
    
    if ($stmt->execute()) {
        logActivity($conn, 'STATUS_CHANGE', "Admin updated status to $new_status for guest: $guest_name");
    }
    
    header("Location: dashboard.php?msg=updated");
    exit();
}

// --- NEW ACTION LOGIC: Handle Direct Confirmation ---
if (isset($_GET['confirm_id'])) {
    $id = (int)$_GET['confirm_id'];
    
    $stmt = $conn->prepare("UPDATE reservations SET status = 'confirmed' WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        logActivity($conn, 'BOOKING_CONFIRMED', "Admin confirmed reservation #$id");
        header("Location: dashboard.php?msg=confirmed");
    } else {
        header("Location: dashboard.php?msg=error");
    }
    exit();
}

// --- NEW ACTION LOGIC: Handle Archiving/Restoring ---
if (isset($_GET['archive_id'])) {
    $id = (int)$_GET['archive_id'];
    $new_state = (int)$_GET['state']; // 1 = archive, 0 = restore
    
    $stmt = $conn->prepare("UPDATE reservations SET is_archived = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_state, $id);
    
    if ($stmt->execute()) {
        $logMsg = ($new_state == 1) ? "Archived reservation #$id" : "Restored reservation #$id";
        logActivity($conn, 'ARCHIVE_ACTION', $logMsg);
    }
    
    $msg_type = ($new_state == 1) ? "archive_success" : "restore_success";
    header("Location: dashboard.php?msg=$msg_type&view=" . ($new_state == 1 ? 'active' : 'archived'));
    exit();
}

$today = date('Y-m-d');

// --- DYNAMIC VIEW FILTER ---
$current_view = isset($_GET['view']) && $_GET['view'] === 'archived' ? 'archived' : 'active';
$archive_filter = ($current_view === 'archived') ? 1 : 0;

// FETCH DATA (Includes 'time' column)
$query = "SELECT r.id, u.name, r.date, r.time, r.status, r.is_archived 
          FROM reservations r 
          JOIN users u ON r.user_id = u.id 
          WHERE r.is_archived = $archive_filter
          ORDER BY (r.date = '$today') DESC, r.date DESC";
$result = $conn->query($query);

// STATS
$total_res = $conn->query("SELECT COUNT(*) as count FROM reservations WHERE is_archived = 0")->fetch_assoc()['count'];
$pending = $conn->query("SELECT COUNT(*) as count FROM reservations WHERE status IN ('pending', 'pending_cancel') AND is_archived = 0")->fetch_assoc()['count'];

// --- NEW REVENUE STAT (Quick View) ---
$revenue_total = $conn->query("SELECT SUM(total_price) as total FROM reservations WHERE status = 'confirmed'")->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Villa Marciana | Admin Control</title>
    
    <!-- Prevent Light/Dark Mode Flash on Page Load -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <link rel="stylesheet" href="../css/style.css">
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
            --input-bg: rgba(255, 255, 255, 0.6);
            --modal-bg: rgba(255, 255, 255, 0.95);
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
            --input-bg: rgba(20, 19, 17, 0.6);
            --modal-bg: rgba(20, 19, 17, 0.98);
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

        /* Metric Cards */
        .stat-box { 
            background: var(--card-bg); 
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            padding: 25px; border-radius: 24px; 
            border: 1px solid var(--card-border); transition: all 0.4s ease;
            box-shadow: 0 10px 30px var(--shadow-color);
        }
        .stat-box label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1.5px; color: var(--gold-accent); font-weight: 800; }
        
        /* Attention Highlight Glow */
        .pulse-alert { animation: pulse-glow 2s infinite; border-color: rgba(212, 175, 55, 0.5) !important; }
        @keyframes pulse-glow {
            0% { box-shadow: 0 0 0 0 rgba(212, 175, 55, 0.2); }
            70% { box-shadow: 0 0 0 15px rgba(212, 175, 55, 0); }
            100% { box-shadow: 0 0 0 0 rgba(212, 175, 55, 0); }
        }

        .dashboard-layout { display: grid; grid-template-columns: 1fr 320px; gap: 30px; margin-top: 30px; }
        
        .sidebar-item { 
            background: var(--card-bg); 
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            padding: 35px 25px; border-radius: 24px; 
            border: 1px solid var(--card-border); text-align: center; margin-bottom: 20px;
            box-shadow: 0 15px 40px var(--shadow-color);
        }

        .btn-gold { 
            background: var(--gold-accent); color: #0f0e0d; padding: 14px; border-radius: 50px; 
            font-weight: 800; text-transform: uppercase; letter-spacing: 1px; width: 100%; 
            margin: 20px 0; border: none; cursor: pointer; transition: 0.3s;
            box-shadow: 0 6px 20px rgba(212, 175, 55, 0.2);
        }
        .btn-gold:hover { background: var(--gold-text); transform: translateY(-3px); box-shadow: 0 10px 25px rgba(212, 175, 55, 0.35); }

        /* Elegant Luxury Data Table Grid */
        .table-responsive { 
            background: var(--card-bg); 
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border-radius: 28px; padding: 25px; 
            border: 1px solid var(--card-border);
            box-shadow: 0 20px 50px var(--shadow-color);
        }
        
        .modern-nav {
            position: absolute; top: 0; left: 0; width: 100%; z-index: 100;
            background: var(--nav-bg) !important;
            backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-subtle);
        }

        /* Toast Alert System */
        #toast {
            visibility: hidden; min-width: 280px; background: var(--card-hover-bg);
            backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px); color: var(--text-main); text-align: center; border-radius: 50px;
            padding: 16px 32px; position: fixed; z-index: 10000; left: 50%; bottom: 30px;
            transform: translateX(-50%); border: 1px solid var(--gold-accent);
            box-shadow: 0 15px 45px var(--shadow-color); font-size: 0.9rem; font-weight: 600;
        }
        #toast.show { visibility: visible; animation: fadein 0.5s, fadeout 0.5s 2.5s; }
        @keyframes fadein { from { bottom: 0; opacity: 0; } to { bottom: 30px; opacity: 1; } }
        @keyframes fadeout { from { bottom: 30px; opacity: 1; } to { bottom: 0; opacity: 0; } }

        @media (max-width: 1000px) { .dashboard-layout { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <nav class="modern-nav">
        <div class="nav-container">
            <h1 class="nav-title" onclick="location.href='../index.php'" style="cursor:pointer; color: var(--gold-accent); font-family: 'Cinzel', serif;">
                Villa Marciana <span style="font-size: 0.75rem; color: var(--gold-text); font-family: sans-serif; vertical-align: middle; margin-left: 8px; letter-spacing: 2px; font-weight: 800;">ADMIN</span>
            </h1>
            <div class="nav-buttons" style="display: flex; align-items: center; gap: 12px;">
                <!-- Theme Toggle Button -->
                <button class="theme-toggle-btn" id="themeToggle" onclick="toggleTheme()">
                    <span id="themeIcon">🌙</span> <span id="themeText">Dark</span>
                </button>

                <a href="../index.php" class="nav-btn-alt">View Site</a>
                
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
                        <a href="revenue_report.php">📊 Revenue Analytics</a>
                        <a href="logs.php">📜 Activity Logs</a>
                        <hr style="border: 0; border-top: 1px solid var(--border-subtle); margin: 5px 0;">
                        <a href="../logout.php" style="color: #ff453a;">🚪 Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="hero-wrapper" style="min-height:90vh; padding-top: 100px; box-sizing: border-box;">
        <div class="container wide" style="max-width: 1200px;">
            
            <!-- Metric Display Grid -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div class="stat-box">
                    <label>Active Bookings</label>
                    <div style="font-size: 2.8rem; font-weight: 300; color: var(--text-main); margin-top: 5px; font-family: 'Cinzel', serif;"><?= $total_res ?></div>
                </div>
                <div class="stat-box <?= $pending > 0 ? 'pulse-alert' : '' ?>">
                    <label>Action Required</label>
                    <div style="font-size: 2.8rem; font-weight: 300; color: var(--gold-text); margin-top: 5px; font-family: 'Cinzel', serif;"><?= $pending ?></div>
                </div>
                <div class="stat-box">
                    <label>Total Revenue</label>
                    <div style="font-size: 2.8rem; font-weight: 300; color: var(--gold-accent); margin-top: 5px; font-family: 'Cinzel', serif;">₱<?= number_format($revenue_total, 0) ?></div>
                </div>
            </div>

            <div class="dashboard-layout">
                <div class="table-responsive">
                    <h3 style="padding: 10px 0 20px; color: var(--text-main); font-size: 1.2rem; font-family: 'Cinzel', serif; font-weight: 400; border-bottom: 1px solid var(--border-subtle); margin-bottom: 15px;">
                        <?= $current_view === 'archived' ? '📁 Archived Records' : '🗓️ Active Reservations' ?>
                    </h3>
                    <table style="width: 100%; border-collapse: separate; border-spacing: 0 12px;">
                        <thead>
                            <tr style="text-align: left; color: var(--gold-accent); font-size: 0.72rem; text-transform: uppercase; letter-spacing: 1.5px;">
                                <th style="padding: 10px 15px;">Guest Details</th>
                                <th>Stay Date & Session</th>
                                <th>Status</th>
                                <th style="text-align: right; padding-right: 15px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                <?php
                                    $time_val = $row['time'];
                                    $admin_display_time = "";
                                    if ($time_val == "08:00:00") {
                                        $admin_display_time = "☀️ Day Session";
                                    } elseif ($time_val == "19:00:00") {
                                        $admin_display_time = "🌙 Night Session";
                                    } elseif ($time_val == "08:00:01") {
                                        $admin_display_time = "⏳ 22 Hours";
                                    } else {
                                        $admin_display_time = (!empty($time_val) && $time_val != '00:00:00') ? date("g:i A", strtotime($time_val)) : '02:00 PM';
                                    }
                                ?>
                                <tr style="background: rgba(255,255,255,0.02); transition: all 0.3s ease; border-radius: 16px;">
                                    <td style="padding: 18px 15px; border-radius: 16px 0 0 16px;">
                                        <div style="font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($row['name']) ?></div>
                                        <?php if($row['date'] == $today && $row['is_archived'] == 0): ?>
                                            <span style="background: rgba(212, 175, 55, 0.12); color: var(--gold-text); font-size: 0.6rem; padding: 2px 8px; border-radius: 4px; font-weight: 800; border: 1px solid rgba(212, 175, 55, 0.3); display: inline-block; margin-top: 4px; letter-spacing: 0.5px;">ARRIVING TODAY</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="color: var(--text-main); font-weight: 600;">
                                            <?= formatResortDate($row['date']) ?>
                                        </div>
                                        <div style="color: var(--text-sub); font-size: 0.8rem; margin-top: 2px;">
                                            <?= $admin_display_time ?>
                                        </div>
                                    </td> 
                                    <td><?= getStatusPill($row['status']) ?></td> 
                                    <td style="text-align: right; padding-right: 15px; border-radius: 0 16px 16px 0;">
                                        
                                        <?php if ($row['status'] == 'pending' && $row['is_archived'] == 0): ?>
                                            <button class="nav-btn" style="font-size: 0.7rem; padding: 6px 14px; margin-right: 5px; background: var(--gold-accent); color: #0f0e0d; border: none; border-radius: 50px; font-weight: 700;" onclick="showModal('dashboard.php?confirm_id=<?= $row['id'] ?>', '✅ Confirm Booking', 'Mark this reservation as confirmed?')">Confirm</button>
                                        <?php endif; ?>

                                        <?php if ($row['status'] == 'pending_cancel'): ?>
                                            <button class="nav-btn" style="font-size: 0.7rem; padding: 6px 14px; margin-right: 5px; border-radius: 50px;" onclick="showModal('dashboard.php?action_id=<?= $row['id'] ?>&set_status=cancelled', '✅ Approve Cancellation', 'Confirm cancellation?')">Approve</button>
                                        <?php endif; ?>

                                        <?php if ($row['is_archived'] == 0): ?>
                                            <a href="javascript:void(0)" style="color: var(--text-sub); font-size: 0.75rem; font-weight: 700; text-decoration: none; margin-right: 12px; transition: color 0.2s;" onmouseover="this.style.color='var(--gold-accent)'" onmouseout="this.style.color='var(--text-sub)'" onclick="showModal('dashboard.php?archive_id=<?= $row['id'] ?>&state=1', '📁 Move to Archive', 'Hide this stay from active views?')">Archive</a>
                                        <?php else: ?>
                                            <a href="javascript:void(0)" style="color: var(--gold-accent); font-size: 0.75rem; font-weight: 700; text-decoration: none; margin-right: 12px; transition: color 0.2s;" onmouseover="this.style.color='var(--gold-text)'" onmouseout="this.style.color='var(--gold-accent)'" onclick="showModal('dashboard.php?archive_id=<?= $row['id'] ?>&state=0', '⏪ Restore Record', 'Bring this back to active dashboard?')">Restore</a>
                                        <?php endif; ?>

                                        <a href="javascript:void(0)" style="color: #ff453a; font-size: 0.75rem; font-weight: 700; text-decoration: none; opacity: 0.6; transition: opacity 0.2s;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.6" onclick="showModal('remove.php?id=<?= $row['id'] ?>', '🗑️ Delete Entry', 'Permanently remove this booking?')">Delete</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align:center; padding: 60px; color:var(--text-sub); font-size:0.9rem;">No bookings found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <aside>
                    <div class="sidebar-item" style="border-color: rgba(212, 175, 55, 0.2);">
                        <div style="font-size: 2.5rem; margin-bottom: 12px;">💹</div>
                        <h4 style="margin:0; font-size: 1.1rem; color: var(--text-main); font-weight: 400; font-family: 'Cinzel', serif;">Financials</h4>
                        <p style="color: var(--text-sub); font-size: 0.8rem; margin: 8px 0 20px; line-height: 1.4;">Track your resort earnings and session popularity reports.</p>
                        <button class="nav-btn" style="width: 100%; padding: 14px; background: transparent; border: 1px solid var(--gold-accent); color: var(--gold-accent); border-radius: 50px;" onclick="location.href='revenue_report.php'">Open Revenue Hub</button>
                    </div>

                    <div class="sidebar-item">
                        <div style="font-size: 2.5rem; margin-bottom: 12px;">🧹</div>
                        <h4 style="margin:0; font-size: 1.1rem; color: var(--text-main); font-weight: 400; font-family: 'Cinzel', serif;">Maintenance</h4>
                        <p style="color: var(--text-sub); font-size: 0.8rem; margin: 8px 0 20px; line-height: 1.4;">Keep the dashboard clean by moving past stays to the archive.</p>
                        <button class="btn-gold" onclick="showModal('cleanup.php', '🛡️ Run Archive Clean-up', 'Move all completed stays to the archive?')">Archive Past Dates</button>
                        
                        <a href="dashboard.php?view=<?= $current_view === 'active' ? 'archived' : 'active' ?>" style="font-size: 0.82rem; color: var(--gold-text); text-decoration: none; font-weight: 700; display: block; margin-top: 10px; transition: color 0.2s;">
                            <?= $current_view === 'active' ? 'Open Archive Storage →' : '← Back to Active List' ?>
                        </a>
                    </div>
                </aside>
            </div>
        </div>
    </main>

    <!-- Ultra-Centering Premium Action Modal -->
    <div id="modernModal" class="modal-overlay" style="display: none; position: fixed !important; inset: 0 !important; top: 0 !important; left: 0 !important; width: 100vw !important; height: 100vh !important; background: rgba(0,0,0,0.6) !important; backdrop-filter: blur(15px) !important; -webkit-backdrop-filter: blur(15px) !important; z-index: 9999 !important;">
        <div class="modal-content" style="position: fixed !important; top: 50% !important; left: 50% !important; transform: translate(-50%, -50%) !important; background: var(--modal-bg) !important; padding: 40px !important; border-radius: 32px !important; border: 1px solid rgba(212, 175, 55, 0.2) !important; width: 90% !important; max-width: 420px !important; text-align: center !important; box-shadow: 0 30px 70px var(--shadow-color) !important; margin: 0 !important; box-sizing: border-box !important;">
            <div id="modalIcon" style="font-size: 3.5rem; margin-bottom: 15px;">🛡️</div>
            <h3 id="modalTitle" style="color: var(--text-main); font-size: 1.5rem; margin-bottom: 10px; font-family: 'Cinzel', serif; font-weight: 400;">Confirm Action</h3>
            <p id="modalMessage" style="color: var(--text-sub); line-height: 1.6; margin-bottom: 30px; font-size: 0.95rem;"></p>
            <div style="display: flex; flex-direction: column; gap: 10px; align-items: center !important; justify-content: center !important; width: 100% !important;">
                <a id="modalConfirmBtn" href="#" style="text-decoration:none !important; background: var(--gold-accent) !important; color: #0f0e0d !important; padding: 18px 0 !important; text-align: center !important; border-radius: 50px !important; margin: 0 auto !important; display: block !important; width: 100% !important; max-width: 100% !important; font-weight: 800 !important; text-transform: uppercase !important; letter-spacing: 1px !important; box-shadow: 0 6px 20px rgba(212, 175, 55, 0.2) !important; box-sizing: border-box !important;">Execute Action</a>
                <button onclick="closeModal()" style="background:transparent; border:none; color:var(--text-sub); cursor:pointer; font-weight: 700; padding: 10px; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; width: 100%;">Cancel and Return</button>
            </div>
        </div>
    </div>

    <div id="toast">✅ Action Completed</div>

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

        const modal = document.getElementById('modernModal');
        const modalConfirmBtn = document.getElementById('modalConfirmBtn');
        function showModal(url, title, msg) {
            document.getElementById('modalTitle').innerText = title;
            document.getElementById('modalMessage').innerText = msg;
            modalConfirmBtn.href = url;
            modal.style.display = 'flex';
        }
        function closeModal() { modal.style.display = 'none'; }

        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            const msg = urlParams.get('msg');
            const toast = document.getElementById("toast");

            if (msg) {
                if (msg === 'confirmed') toast.innerText = "✨ Booking successfully confirmed!";
                if (msg === 'archive_success') toast.innerText = "📁 Record moved to archive";
                if (msg === 'restore_success') toast.innerText = "⏪ Record restored to active list";
                if (msg === 'updated') toast.innerText = "✅ Status updated successfully";
                if (msg === 'cleanup_done') toast.innerText = "🧹 Dashboard cleanup complete";
                if (msg === 'error') {
                    toast.innerText = "❌ An error occurred";
                    toast.style.borderColor = "rgba(255, 69, 58, 0.4)";
                }

                toast.className = "show";
                setTimeout(function() { toast.className = toast.className.replace("show", ""); }, 3000);
                
                const newUrl = window.location.pathname + (urlParams.get('view') ? '?view=' + urlParams.get('view') : '');
                window.history.replaceState({}, document.title, newUrl);
            }
        }
    </script>
</body>
</html>