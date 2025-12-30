<?php
session_start();
include '../includes/db.php';
require_once __DIR__ . '/../includes/anti_inspect.php';

if (!isset($_SESSION['admin'])) {
    include(__DIR__ . '/../forbidden.php');
    exit();
}

include 'header.php';

$per_page = 25;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'latest';

// Get pending approval count
$pending_stmt = $pdo->query("SELECT COUNT(*) FROM students WHERE status = 'Pending Approval'");
$pending_count = $pending_stmt->fetchColumn();

// Build WHERE
$where_clause = "";
$where_params = [];
if ($search !== '') {
    $where_clause = "WHERE (s.reg_no LIKE ? OR s.student_name LIKE ? OR s.mobile LIKE ? OR s.father_name LIKE ?)";
    $like = "%$search%";
    $where_params = [$like, $like, $like, $like];
}


// Sorting
$order_by = match ($sort) {
    'name_asc' => "s.student_name ASC",
    'name_desc' => "s.student_name DESC",
    'oldest' => "s.admission_date ASC",
    'due_desc' => "(s.total_fees - COALESCE(SUM(fp.amount), 0)) DESC",
    default => "s.id DESC"
};

// MAIN QUERY – paid_fees from fee_payments table (supports multiple payments)
$sql = "
    SELECT 
        s.id,
        s.reg_no,
        s.student_name,
        s.father_name,
        s.mobile,
        s.admission_date,
        s.total_fees,
        s.photo,
        c.course_name,
        COALESCE(SUM(fp.amount), 0) AS paid_fees
    FROM students s
    LEFT JOIN courses c ON s.course_code = c.course_code
    LEFT JOIN fee_payments fp ON fp.reg_no = s.reg_no
    $where_clause
    GROUP BY s.id
    ORDER BY $order_by
    LIMIT $per_page OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($where_params);
$students = $stmt->fetchAll();

// COUNT TOTAL
$count_sql = "SELECT COUNT(*) FROM students s $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($where_params);
$total = $count_stmt->fetchColumn();
$total_pages = ceil($total / $per_page);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>All Students</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/ska-logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body>

    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>All Students <span class="badge bg-primary fs-5"><?= $total ?></span></h3>
            <div class="btn-group" role="group">
                <a href="add-student" class="btn btn-success btn-lg shadow">
                    <i class="bi bi-plus-circle me-2"></i>Add Student
                </a>
                <a href="pending-approval-list" class="btn btn-warning btn-lg shadow">
                    <i class="bi bi-hourglass-split me-2"></i>Pending Approvals (<?= $pending_count ?? 0 ?>)
                </a>
            </div>
        </div>

        <!-- Search & Sort -->
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-5">
                        <input type="text" class="form-control form-control-lg" id="searchBox"
                            placeholder="Search by Reg No, Name, Mobile, Father..."
                            value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-lg-4">
                        <select class="form-select form-select-lg" id="sortSelect">
                            <option value="latest" <?= $sort == 'latest' ? 'selected' : '' ?>>Latest First</option>
                            <option value="name_asc" <?= $sort == 'name_asc' ? 'selected' : '' ?>>Name A-Z</option>
                            <option value="name_desc" <?= $sort == 'name_desc' ? 'selected' : '' ?>>Name Z-A</option>
                            <option value="oldest" <?= $sort == 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                            <option value="due_desc" <?= $sort == 'due_desc' ? 'selected' : '' ?>>Due Amount High-Low
                            </option>
                        </select>
                    </div>
                    <div class="col-lg-3">
                        <button class="btn btn-primary btn-lg w-100" onclick="applyFilters()">Search & Sort</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Students Table -->
        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle shadow-sm">
                <thead class="table-dark">
                    <tr>
                        <th width="10%">Reg No</th>
                        <th width="22%">Student Name</th>
                        <th width="18%">Father's Name</th>
                        <th width="10%">Mobile</th>
                        <th width="12%">Course</th>
                        <th width="12%">Fees Status</th>
                        <th width="10%">Admission</th>
                        <th width="16%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted fs-4">No students found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $s):
                            $paid = (float) $s['paid_fees'];
                            $total_fees = (float) $s['total_fees'];
                            $due = $total_fees - $paid;
                            ?>
                            <tr>
                                <td><strong class="text-primary"><?= $s['reg_no'] ?></strong></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php
                                        // Proper photo path logic
                                        $photo_filename = trim($s['photo'] ?? '');
                                        $photo_path = $photo_filename ? "../assets/images/students/" . htmlspecialchars($photo_filename) : "../assets/images/default.jpeg";
                                        ?>

                                        <img src="<?= $photo_path ?>" alt="Student Photo" class="rounded-circle me-3" width="40"
                                            height="40" onerror="this.onerror=null;this.src='../assets/images/default.jpeg';">
                                        <?= htmlspecialchars($s['student_name']) ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($s['father_name']) ?></td>
                                <td><a href="tel:<?= $s['mobile'] ?>"><?= $s['mobile'] ?></a></td>
                                <td><span class="badge bg-info fs-6"><?= $s['course_name'] ?? '—' ?></span></td>
                                <td>
                                    <span class="badge fs-6 bg-<?= $due <= 0 ? 'success' : 'warning' ?>">
                                        ₹<?= number_format($paid) ?> / ₹<?= number_format($total_fees) ?>
                                        <?php if ($due > 0): ?>
                                            <br><small class="text-danger">Due: ₹<?= number_format($due) ?></small>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td><?= date('d-M-Y', strtotime($s['admission_date'])) ?></td>
                                <td>
                                    <div class="btn-group" role="group">

                                        <a href="view-student?reg=<?= urlencode($s['reg_no']) ?>"
                                            class="btn btn-info btn-sm">View</a>

                                        <a href="edit-student?reg=<?= urlencode($s['reg_no']) ?>"
                                            class="btn btn-primary btn-sm">Edit</a>
                                        <button type="button"
                                            class="btn btn-secondary btn-sm dropdown-toggle dropdown-toggle-split"
                                            data-bs-toggle="dropdown">
                                            More
                                        </button>
                                        <ul class="dropdown-menu">

                                            <li><a class="dropdown-item text-success"
                                                    href="add-fee?reg=<?= $s['reg_no'] ?>">Add Fee</a></li>
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>

                                            <li><a class="dropdown-item text-warning"
                                                    href="manage-id-admit-cert-mark?reg=<?= $s['reg_no'] ?>">Manage
                                                    Documents</a></li>
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>
                                            <li><a class="dropdown-item"
                                                    href="generate-id-card?reg=<?= $s['reg_no'] ?>">Generate ID Card</a>
                                            </li>
                                            <li><a class="dropdown-item" href="add-result?reg=<?= $s['reg_no'] ?>">Add
                                                    Result</a></li>
                                            <li><a class="dropdown-item" href="generate-certificate?reg=<?= $s['reg_no'] ?>"
                                                    target="_blank">Generate Certificate</a></li>
                                            <li><a class="dropdown-item"
                                                    href="generate-marksheet?reg=<?= $s['reg_no'] ?>">Generate Marksheet</a>
                                            </li>
                                            <li><a class="dropdown-item"
                                                    href="generate-admit-card?reg=<?= $s['reg_no'] ?>">Generate Admit
                                                    Card</a></li>

                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php
                    $start = max(1, $page - 5);
                    $end = min($total_pages, $page + 5);
                    for ($i = $start; $i <= $end; $i++):
                        ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function applyFilters() {
            const search = document.getElementById('searchBox').value.trim();
            const sort = document.getElementById('sortSelect').value;
            window.location.href = `?search=${encodeURIComponent(search)}&sort=${sort}&page=1`;
        }

        // Press Enter to search
        document.getElementById('searchBox').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') applyFilters();
        });
    </script>

    <?php include '../includes/../includes/footer.php'; ?>
    </body>

</html>