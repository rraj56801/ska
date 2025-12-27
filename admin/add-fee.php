<?php
session_start();
include '../includes/db.php';
require_once __DIR__ . '/../includes/anti_inspect.php';

if (!isset($_SESSION['admin'])) {
    include(__DIR__ . '/../forbidden.php');
    exit();
}

$reg_no = $_GET['reg'] ?? ($_POST['reg_no'] ?? '');
$student = null;
$paid_fees = 0;
$due_fees = 0;

$lookup_message = '';
$lookup_message_class = '';
$payment_message = '';
$payment_message_class = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'lookup') {
        $reg_no = trim($_POST['reg_no'] ?? '');

        if (empty($reg_no)) {
            $lookup_message = 'Registration number is required.';
            $lookup_message_class = 'danger';
        } else {
            $stmt = $pdo->prepare("
                SELECT s.*, c.course_name, c.duration, sc.center_name 
                FROM students s
                LEFT JOIN courses c ON s.course_code = c.course_code
                LEFT JOIN study_centers sc ON s.study_center_code = sc.center_code
                WHERE s.reg_no = ?
            ");
            $stmt->execute([$reg_no]);
            $student = $stmt->fetch();

            if (!$student) {
                $lookup_message = 'Student not found with this registration number.';
                $lookup_message_class = 'danger';
            } else {
                $paid_stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM fee_payments WHERE reg_no = ?");
                $paid_stmt->execute([$reg_no]);
                $paid_fees = (float) $paid_stmt->fetchColumn();
                $due_fees = (float) $student['total_fees'] - $paid_fees;
            }
        }

    } elseif ($action === 'payment') {
        // ✅ FIXED: Always re-fetch student for payment
        $reg_no = trim($_POST['reg_no'] ?? '');

        if (empty($reg_no)) {
            $payment_message = 'Registration number is missing.';
            $payment_message_class = 'danger';
        } else {
            // Fetch student data first
            $stmt = $pdo->prepare("
                SELECT s.*, c.course_name, c.duration, sc.center_name 
                FROM students s
                LEFT JOIN courses c ON s.course_code = c.course_code
                LEFT JOIN study_centers sc ON s.study_center_code = sc.center_code
                WHERE s.reg_no = ?
            ");
            $stmt->execute([$reg_no]);
            $student = $stmt->fetch();

            if (!$student) {
                $payment_message = 'Student not found. Please lookup first.';
                $payment_message_class = 'danger';
            } else {
                // Calculate current fees
                $paid_stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM fee_payments WHERE reg_no = ?");
                $paid_stmt->execute([$reg_no]);
                $paid_fees = (float) $paid_stmt->fetchColumn();
                $due_fees = (float) $student['total_fees'] - $paid_fees;

                // Payment validation
                $amount = (float) ($_POST['amount'] ?? 0);
                $mode = trim($_POST['mode'] ?? 'Cash');
                $cashier_name = trim($_POST['cashier_name'] ?? '');
                $receipt_no = trim($_POST['receipt_no'] ?? '');

                // ✅ FIXED: Generate receipt_no if empty
                if (empty($receipt_no)) {
                    $receipt_no = 'R' . date('ymd') . rand(100, 999);
                }

                if ($amount <= 0) {
                    $payment_message = 'Amount must be greater than zero.';
                    $payment_message_class = 'danger';
                } elseif ($amount > $due_fees) {
                    $payment_message = 'Amount exceeds due fees. Maximum: ₹' . number_format($due_fees, 2);
                    $payment_message_class = 'danger';
                } elseif (empty($cashier_name)) {
                    $payment_message = 'Cashier name is required.';
                    $payment_message_class = 'danger';
                } else {
                    // ✅ INSERT - Now guaranteed to have $student data
                    $insert_stmt = $pdo->prepare("
                        INSERT INTO fee_payments (
                            reg_no, amount, payment_mode, receipt_no,payment_date,added_by,created_at
                        ) VALUES (?, ?, ?, ?, CURDATE(), ?, NOW())
                    ");
                    $success = $insert_stmt->execute([
                        $reg_no,
                        $amount,
                        $mode,
                        $receipt_no,
                        $cashier_name
                    ]);

                    if ($success && $insert_stmt->rowCount() > 0) {
                        $payment_message = "Payment added successfully! Receipt: <strong>$receipt_no</strong> (₹" . number_format($amount, 2) . ")";
                        $payment_message_class = 'success';

                        // Refresh fee calculations
                        $paid_stmt->execute([$reg_no]);
                        $paid_fees = (float) $paid_stmt->fetchColumn();
                        $due_fees = (float) $student['total_fees'] - $paid_fees;
                    } else {
                        $error = $insert_stmt->errorInfo();
                        $payment_message = 'Failed to add payment. Error: ' . ($error[2] ?? 'Unknown');
                        $payment_message_class = 'danger';
                    }
                }
            }
        }
    }
} elseif (!empty($reg_no)) {
    $stmt = $pdo->prepare("
        SELECT s.*, c.course_name, c.duration, sc.center_name 
        FROM students s
        LEFT JOIN courses c ON s.course_code = c.course_code
        LEFT JOIN study_centers sc ON s.study_center_code = sc.center_code
        WHERE s.reg_no = ?
    ");
    $stmt->execute([$reg_no]);
    $student = $stmt->fetch();

    if ($student) {
        $paid_stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM fee_payments WHERE reg_no = ?");
        $paid_stmt->execute([$reg_no]);
        $paid_fees = (float) $paid_stmt->fetchColumn();
        $due_fees = (float) $student['total_fees'] - $paid_fees;
    }
}
?>

<?php include 'header.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add Fee Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }

        .section-card {
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
            border-radius: 16px;
            margin-bottom: 30px;
            border: none;
        }

        .card-header {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            font-weight: 600;
        }

        .fee-summary {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            border-radius: 16px;
        }

        .fee-box {
            min-height: 140px;
            transition: all 0.3s ease;
        }

        .fee-box:hover {
            transform: translateY(-2px);
        }

        .form-row {
            min-height: 90px;
        }
    </style>
</head>

<body>

    <div class="container mt-4 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-success mb-1"><i class="bi bi-cash-coin me-2"></i>Add Fee Payment</h2>
            <?php if (isset($_SESSION['admin']) && $student): ?>
                <a href="students" class="btn btn-outline-secondary"><i class="bi bi-people me-1"></i>Students</a>
            <?php else: ?>
                <a href="javascript:history.back()" class="btn btn-outline-secondary"><i
                        class="bi bi-arrow-left me-1"></i>Back</a>
            <?php endif; ?>
        </div>

        <?php if (!$student): ?>
            <!-- STEP 1: Find Student -->
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="card section-card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-search me-2"></i>Find Student</h5>
                        </div>
                        <div class="card-body p-5">
                            <?php if ($lookup_message): ?>
                                <div class="alert alert-<?= $lookup_message_class ?> alert-dismissible fade show mb-4">
                                    <?= htmlspecialchars($lookup_message) ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST">
                                <input type="hidden" name="action" value="lookup">
                                <div class="form-floating mb-4">
                                    <input type="text" class="form-control form-control-lg" id="reg_no" name="reg_no"
                                        value="<?= htmlspecialchars($_POST['reg_no'] ?? '') ?>" required>
                                    <label for="reg_no"><i class="bi bi-person-badge me-1"></i>Registration Number</label>
                                </div>
                                <button type="submit" class="btn btn-success btn-lg w-100 shadow-lg py-3">
                                    <i class="bi bi-search me-2"></i>Find Student
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- STEP 2: Fee Summary + Payment Form -->
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <!-- Fee Summary -->
                    <div class="card section-card fee-summary mb-4">
                        <div class="card-body p-4">
                            <div class="row g-3 h-100 align-items-stretch">
                                <div class="col-md-3">
                                    <div class="fee-box h-100 d-flex flex-column justify-content-center p-3 border rounded-3 shadow-sm"
                                        style="background:rgba(96,165,250,0.15);">
                                        <div class="h6 text-info mb-2 fw-semibold text-uppercase small">Student</div>
                                        <div class="h5 fw-semibold text-dark mb-1">
                                            <?= htmlspecialchars(substr($student['student_name'], 0, 18)) ?>
                                        </div>
                                        <small class="text-muted"><?= $student['center_name'] ?? 'Main' ?></small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="fee-box h-100 d-flex flex-column justify-content-center p-3 border rounded-3 shadow-sm"
                                        style="background:rgba(59,130,246,0.15);">
                                        <div class="h6 text-primary mb-2 fw-semibold text-uppercase small">Total Fee</div>
                                        <div class="h3 fw-bold text-primary mb-1">
                                            ₹<?= number_format($student['total_fees'], 2) ?></div>
                                        <small class="text-muted">Course Total</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="fee-box h-100 d-flex flex-column justify-content-center p-3 border rounded-3 shadow-sm"
                                        style="background:rgba(34,197,94,0.15);">
                                        <div class="h6 text-success mb-2 fw-semibold text-uppercase small">Paid</div>
                                        <div class="h3 fw-bold text-success mb-1">₹<?= number_format($paid_fees, 2) ?></div>
                                        <small class="text-muted">So Far</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="fee-box h-100 d-flex flex-column justify-content-center p-3 border rounded-3 shadow-sm"
                                        style="<?= $due_fees > 0 ? 'background:rgba(239,68,68,0.15)' : 'background:rgba(34,197,94,0.15)' ?>">
                                        <div
                                            class="h6 <?= $due_fees > 0 ? 'text-danger' : 'text-success' ?> mb-2 fw-semibold text-uppercase small">
                                            <?= $due_fees > 0 ? 'Due' : 'Paid' ?>
                                        </div>
                                        <div class="h3 fw-bold <?= $due_fees > 0 ? 'text-danger' : 'text-success' ?> mb-1">
                                            ₹<?= number_format($due_fees, 2) ?>
                                        </div>
                                        <small class="text-muted"><?= $due_fees > 0 ? 'Balance' : 'Complete' ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Form -->
                    <?php if ($payment_message): ?>
                        <div class="alert alert-<?= $payment_message_class ?> alert-dismissible fade show mb-4">
                            <i
                                class="bi bi-<?= $payment_message_class == 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                            <?= $payment_message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card section-card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-credit-card me-2"></i>Add New Payment</h5>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST">
                                <input type="hidden" name="action" value="payment">
                                <input type="hidden" name="reg_no" value="<?= htmlspecialchars($reg_no) ?>">

                                <div class="row g-3 align-items-end" style="min-height:85px;">
                                    <div class="col-md-3">
                                        <div class="form-floating">
                                            <input type="number" class="form-control form-control-lg" id="amount"
                                                name="amount" step="0.01" min="1" max="<?= max($due_fees, 0) ?>"
                                                value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>" required>
                                            <label for="amount"><i class="bi bi-currency-rupee"></i> Amount</label>
                                        </div>
                                        <small class="text-muted">Max: ₹<?= number_format($due_fees, 2) ?></small>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-floating">
                                            <select class="form-select form-control-lg" id="mode" name="mode" required>
                                                <option value="Cash" <?= ($_POST['mode'] ?? 'Cash') == 'Cash' ? 'selected' : '' ?>>💰 Cash</option>
                                                <option value="Cheque">💳 Cheque</option>
                                                <option value="UPI">📱 UPI</option>
                                                <option value="NEFT">🏦 NEFT</option>
                                                <option value="Online">🌐 Online</option>
                                            </select>
                                            <label for="mode"><i class="bi bi-wallet2"></i> Mode</label>
                                        </div>
                                        <small class="text-muted">Payment Method</small>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-floating">
                                            <input type="text" class="form-control form-control-lg" id="receipt_no"
                                                name="receipt_no" placeholder="R202312345"
                                                value="<?= htmlspecialchars($_POST['receipt_no'] ?? '') ?>">
                                            <label for="receipt_no"><i class="bi bi-receipt"></i> Receipt</label>
                                        </div>
                                        <small class="text-muted">Auto-generated if empty</small>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-floating">
                                            <input type="text" class="form-control form-control-lg" id="cashier_name"
                                                name="cashier_name"
                                                value="<?= htmlspecialchars($_POST['cashier_name'] ?? ($_SESSION['admin']['name'] ?? '')) ?>"
                                                required>
                                            <label for="cashier_name"><i class="bi bi-person-badge"></i> Cashier</label>
                                        </div>
                                        <small class="text-muted">Your Name</small>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-10 text-center">
                                        <button type="submit" class="btn btn-success btn-lg px-5 py-3 shadow-lg">
                                            <i class="bi bi-check-circle-fill me-2"></i>Add Payment
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <div class="row mt-4 pt-3 border-top">
                                <div class="col-6">
                                    <a href="view-student?reg=<?= urlencode($reg_no) ?>"
                                        class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-eye me-1"></i>View Profile
                                    </a>
                                </div>
                                <div class="col-6 text-end">
                                    <a href="../student/payment-history?reg=<?= urlencode($reg_no) ?>" target="_blank"
                                        class="btn btn-outline-info btn-sm">
                                        <i class="bi bi-receipt me-1"></i>Payment History
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    
    <?php include '../includes/../includes/footer.php'; ?>
</body>

</html>