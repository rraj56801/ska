<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/anti_inspect.php';

if (!isset($_SESSION['admin'])) {
    include(__DIR__ . '/../forbidden.php');
    exit();
}

$admin = $_SESSION['admin'];

// Session security
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} elseif (time() - $_SESSION['created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// Fetch dashboard stats
$total_students = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$today_admissions = $pdo->query("SELECT COUNT(*) FROM students WHERE DATE(admission_date) = CURDATE()")->fetchColumn();
$today_collection = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM fee_payments WHERE DATE(payment_date) = CURDATE()")->fetchColumn();
$pending_approval = $pdo->query("SELECT COUNT(*) FROM students WHERE status = 'Pending Approval'")->fetchColumn();
$total_centers = $pdo->query("SELECT COUNT(*) FROM study_centers WHERE is_active = '1'")->fetchColumn();
?>
<?php include 'header.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .dashboard-card {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            border: none;
            border-radius: 20px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--bs-primary), var(--bs-info));
        }

        .dashboard-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3) !important;
        }

        .stat-icon {
            font-size: 3rem;
            opacity: 0.9;
        }

        .welcome-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }

        .quick-links {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }
    </style>
</head>

<body>

    <div class="container-fluid mt-4 mb-5">
        <!-- Welcome Section -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="welcome-section p-5 rounded-4 shadow-lg text-center">
                    <h1 class="display-4 fw-bold text-primary mb-3">
                        <i class="bi bi-speedometer2 me-3"></i>Welcome Back, <?= htmlspecialchars($admin['name']) ?>!
                    </h1>
                    <p class="lead text-muted mb-0">Manage your institute efficiently with these quick insights</p>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card dashboard-card text-white bg-primary h-100">
                    <div class="card-body text-center p-4" style="cursor: pointer; transition: all 0.3s ease;"
                        onclick="window.location.href='students'">
                        <div class="stat-icon mb-3">
                            <i class="bi bi-people-fill" style="font-size: 2.5rem; color: #3b82f6;"></i>
                        </div>
                        <h2 class="display-4 fw-bold mb-1" style="color: #ffffff;"><?= number_format($total_students) ?>
                        </h2>
                        <p class="mb-0 fs-5" style="color: #ffffff;">Total Students</p>
                    </div>

                    <style>
                        .card-body[onclick] {
                            position: relative;
                            border-radius: 12px;
                        }

                        .card-body[onclick]:hover {
                            transform: translateY(-4px);
                            box-shadow: 0 20px 40px rgba(59, 130, 246, 0.3) !important;
                        }

                        .card-body[onclick]:hover .bi-people-fill {
                            transform: scale(1.1);
                            color: #2563eb !important;
                        }

                        .card-body[onclick]:hover h2,
                        .card-body[onclick]:hover p {
                            color: #ffffff !important;
                        }
                    </style>

                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card dashboard-card text-white bg-success h-100">
                    <div class="card-body text-center p-4">
                        <div class="stat-icon mb-3"><i class="bi bi-person-plus-fill"></i></div>
                        <h2 class="display-4 fw-bold mb-1"><?= number_format($today_admissions) ?></h2>
                        <p class="mb-0 fs-5">Today Admissions</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card dashboard-card text-white bg-warning h-100">
                    <div class="card-body text-center p-4">
                        <div class="stat-icon mb-3"><i class="bi bi-cash-stack"></i></div>
                        <h2 class="display-4 fw-bold mb-1">₹<?= number_format($today_collection, 2) ?></h2>
                        <p class="mb-0 fs-5">Today Collection</p>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card dashboard-card text-white bg-danger h-100">
                    <div class="card-body text-center p-4" style="cursor: pointer; transition: all 0.3s ease;"
                        onclick="window.location.href='pending-approval-list'">
                        <div class="stat-icon mb-3">
                            <i class="bi bi-chat-dots" style="font-size: 2.5rem; color: #dc2626;"></i>
                        </div>
                        <h2 class="display-4 fw-bold mb-1" style="color: #ffffff;">
                            <?= number_format($pending_approval) ?>
                        </h2>
                        <p class="mb-0 fs-5" style="color: #ffffff;">Pending Approvals</p>
                    </div>

                    <style>
                        .card-body[onclick] {
                            position: relative;
                            border-radius: 12px;
                        }

                        .card-body[onclick]:hover {
                            transform: translateY(-4px);
                            box-shadow: 0 20px 40px rgba(220, 38, 38, 0.3) !important;
                        }

                        .card-body[onclick]:hover .bi-chat-dots {
                            transform: scale(1.1);
                            color: #b91c1c !important;
                        }

                        .card-body[onclick]:hover h2,
                        .card-body[onclick]:hover p {
                            color: #ffffff !important;
                        }
                    </style>

                </div>
            </div>
        </div>

    </div>

    <!-- Quick Links -->
    <div class="row">
        <div class="col-12">
            <div class="quick-links p-4 rounded-4 shadow-lg"
                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h3 class="mb-4 text-center text-white">
                    <i class="bi bi-lightning-charge me-2"></i>Quick Actions
                </h3>

                <!-- First Row - 5 Buttons -->
                <div class="row g-3 mb-3 justify-content-center">
                    <div class="col-6 col-md-4 col-lg">
                        <a href="add-student" class="btn btn-success btn-lg w-100 py-3 shadow-lg hover-lift">
                            <i class="bi bi-person-plus fs-4 d-block mb-1"></i>
                            <small>Add Student</small>
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg">
                        <a href="add-fee" class="btn btn-primary btn-lg w-100 py-3 shadow-lg hover-lift">
                            <i class="bi bi-cash-coin fs-4 d-block mb-1"></i>
                            <small>Fee Payment</small>
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg">
                        <a href="add-bulk-result" class="btn btn-info btn-lg w-100 py-3 shadow-lg hover-lift">
                            <i class="bi bi-clipboard-check fs-4 d-block mb-1"></i>
                            <small>Add Results</small>
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg">
                        <a href="courses" class="btn btn-secondary btn-lg w-100 py-3 shadow-lg hover-lift">
                            <i class="bi bi-book fs-4 d-block mb-1"></i>
                            <small>Courses</small>
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg">
                        <a href="all-study-centers" class="btn btn-dark btn-lg w-100 py-3 shadow-lg hover-lift">
                            <i class="bi bi-building fs-4 d-block mb-1"></i>
                            <small>Study Centers</small>
                        </a>
                    </div>
                </div>

                <!-- Second Row - 4 or 5 Buttons (depending on Super Admin) -->
                <div class="row g-3 justify-content-center">
                    <div class="col-6 col-md-4 col-lg">
                        <a href="branch-wallet"
                            class="btn btn-warning text-dark btn-lg w-100 py-3 shadow-lg hover-lift">
                            <i class="bi bi-wallet2 fs-4 d-block mb-1"></i>
                            <small>Branch Wallet</small>
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg">
                        <a href="cashier-wallet" class="btn btn-danger btn-lg w-100 py-3 shadow-lg hover-lift">
                            <i class="bi bi-person-badge fs-4 d-block mb-1"></i>
                            <small>Cashier Wallet</small>
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg">
                        <a href="upload-gallery-photo"
                            class="btn btn-outline-light btn-lg w-100 py-3 shadow-lg hover-lift border-2">
                            <i class="bi bi-images fs-4 d-block mb-1"></i>
                            <small>Gallery</small>
                        </a>
                    </div>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin'): ?>
                        <div class="col-6 col-md-4 col-lg">
                            <a href="manage-admin" class="btn btn-light text-dark btn-lg w-100 py-3 shadow-lg hover-lift">
                                <i class="bi bi-shield-lock fs-4 d-block mb-1"></i>
                                <small>Manage Admins</small>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <style>
        .hover-lift {
            transition: all 0.3s ease;
        }

        .hover-lift:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3) !important;
        }
    </style>

    <style>
        .hover-lift {
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .hover-lift:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3) !important;
            border-color: rgba(255, 255, 255, 0.3);
        }

        .btn-outline-light {
            color: white !important;
        }

        .btn-outline-light:hover {
            background: white !important;
            color: #667eea !important;
        }

        .border-2 {
            border-width: 2px !important;
        }

        .quick-links {
            backdrop-filter: blur(10px);
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php include '../includes/../includes/footer.php'; ?>
</body>

</html>