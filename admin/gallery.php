<?php
session_start();
include '../db/connection.php';
include 'functions.php'; 

// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$displayName = isset($_SESSION['name']) ? explode(' ', $_SESSION['name'])[0] : 'Admin';
$message = "";

// 2. Handle Image Upload
if (isset($_POST['upload_image'])) {
    $caption = $_POST['caption'] ?? '';
    $category = $_POST['category'] ?? 'General';
    
    $file = $_FILES['image'];
    $fileName = time() . "_" . basename($file["name"]);
    $targetDir = "../uploads/gallery/";
    $targetFilePath = $targetDir . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

    $allowTypes = array('jpg', 'png', 'jpeg', 'gif');
    if (in_array(strtolower($fileType), $allowTypes)) {
        if (move_uploaded_file($file["tmp_name"], $targetFilePath)) {
            $stmt = $conn->prepare("INSERT INTO gallery (image_path, caption, category) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $fileName, $caption, $category);
            if ($stmt->execute()) {
                $message = "✨ Image added to the collection!";
            }
        } else {
            $message = "❌ Error uploading your file.";
        }
    } else {
        $message = "❌ Only JPG, JPEG, PNG, & GIF allowed.";
    }
}

// 3. Handle Image Deletion
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    $res = $conn->query("SELECT image_path FROM gallery WHERE id = $id");
    if ($row = $res->fetch_assoc()) {
        $fullPath = "../uploads/gallery/" . $row['image_path'];
        if (file_exists($fullPath)) { unlink($fullPath); }
        $conn->query("DELETE FROM gallery WHERE id = $id");
        header("Location: gallery.php?msg=deleted");
        exit();
    }
}

// 4. Fetch All Images
$images = $conn->query("SELECT * FROM gallery ORDER BY uploaded_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery Manager | Villa Marciana</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* --- THEME SYNC --- */
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

        /* --- GALLERY STYLING --- */
        .gallery-container { max-width: 1200px; margin: 40px auto; padding: 20px; animation: fadeInUp 0.8s ease; }
        
        .upload-card {
            background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(10px);
            padding: 30px; border-radius: 30px; border: 1px solid rgba(255, 255, 255, 0.05);
            margin-bottom: 50px;
        }

        .gallery-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px;
        }

        .gallery-item {
            background: rgba(20, 20, 20, 0.4); border-radius: 24px; overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.05); transition: 0.3s ease;
        }
        .gallery-item:hover { transform: translateY(-5px); border-color: #00ff88; box-shadow: 0 10px 30px rgba(0,255,136,0.1); }

        .gallery-img-wrapper { width: 100%; height: 200px; overflow: hidden; position: relative; }
        .gallery-item img { width: 100%; height: 100%; object-fit: cover; }
        
        .category-badge {
            position: absolute; top: 15px; left: 15px; background: rgba(0,0,0,0.6);
            backdrop-filter: blur(5px); color: #00ff88; font-size: 0.6rem;
            padding: 5px 12px; border-radius: 50px; font-weight: 800; text-transform: uppercase;
        }

        .item-details { padding: 20px; text-align: left; }
        .item-caption { color: #eee; font-size: 0.9rem; margin-bottom: 15px; font-weight: 500; }

        /* Form Elements */
        .glass-input {
            background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
            color: white; padding: 12px 15px; border-radius: 12px; margin-bottom: 15px; width: 100%; outline: none;
        }
        .glass-input:focus { border-color: #00ff88; }

        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
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
                        <div style="padding: 10px 15px; font-size: 0.7rem; color: #555; text-transform: uppercase; font-weight: 800;">Admin Menu</div>
                        <a href="archive.php">📂 Archive</a>
                        <a href="logs.php">📜 Activity Logs</a>
                        <hr style="border: 0; border-top: 1px solid #222; margin: 5px 0;">
                        <a href="../logout.php" style="color: #ff4444;">🚪 Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="gallery-container">
        <header style="margin-bottom: 30px;">
            <h2 style="color: #fff; margin-bottom: 5px;">Gallery Manager</h2>
            <p style="color: #555; font-size: 0.9rem;">Curate the visual experience of Villa Marciana.</p>
        </header>

        <div class="upload-card">
            <form action="" method="POST" enctype="multipart/form-data" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div style="grid-column: span 2;">
                    <label style="color: #555; font-size: 0.7rem; text-transform: uppercase; font-weight: 800; display: block; margin-bottom: 10px;">Select Image</label>
                    <input type="file" name="image" required style="color: #888;">
                </div>
                <div>
                    <input type="text" name="caption" class="glass-input" placeholder="Enter caption...">
                </div>
                <div>
                    <select name="category" class="glass-input">
                        <option value="Exterior">Exterior View</option>
                        <option value="Rooms">Luxury Rooms</option>
                        <option value="Events">Special Events</option>
                    </select>
                </div>
                <div style="grid-column: span 2;">
                    <button type="submit" name="upload_image" class="nav-btn" style="width: 100%; padding: 15px;">Add to Gallery</button>
                    <?php if($message): ?> <p style="color: #00ff88; margin-top: 15px; font-size: 0.8rem; text-align: center;"><?= $message ?></p> <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="gallery-grid">
            <?php if ($images->num_rows > 0): ?>
                <?php while($img = $images->fetch_assoc()): ?>
                    <div class="gallery-item">
                        <div class="gallery-img-wrapper">
                            <span class="category-badge"><?= $img['category'] ?></span>
                            <img src="../uploads/gallery/<?= $img['image_path'] ?>" alt="Villa Gallery">
                        </div>
                        <div class="item-details">
                            <p class="item-caption"><?= htmlspecialchars($img['caption']) ?: 'No caption' ?></p>
                            <a href="?delete_id=<?= $img['id'] ?>" style="color: #ff4444; font-size: 0.7rem; text-decoration: none; font-weight: bold;" onclick="return confirm('Remove this image permanently?')">🗑 Remove Image</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: span 3; text-align: center; padding: 100px; color: #333;">
                    <p>No photos uploaded yet. Start by adding your first villa shot!</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function togglePremiumMenu(event) {
            event.stopPropagation();
            const menu = document.getElementById('userMenu');
            if (menu.classList.contains('active')) {
                closePremiumMenu();
            } else {
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
    </script>
</body>
</html>