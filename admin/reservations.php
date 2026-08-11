<?php
session_start();
include '../db/connection.php';

// 1. SECURITY: Admin Only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// 2. LOGIC: Handle "Approve Cancellation"
if (isset($_GET['confirm_cancel_id'])) {
    $confirm_id = (int)$_GET['confirm_cancel_id'];
    $stmt = $conn->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ? AND status = 'pending_cancel'");
    $stmt->bind_param("i", $confirm_id);
    if ($stmt->execute()) {
        $stmt->close();
        header("Location: reservations.php?msg=cancelled"); 
        exit();
    }
}

// 3. FILTERING
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$query = "SELECT r.id, u.name, r.date, r.time, r.payment_type, r.status 
          FROM reservations r 
          JOIN users u ON r.user_id = u.id";

if ($filter !== 'all') {
    $query .= " WHERE r.status = '" . $conn->real_escape_string($filter) . "'";
}
$query .= " ORDER BY r.date DESC";
$result = $conn->query($query);

$displayName = isset($_SESSION['name']) ? explode(' ', $_SESSION['name'])[0] : 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Reservations | Villa Marciana</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* Modern Modal Styles */
        .modal-overlay {
            display: none; position: fixed; top: 0; left: 0;
            width: 100%; height: 100%; background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px); z-index: 2000;
            align-items: center; justify-content: center;
        }
        .modal-content {
            background: #1a1a1a; padding: 30px; border-radius: 24px;
            border: 1px solid rgba(0, 255, 136, 0.2); width: 90%; max-width: 420px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.6); text-align: left;
            animation: modalFadeIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        @keyframes modalFadeIn {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .modal-btn-group { display: flex; gap: 12px; justify-content: flex-end; margin-top: 25px; }
    </style>
</head>
<body class="dark">

    <nav class="modern-nav">
        <div class="nav-container">
            <h1 class="nav-title" onclick="location.href='../index.php'">Villa Marciana</h1>
            <div class="nav-buttons">
                <a href="dashboard.php" style="color: #fff; text-decoration: none; font-size: 0.9rem; margin-right: 15px;">← Dashboard</a>
                <div class="account-dropdown">
                    <button class="account-toggle" onclick="toggleMenu()">👤 <?= $displayName ?></button>
                    <div id="accountMenu" class="account-menu">
                        <a href="../logout.php" style="color: #ff4444;">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="hero-wrapper">
        <div class="container wide">
            
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; flex-wrap: wrap; gap: 20px;">
                <div style="text-align: left;">
                    <h2 style="color: #00ff88; margin: 0;">Master Reservations</h2>
                    <p style="color: rgba(255,255,255,0.5);">Filter and manage all database records.</p>
                </div>
                
                <div style="display: flex; gap: 10px; background: rgba(255,255,255,0.05); padding: 5px; border-radius: 12px;">
                    <a href="?filter=all" class="<?= $filter == 'all' ? 'nav-btn' : '' ?>" style="text-decoration:none; padding: 8px 15px; font-size: 0.8rem; color: #fff;">All</a>
                    <a href="?filter=pending_cancel" class="<?= $filter == 'pending_cancel' ? 'nav-btn' : '' ?>" style="text-decoration:none; padding: 8px 15px; font-size: 0.8rem; color: #fff;">Requests</a>
                    <a href="?filter=confirmed" class="<?= $filter == 'confirmed' ? 'nav-btn' : '' ?>" style="text-decoration:none; padding: 8px 15px; font-size: 0.8rem; color: #fff;">Confirmed</a>
                </div>
            </div>

            <?php if (isset($_GET['msg'])): ?>
                <div id="alertBox" style="background: rgba(0, 255, 136, 0.1); color: #00ff88; padding: 15px; border-radius: 10px; border: 1px solid #00ff88; margin-bottom: 25px; text-align: left; font-size: 0.9rem; display: flex; justify-content: space-between; align-items: center;">
                    <span>
                        <?php 
                            if($_GET['msg'] == 'deleted') echo "✅ Record permanently removed.";
                            if($_GET['msg'] == 'cancelled') echo "✅ Cancellation approved.";
                        ?>
                    </span>
                    <span style="cursor:pointer; opacity: 0.5;" onclick="this.parentElement.style.display='none'">✕</span>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Guest</th>
                            <th>Date</th>
                            <th>Session</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                                <td style="color: #fff;"><?= date("M d, Y", strtotime($row['date'])) ?></td>
                                <td style="color: #fff;"><?= $row['time'] == '08:00' ? '☀️ Day' : '🌙 Night' ?></td>
                                <td>
                                    <span style="font-weight: 600; color: <?= $row['payment_type'] == 'GCash' ? '#00ff88' : '#ffffff'; ?>;">
                                        <?= htmlspecialchars($row['payment_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-pill <?= $row['status'] ?>">
                                        <?= str_replace('_', ' ', strtoupper($row['status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['status'] == 'pending_cancel'): ?>
                                        <button class="nav-btn" style="padding: 5px 12px; font-size: 0.7rem;" 
                                                onclick="showModal('reservations.php?confirm_cancel_id=<?= $row['id'] ?>', 'Confirm Cancellation', 'Are you sure you want to approve this guest\'s cancellation request?')">
                                            Approve
                                        </button>
                                    <?php else: ?>
                                        <a href="javascript:void(0)" 
                                           style="color: #ff4444; font-size: 0.85rem; text-decoration: none; font-weight: bold;"
                                           onclick="showModal('remove.php?id=<?= $row['id'] ?>', 'Delete Record', 'This action is permanent. Do you want to delete this reservation from the database?')">
                                            Remove
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="padding: 50px; color: rgba(255,255,255,0.2);">No matching records.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="modernModal" class="modal-overlay">
        <div class="modal-content">
            <h3 id="modalTitle" style="margin-top:0;">Action</h3>
            <p id="modalMessage" style="color: rgba(255,255,255,0.7); line-height: 1.5;"></p>
            <div class="modal-btn-group">
                <button onclick="closeModal()" class="nav-btn" style="background:transparent; color:#fff; border:1px solid rgba(255,255,255,0.1);">Cancel</button>
                <a id="modalConfirmBtn" href="#" class="nav-btn" style="text-decoration:none; background:#ff4444; color:#fff;">Confirm</a>
            </div>
        </div>
    </div>

    <script>
        function toggleMenu() { document.getElementById('accountMenu').classList.toggle('show'); }

        const modal = document.getElementById('modernModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalMessage = document.getElementById('modalMessage');
        const modalConfirmBtn = document.getElementById('modalConfirmBtn');

        function showModal(url, title, message) {
            modalTitle.innerText = title;
            modalMessage.innerText = message;
            modalConfirmBtn.href = url;
            modalConfirmBtn.style.background = url.includes('remove') ? '#ff4444' : '#00ff88';
            modalConfirmBtn.style.color = url.includes('remove') ? '#fff' : '#000';
            modal.style.display = 'flex';
        }

        function closeModal() { modal.style.display = 'none'; }

        window.onclick = function(e) {
            if (!e.target.matches('.account-toggle')) {
                const menu = document.getElementById('accountMenu');
                if (menu && menu.classList.contains('show')) menu.classList.remove('show');
            }
            if (e.target == modal) closeModal();
        }
    </script>
</body>
</html>