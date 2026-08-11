<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_path = __DIR__ . '/../db/connection.php';
if (file_exists($db_path)) {
    include_once $db_path;
}

$page_title = $page_title ?? 'Villa Marciana Resort | Premium Getaway';

// --- DYNAMIC PATH FIX ---
// Automatically computes '/villa_reservation/' no matter what folder depth you are in
$app_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
// If inside a subfolder like 'admin', step up to project root
if (basename($app_path) === 'admin') {
    $app_path = dirname($app_path);
}
$base_url = rtrim($app_path, '/') . '/';

$css_file_path = __DIR__ . '/../css/style.css';
$css_version = file_exists($css_file_path) ? filemtime($css_file_path) : time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    
    <!-- Prevent Light/Dark Mode Flash -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Global Stylesheet (Dynamic Path) -->
    <link rel="stylesheet" href="<?= $base_url ?>css/style.css?v=<?= $css_version ?>">

    <!-- Tuqlas AI Chatbot Embed -->
    <script
      src="https://www.tuqlas.com/chatbot.js"
      data-key="tq_live_76df22af5bc6ea5a49ef85cf1d09713975ce0793"
      data-api="https://www.tuqlas.com"
      defer
    ></script>
</head>
<body>