<?php
header("Content-Type: text/html; charset=utf-8");
include 'includes/db.php';

$stmt = $pdo->query("SELECT title, message FROM notifications WHERE is_enabled = 1 ORDER BY created_at DESC");
$notifications = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Ticker</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            overflow: hidden;
            font-family: Arial, sans-serif;
            background-color: #d32f2f;
            color: white;
            font-size: 16px;
            white-space: nowrap;
        }
        .ticker-container {
            display: inline-block;
            animation: scroll-left 20s linear infinite;
        }
        .notification {
            display: inline-block;
            padding: 0 40px;
        }
        @keyframes scroll-left {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }
    </style>
</head>
<body>
    <div class="ticker-container">
        <?php foreach ($notifications as $n): ?>
            <span class="notification">
                <?= htmlspecialchars($n['title']) ?> – <?= htmlspecialchars($n['message']) ?>
            </span>
        <?php endforeach; ?>
    </div>
</body>
</html>
