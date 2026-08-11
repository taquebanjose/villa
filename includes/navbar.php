<?php
// includes/navbar.php

// Ensure base URL exists fallback
if (!isset($base_url)) {
    $app_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    if (basename($app_path) === 'admin') {
        $app_path = dirname($app_path);
    }
    $base_url = rtrim($app_path, '/') . '/';
}

$uid = $_SESSION['user_id'] ?? null; 
$displayName = isset($_SESSION['name']) ? explode(' ', $_SESSION['name'])[0] : 'Guest';

// Fetch avatar if logged in
$user_image = null;
if ($uid && isset($conn)) {
    $stmt = $conn->prepare("SELECT image FROM users WHERE id = ?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $user_image = !empty($res['image']) ? $base_url . 'uploads/' . $res['image'] : null;
    $stmt->close();
}
?>
<nav class="modern-nav">
    <div class="nav-container">
        <h1 class="nav-title" onclick="location.href='<?= $base_url ?>index.php'">Villa Marciana</h1>
        <div class="nav-buttons">
            <button class="theme-toggle-btn" id="themeToggle" onclick="toggleTheme()">
                <span id="themeIcon">🌙</span> <span id="themeText">Dark</span>
            </button>

            <?php if ($uid): ?>
                <a href="<?= $base_url ?>reserve.php" class="nav-btn">✨ Book Now</a>
                <div class="account-dropdown">
                    <button class="account-toggle" onclick="togglePremiumMenu(event)">
                        <?php if ($user_image): ?>
                            <img src="<?= htmlspecialchars($user_image, ENT_QUOTES, 'UTF-8') ?>" class="nav-avatar" alt="User Profile">
                        <?php else: ?>
                            <div class="nav-avatar" style="display:flex; align-items:center; justify-content:center; background: rgba(0,0,0,0.03); color: #b8931d; font-size: 0.9rem;">👤</div>
                        <?php endif; ?>
                        <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>
                    </button>
                    <div class="account-menu" id="userMenu">
                        <div style="padding: 10px 15px; font-size: 0.75rem; color: var(--gold-text); text-transform: uppercase; font-weight: 800; letter-spacing: 1px;">Member Portal</div>
                        <a href="<?= $base_url ?>myreservations.php">📅 My Reservations</a>
                        <a href="<?= $base_url ?>profile.php">⚙️ Account Settings</a>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <a href="<?= $base_url ?>admin/dashboard.php" style="color: #ffa000; font-weight: bold;">🛠️ Admin Dashboard</a>
                        <?php endif; ?>
                        <hr style="border: 0; border-top: 1px solid var(--border-subtle); margin: 5px 0;">
                        <a href="<?= $base_url ?>logout.php" style="color: #ff3b30;">🚪 Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= $base_url ?>login.php" class="nav-btn-alt">Login</a>
                <a href="<?= $base_url ?>register.php" class="nav-btn">Join Now</a>
            <?php endif; ?>
        </div>
    </div>
</nav>