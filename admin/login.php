<?php
session_start();
include '../includes/db.php';
require_once __DIR__ . '/../includes/anti_inspect.php';

// HANDLE LOGIN FIRST - BEFORE ANY OUTPUT
$error = '';
if ($_POST) {
    $user = $_POST['username'];
    $pass = $_POST['password'];
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username=?");
    $stmt->execute([$user]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($pass, $admin['password'])) {
        $_SESSION['admin'] = $admin;
        header("Location: /ska/admin");
        exit();
    } else {
        $error = 'Wrong username/password!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Mahaveer CEC</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/ska-logo.png">
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .logo-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 2.5rem;
        }

        .institute-logo {
            width: 90px;
            height: 90px;
            border-radius: 20px;
            object-fit: contain;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(14px);
            padding: 10px;
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .login-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 3rem 2.5rem;
            max-width: 450px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 24px;
            padding: 3px;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.8), rgba(59, 130, 246, 0.8));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            z-index: -1;
        }

        .login-title {
            color: #fff;
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .login-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
            margin-bottom: 2.5rem;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.9) !important;
            border: 2px solid rgba(255, 255, 255, 0.3) !important;
            border-radius: 16px !important;
            color: #1e293b !important;
            padding: 18px 24px !important;
            font-size: 1.1rem;
            height: 65px;
            margin-bottom: 1.5rem !important;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .form-control::placeholder {
            color: #64748b !important;
        }

        .form-control:focus {
            background: #fff !important;
            border-color: #10b981 !important;
            box-shadow: 0 0 0 0.25rem rgba(16, 185, 129, 0.25), 0 8px 25px rgba(0, 0, 0, 0.2) !important;
            transform: translateY(-3px);
        }

        .login-btn {
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            color: #ecfdf5;
            padding: 20px 0;
            font-size: 1.25rem;
            font-weight: 700;
            border-radius: 50px;
            width: 100%;
            height: 70px;
            transition: all 0.3s ease;
            box-shadow: 0 12px 40px rgba(16, 185, 129, 0.4);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .login-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 50px rgba(16, 185, 129, 0.6);
            color: #ecfdf5;
        }

        .error-alert {
            background: rgba(239, 68, 68, 0.9) !important;
            border: none !important;
            color: #fff !important;
            border-radius: 16px !important;
            margin-bottom: 2rem;
            font-weight: 500;
            backdrop-filter: blur(12px);
        }

        .back-link {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 1.5rem;
            justify-content: center;
        }

        .back-link:hover {
            color: #fff;
            text-shadow: 0 0 12px rgba(255, 255, 255, 0.8);
        }

        @media (max-width: 576px) {
            .login-card {
                padding: 2rem 1.5rem;
                margin: 10px;
            }

            .login-title {
                font-size: 1.8rem;
            }

            .institute-logo {
                width: 70px;
                height: 70px;
            }
        }
    </style>
</head>

<body>
    <div class="login-card">
        <!-- CENTERED LOGO -->
        <div class="logo-container">
            <img src="../assets/images/ska-logo.png" alt="Mahaveer Logo" class="institute-logo">
        </div>

        <!-- TITLE -->
        <h1 class="login-title">
            <i class="bi bi-person-gear me-3"></i>
            Admin Login
        </h1>
        <p class="login-subtitle">Enter your credentials to access admin panel</p>

        <!-- ERROR MESSAGE -->
        <?php if ($error): ?>
            <div class="alert alert-danger error-alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- FORM -->
        <form method="post">
            <input type="text" name="username" class="form-control" placeholder="Username" required>
            <input type="password" name="password" class="form-control" placeholder="Password" required>
            <button type="submit" class="login-btn">Login</button>
        </form>

        <!-- BACK TO HOME -->
        <a href="../index" class="back-link">
            <i class="bi bi-house-door"></i>
            Back to Home
        </a>
    </div>
</body>

</html>