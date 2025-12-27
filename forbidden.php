<?php
require_once __DIR__ . '/includes/anti_inspect.php';

http_response_code(403);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Access Denied</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at top, #1f2937, #020617 60%);
            color: #e5e7eb;
        }

        .wrap {
            text-align: center;
            padding: 32px 28px;
            max-width: 480px;
            width: 100%;
            background: rgba(15, 23, 42, 0.9);
            border-radius: 18px;
            border: 1px solid #1f2937;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.7);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.75rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            background: rgba(248, 113, 113, 0.1);
            color: #fecaca;
            border: 1px solid rgba(248, 113, 113, 0.4);
            margin-bottom: 16px;
        }

        .badge-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #f97373;
            box-shadow: 0 0 8px rgba(248, 113, 113, 0.9);
        }

        h1 {
            margin: 0 0 10px;
            font-size: 1.6rem;
            color: #f9fafb;
        }

        p {
            margin: 4px 0;
            font-size: 0.95rem;
            color: #9ca3af;
        }

        .code {
            margin-top: 14px;
            font-size: 0.8rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: #6b7280;
        }

        .actions {
            margin-top: 22px;
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            border-radius: 999px;
            padding: 9px 18px;
            font-size: 0.85rem;
            border: none;
            cursor: pointer;
            transition: transform 0.12s ease, box-shadow 0.12s ease, background 0.12s;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #38bdf8, #6366f1);
            color: #020617;
            font-weight: 600;
            box-shadow: 0 10px 26px rgba(56, 189, 248, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 34px rgba(56, 189, 248, 0.5);
        }

        .btn-secondary {
            background: transparent;
            color: #e5e7eb;
            border: 1px solid #4b5563;
        }

        .btn-secondary:hover {
            background: #111827;
        }

        .hint {
            margin-top: 18px;
            font-size: 0.8rem;
            color: #6b7280;
        }
    </style>
</head>

<body>
    <div class="wrap">
        <div class="badge">
            <span class="badge-dot"></span>
            Restricted Area
        </div>
        <h1>You are not authorized</h1>
        <p>This page is not accessible directly.</p>
        <p>Please use the main system interface to continue.</p>

        <div class="code">Error code: 403 – Forbidden</div>

        <div class="actions">
            <a href="/ska" class="btn btn-primary">Go to dashboard</a>
            <button class="btn btn-secondary" onclick="history.back()">Go back</button>
        </div>

        <div class="hint">
            If you believe this is a mistake, contact our system administrator.
        </div>
    </div>
</body>

</html>