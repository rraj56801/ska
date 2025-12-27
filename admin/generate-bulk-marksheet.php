<?php
session_start();
include '../includes/db.php';
require_once __DIR__ . '/../includes/anti_inspect.php';

if (!isset($_SESSION['admin'])) {
    include(__DIR__ . '/../forbidden.php');
    exit();
}

// Handle bulk ID card generation
$message = '';
$message_type = '';
$selected_statuses = [];

// ---------- FILTERS ----------
$where = "WHERE (s.marksheet_gen = 'No' OR s.marksheet_gen IS NULL)";
$params = [];

// Reg No filter (partial)
if (!empty($_GET['reg_no'])) {
    $where .= " AND s.reg_no LIKE :reg_no";
    $params[':reg_no'] = '%' . $_GET['reg_no'] . '%';
}

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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $selected_reg_nos = $_POST['reg_nos'] ?? [];

    if (!empty($selected_reg_nos)) {
        $placeholders = str_repeat('?,', count($selected_reg_nos) - 1) . '?';
        $stmt = $pdo->prepare("UPDATE students SET marksheet_gen = 'Yes', marksheet_gen_date = NOW() WHERE reg_no IN ($placeholders)");
        $stmt->execute($selected_reg_nos);

        $count = count($selected_reg_nos);
        $message = "Successfully generated Marksheets for {$count} student(s)!";
        $message_type = 'success';
    } else {
        $message = 'Please select at least one student.';
        $message_type = 'warning';
    }
}

// ---------- FETCH STUDENTS FOR CURRENT PAGE ----------
$list_sql = "
    SELECT s.reg_no, s.student_name, s.father_name, s.mobile, s.course_code,
           c.course_name, sc.address, c.is_active as c_active, sc.is_active as sc_active,
           s.admission_date
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
    <title>Bulk Generate Marksheet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .form-label {
            font-weight: 600;
            color: #2c3e50;
        }

        .required {
            color: red;
        }

        .section-title {
            color: #007bff;
            border-bottom: 2px solid #007bff;
            padding-bottom: 8px;
            margin: 25px 0 20px;
        }

        .select-zone {
            border: 3px dashed #007bff;
            border-radius: 15px;
            transition: all 0.3s ease;
            min-height: 200px;
        }

        .select-zone:hover {
            border-color: #0056b3;
            background: #f0f8ff;
        }

        .select-zone.dragover {
            border-color: #28a745;
            background: #f8fff8;
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2) !important;
        }

        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%);
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
                    <div class="card-header bg-gradient bg-primary text-white text-center py-5">
                        <h2 class="mb-0">
                            <i class="bi bi-card-text me-2"></i>Bulk Generate Marksheets
                        </h2>
                        <p class="mb-0 opacity-90 mt-2">Select students → Generate Marksheets in bulk → Print
                            individually</p>
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
                                    <small>Pending Marksheets (Filtered)</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-success text-white text-center p-4 rounded-3 h-100">
                                    <i class="bi bi-check-circle-fill fs-1 mb-3"></i>
                                    <h3 class="mb-1">Ready</h3>
                                    <small>Click to Generate</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-info text-white text-center p-4 rounded-3 h-100">
                                    <i class="bi bi-printer-fill fs-1 mb-3"></i>
                                    <h3 class="mb-1">Print</h3>
                                    <small>Individual PDFs</small>
                                </div>
                            </div>
                        </div>

                        <!-- FILTER FORM -->
                        <h5 class="section-title">Filters</h5>
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Reg No</label>
                                <input type="text" name="reg_no" class="form-control"
                                       value="<?= htmlspecialchars($_GET['reg_no'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Course Code</label>
                                <input type="text" name="course" class="form-control"
                                       value="<?= htmlspecialchars($_GET['course'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Center Code</label>
                                <input type="text" name="center" class="form-control"
                                       value="<?= htmlspecialchars($_GET['center'] ?? '') ?>">
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-funnel me-1"></i>Apply Filters
                                </button>
                                <a href="<?= strtok($_SERVER['REQUEST_URI'], '?') ?>" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle me-1"></i>Clear
                                </a>
                            </div>
                        </form>

                        <!-- BULK SELECTION FORM -->
                        <h5 class="section-title">Select Students for Marksheet Generation</h5>
                        <form method="POST" id="bulkForm">
                            <div class="row g-4 mb-4">
                                <div class="col-12">
                                    <div class="select-zone text-center p-5 position-relative">
                                        <i class="bi bi-people display-4 text-primary mb-3"></i>
                                        <h5 class="mb-2">Select Students Below</h5>
                                        <p class="text-muted mb-0">Check boxes → Generate Marksheet Status → Print individual
                                            cards</p>
                                        <?php if (empty($students)): ?>
                                            <div class="mt-4 p-4 bg-light rounded-3">
                                                <i class="bi bi-check-circle fs-3 text-success mb-3"></i>
                                                <h6 class="text-success">All Marksheets Generated!</h6>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
<!-- STUDENTS LIST -->
                            <?php if (!empty($students)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-primary">
                                            <tr>
                                                <th width="8%">
                                                    <input type="checkbox" id="selectAll" class="form-check-input">
                                                </th>
                                                <th>Reg No</th>
                                                <th>Student</th>
                                                <th width="15%">Course</th>
                                                <th width="15%">Mobile</th>
                                                <th width="20%">Center</th>
                                                <th width="15%">Action</th>
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
                                                    <td><strong
                                                            class="text-primary"><?= htmlspecialchars($student['reg_no']) ?></strong>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar me-3 bg-light rounded-circle d-flex align-items-center justify-content-center"
                                                                style="width:45px;height:45px;font-weight:600;color:#495057;">
                                                                <?= strtoupper(substr($student['student_name'], 0, 2)) ?>
                                                            </div>
                                                            <div>
                                                                <div class="fw-bold text-nowrap">
                                                                    <?= htmlspecialchars($student['student_name']) ?></div>
                                                                <small
                                                                    class="text-muted text-nowrap"><?= htmlspecialchars($student['father_name']) ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><span
                                                        class="badge pending-badge <?= $student['c_active'] == 0 ? 'course-inactive' : '' ?>">
                                                    <?= htmlspecialchars($student['course_name']) ?>
                                                        </span>
                                                    </td>
                                                    <td><a href="tel:<?= htmlspecialchars($student['mobile']) ?>"
                                                            class="text-decoration-none"><?= htmlspecialchars($student['mobile']) ?></a>
                                                    </td>
                                                    <td>
                                                <span
                                                            class="badge pending-badge <?= $student['sc_active'] == 0 ? 'course-inactive' : '' ?>">
                                                            <?= htmlspecialchars($student['address'] ?: 'MAIN CENTER') ?>
                                                        </span>
                                                
                                                </td>
                                                    <td>
                                                        <a href="../pdf/marksheet?reg=<?= urlencode($student['reg_no']) ?>"
                                                            target="_blank" class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-eye"></i> View
                                                        </a>
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
                                <button type="submit" class="btn btn-success btn-lg px-5 me-3" id="generateBulkBtn"
                                    <?= empty($students) ? 'disabled' : '' ?>>
                                    <i class="bi bi-card-text me-2"></i>
                                    Generate Selected Marksheets (0)
                                </button>
                                <a href="bulk-generation" class="btn btn-outline-secondary btn-lg px-5">
                                    <i class="bi bi-arrow-left me-2"></i>Back
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
            const generateBtn = document.getElementById('generateBulkBtn');

            function updateGenerateButton() {
                const checkedCount = document.querySelectorAll('.student-checkbox:checked').length;
                if (generateBtn) {
                    generateBtn.innerHTML = `Generate Selected Marksheets (${checkedCount})`;
                    generateBtn.disabled = checkedCount === 0;
                }
            }

            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    checkboxes.forEach(cb => cb.checked = this.checked);
                    updateGenerateButton();
                });
            }

            checkboxes.forEach(cb => {
                cb.addEventListener('change', updateGenerateButton);
            });

            updateGenerateButton();
        });
    </script>

    <?php include '../includes/footer.php'; ?>
</body>

</html>
