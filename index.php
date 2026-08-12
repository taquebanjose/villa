<?php
session_start();
include 'db/connection.php';

$uid = $_SESSION['user_id'] ?? null; 
$displayName = isset($_SESSION['name']) ? explode(' ', $_SESSION['name'])[0] : 'Guest';

// --- FETCH USER IMAGE ---
$user_image = null;
if ($uid) {
    /** @var PDO $pdo */
    $stmt = $pdo->prepare("SELECT image FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    $user_image = !empty($res['image']) ? 'uploads/' . $res['image'] : null;
}

// Collect all gallery items into a single array for unified lightbox support
$gallery_items = [];

// --- FETCH VILLA GALLERY IMAGES ---
$gallery_stmt = $pdo->query("SELECT * FROM gallery ORDER BY uploaded_at DESC");
$rows = $gallery_stmt->fetchAll(PDO::FETCH_ASSOC);
if ($rows) {
    foreach ($rows as $row) {
        $gallery_items[] = [
            'src'      => 'uploads/gallery/' . rawurlencode($row['image_path']), 
            'caption'  => htmlspecialchars($row['caption'] ?? '', ENT_QUOTES, 'UTF-8'),
            'category' => htmlspecialchars($row['category'] ?? '', ENT_QUOTES, 'UTF-8')
        ];
    }
}

// --- FETCH ROOM COLLECTION IMAGES ---
$room_stmt = $pdo->query("SELECT * FROM rooms_gallery ORDER BY id ASC");
$room_rows = $room_stmt->fetchAll(PDO::FETCH_ASSOC);
if ($room_rows) {
    foreach ($room_rows as $row) {
        $room_caption = $row['caption'] ?? $row['title'] ?? $row['name'] ?? '';
        
        $gallery_items[] = [
            'src'      => 'uploads/gallery/' . rawurlencode($row['image_path']),
            'caption'  => htmlspecialchars($room_caption, ENT_QUOTES, 'UTF-8'),
            'category' => 'The Stay Experience'
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Villa Marciana Resort | Premium Getaway</title>
    
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
            --card-bg: rgba(255, 255, 255, 0.65);
            --card-border: rgba(255, 255, 255, 0.5);
            --card-hover-bg: rgba(255, 255, 255, 0.95);
            --nav-bg: rgba(255, 255, 255, 0.85);
            --border-subtle: rgba(0, 0, 0, 0.06);
            --shadow-color: rgba(0, 0, 0, 0.08);
            --gold-accent: #d4af37;
            --gold-text: #b8931d;
        }

        [data-theme="dark"] {
            --bg-color: #0f1218;
            --text-main: #f0f0f0;
            --text-sub: #b0b5c0;
            --card-bg: rgba(22, 27, 36, 0.65);
            --card-border: rgba(255, 255, 255, 0.08);
            --card-hover-bg: rgba(28, 35, 48, 0.95);
            --nav-bg: rgba(15, 18, 24, 0.85);
            --border-subtle: rgba(255, 255, 255, 0.1);
            --shadow-color: rgba(0, 0, 0, 0.4);
            --gold-accent: #d4af37;
            --gold-text: #e6c65c;
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

        /* --- PREMIUM CONFIGURATIONS --- */
        .hero-title {
            font-family: 'Cinzel', serif !important;
            font-size: 4.5rem !important;
            margin-bottom: 15px !important;
            font-weight: 400 !important;
            letter-spacing: 1px !important;
            color: var(--text-main) !important;
        }

        .hero-subtitle {
            font-size: 1.4rem;
            color: var(--text-sub);
            margin-bottom: 40px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            width: 100%;
            max-width: 1100px;
            margin: 60px auto;
            padding: 0 20px;
            box-sizing: border-box;
        }

        .feature-card {
            background: var(--card-bg);
            padding: 40px 30px;
            border-radius: 25px;
            text-align: center;
            border: 1px solid var(--card-border);
            transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
            backdrop-filter: blur(25px) saturate(180%);
            -webkit-backdrop-filter: blur(25px) saturate(180%);
            box-shadow: 0 15px 35px var(--shadow-color);
        }

        .feature-card:hover {
            transform: translateY(-12px) scale(1.02);
            background: var(--card-hover-bg);
            border-color: var(--gold-accent);
            box-shadow: 0 20px 40px rgba(212, 175, 55, 0.15);
        }

        .feature-card i { 
            font-size: 3rem; 
            margin-bottom: 20px; 
            display: block; 
            transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.15));
        }

        .feature-card:hover i {
            transform: scale(1.2) rotate(4deg);
        }

        /* --- GALLERY SECTION PACKING --- */
        .gallery-section {
            padding: 60px 0;
            max-width: 100%;
            overflow: hidden;
        }

        .gallery-header { 
            margin-bottom: 30px; 
            text-align: center; 
        }
        
        .gallery-badge { 
            color: var(--gold-text); 
            font-weight: 800; 
            text-transform: uppercase; 
            font-size: 0.75rem; 
            letter-spacing: 2px; 
        }

        .photo-scroller {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            padding: 20px 40px;
            scroll-behavior: smooth;
            scrollbar-width: none;
        }
        .photo-scroller::-webkit-scrollbar { display: none; }

        .photo-card {
            flex: 0 0 auto;
            width: 350px;
            height: 250px;
            position: relative;
            border-radius: 25px;
            overflow: hidden;
            border: 1px solid var(--border-subtle);
            box-shadow: 0 20px 45px var(--shadow-color);
            transition: 0.4s ease;
            cursor: pointer;
        }

        .photo-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .photo-card:hover img { transform: scale(1.06); }

        /* --- LIGHTBOX OVERLAY --- */
        #lightbox {
            display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(25px); z-index: 9999; align-items: center; justify-content: center;
            opacity: 0; transition: opacity 0.4s ease;
        }
        #lightbox.active { display: flex; opacity: 1; }
        .lightbox-content { position: relative; display: flex; flex-direction: column; align-items: center; width: 100%; height: 100%; justify-content: center; }
        
        #lightbox-img { 
            max-width: 85%; max-height: 75%; border-radius: 20px; 
            box-shadow: 0 35px 80px rgba(0,0,0,0.5); transform: scale(0.95); 
            border: 1px solid var(--border-subtle);
            transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.3s ease; 
        }
        #lightbox.active #lightbox-img { transform: scale(1); }
        
        .nav-arrow {
            position: absolute; top: 50%; transform: translateY(-50%);
            background: rgba(255,255,255,0.1); border: 1px solid var(--border-subtle);
            color: #fff; width: 60px; height: 60px; border-radius: 50%;
            font-size: 1.5rem; cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); z-index: 10001;
        }
        .nav-arrow:hover { background: var(--gold-accent); color: #ffffff; border-color: var(--gold-accent); box-shadow: 0 0 20px rgba(212, 175, 55, 0.4); }
        .prev-arrow { left: 30px; }
        .next-arrow { right: 30px; }
        #lightbox-close { position: absolute; top: 30px; right: 30px; font-size: 2.5rem; color: #fff; cursor: pointer; z-index: 10002; opacity: 0.7; transition: 0.3s; }
        #lightbox-close:hover { opacity: 1; color: #ff3b30; }
        #lightbox-caption-box { position: absolute; bottom: 50px; text-align: center; color: #fff; }

        .account-dropdown { position: relative; }
        .account-toggle {
            cursor: pointer; transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); border-radius: 50px;
            background: var(--card-bg); border: 1px solid var(--border-subtle);
            color: var(--text-main); padding: 6px 20px 6px 8px; display: flex; align-items: center; gap: 10px;
        }
        .nav-avatar { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 1px solid rgba(212, 175, 55, 0.4); }
        .account-toggle:hover { transform: translateY(-2px); background: rgba(212, 175, 55, 0.1); border-color: var(--gold-accent); }
        
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
    </style>
</head>
<body>

    <nav class="modern-nav">
        <div class="nav-container">
            <h1 class="nav-title" onclick="location.href='index.php'">Villa Marciana</h1>
            <div class="nav-buttons">
                <!-- Theme Toggle Button -->
                <button class="theme-toggle-btn" id="themeToggle" onclick="toggleTheme()">
                    <span id="themeIcon">🌙</span> <span id="themeText">Dark</span>
                </button>

                <?php if ($uid): ?>
                    <a href="reserve.php" class="nav-btn">✨ Book Now</a>
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
                            <a href="myreservations.php">📅 My Reservations</a>
                            <a href="profile.php">⚙️ Account Settings</a>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                <a href="admin/dashboard.php" style="color: #ffa000; font-weight: bold;">🛠️ Admin Dashboard</a>
                            <?php endif; ?>
                            <hr style="border: 0; border-top: 1px solid var(--border-subtle); margin: 5px 0;">
                            <a href="logout.php" style="color: #ff3b30;">🚪 Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="nav-btn-alt">Login</a>
                    <a href="register.php" class="nav-btn">Join Now</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="hero-wrapper" style="flex-direction: column; padding-top: 140px;">
        <div class="hero-content" 
             style="
                max-width: 900px; 
                background: var(--card-bg); 
                border: 1px solid var(--card-border); 
                backdrop-filter: blur(25px) saturate(180%); 
                -webkit-backdrop-filter: blur(25px) saturate(180%);
                border-radius: 28px;
                padding: 50px;
                transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
                margin: 0 auto;
                box-shadow: 0 15px 35px var(--shadow-color);
             ">
            <h1 class="hero-title" style="font-size: 3.8rem !important;">Dive into Comfort</h1>
            <p class="hero-subtitle" style="font-size: 1.1rem; margin-bottom: 35px;">
                Relax, recharge, and make unforgettable memories at your home away from home.
            </p>
            <div style="display: flex; gap: 15px; justify-content: center;">
                <?php if (!$uid): ?>
                    <a href="register.php" class="nav-btn" style="padding: 16px 40px; font-size: 1rem;">Start Your Journey</a>
                <?php else: ?>
                    <a href="reserve.php" class="nav-btn" style="padding: 16px 40px; font-size: 1rem;">Check Availability</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <i>🌊</i>
                <h3 style="color: var(--gold-text); margin-bottom: 12px; font-weight: 600;">Crystal Waters</h3>
                <p style="font-size: 0.85rem; color: var(--text-sub); opacity: 0.9;">Dip into our pristine, temperature-controlled pools.</p>
            </div>
            <div class="feature-card">
                <i>🌙</i>
                <h3 style="color: var(--gold-text); margin-bottom: 12px; font-weight: 600;">Starry Swims</h3>
                <p style="font-size: 0.85rem; color: var(--text-sub); opacity: 0.9;">Enjoy exclusive night sessions under the moonlight.</p>
            </div>
            <div class="feature-card">
                <i>🍹</i>
                <h3 style="color: var(--gold-text); margin-bottom: 12px; font-weight: 600;">Cozy Retreat</h3>
                <p style="font-size: 0.85rem; color: var(--text-sub); opacity: 0.9;">Recharge in quiet comfort after a day of fun and swimming.</p>
            </div>
        </div>

        <section class="gallery-section">
            <div class="gallery-header">
                <span class="gallery-badge">The Visual Experience</span>
                <h2 style="color: var(--text-main); font-family: 'Cinzel', serif; font-size: 2.2rem; margin-top: 10px; font-weight: 400; letter-spacing: 1px;">The Villa Collection</h2>
            </div>
            
            <div class="photo-scroller">
                <?php if (!empty($gallery_items)): ?>
                    <?php 
                    $global_counter = 0;
                    foreach ($gallery_items as $photo): 
                        if ($photo['category'] !== 'The Stay Experience'):
                    ?>
                        <div class="photo-card" onclick="openLightbox(<?= (int)$global_counter ?>)">
                            <img src="<?= htmlspecialchars($photo['src'], ENT_QUOTES, 'UTF-8') ?>" alt="Villa Gallery">
                        </div>
                    <?php 
                        endif;
                        $global_counter++;
                    endforeach; 
                    ?>
                <?php else: ?>
                    <p style="color: var(--text-sub); text-align: center; width: 100%; font-size: 0.9rem;">New photos coming soon!</p>
                <?php endif; ?>
            </div>
        </section>

        <!-- The Room Collection Showcase -->
        <section class="gallery-section" style="margin-top: 40px;">
            <div class="gallery-header">
                <span class="gallery-badge">The Stay Experience</span>
                <h2 style="color: var(--text-main); font-family: 'Cinzel', serif; font-size: 2.2rem; margin-top: 10px; font-weight: 400; letter-spacing: 1px;">The Room Collection</h2>
            </div>

            <div class="photo-scroller">
                <?php 
                $has_rooms = false;
                if (!empty($gallery_items)):
                    $global_counter = 0;
                    foreach ($gallery_items as $photo):
                        if ($photo['category'] === 'The Stay Experience'):
                            $has_rooms = true;
                ?>
                            <div class="photo-card" onclick="openLightbox(<?= (int)$global_counter ?>)">
                                <img src="<?= htmlspecialchars($photo['src'], ENT_QUOTES, 'UTF-8') ?>" alt="Villa Room">
                            </div>
                <?php 
                        endif;
                        $global_counter++;
                    endforeach;
                endif;
                
                if (!$has_rooms): 
                ?>
                    <p style="color: var(--text-sub); text-align: center; width: 100%; font-size: 0.9rem;">New rooms coming soon!</p>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <div id="lightbox">
        <span id="lightbox-close" onclick="closeLightbox()">&times;</span>
        <div class="nav-arrow prev-arrow" onclick="changeSlide(-1)">&#10094;</div>
        <div class="lightbox-content">
            <img id="lightbox-img" src="" alt="Gallery Image Large">
            <div id="lightbox-caption-box">
                <div id="lightbox-cat" style="color: var(--gold-accent); font-size:0.8rem; font-weight:800; text-transform:uppercase; letter-spacing:2px;"></div>
                <h3 id="lightbox-cap" style="margin-top:10px; font-family: 'Montserrat', sans-serif;"></h3>
            </div>
        </div>
        <div class="nav-arrow next-arrow" onclick="changeSlide(1)">&#10095;</div>
    </div>

    <footer style="padding: 40px; text-align: center; color: var(--text-sub); font-size: 0.8rem; border-top: 1px solid var(--border-subtle); width: 100%; box-sizing: border-box;">
        <p>&copy; 2026 Villa Marciana Resort. Designed for Excellence.</p>
    </footer>

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

        // --- GALLERY SLIDER INTERACTION LOGIC ---
        const galleryItems = <?= json_encode($gallery_items, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        let currentIndex = 0;

        function openLightbox(index) {
            currentIndex = index;
            updateLightboxContent();
            const lb = document.getElementById('lightbox');
            lb.style.display = 'flex';
            setTimeout(() => lb.classList.add('active'), 10);
            document.body.style.overflow = 'hidden'; 
        }

        function closeLightbox() {
            const lb = document.getElementById('lightbox');
            lb.classList.remove('active');
            setTimeout(() => { lb.style.display = 'none'; }, 400);
            document.body.style.overflow = 'auto'; 
        }

        function changeSlide(direction) {
            if (!galleryItems.length) return;
            currentIndex += direction;
            if (currentIndex >= galleryItems.length) currentIndex = 0;
            if (currentIndex < 0) currentIndex = galleryItems.length - 1;
            
            const img = document.getElementById('lightbox-img');
            img.style.opacity = '0';
            setTimeout(() => {
                updateLightboxContent();
                img.style.opacity = '1';
            }, 200);
        }

        function updateLightboxContent() {
            if (!galleryItems.length) return;
            const item = galleryItems[currentIndex];
            document.getElementById('lightbox-img').src = item.src;
            document.getElementById('lightbox-cap').innerText = item.caption;
            document.getElementById('lightbox-cat').innerText = item.category;
        }

        document.addEventListener('keydown', (e) => {
            if (!document.getElementById('lightbox').classList.contains('active')) return;
            if (e.key === "ArrowRight") changeSlide(1);
            if (e.key === "ArrowLeft") changeSlide(-1);
            if (e.key === "Escape") closeLightbox();
        });

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
            if (event.target.id === 'lightbox') { closeLightbox(); } 
        }
    </script>
</body>
</html>