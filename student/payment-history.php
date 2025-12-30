<?php
session_start();
include '../includes/db.php';
require_once __DIR__ . '/../includes/anti_inspect.php';

if (!isset($_SESSION['admin']) && !isset($_SESSION['student'])) {
    include(__DIR__ . '/../forbidden.php');
    exit();
}

if (!isset($_GET['reg']) || empty($_GET['reg'])) {
    die('Invalid Registration Number');
}
$reg_no = $_GET['reg'];

// ✅ Fetch student details + ALL payments
$student_stmt = $pdo->prepare("
    SELECT s.*, c.course_name, sc.center_name 
    FROM students s
    LEFT JOIN courses c ON s.course_code = c.course_code
    LEFT JOIN study_centers sc ON s.study_center_code = sc.center_code
    WHERE s.reg_no = ?
");
$student_stmt->execute([$reg_no]);
$student = $student_stmt->fetch();

if (!$student) {
    die('Student not found');
}

$payment_stmt = $pdo->prepare("
    SELECT id, reg_no, amount, payment_mode, receipt_no, 
           payment_date, added_by, created_at
    FROM fee_payments 
    WHERE reg_no = ? 
    ORDER BY payment_date DESC, created_at DESC
");
$payment_stmt->execute([$reg_no]);
$payments = $payment_stmt->fetchAll();

$total_paid = array_sum(array_column($payments, 'amount'));
$due_fees = (float) $student['total_fees'] - $total_paid;
$is_complete = $due_fees <= 0;

// ✅ Student photo path (adjust path as needed)
$student_photo = !empty($student['photo']) ? '../assets/images/students/' . htmlspecialchars($student['photo']) : '../assets/images/default.jpeg';
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fee Payment History - <?= htmlspecialchars($reg_no) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }

        .section-card {
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
            border-radius: 20px;
            margin-bottom: 30px;
            border: none;
        }

        .overview-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            height: 200px;
        }

        .fee-metric {
            height: 120px;
            border-radius: 16px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            transition: all 0.3s ease;
        }

        .fee-metric:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15) !important;
        }

        .metric-primary {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(139, 92, 246, 0.2));
            border: 2px solid rgba(99, 102, 241, 0.3);
        }

        .metric-success {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(16, 185, 129, 0.2));
            border: 2px solid rgba(34, 197, 94, 0.3);
        }

        .metric-danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(220, 38, 127, 0.2));
            border: 2px solid rgba(239, 68, 68, 0.3);
        }

        .metric-info {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(37, 99, 235, 0.2));
            border: 2px solid rgba(59, 130, 246, 0.3);
        }

        .card-header {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            font-weight: 600;
            border-radius: 20px 20px 0 0 !important;
        }

        .table-header {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .header-section {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .header-title {
            color: #1e293b !important;
            font-weight: 800;
        }

        .header-subtitle {
            color: #64748b !important;
        }

        /* ✅ STUDENT PHOTO STYLING - RIGHT SIDE POSITION */
        .student-photo {
            width: 100px;
            height: 120px;
            border-radius: 16px;
            object-fit: cover;
            border: 4px solid #fff;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
        }

        .student-photo:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>

<body>


    <div class="container mt-4 mb-5">
        <!-- 🎯 PERFECT HEADER WITH PHOTO ON RIGHT (NO BACK BUTTON) -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="header-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <!-- Left: Title + Subtitle -->
                        <div>
                            <h1 class="header-title mb-1" style="font-size: 2.5rem;">
                                <i class="bi bi-receipt me-2 text-success"></i>Fee Payments
                            </h1>
                            <h5 class="header-subtitle mb-0 fw-semibold">
                                <?= htmlspecialchars($student['student_name']) ?> • <?= htmlspecialchars($reg_no) ?>
                            </h5>
                        </div>
                        <!-- Right: STUDENT PHOTO (Replaces Back Button) -->
                        <div>
                            <img src="<?= $student_photo ?>" alt="<?= htmlspecialchars($student['student_name']) ?>"
                                class="student-photo" onerror="this.src='../assets/images/default.jpeg'">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rest remains EXACTLY the same -->
        <div class="row g-4 mb-5">
            <div class="col-lg-3 col-md-6">
                <div class="fee-metric metric-primary h-100 shadow-lg">
                    <div class="h6 fw-semibold text-primary mb-1 text-uppercase">Total Fees</div>
                    <div class="h3 fw-bold text-primary mb-2">₹<?= number_format($student['total_fees'], 2) ?></div>
                    <small class="text-primary-50">Course Amount</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="fee-metric metric-success h-100 shadow-lg">
                    <div class="h6 fw-semibold text-success mb-1 text-uppercase">Paid Amount</div>
                    <div class="h3 fw-bold text-success mb-2">₹<?= number_format($total_paid, 2) ?></div>
                    <small class="text-success-50"><?= count($payments) ?> Transactions</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="fee-metric <?= $is_complete ? 'metric-success' : 'metric-danger' ?> h-100 shadow-lg">
                    <div
                        class="h6 fw-semibold <?= $is_complete ? 'text-success' : 'text-danger' ?> mb-1 text-uppercase">
                        <?= $is_complete ? 'Balance' : 'Due' ?>
                    </div>
                    <div class="h3 fw-bold <?= $is_complete ? 'text-success' : 'text-danger' ?> mb-2">
                        ₹<?= number_format($due_fees, 2) ?>
                    </div>
                    <small class="<?= $is_complete ? 'text-success-50' : 'text-danger-50' ?>">
                        <?= $is_complete ? 'Paid Complete' : 'Remaining' ?>
                    </small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="fee-metric metric-info h-100 shadow-lg">
                    <div class="h6 fw-semibold text-info mb-1 text-uppercase">Last Payment</div>
                    <?php $last_payment = end($payments); ?>
                    <div class="h5 fw-bold text-info mb-2">
                        <?= $last_payment ? date('d-M-Y', strtotime($last_payment['payment_date'])) : 'None' ?>
                    </div>
                    <small class="text-info-50">Most Recent</small>
                </div>
            </div>
        </div>

        <!-- Table section (unchanged) -->
        <div class="card section-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-receipt-stack me-2"></i>Payment History (<?= count($payments) ?>
                    records)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-header">
                            <tr>
                                <th style="width:13%">Date</th>
                                <th style="width:14%">Receipt</th>
                                <th style="width:13%">Amount</th>
                                <th style="width:12%">Mode</th>
                                <th style="width:15%">Cashier</th>
                                <th style="width:18%">Added On</th>
                                <th style="width:15%">Action</th>
                            </tr>
                        </thead>
                        <tbody class="table-group-divider">
                            <?php if ($payments): ?>
                                <?php foreach ($payments as $p): ?>
                                    <tr class="hover-shadow">
                                        <td>
                                            <div class="fw-semibold"><?= date('d-M-Y', strtotime($p['payment_date'])) ?></div>
                                            <small class="text-muted"><?= date('h:i A', strtotime($p['created_at'])) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary fs-6 px-3 py-2 fw-semibold shadow-sm">
                                                <?= htmlspecialchars($p['receipt_no']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong
                                                class="text-success h4 mb-0 d-block">₹<?= number_format($p['amount'], 2) ?></strong>
                                        </td>
                                        <td>
                                            <?php $mode_color = $p['payment_mode'] == 'Cash' ? 'bg-success' : ($p['payment_mode'] == 'UPI' ? 'bg-info' : 'bg-warning'); ?>
                                            <span class="badge <?= $mode_color ?> px-3 py-2">
                                                <?= htmlspecialchars($p['payment_mode']) ?>
                                            </span>
                                        </td>
                                        <style>
                                            .wallet-cell {
                                                background-color: #09e40dff;
                                                transition: background-color 0.2s;
                                            }

                                            <?php if (isset($_SESSION['admin'])): ?>
                                                .wallet-cell {
                                                    cursor: pointer;
                                                }

                                                .wallet-cell:hover {
                                                    background-color: #059adeff;
                                                }

                                            <?php endif; ?>
                                        </style>

                                        <td class="wallet-cell" <?php if (isset($_SESSION['admin'])): ?>onclick="window.location.href='../admin/cashier-wallet';" <?php endif; ?>>
                                            <div class="fw-semibold"><?= htmlspecialchars($p['added_by']) ?></div>
                                        </td>
                                        <td>
                                            <?= date('M d, Y<br>h:i A', strtotime($p['created_at'])) ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-success shadow-sm disabled" disabled>
                                                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <i class="bi bi-receipt-slash display-3 text-muted mb-3"></i>
                                        <h4 class="text-muted mb-1">No Payments Found</h4>
                                        <p class="text-muted mb-0">Your payment history will appear here.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>


    <?php include '../includes/footer.php'; ?>
</body>

</html>