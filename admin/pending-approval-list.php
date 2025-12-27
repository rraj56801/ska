<?php
session_start();
include '../includes/db.php';
require_once __DIR__ . '/../includes/anti_inspect.php';

if (!isset($_SESSION['admin'])) {
    include(__DIR__ . '/../forbidden.php');
    exit();
}

// ---------- FILTERS ----------
$where = "WHERE s.status = 'Pending Approval'";
$params = [];

// Course filter
if (!empty($_GET['course'])) {
    $where .= " AND s.course_code = :course";
    $params[':course'] = $_GET['course'];
}

// Center filter
if (!empty($_GET['center'])) {
    $where .= " AND s.study_center_code = :center";
    $params[':center'] = $_GET['center'];
}

// Only active course & center filter
if (!empty($_GET['only_active']) && $_GET['only_active'] == '1') {
    $where .= " AND c.is_active = 1 AND sc.is_active = 1";
}

// ---------- PAGINATION ----------
$per_page = 25;
$page = isset($_GET['page']) && ctype_digit($_GET['page']) && $_GET['page'] > 0
    ? (int)$_GET['page']
    : 1;
$offset = ($page - 1) * $per_page;

// Count total records for pagination
$count_sql = "
    SELECT COUNT(*) AS total
    FROM students s
    LEFT JOIN courses c ON s.course_code = c.course_code
    LEFT JOIN study_centers sc ON s.study_center_code = sc.center_code
    $where
";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total = (int)$count_stmt->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));
if ($page > $total_pages) {
    $page = $total_pages;
    $offset = ($page - 1) * $per_page;
}

// ---------- BULK APPROVAL ----------
$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $selected_reg_nos = $_POST['reg_nos'] ?? [];

    if (!empty($selected_reg_nos)) {
        $placeholders = str_repeat('?,', count($selected_reg_nos) - 1) . '?';

        $sql = "
            UPDATE students s
            JOIN courses c ON s.course_code = c.course_code
            JOIN study_centers sc ON s.study_center_code = sc.center_code
            SET s.status = 'Active',
                s.id_card_generated = 'Yes'
            WHERE s.reg_no IN ($placeholders)
              AND s.status = 'Pending Approval'
              AND c.is_active = 1
              AND sc.is_active = 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($selected_reg_nos);

        $count = $stmt->rowCount();
        $message = 'Successfully approved ' . $count . ' student(s)! ID cards generated.';
        $message_type = 'success';
    } else {
        $message = 'Please select at least one student.';
        $message_type = 'warning';
    }
}

// ---------- FETCH STUDENTS FOR CURRENT PAGE ----------
$list_sql = "
    SELECT s.reg_no, s.student_name, s.father_name, s.mobile, s.course_code,
           c.course_name, sc.center_name, s.admission_date,
           c.is_active AS c_active, sc.is_active AS sc_active
    FROM students s 
    LEFT JOIN courses c ON s.course_code = c.course_code
    LEFT JOIN study_centers sc ON s.study_center_code = sc.center_code
    $where
    ORDER BY s.admission_date DESC
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($list_sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$students = $stmt->fetchAll();
?>
<?php include 'header.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pending Approval Students</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .form-label {
            font-weight: 600;
            color: #2c3e50;
        }
        .section-title {
            color: #28a740;
            border-bottom: 2px solid #28a740;
            padding-bottom: 8px;
            margin: 25px 0 20px;
        }
        .select-zone {
            border: 3px dashed #28a740;
            border-radius: 15px;
            transition: all 0.3s ease;
            min-height: 200px;
        }
        .select-zone:hover {
            border-color: #1e7e34;
            background: #f8fff8;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2) !important;
        }
        .stats-card {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        .pending-badge {
            background: linear-gradient(45deg, #ffc107, #fd7e14);
            color: #000;
            font-weight: 600;
        }
        .badge.pending-badge.course-inactive {
            opacity: 0.4;
            filter: grayscale(60%);
        }
    </style>
</head>

<body class="bg-light">

<div class="container mt-4 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-11">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-header bg-gradient bg-success text-white text-center py-5">
                    <h2 class="mb-0">
                        <i class="bi bi-hourglass-split me-2"></i>Pending Approval Students
                    </h2>
                    <p class="mb-0 opacity-90 mt-2">Review self-filled details → Approve students → Generate ID cards</p>
                </div>

                <div class="card-body p-5">
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show mb-4">
                            <strong><?= $message_type === 'success' ? 'Success!' : 'Note!' ?></strong>
                            <?= htmlspecialchars($message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- STATS -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card stats-card text-center p-4 rounded-3 h-100">
                                <i class="bi bi-people-fill fs-1 mb-3 opacity-75"></i>
                                <h3 class="mb-1"><?= $total ?></h3>
                                <small>Pending Approval (Filtered)</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info text-white text-center p-4 rounded-3 h-100">
                                <i class="bi bi-eye-fill fs-1 mb-3"></i>
                                <h3 class="mb-1">Review</h3>
                                <small>View Details</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-primary text-white text-center p-4 rounded-3 h-100">
                                <i class="bi bi-check-lg fs-1 mb-3"></i>
                                <h3 class="mb-1">Approve</h3>
                                <small>Bulk Action</small>
                            </div>
                        </div>
                    </div>

                    <!-- FILTER FORM -->
                    <form method="GET" class="row g-3 mb-4">
                          <div class="col-md-3">
                            <label class="form-label">Center Code</label>
                            <input type="text" name="center" class="form-control"
                                   value="<?= htmlspecialchars($_GET['center'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Course Code</label>
                            <input type="text" name="course" class="form-control"
                                   value="<?= htmlspecialchars($_GET['course'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Only Active Course & Center</label>
                            <select name="only_active" class="form-select">
                                <option value="">All</option>
                                <option value="1" <?= (($_GET['only_active'] ?? '') === '1') ? 'selected' : '' ?>>
                                    Yes
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-funnel me-1"></i>Filter
                            </button>
                        </div>
                    </form>

                    <!-- INFO ZONE -->
                    <div class="row g-4 mb-4">
                        <div class="col-12">
                            <div class="select-zone text-center p-5 position-relative">
                                <i class="bi bi-person-plus display-4 text-success mb-3"></i>
                                <h5 class="mb-2">Self-Filled Student Applications</h5>
                                <p class="text-muted mb-0">Review details → Select students → Approve in bulk</p>
                                <?php if (empty($students)): ?>
                                    <div class="mt-4 p-4 bg-light rounded-3">
                                        <i class="bi bi-check-circle fs-3 text-success mb-3"></i>
                                        <h6 class="text-success">No pending approvals! All students approved.</h6>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- BULK APPROVAL FORM + TABLE -->
                    <form method="POST" id="bulkForm">
                        <?php if (!empty($students)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-success">
                                    <tr>
                                        <th width="8%">
                                            <input type="checkbox" id="selectAll" class="form-check-input">
                                        </th>
                                        <th>Reg No</th>
                                        <th>Student</th>
                                        <th width="15%">Course</th>
                                        <th width="15%">Mobile</th>
                                        <th width="15%">Center</th>
                                        <th width="12%">Date</th>
                                        <th width="20%">Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($students as $student): ?>
                                        <tr class="card-hover">
                                            <td>
                                                <?php if ($student['c_active'] == 1 && $student['sc_active'] == 1): ?>
                                                    <input type="checkbox" class="form-check-input student-checkbox"
                                                           name="reg_nos[]"
                                                           value="<?= htmlspecialchars($student['reg_no']) ?>">
                                                <?php else: ?>
                                                    <span class="text-muted small">Inactive course/center</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong class="text-success">
                                                    <?= htmlspecialchars($student['reg_no']) ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar me-3 bg-light rounded-circle d-flex align-items-center justify-content-center"
                                                         style="width:45px;height:45px;font-weight:600;color:#495057;">
                                                        <?= strtoupper(substr($student['student_name'], 0, 2)) ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold">
                                                            <?= htmlspecialchars($student['student_name']) ?>
                                                        </div>
                                                        <small class="text-muted">
                                                            <?= htmlspecialchars($student['father_name']) ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span
                                                    class="badge pending-badge <?= $student['c_active'] == 0 ? 'course-inactive' : '' ?>">
                                                    <?= htmlspecialchars($student['course_name']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="tel:<?= htmlspecialchars($student['mobile']) ?>"
                                                   class="text-decoration-none">
                                                    <?= htmlspecialchars($student['mobile']) ?>
                                                </a>
                                            </td>
                                            <td>
                                                <span
                                                    class="badge pending-badge <?= $student['sc_active'] == 0 ? 'course-inactive' : '' ?>">
                                                    <?= htmlspecialchars($student['center_name'] ?: 'MAIN CENTER') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?= date('M d, Y', strtotime($student['admission_date'])) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="view-student?reg=<?= urlencode($student['reg_no']) ?>"
                                                       class="btn btn-outline-primary" title="View Details">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="../pdf/idcard?reg=<?= urlencode($student['reg_no']) ?>"
                                                       target="_blank" class="btn btn-outline-info" title="Print ID">
                                                        <i class="bi bi-printer"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                        <!-- PAGINATION WITH ELLIPSIS -->
                        <?php if ($total_pages > 1): ?>
                            <?php
                            $adjacents = 2;
                            $pages = [];
                            $pages[] = 1;
                            $pages[] = $total_pages;
                            for ($i = $page - $adjacents; $i <= $page + $adjacents; $i++) {
                                if ($i > 1 && $i < $total_pages) {
                                    $pages[] = $i;
                                }
                            }
                            $pages = array_unique($pages);
                            sort($pages);

                            $display = [];
                            $prev_p = null;
                            foreach ($pages as $p) {
                                if ($prev_p !== null && $p > $prev_p + 1) {
                                    $display[] = '...';
                                }
                                $display[] = $p;
                                $prev_p = $p;
                            }
                            ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link"
                                           href="<?= $page <= 1 ? '#' : '?' . http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                            Previous
                                        </a>
                                    </li>

                                    <?php foreach ($display as $item): ?>
                                        <?php if ($item === '...'): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">…</span>
                                            </li>
                                        <?php else: ?>
                                            <li class="page-item <?= $item == $page ? 'active' : '' ?>">
                                                <a class="page-link"
                                                   href="?<?= http_build_query(array_merge($_GET, ['page' => $item])) ?>">
                                                    <?= $item ?>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>

                                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                        <a class="page-link"
                                           href="<?= $page >= $total_pages ? '#' : '?' . http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                            Next
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>

                        <!-- BULK ACTION BUTTONS -->
                        <div class="text-end mt-4 p-4 bg-light rounded-3">
                            <button type="submit" class="btn btn-success btn-lg px-5 me-3 text-white"
                                    id="approveBulkBtn" <?= empty($students) ? 'disabled' : '' ?>>
                                <i class="bi bi-check-lg me-2"></i>
                                Approve Selected Students (0)
                            </button>
                            <a href="students" class="btn btn-outline-secondary btn-lg px-5">
                                <i class="bi bi-arrow-left me-2"></i>Back to Students
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.student-checkbox');
    const approveBtn = document.getElementById('approveBulkBtn');

    function updateApproveButton() {
        const checkedCount = document.querySelectorAll('.student-checkbox:checked').length;
        if (approveBtn) {
            approveBtn.innerHTML = `Approve Selected Students (${checkedCount})`;
            approveBtn.disabled = checkedCount === 0;
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateApproveButton();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateApproveButton);
    });

    updateApproveButton();
});
</script>

<?php include '../includes/../includes/footer.php'; ?>
</body>
</html>
