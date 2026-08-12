<?php
session_start();
include '../db/connection.php';
include 'functions.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    exit("Access Denied: Admin privileges required."); 
}

// Fetch the last 100 logs using PDO
$stmt = $pdo->query("SELECT * FROM admin_logs ORDER BY created_at DESC LIMIT 100");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs | Villa Marciana</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* --- EXECUTIVE LIGHT LUXURY DESIGN SYSTEM --- */
        .glass-panel {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 32px;
            padding: 40px;
            margin-top: 20px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.03);
        }

        .log-action {
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }

        /* Redesigned Luxury-Toned Badges */
        .action-delete { background: rgba(255, 59, 48, 0.08); color: #ff3b30; border: 1px solid rgba(255, 59, 48, 0.15); }
        .action-approve { background: rgba(212, 175, 55, 0.1); color: #b8931d; border: 1px solid rgba(212, 175, 55, 0.2); }
        .action-update { background: rgba(0, 122, 255, 0.08); color: #007aff; border: 1px solid rgba(0, 122, 255, 0.15); }
        .action-cleanup { background: rgba(255, 149, 0, 0.08); color: #ff9500; border: 1px solid rgba(255, 149, 0, 0.15); }
        .action-default { background: rgba(0, 0, 0, 0.04); color: #555; border: 1px solid rgba(0, 0, 0, 0.06); }

        /* Custom Table Styling for Light Glass UI */
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }
        th {
            font-weight: 800;
            color: #b8931d;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
            padding: 16px;
            border-bottom: 2px solid rgba(0, 0, 0, 0.05);
        }
        td {
            padding: 16px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.03);
            color: #333;
            vertical-align: middle;
        }
        tr:hover td {
            background: rgba(212, 175, 55, 0.02);
        }
    </style>
</head>
<body class="light">
    <nav class="modern-nav">
        <div class="nav-container">
            <h1 class="nav-title" onclick="location.href='dashboard.php'" style="cursor:pointer; color: #d4af37; font-family: 'Cinzel', serif;">
                Villa Marciana <span style="font-size: 0.75rem; color: #b8931d; font-family: sans-serif; vertical-align: middle; margin-left: 8px; letter-spacing: 2px; font-weight: 800;">ADMIN</span>
            </h1>
            <a href="dashboard.php" class="nav-btn-alt" style="text-decoration:none">← Dashboard</a>
        </div>
    </nav>

    <main class="hero-wrapper" style="min-height:90vh; padding-top: 100px; box-sizing: border-box;">
        <div class="container wide" style="max-width: 1100px;">
            <div class="glass-panel">
                <div style="margin-bottom: 30px;">
                    <h2 style="color: #111; margin: 0; font-size: 1.8rem; font-family: 'Cinzel', serif; font-weight: 400;">System Audit Logs</h2>
                    <p style="color: #666; font-size: 0.85rem;">Track and monitor administrator activity over time</p>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Administrator</th>
                                <th>Action Type</th>
                                <th>Details & Context</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($logs)): ?>
                                <?php foreach($logs as $row): 
                                    $action = strtolower($row['action_type']);
                                    $actionClass = 'action-default';
                                    
                                    if(strpos($action, 'delete') !== false) $actionClass = 'action-delete';
                                    elseif(strpos($action, 'status') !== false || strpos($action, 'approve') !== false) $actionClass = 'action-approve';
                                    elseif(strpos($action, 'update') !== false) $actionClass = 'action-update';
                                    elseif(strpos($action, 'cleanup') !== false) $actionClass = 'action-cleanup';
                                ?>
                                <tr>
                                    <td style="color:#666; font-size:0.85rem;">
                                        <strong style="color: #333;"><?= date("M d, Y", strtotime($row['created_at'])) ?></strong><br>
                                        <span style="font-size: 0.75rem; color: #888;"><?= date("h:i A", strtotime($row['created_at'])) ?></span>
                                    </td>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:10px;">
                                            <div style="width:32px; height:32px; background: rgba(212, 175, 55, 0.15); color: #b8931d; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:0.75rem; font-weight: 800; border: 1px solid rgba(212, 175, 55, 0.3);">
                                                <?= strtoupper(substr($row['admin_name'], 0, 1)) ?>
                                            </div>
                                            <strong style="color: #111; font-size: 0.9rem;"><?= htmlspecialchars($row['admin_name']) ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="log-action <?= $actionClass ?>">
                                            <?= htmlspecialchars($row['action_type']) ?>
                                        </span>
                                    </td>
                                    <td style="color:#555; font-size:0.85rem; max-width:400px; line-height:1.5;">
                                        <?= htmlspecialchars($row['details']) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align:center; padding:50px; color:#888;">No activity logs found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>
</html>