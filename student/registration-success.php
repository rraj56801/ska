<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Get credentials from URL parameters
$reg_no = $_GET['reg_no'] ?? '';
$mobile = $_GET['mobile'] ?? '';
$student_name = $_GET['name'] ?? '';

if (empty($reg_no) || empty($mobile)) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful | Mahaveer CEC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Pacifico&display=swap"
        rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .success-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 2.5rem;
            max-width: 900px;
            width: 95%;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2rem;
        }

        .success-card::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 24px;
            padding: 3px;
            background: linear-gradient(135deg, #10b981, #059669, #10b981);
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            z-index: -1;
        }

        /* YOUR EXACT LOGO STRUCTURE */
        .logo-institute {
            display: inline-flex;
            align-items: center;
            gap: 18px;
            margin-bottom: 0;
            background: rgba(15, 23, 42, 0.35);
            backdrop-filter: blur(14px);
            padding: 12px 26px;
            border-radius: 999px;
            box-shadow: 0 14px 45px rgba(15, 23, 42, 0.7);
        }

        .institute-logo {
            width: 82px;
            height: 82px;
            border-radius: 18px;
            object-fit: contain;
            background: #ffffff;
            padding: 6px;
        }

        .institute-name {
            font-family: 'Pacifico', cursive;
            font-size: 1.8rem;
            letter-spacing: 0.08em;
            color: #f9fafb;
            text-shadow: 0 0 6px rgba(15, 23, 42, 0.9), 0 0 18px rgba(167, 139, 250, 0.9);
        }

        .header-section {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2rem;
            width: 100%;
            flex-wrap: wrap;
        }

        .check-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #ecfdf5, #d1fae5);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.4);
            flex-shrink: 0;
        }

        .check-icon i {
            font-size: 3.2rem;
            color: #10b981;
        }

        .credentials-row {
            display: flex;
            gap: 2rem;
            width: 100%;
            justify-content: center;
        }

        .credential-box {
            flex: 1;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 2rem 1.5rem;
            backdrop-filter: blur(12px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            position: relative;
            text-align: center;
            min-height: 140px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .copy-btn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            border-radius: 50%;
            width: 42px;
            height: 42px;
            color: #fff;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .copy-btn:hover {
            background: rgba(16, 185, 129, 0.8);
            transform: scale(1.1);
        }

        .copy-btn.copied {
            background: #10b981;
            animation: pulse 0.6s ease-out;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.2);
            }
        }

        .copy-feedback {
            position: absolute;
            bottom: -25px;
            left: 50%;
            transform: translateX(-50%);
            background: #10b981;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            opacity: 0;
            transition: all 0.3s ease;
        }

        .copy-feedback.show {
            opacity: 1;
            bottom: -30px;
        }

        .credential-label {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .credential-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: 1px;
        }

        .reg-no-value {
            font-family: 'Courier New', monospace;
            letter-spacing: 5px;
        }

        .success-title {
            color: #fff;
            font-size: 2rem;
            font-weight: 700;
            text-align: center;
            margin: 0 0 0.5rem 0;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .success-subtitle {
            color: rgba(255, 255, 255, 0.95);
            font-size: 1.1rem;
            text-align: center;
            margin: 0;
            line-height: 1.5;
        }

        .btn-login {
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            color: #ecfdf5;
            padding: 16px 50px;
            font-size: 1.15rem;
            font-weight: 600;
            border-radius: 50px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.4);
        }

        .btn-login:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(16, 185, 129, 0.6);
            color: #ecfdf5;
        }

        .btn-home {
            border: 2px solid rgba(255, 255, 255, 0.5);
            color: #fff;
            padding: 14px 40px;
            font-size: 1.1rem;
            font-weight: 500;
            border-radius: 50px;
            text-decoration: none;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .btn-home:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            color: #fff;
        }

        @media (max-width: 768px) {
            .credentials-row {
                flex-direction: column;
            }

            .success-card {
                width: 95%;
                padding: 2rem 1.5rem;
            }

            .header-section {
                gap: 1rem;
            }

            .success-title {
                font-size: 1.7rem;
            }

            .logo-institute {
                padding: 10px 20px;
                gap: 12px;
            }

            .institute-logo {
                width: 64px;
                height: 64px;
            }

            .institute-name {
                font-size: 1.4rem;
            }
        }
    </style>
</head>

<body>
    <div class="success-card">
        <!-- YOUR EXACT LOGO STRUCTURE + CHECKMARK -->
        <div class="header-section">
            <div class="logo-institute">
                <img src="../assets/images/ska-logo.png" alt="Sri Krishna Academy Logo"
                    class="institute-logo">
                <div class="institute-name">Sri Krishna Academy</div>
            </div>
            <div class="check-icon">
                <i class="bi bi-check-circle-fill"></i>
            </div>
        </div>

        <!-- Welcome Message -->
        <div style="text-align: center; flex-grow: 0;">
            <h1 class="success-title">Registration Successful!</h1>
            <p class="success-subtitle">
                Welcome <strong><?= htmlspecialchars($student_name) ?></strong>!<br>
                Your form has been submitted successfully.
            </p>
        </div>

        <!-- TWO-COLUMN CREDENTIALS -->
        <div class="credentials-row">
            <!-- REGISTRATION NUMBER -->
            <div class="credential-box" style="position: relative;">
                <button class="copy-btn" onclick="copyToClipboard('regNo')" title="Copy Registration Number">
                    <i class="bi bi-copy"></i>
                </button>
                <div class="credential-label">
                    <i class="bi bi-card-text"></i> Registration Number
                </div>
                <div class="credential-value reg-no-value" id="regNo"><?= htmlspecialchars($reg_no) ?></div>
                <div class="copy-feedback" id="regFeedback">Copied!</div>
            </div>

            <!-- MOBILE NUMBER -->
            <div class="credential-box" style="position: relative;">
                <button class="copy-btn" onclick="copyToClipboard('mobileNo')" title="Copy Mobile Number">
                    <i class="bi bi-copy"></i>
                </button>
                <div class="credential-label">
                    <i class="bi bi-telephone-fill"></i> Mobile Number
                </div>
                <div class="credential-value" id="mobileNo"><?= htmlspecialchars($mobile) ?></div>
                <div class="copy-feedback" id="mobileFeedback">Copied!</div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div style="display: flex; flex-wrap: wrap; gap: 1rem; justify-content: center;">
            <a href="../student-login.php" class="btn-login">
                <i class="bi bi-box-arrow-in-right"></i>
                Login to Student Portal
            </a>
            <a href="../index" class="btn-home">
                <i class="bi bi-house-door"></i> Back to Home
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const btn = element.closest('.credential-box').querySelector('.copy-btn');
            const feedback = element.closest('.credential-box').querySelector('.copy-feedback');

            navigator.clipboard.writeText(element.textContent).then(() => {
                btn.innerHTML = '<i class="bi bi-check-lg"></i>';
                btn.classList.add('copied');
                feedback.classList.add('show');
                setTimeout(() => {
                    btn.innerHTML = '<i class="bi bi-copy"></i>';
                    btn.classList.remove('copied');
                    feedback.classList.remove('show');
                }, 2000);
            }).catch(() => {
                alert('Copy failed. Please select and copy manually.');
            });
        }
    </script>
</body>

</html>