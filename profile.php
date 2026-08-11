<?php
session_start();
include 'db/connection.php';

// 1. Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. Fetch Latest User Data (Using PDO)
$stmt = $pdo->prepare("SELECT name, email, image, role FROM users WHERE id = ?");
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
    <title>Account Settings | Villa Marciana</title>
    
    <!-- Prevent Light/Dark Mode Flash on Page Load -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.5/croppie.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.5/croppie.min.js"></script>
    
    <link rel="stylesheet" href="css/style.css">
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
            --input-focus-bg: rgba(255, 255, 255, 0.95);
            --modal-bg: rgba(255, 255, 255, 0.95);
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
            --input-bg: rgba(15, 18, 24, 0.6);
            --input-focus-bg: rgba(28, 35, 48, 0.95);
            --modal-bg: rgba(22, 27, 36, 0.95);
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
            background: var(--modal-bg); backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid var(--border-subtle); border-radius: 20px; width: 240px;
            padding: 12px; box-shadow: 0 20px 50px var(--shadow-color); z-index: 2000;
            flex-direction: column; opacity: 0; transform: translateY(-10px) scale(0.98);
            transform-origin: top right; transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .account-menu.active { display: flex; opacity: 1; transform: translateY(0) scale(1); }
        .account-menu a { padding: 12px 18px; color: var(--text-main); text-decoration: none; display: block; border-radius: 12px; font-size: 0.85rem; font-weight: 500; transition: all 0.3s ease; }
        .account-menu a:hover { background: rgba(212, 175, 55, 0.12); color: var(--gold-text); padding-left: 24px; }

        /* --- PROFILE CARD STYLING --- */
        .reserve-card {
            background: var(--card-bg);
            backdrop-filter: blur(30px) saturate(180%);
            -webkit-backdrop-filter: blur(30px) saturate(180%);
            border: 1px solid var(--card-border);
            box-shadow: 0 30px 70px var(--shadow-color), 0 10px 30px rgba(0, 0, 0, 0.02);
            border-radius: 32px; padding: 50px 40px; max-width: 480px; width: 90%; text-align: center;
            margin: auto; margin-top: 50px;
            animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        /* Avatar with Warm Gold Frame */
        .profile-pic-container { position: relative; width: 130px; height: 130px; margin: 0 auto 30px; }
        .big-avatar {
            width: 100%; height: 100%; border-radius: 50%; object-fit: cover;
            border: 2px solid var(--gold-accent); padding: 5px; background: rgba(212, 175, 55, 0.03);
            box-shadow: 0 8px 30px rgba(212, 175, 55, 0.15);
            transition: all 0.3s ease;
        }
        .image-upload-label {
            position: absolute; bottom: 5px; right: 5px; background: var(--gold-accent);
            width: 38px; height: 38px; border-radius: 50%; color: #fff;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: 1.1rem; transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); 
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
        }
        .image-upload-label:hover { transform: scale(1.1) rotate(10deg); background: var(--gold-text); }

        /* FORM INPUT STYLES WITH OVERRIDE FOR EXTERNAL CSS */
        .form-group { text-align: left; margin-bottom: 25px; }
        .form-group label { 
            display: block; color: var(--gold-text); font-size: 0.75rem; 
            font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px;
            margin-bottom: 10px; margin-left: 4px;
        }
        .modern-input {
            width: 100% !important; 
            padding: 16px !important; 
            background: var(--input-bg) !important;
            border: 1px solid var(--border-subtle) !important; 
            border-radius: 16px !important;
            color: var(--text-main) !important; 
            -webkit-text-fill-color: var(--text-main) !important;
            font-size: 1rem !important; 
            outline: none !important; 
            transition: all 0.3s ease !important;
            box-sizing: border-box !important;
        }
        .modern-input:focus { 
            border-color: var(--gold-accent) !important; 
            background: var(--input-focus-bg) !important; 
            color: var(--text-main) !important;
            -webkit-text-fill-color: var(--text-main) !important;
            box-shadow: 0 5px 20px var(--shadow-color) !important; 
        }
        .modern-input:disabled { 
            color: var(--text-sub) !important; 
            -webkit-text-fill-color: var(--text-sub) !important;
            background: rgba(0, 0, 0, 0.03) !important; 
            cursor: not-allowed !important; 
        }

        /* Autofill fixes */
        .modern-input:-webkit-autofill,
        .modern-input:-webkit-autofill:hover,
        .modern-input:-webkit-autofill:focus {
            -webkit-text-fill-color: var(--text-main) !important;
            -webkit-box-shadow: 0 0 0px 1000px var(--input-focus-bg) inset !important;
            transition: background-color 5000s ease-in-out 0s;
        }

        .save-btn {
            background: var(--gold-accent); color: #fff; width: 100%; padding: 18px;
            border-radius: 50px; border: none; font-weight: 800; text-transform: uppercase;
            font-size: 0.9rem; cursor: pointer; transition: all 0.3s ease; letter-spacing: 1.5px;
            margin-top: 10px;
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.2);
        }
        .save-btn:hover { background: var(--gold-text); transform: translateY(-2px); box-shadow: 0 10px 30px rgba(212, 175, 55, 0.3); }

        /* CROP MODAL */
        #cropModal {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background: rgba(0,0,0,0.4); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px); z-index: 5000; 
            align-items: center; justify-content: center;
        }
        .crop-container {
            background: var(--modal-bg); padding: 30px; border-radius: 30px; border: 1px solid var(--border-subtle);
            max-width: 90%; width: 400px; text-align: center; box-shadow: 0 30px 70px var(--shadow-color);
        }

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

<div id="success-toast">✔️ Profile Updated Successfully</div>

<div id="cropModal">
    <div class="crop-container">
        <h3 style="color: var(--text-main); margin-bottom: 20px; font-weight: 400; font-family: 'Cinzel', serif; letter-spacing: 1px;">Adjust Photo</h3>
        <div id="croppie-viewport"></div>
        <div style="display: flex; gap: 12px; margin-top: 25px;">
            <button class="nav-btn-alt" style="flex: 1; padding: 12px 24px; border: 1px solid var(--border-subtle); background: transparent; color: var(--text-sub); border-radius: 50px; cursor: pointer; transition: all 0.3s;" onclick="closeCrop()">Cancel</button>
            <button class="nav-btn" style="flex: 1; padding: 12px 24px; background: var(--gold-accent); color: #fff; border: none; border-radius: 50px; font-weight: bold; cursor: pointer; transition: all 0.3s;" onclick="saveCrop()">Crop & Use</button>
        </div>
    </div>
</div>

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
                    <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>
                </button>
                <div class="account-menu" id="userMenu">
                    <div style="padding: 10px 15px; font-size: 0.75rem; color: var(--gold-text); text-transform: uppercase; font-weight: 800; letter-spacing: 1px;">Member Portal</div>
                    <a href="myreservations.php">📅 My Reservations</a>
                    <a href="reserve.php">✨ Book New Stay</a>
                    
                    <?php if (isset($user['role']) && $user['role'] === 'admin'): ?>
                        <a href="admin/dashboard.php" style="color: #ffa000; font-weight: bold;">🛡️ Admin Dashboard</a>
                    <?php endif; ?>

                    <hr style="border: 0; border-top: 1px solid var(--border-subtle); margin: 5px 0;">
                    <a href="logout.php" style="color: #ff3b30;">🚪 Logout</a>
                </div>
            </div>
        </div>
    </div>
</nav>

<main class="hero-wrapper" style="display:flex; align-items:center; min-height:90vh; padding-top: 80px; box-sizing: border-box;">
    <div class="reserve-card">
        <h2 style="color: var(--text-main); margin-bottom: 5px; font-size: 1.8rem; font-family: 'Cinzel', serif; font-weight: 400; letter-spacing: 1px;">Profile Settings</h2>
        <p style="color: var(--text-sub); font-size: 0.9rem; margin-bottom: 35px;">Update your personal luxury profile</p>

        <form action="process_profile.php" method="POST" enctype="multipart/form-data">
            <div class="profile-pic-container">
                <img src="<?= $user_image ? htmlspecialchars($user_image, ENT_QUOTES, 'UTF-8') : 'img/default-avatar.png' ?>" id="preview" class="big-avatar">
                <label for="profile_image" class="image-upload-label">📸</label>
                <input type="file" id="profile_image" accept="image/*" style="display: none;" onchange="initCropper(this)">
                <input type="hidden" name="cropped_image" id="cropped_image">
            </div>

            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" class="modern-input" value="<?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>" required placeholder="Enter your name">
            </div>

            <div class="form-group">
                <label>Email Address (Locked)</label>
                <input type="email" class="modern-input" value="<?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?>" disabled>
            </div>

            <button type="submit" class="save-btn">
                Save Changes
            </button>
        </form>
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

    let croppieInstance = null;
    function initCropper(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('cropModal').style.display = 'flex';
                if (croppieInstance) croppieInstance.destroy();
                croppieInstance = new Croppie(document.getElementById('croppie-viewport'), {
                    viewport: { width: 200, height: 200, type: 'circle' },
                    boundary: { width: 300, height: 300 },
                    showZoomer: true
                });
                croppieInstance.bind({ url: e.target.result });
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function saveCrop() {
        croppieInstance.result({ type: 'base64', size: 'viewport', format: 'png' }).then(function(base64) {
            document.getElementById('preview').src = base64;
            document.getElementById('cropped_image').value = base64;
            closeCrop();
        });
    }

    function closeCrop() {
        document.getElementById('cropModal').style.display = 'none';
        document.getElementById('profile_image').value = ''; 
    }

    window.onload = function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('msg') === 'updated') {
            const toast = document.getElementById('success-toast');
            toast.style.display = 'block';
            requestAnimationFrame(() => { toast.classList.add('reveal'); });
            setTimeout(() => { 
                toast.classList.remove('reveal');
                setTimeout(() => { toast.style.display = 'none'; }, 500);
            }, 3000);
        }
    }
</script>
</body>
</html>