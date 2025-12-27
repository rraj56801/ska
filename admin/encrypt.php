<?php
require_once __DIR__ . '/../includes/anti_inspect.php';

$hashed_password = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';

    if ($password === '') {
        $error = 'Please enter a password.';
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Password Hasher</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: #e5e7eb;
        }

        .card {
            background: #020617;
            border-radius: 12px;
            padding: 24px 28px;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.7);
            border: 1px solid #1f2937;
        }

        .card h1 {
            margin: 0 0 8px;
            font-size: 1.4rem;
            color: #f9fafb;
        }

        .card p.subtitle {
            margin: 0 0 20px;
            font-size: 0.9rem;
            color: #9ca3af;
        }

        .field-row {
            margin-bottom: 8px;
        }

        label {
            display: block;
            font-size: 0.9rem;
            margin-bottom: 6px;
            color: #e5e7eb;
        }

        .password-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        input[type="password"],
        input[type="text"] {
            flex: 1;
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid #374151;
            background: #020617;
            color: #e5e7eb;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
        }

        input:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 0 1px #38bdf8;
            background: #020617;
        }

        .toggle-visibility {
            border-radius: 999px;
            padding: 8px 14px;
            font-size: 0.8rem;
            border: 1px solid #4b5563;
            background: #111827;
            color: #e5e7eb;
            cursor: pointer;
            white-space: nowrap;
        }

        .toggle-visibility:hover {
            background: #1f2937;
        }

        .btn-row {
            margin-top: 16px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        button {
            border: none;
            border-radius: 999px;
            padding: 9px 18px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: transform 0.12s ease, box-shadow 0.12s ease, background 0.12s;
        }

        .btn-secondary {
            background: transparent;
            color: #9ca3af;
            border: 1px solid #4b5563;
        }

        .btn-secondary:hover {
            background: #111827;
            color: #e5e7eb;
        }

        .btn-primary {
            background: linear-gradient(135deg, #38bdf8, #6366f1);
            color: #0b1120;
            font-weight: 600;
            box-shadow: 0 8px 20px rgba(56, 189, 248, 0.35);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 26px rgba(56, 189, 248, 0.45);
        }

        .btn-primary:active {
            transform: translateY(0);
            box-shadow: 0 6px 16px rgba(56, 189, 248, 0.35);
        }

        .btn-copy {
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 0.8rem;
            margin-left: 8px;
            background: #111827;
            color: #e5e7eb;
            border: 1px solid #4b5563;
            cursor: pointer;
        }

        .btn-copy:hover {
            background: #1f2937;
        }

        .copy-status {
            font-size: 0.8rem;
            color: #a5b4fc;
            margin-top: 6px;
        }

        .error {
            margin-top: 10px;
            font-size: 0.85rem;
            color: #fca5a5;
        }

        .result {
            margin-top: 18px;
            padding: 12px 12px;
            border-radius: 8px;
            background: #020617;
            border: 1px solid #1f2937;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Courier New", monospace;
            font-size: 0.8rem;
            color: #e5e7eb;
            word-break: break-all;
        }

        .result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
        }

        .result-title {
            font-size: 0.85rem;
            color: #9ca3af;
        }
    </style>
</head>

<body>
    <div class="card">
        <h1>Password Hasher</h1>
        <p class="subtitle">Enter a password, reveal it if needed, hash it, and copy the hash.</p>

        <form method="post" autocomplete="off">
            <div class="field-row">
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" placeholder="Type a strong password..."
                        required>
                    <button type="button" class="toggle-visibility" id="toggle-password" aria-label="Show password"
                        aria-pressed="false">
                        Show
                    </button>
                </div>
            </div>

            <div class="btn-row">
                <button type="reset" class="btn-secondary">Clear</button>
                <button type="submit" class="btn-primary">Generate hash</button>
            </div>
        </form>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($hashed_password): ?>
            <div class="result">
                <div class="result-header">
                    <div class="result-title">Hashed password</div>
                    <button type="button" class="btn-copy" id="copy-btn"
                        data-hash="<?= htmlspecialchars($hashed_password, ENT_QUOTES, 'UTF-8') ?>">
                        Copy
                    </button>
                </div>
                <div id="hash-text"><?= htmlspecialchars($hashed_password, ENT_QUOTES, 'UTF-8') ?></div>
                <div class="copy-status" id="copy-status" style="display:none;">Copied!</div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Toggle password visibility
            const input = document.getElementById('password');
            const toggleBtn = document.getElementById('toggle-password');

            if (input && toggleBtn) {
                toggleBtn.addEventListener('click', () => {
                    const isHidden = input.type === 'password';
                    input.type = isHidden ? 'text' : 'password';
                    toggleBtn.textContent = isHidden ? 'Hide' : 'Show';
                    toggleBtn.setAttribute('aria-pressed', String(isHidden));
                    toggleBtn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
                });
            }

            // Copy hash to clipboard
            const copyBtn = document.getElementById('copy-btn');
            const statusEl = document.getElementById('copy-status');

            if (copyBtn) {
                copyBtn.addEventListener('click', async () => {
                    const hash = copyBtn.getAttribute('data-hash') || '';

                    if (!hash) return;

                    try {
                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            await navigator.clipboard.writeText(hash);
                        } else {
                            const temp = document.createElement('textarea');
                            temp.value = hash;
                            document.body.appendChild(temp);
                            temp.select();
                            document.execCommand('copy');
                            document.body.removeChild(temp);
                        }

                        if (statusEl) {
                            statusEl.style.display = 'block';
                            statusEl.textContent = 'Copied!';
                            setTimeout(() => {
                                statusEl.style.display = 'none';
                            }, 1500);
                        }
                    } catch (e) {
                        if (statusEl) {
                            statusEl.style.display = 'block';
                            statusEl.textContent = 'Failed to copy';
                            setTimeout(() => {
                                statusEl.style.display = 'none';
                            }, 1500);
                        }
                    }
                });
            }
        });
    </script>
</body>

</html>