<?php
session_start();
include '../includes/db.php';
require_once __DIR__ . '/../includes/anti_inspect.php';

if (!isset($_SESSION['admin'])) {
    include(__DIR__ . '/../forbidden.php');
    exit();
}

$message = '';
$message_class = '';

// Get cashier filter from GET parameter
$cashier_filter = isset($_GET['cashier_filter']) ? trim($_GET['cashier_filter']) : '';

// PAGINATION SETUP
$per_page = 15;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

// Get all cashiers for filter dropdown
$cashiers = $pdo->query("
    SELECT DISTINCT added_by as cashier_name
    FROM fee_payments 
    WHERE added_by IS NOT NULL AND added_by != ''
    ORDER BY added_by
")->fetchAll();

// CASHIER WALLET DETAILS FOR SELECTED CASHIER
$cashier_wallet = [];
if (!empty($cashier_filter)) {
    // Total amount + count for that cashier
    $wallet_total = $pdo->prepare("
        SELECT SUM(amount) AS total_amount, COUNT(*) as txn_count
        FROM fee_payments 
        WHERE added_by = ?
    ");
    $wallet_total->execute([$cashier_filter]);
    $wallet_result = $wallet_total->fetch();
    $cashier_wallet['total_amount'] = $wallet_result['total_amount'] ?: 0;
    $cashier_wallet['txn_count'] = $wallet_result['txn_count'] ?: 0;

    // Today's total for that cashier
    $wallet_today = $pdo->prepare("
        SELECT SUM(amount) AS today_amount
        FROM fee_payments 
        WHERE added_by = ? AND DATE(payment_date) = CURDATE()
    ");
    $wallet_today->execute([$cashier_filter]);
    $cashier_wallet['today_amount'] = $wallet_today->fetchColumn() ?: 0;
}

// COUNT TOTAL RESULTS
$count_query = "SELECT COUNT(*) as total FROM fee_payments fp WHERE added_by IS NOT NULL";
$count_params = [];
if (!empty($cashier_filter)) {
    $count_query .= " AND fp.added_by = ?";
    $count_params[] = $cashier_filter;
}

$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($count_params);
$total_payments = $count_stmt->fetchColumn();
$total_pages = ceil($total_payments / $per_page);

// FETCH PAYMENTS WITH PAGINATION + FILTER
$payments_query = "
    SELECT fp.*, s.student_name, sc.center_name, sc.center_code
    FROM fee_payments fp
    LEFT JOIN students s ON fp.reg_no = s.reg_no
    LEFT JOIN study_centers sc ON s.study_center_code = sc.center_code
    WHERE fp.added_by IS NOT NULL
";
$params = [];

if (!empty($cashier_filter)) {
    $payments_query .= " AND fp.added_by = ?";
    $params[] = $cashier_filter;
}

$payments_query .= " ORDER BY fp.payment_date DESC, fp.id DESC LIMIT $per_page OFFSET $offset";

$stmt = $pdo->prepare($payments_query);
$stmt->execute($params);
$payments = $stmt->fetchAll();

include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Cashier Wallet - Fee Payments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }

        .section-card {
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
            border-radius: 16px;
            border: none;
        }

        .card-header-main {
            background: linear-gradient(135deg, #ff6b6b, #feca57);
            color: white;
            font-weight: 600;
            border-radius: 16px 16px 0 0;
        }

        .btn-rounded {
            border-radius: 50px;
            transition: all 0.3s ease;
            min-width: 44px;
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-rounded:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2) !important;
        }

        .pagination-btn {
            min-width: 45px;
        }

        .page-item.active .page-link {
            background: #ff6b6b !important;
            border-color: #ff6b6b !important;
        }

        .amount-badge {
            background: linear-gradient(135deg, #28a745, #20c997);
            font-weight: 600;
        }

        .cashier-highlight {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
        }
    </style>
</head>

<body>

    <div class="container-fluid mt-4 mb-5">

        <?php if ($message): ?>
            <div class="alert <?= $message_class ?> alert-dismissible fade show mx-auto mb-4" style="max-width:600px;">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- FILTER + PAGINATION INFO -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="d-flex align-items-center mb-2">

                    <h3 class="mb-0">Cashier Wallet
                        <span class="badge bg-primary fs-5"><?= $total_payments ?></span>
                        <?php if (!empty($cashier_filter)): ?>
                            <span class="badge bg-warning fs-6 ms-2">Page <?= $page ?> of <?= $total_pages ?></span>
                        <?php endif; ?>
                    </h3>

                </div>
                <small class="text-muted">
                    Showing <?= count($payments) ?> of <?= $total_payments ?> payments
                    (<?= $page ?> of <?= $total_pages ?> pages)
                    <?php if (!empty($cashier_filter)): ?>
                        <br><strong>Filtered: <?= htmlspecialchars($cashier_filter) ?></strong>
                    <?php endif; ?>
                </small>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-end gap-2 align-items-center">
                    <form method="GET" class="d-flex me-3" style="max-width: 350px;">
                        <input type="hidden" name="page" value="1">
                        <select name="cashier_filter" class="form-select form-select-lg" onchange="this.form.submit()"
                            style="min-width: 250px;">
                            <option value="">🎯 All Cashiers (<?= $total_payments ?>)</option>
                            <?php foreach ($cashiers as $c): ?>
                                <?php
                                $cashier_count = $pdo->prepare("SELECT COUNT(*) FROM fee_payments WHERE added_by = ?");
                                $cashier_count->execute([$c['cashier_name']]);
                                $count = $cashier_count->fetchColumn();
                                ?>
                                <option value="<?= htmlspecialchars($c['cashier_name']) ?>"
                                    <?= $cashier_filter == $c['cashier_name'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['cashier_name']) ?> (<?= $count ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <?php if (!empty($cashier_filter)): ?>
                        <a href="?" class="btn btn-outline-danger btn-rounded" title="Clear Filter">
                            <i class="bi bi-x-circle"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-12 text-end">
                <a href="branch-wallet" class="btn btn-outline-danger btn-rounded">
                    <i class="bi bi-building me-1"></i> Branch Wallet
                </a>
            </div>
        </div>
        <?php if (!empty($cashier_filter)): ?>
            <!-- CASHIER WALLET SUMMARY -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card section-card cashier-highlight">
                        <div class="card-body text-center text-white">
                            <h4 class="mb-3">
                                <i class="bi bi-person-badge me-2"></i><?= htmlspecialchars($cashier_filter) ?> Wallet
                            </h4>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="card bg-success bg-opacity-75 border-0">
                                        <div class="card-body">
                                            <i class="bi bi-wallet2 fs-1 mb-2"></i>
                                            <h3>₹<?= number_format($cashier_wallet['total_amount'], 2) ?></h3>
                                            <p class="mb-0">Total Collected</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-primary bg-opacity-75 border-0">
                                        <div class="card-body">
                                            <i class="bi bi-calendar-day fs-1 mb-2"></i>
                                            <h3>₹<?= number_format($cashier_wallet['today_amount'], 2) ?></h3>
                                            <p class="mb-0">Today Collected</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-info bg-opacity-75 border-0">
                                        <div class="card-body">
                                            <i class="bi bi-receipt fs-1 mb-2"></i>
                                            <h3><?= $cashier_wallet['txn_count'] ?></h3>
                                            <p class="mb-0">Total Transactions</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="card section-card">
            <div class="card-header card-header-main d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-person-cash me-2"></i>
                    Fee Payments
                    <?= !empty($cashier_filter) ? '(Filtered: ' . htmlspecialchars($cashier_filter) . ')' : '' ?>
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th style="width:5%">#</th>
                                <th style="width:12%">SN</th>
                                <th style="width:12%">Cashier</th>
                                <th style="width:12%">Branch</th>
                                <th style="width:12%">Amount</th>
                                <th style="width:15%">Date</th>
                                <th style="width:20%">Student</th>
                                <th style="width:12%">Payment Mode</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $i => $p): ?>
                                <tr>
                                    <td><strong><?= (($page - 1) * $per_page + $i + 1) ?></strong></td>
                                    <td><strong><?= htmlspecialchars($p['reg_no']) ?></strong></td>
                                    <td>
                                        <span class="badge bg-warning fs-6"><?= htmlspecialchars($p['added_by']) ?></span>
                                    </td>
                                    <td>
                                        <span
                                            class="badge bg-info fs-6"><?= htmlspecialchars($p['center_code']) ?></span><br>
                                        <small><?= htmlspecialchars($p['center_name']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge amount-badge text-white fs-6">
                                            ₹<?= number_format($p['amount'], 2) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?= date('d-M-Y', strtotime($p['payment_date'])) ?></strong><br>
                                        <small><?= date('h:i A', strtotime($p['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($p['student_name'] ?: 'N/A') ?><br>
                                        <small>Receipt: #<?= htmlspecialchars($p['receipt_no']) ?></small>
                                    </td>
                                    <td>
                                        <span
                                            class="badge bg-<?= $p['payment_mode'] == 'Cash' ? 'success' : 'primary' ?> fs-6">
                                            <?= htmlspecialchars($p['payment_mode']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($payments)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted fs-5">
                                        <?= !empty($cashier_filter) ? 'No payments found for cashier ' . htmlspecialchars($cashier_filter) : 'No payments found' ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- PAGINATION -->
        <?php if ($total_pages > 1): ?>
            <div class="row mt-4">
                <div class="col-md-12">
                    <nav aria-label="Payments pagination">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link pagination-btn"
                                        href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            if ($start_page > 1): ?>
                                <li class="page-item"><a class="page-link pagination-btn"
                                        href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">1</a></li>
                                <?php if ($start_page > 2): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link pagination-btn"
                                        href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($end_page < $total_pages): ?>
                                <?php if ($end_page < $total_pages - 1): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                                <li class="page-item"><a class="page-link pagination-btn"
                                        href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>"><?= $total_pages ?></a>
                                </li>
                            <?php endif; ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link pagination-btn"
                                        href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php include '../includes/footer.php'; ?>
</body>

</html>