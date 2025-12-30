<?php
session_start();
include '../includes/db.php';
require_once __DIR__ . '/../includes/anti_inspect.php';

if (!isset($_SESSION['admin']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    include(__DIR__ . '/../forbidden.php');
    exit();
}

$message = '';
$message_class = '';

// Get branch filter from GET parameter
$branch_filter = isset($_GET['branch_filter']) ? trim($_GET['branch_filter']) : '';

// PAGINATION SETUP
$per_page = 15;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

// Get all study centers (branches) for filter dropdown
$branches = $pdo->query("
    SELECT DISTINCT sc.center_code, sc.center_name, sc.id, sc.address
    FROM study_centers sc 
    ORDER BY sc.center_name
")->fetchAll();

// WALLET DETAILS FOR SELECTED BRANCH
$branch_wallet = [];
$branch_name = '';
$branch_yearly_totals = [];

if (!empty($branch_filter)) {
    // Get branch name for display
    $branch_info = $pdo->prepare("SELECT center_name FROM study_centers WHERE center_code = ?");
    $branch_info->execute([$branch_filter]);
    $branch_info_result = $branch_info->fetch();
    $branch_name = $branch_info_result ? $branch_info_result['center_name'] : '';

    // Total amount + count for that branch
    $wallet_total = $pdo->prepare("
        SELECT SUM(fp.amount) AS total_amount, COUNT(*) as txn_count
        FROM fee_payments fp
        INNER JOIN students s ON fp.reg_no = s.reg_no
        INNER JOIN study_centers sc ON s.study_center_code = sc.center_code
        WHERE sc.center_code = ?
    ");
    $wallet_total->execute([$branch_filter]);
    $wallet_result = $wallet_total->fetch();
    $branch_wallet['total_amount'] = $wallet_result['total_amount'] ?: 0;
    $branch_wallet['txn_count'] = $wallet_result['txn_count'] ?: 0;

    // Today's total for that branch
    $wallet_today = $pdo->prepare("
        SELECT SUM(fp.amount) AS today_amount
        FROM fee_payments fp
        INNER JOIN students s ON fp.reg_no = s.reg_no
        INNER JOIN study_centers sc ON s.study_center_code = sc.center_code
        WHERE sc.center_code = ? AND DATE(fp.payment_date) = CURDATE()
    ");
    $wallet_today->execute([$branch_filter]);
    $branch_wallet['today_amount'] = $wallet_today->fetchColumn() ?: 0;

    // YEARLY TOTALS FOR SELECTED BRANCH
    $yearly_stmt = $pdo->prepare("
        SELECT YEAR(fp.payment_date) AS pay_year,
               SUM(fp.amount) AS total_amount,
               COUNT(*) AS txn_count
        FROM fee_payments fp
        INNER JOIN students s ON fp.reg_no = s.reg_no
        INNER JOIN study_centers sc ON s.study_center_code = sc.center_code
        WHERE sc.center_code = ?
        GROUP BY YEAR(fp.payment_date)
        ORDER BY pay_year DESC
    ");
    $yearly_stmt->execute([$branch_filter]);
    $branch_yearly_totals = $yearly_stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // ALL BRANCHES TOTALS
    $branch_name = 'All Branches';

    $wallet_total_all = $pdo->query("
        SELECT SUM(fp.amount) AS total_amount, COUNT(*) as txn_count
        FROM fee_payments fp
        INNER JOIN students s ON fp.reg_no = s.reg_no
        INNER JOIN study_centers sc ON s.study_center_code = sc.center_code
    ");
    $wallet_result_all = $wallet_total_all->fetch();
    $branch_wallet['total_amount'] = $wallet_result_all['total_amount'] ?: 0;
    $branch_wallet['txn_count'] = $wallet_result_all['txn_count'] ?: 0;

    // Today's total for all branches
    $wallet_today_all = $pdo->query("
        SELECT SUM(fp.amount) AS today_amount
        FROM fee_payments fp
        INNER JOIN students s ON fp.reg_no = s.reg_no
        INNER JOIN study_centers sc ON s.study_center_code = sc.center_code
        WHERE DATE(fp.payment_date) = CURDATE()
    ");
    $branch_wallet['today_amount'] = $wallet_today_all->fetchColumn() ?: 0;

    // YEARLY TOTALS FOR ALL BRANCHES
    $yearly_stmt_all = $pdo->query("
        SELECT YEAR(fp.payment_date) AS pay_year,
               SUM(fp.amount) AS total_amount,
               COUNT(*) AS txn_count
        FROM fee_payments fp
        INNER JOIN students s ON fp.reg_no = s.reg_no
        INNER JOIN study_centers sc ON s.study_center_code = sc.center_code
        GROUP BY YEAR(fp.payment_date)
        ORDER BY pay_year DESC
    ");
    $branch_yearly_totals = $yearly_stmt_all->fetchAll(PDO::FETCH_ASSOC);
}


// COUNT TOTAL RESULTS
$count_query = "
    SELECT COUNT(DISTINCT fp.id) as total 
    FROM fee_payments fp
    INNER JOIN students s ON fp.reg_no = s.reg_no
    INNER JOIN study_centers sc ON s.study_center_code = sc.center_code
";
$count_params = [];
if (!empty($branch_filter)) {
    $count_query .= " WHERE sc.center_code = ?";
    $count_params[] = $branch_filter;
} else {
    $count_query .= " WHERE s.reg_no IS NOT NULL"; // Only count payments with valid students
}

$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($count_params);
$total_payments = $count_stmt->fetchColumn();
$total_pages = ceil($total_payments / $per_page);

// FETCH PAYMENTS WITH PAGINATION + FILTER
$payments_query = "
    SELECT fp.*, s.student_name, sc.center_name, sc.center_code
    FROM fee_payments fp
    INNER JOIN students s ON fp.reg_no = s.reg_no
    INNER JOIN study_centers sc ON s.study_center_code = sc.center_code
";
$params = [];

if (!empty($branch_filter)) {
    $payments_query .= " WHERE sc.center_code = ?";
    $params[] = $branch_filter;
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
    <title>Branch Wallet - Fee Payments</title>
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

        .branch-highlight {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
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
                <h3 class="mb-2">Branch Wallet
                    <span class="badge bg-primary fs-5"><?= $total_payments ?></span>
                    <?php if (!empty($branch_filter)): ?>
                        <span class="badge bg-warning fs-6 ms-2">Page <?= $page ?> of <?= $total_pages ?></span>
                    <?php endif; ?>
                </h3>
                <small class="text-muted">
                    Showing <?= count($payments) ?> of <?= $total_payments ?> payments
                    (<?= $page ?> of <?= $total_pages ?> pages)
                    <?php if (!empty($branch_filter)): ?>
                        <br><strong>Filtered: <?= htmlspecialchars($branch_filter) ?> -
                            <?= htmlspecialchars($branch_name) ?></strong>
                    <?php endif; ?>
                </small>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-end gap-2 align-items-center">
                    <form method="GET" class="d-flex me-3" style="max-width: 350px;">
                        <input type="hidden" name="page" value="1">
                        <select name="branch_filter" class="form-select form-select-lg" onchange="this.form.submit()"
                            style="min-width: 250px;">
                            <option value="">🎯 All Branches (<?= $total_payments ?>)</option>
                            <?php foreach ($branches as $b): ?>
                                <?php
                                $branch_count = $pdo->prepare("
                                    SELECT COUNT(DISTINCT fp.id) 
                                    FROM fee_payments fp
                                    INNER JOIN students s ON fp.reg_no = s.reg_no
                                    INNER JOIN study_centers sc ON s.study_center_code = sc.center_code
                                    WHERE sc.center_code = ?
                                ");
                                $branch_count->execute([$b['center_code']]);
                                $count = $branch_count->fetchColumn();
                                ?>
                                <option value="<?= $b['center_code'] ?>" <?= $branch_filter == $b['center_code'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($b['center_code']) ?> - <?= htmlspecialchars($b['address']) ?>
                                    (<?= $count ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <?php if (!empty($branch_filter)): ?>
                        <a href="?" class="btn btn-outline-danger btn-rounded" title="Clear Filter">
                            <i class="bi bi-x-circle"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-12 text-end">
                <a href="cashier-wallet" class="btn btn-outline-success btn-rounded">
                    <i class="bi bi-person-cash me-1"></i> Cashier Wallet
                </a>
            </div>
        </div>

        <?php if (!empty($branch_filter) || empty($branch_filter)): ?>
            <!-- BRANCH WALLET SUMMARY -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card section-card branch-highlight">
                        <div class="card-body text-center text-white">
                            <h4 class="mb-3">
                                <i class="bi bi-building me-2"></i>
                                <?php if (!empty($branch_filter)): ?>
                                    <?= htmlspecialchars($branch_filter) ?> - <?= htmlspecialchars($branch_name) ?>
                                <?php else: ?>
                                    <?= htmlspecialchars($branch_name) ?>
                                <?php endif; ?>
                                Wallet
                            </h4>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="card bg-success bg-opacity-75 border-0">
                                        <div class="card-body">
                                            <i class="bi bi-wallet2 fs-1 mb-2"></i>
                                            <h3>₹<?= number_format($branch_wallet['total_amount'], 2) ?></h3>
                                            <p class="mb-0">Total Collected</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-primary bg-opacity-75 border-0">
                                        <div class="card-body">
                                            <i class="bi bi-calendar-day fs-1 mb-2"></i>
                                            <h3>₹<?= number_format($branch_wallet['today_amount'], 2) ?></h3>
                                            <p class="mb-0">Today Collected</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-info bg-opacity-75 border-0">
                                        <div class="card-body">
                                            <i class="bi bi-receipt fs-1 mb-2"></i>
                                            <h3><?= $branch_wallet['txn_count'] ?></h3>
                                            <p class="mb-0">Total Transactions</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($branch_yearly_totals)): ?>
                                <hr class="border-light my-4">
                                <h5 class="mb-3">Yearly Collection</h5>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered table-striped mb-0 text-white">
                                        <thead>
                                            <tr>
                                                <th>Year</th>
                                                <th>Total Amount</th>
                                                <th>Transactions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($branch_yearly_totals as $y): ?>
                                                <tr>
                                                    <td><strong><?= (int) $y['pay_year'] ?></strong></td>
                                                    <td>₹<?= number_format($y['total_amount'], 2) ?></td>
                                                    <td><?= (int) $y['txn_count'] ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>


        <div class="card section-card">
            <div class="card-header card-header-main d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-wallet2 me-2"></i>
                    Fee Payments
                    <?= !empty($branch_filter) ? '(Filtered: ' . htmlspecialchars($branch_filter) . ')' : '' ?>
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th style="width:5%">#</th>
                                <th style="width:12%">SN</th>
                                <th style="width:12%">Branch</th>
                                <th style="width:12%">Amount</th>
                                <th style="width:15%">Date</th>
                                <th style="width:25%">Description</th>
                                <th style="width:12%">Payment Mode</th>
                                <th style="width:7%">Cashier</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $i => $p): ?>
                                <tr>
                                    <td><strong><?= (($page - 1) * $per_page + $i + 1) ?></strong></td>
                                    <td><strong><?= htmlspecialchars($p['reg_no']) ?></strong></td>
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
                                    <td>
                                        <span class="badge bg-secondary text-white fs-6">
                                            <?= htmlspecialchars($p['added_by']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($payments)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted fs-5">
                                        <?= !empty($branch_filter) ? 'No payments found for branch ' . htmlspecialchars($branch_filter) : 'No payments found' ?>
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