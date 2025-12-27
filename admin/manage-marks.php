<?php
session_start();
include '../includes/db.php';
require_once __DIR__ . '/../includes/anti_inspect.php';

if (!isset($_SESSION['admin'])) {
    include(__DIR__ . '/../forbidden.php');
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['update_marks'])) {
        $result_id = (int) $_POST['result_id'];
        $stmt = $pdo->prepare("UPDATE results SET 
            theory_marks = ?, total_theory_marks = ?, result_status = ?, result_date = ?
            WHERE id = ?");
        $stmt->execute([
            (float) $_POST['theory_marks'],
            (float) $_POST['total_theory_marks'],
            trim($_POST['result_status']),
            $_POST['result_date'],
            $result_id
        ]);
        $_SESSION['success'] = "Marks updated successfully!";
    }

    if (isset($_POST['delete_marks'])) {
        $result_id = (int) $_POST['result_id'];
        $stmt = $pdo->prepare("DELETE FROM results WHERE id = ?");
        $stmt->execute([$result_id]);
        $_SESSION['success'] = "Marks deleted successfully!";
    }

    header("Location: " . $_SERVER['PHP_SELF'] .
        (isset($_GET['course_code']) ? '?course_code=' . urlencode($_GET['course_code']) : '') .
        (isset($_GET['session']) ? (isset($_GET['course_code']) ? '&session=' . urlencode($_GET['session']) : '?session=' . urlencode($_GET['session'])) : ''));
    exit();
}

// Get filters - course_code as string
$course_code = isset($_GET['course_code']) ? trim($_GET['course_code']) : '';
$session_filter = isset($_GET['session']) ? trim($_GET['session']) : '';

// Fetch courses
$courses = $pdo->query("SELECT id, course_code, course_name FROM courses ORDER BY course_name")->fetchAll();

// Fetch sessions
$sessions = $pdo->query("SELECT DISTINCT exam_held_on FROM results ORDER BY exam_held_on DESC")->fetchAll(PDO::FETCH_COLUMN);

// Fetch selected course
$selected_course = null;
if (!empty($course_code)) {
    $stmt = $pdo->prepare("SELECT id, course_code, course_name FROM courses WHERE course_code = ?");
    $stmt->execute([$course_code]);
    $selected_course = $stmt->fetch();
}


// Fetch RESULTS - course_code as string with proper session filter
$results_sql = "
    SELECT r.*, s.student_name, s.course_code, sub.subject_code, sub.subject_name
    FROM results r
    LEFT JOIN students s ON r.reg_no = s.reg_no
    LEFT JOIN subjects sub ON r.subject_code = sub.subject_code
";
$results_params = [];

if (!empty($course_code) && !empty($session_filter)) {
    // Both filters
    $results_sql .= " WHERE s.course_code = ? AND r.exam_held_on = ?";
    $results_params = [$course_code, $session_filter];
} elseif (!empty($course_code)) {
    // Course only
    $results_sql .= " WHERE s.course_code = ?";
    $results_params[] = $course_code;
} elseif (!empty($session_filter)) {
    // Session only
    $results_sql .= " WHERE r.exam_held_on = ?";
    $results_params[] = $session_filter;
}

$results_sql .= " ORDER BY s.student_name, r.exam_held_on DESC, sub.subject_code";


$stmt = $pdo->prepare($results_sql);
$stmt->execute($results_params);
$results = $stmt->fetchAll();

// Fetch STUDENTS - course_code as string, correct join
$students_sql = "
    SELECT DISTINCT s.reg_no, s.student_name, s.course_code, c.course_name
    FROM students s 
    LEFT JOIN courses c ON s.course_code = c.course_code
";
$students_params = [];

if (!empty($course_code) && !empty($session_filter)) {
    // Both filters
    $students_sql .= " WHERE s.course_code = ? 
                       AND EXISTS (
                           SELECT 1 FROM results r 
                           WHERE r.reg_no = s.reg_no 
                             AND r.exam_held_on = ?
                       )";
    $students_params = [$course_code, $session_filter];
} elseif (!empty($course_code)) {
    // Course only
    $students_sql .= " WHERE s.course_code = ?";
    $students_params = [$course_code];
} elseif (!empty($session_filter)) {
    // Session only
    $students_sql .= " WHERE EXISTS (
                           SELECT 1 FROM results r 
                           WHERE r.reg_no = s.reg_no 
                             AND r.exam_held_on = ?
                       )";
    $students_params = [$session_filter];
}

$students_sql .= " ORDER BY s.student_name";

$stmt = $pdo->prepare($students_sql);
$stmt->execute($students_params);
$students = $stmt->fetchAll();
?>

<?php include 'header.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Marksheet</title>
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

        .filter-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
        }

        .action-btn {
            border-radius: 8px;
        }

        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>

<body class="bg-light">

    <div class="container mt-4 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-header bg-gradient bg-primary text-white text-center py-4">
                        <h2 class="mb-0"><i class="bi bi-clipboard-data me-3"></i>Manage Marksheet</h2>
                        <p class="mb-0 opacity-75 mt-2">Update, View or Delete student marks</p>
                    </div>

                    <div class="card-body p-4">
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <strong>Success!</strong> <?= $_SESSION['success'] ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['success']); ?>
                        <?php endif; ?>

                        <!-- Filters -->
                        <div class="filter-card mb-4">
                            <form method="GET" class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Select Course</label>
                                    <select name="course_code" class="form-select">
                                        <option value="">All Courses</option>
                                        <?php foreach ($courses as $course): ?>
                                            <option value="<?= htmlspecialchars($course['course_code']) ?>" <?= ($course_code === $course['course_code']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($course['course_code']) ?> - <?= htmlspecialchars($course['course_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Exam Held On</label>
                                    <select name="session" class="form-select">
                                        <option value="">All Dates</option>
                                        <?php foreach ($sessions as $session): ?>
                                            <option value="<?= htmlspecialchars($session) ?>" <?= ($session_filter == $session) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($session) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 text-end">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="bi bi-search me-2"></i>Filter Results
                                    </button>
                                    <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-clockwise me-2"></i>Reset
                                    </a>
                                </div>
                            </form>
                        </div>

                        <?php if (!empty($results)): ?>
                            <!-- Results Table -->
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Student</th>
                                            <th>Reg No</th>
                                            <th>Subject</th>
                                            <th>Exam Held On</th>
                                            <th>Theory</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($results as $result): ?>
                                            <tr data-result='<?= json_encode([
                                                'id' => $result['id'],
                                                'theory_marks' => $result['theory_marks'],
                                                'total_theory_marks' => $result['total_theory_marks'],
                                                'result_status' => $result['result_status'],
                                                'result_date' => date('Y-m-d\TH:i', strtotime($result['result_date']))
                                            ]) ?>'>
                                                <td><strong><?= htmlspecialchars($result['student_name']) ?></strong></td>
                                                <td><span class="badge bg-secondary"><?= htmlspecialchars($result['reg_no']) ?></span></td>
                                                <td><?= htmlspecialchars($result['subject_code']) ?> - <?= htmlspecialchars($result['subject_name']) ?></td>
                                                <td><?= htmlspecialchars($result['exam_held_on']) ?></td>
                                                <td><strong><?= $result['theory_marks'] ?>/<?= $result['total_theory_marks'] ?></strong></td>
                                                <td><span class="badge bg-<?= $result['result_status'] == 'PASS' ? 'success' : 'danger' ?>"><?= htmlspecialchars($result['result_status']) ?></span></td>
                                                <td><?= date('d-M-Y', strtotime($result['result_date'])) ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary action-btn edit-marks" title="Edit">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <button class="btn btn-outline-danger action-btn delete-confirm-btn"
                                                            data-id="<?= $result['id'] ?>"
                                                            data-student="<?= htmlspecialchars($result['student_name']) ?>"
                                                            data-subject="<?= htmlspecialchars($result['subject_code'] . ' - ' . $result['subject_name']) ?>"
                                                            data-session="<?= htmlspecialchars($result['exam_held_on']) ?>"
                                                            title="Delete">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-clipboard-data display-1 text-muted mb-4"></i>
                                <h4 class="text-muted">No Marks Found</h4>
                                <p class="lead text-muted">Try adjusting your filters above.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editMarksModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Marks</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="result_id" id="edit_result_id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Theory Marks <span class="required">*</span></label>
                                <input type="number" step="0.01" min="0" name="theory_marks" id="edit_theory_marks" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Total Theory <span class="required">*</span></label>
                                <input type="number" step="0.01" min="1" name="total_theory_marks" id="edit_total_theory_marks" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Status <span class="required">*</span></label>
                                <select name="result_status" id="edit_result_status" class="form-select" required>
                                    <option value="PASS">PASS</option>
                                    <option value="FAIL">FAIL</option>
                                    <option value="PENDING">PENDING</option>
                                    <option value="ABSENT">ABSENT</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Result Date <span class="required">*</span></label>
                                <input type="datetime-local" name="result_date" id="edit_result_date" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_marks" class="btn btn-success">
                            <i class="bi bi-check-circle me-2"></i>Update Marks
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Delete</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <i class="bi bi-trash3-fill fs-1 text-danger mb-3"></i>
                    <h4 class="text-danger mb-2">Are you sure?</h4>
                    <p class="lead text-muted mb-4">This action cannot be undone.</p>
                    <div class="alert alert-warning">
                        <h6 class="mb-2"><i class="bi bi-info-circle me-2 text-warning"></i>Deleting:</h6>
                        <div id="deleteDetails" class="text-start small"></div>
                    </div>
                    <form method="POST" id="deleteForm">
                        <input type="hidden" name="result_id" id="delete_result_id">
                    </form>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </button>
                    <button type="submit" form="deleteForm" name="delete_marks" class="btn btn-danger">
                        <i class="bi bi-trash-fill me-2"></i>Yes, Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.edit-marks').forEach(btn => {
            btn.addEventListener('click', function () {
                const tr = this.closest('tr');
                const resultData = JSON.parse(tr.dataset.result);
                document.getElementById('edit_result_id').value = resultData.id;
                document.getElementById('edit_theory_marks').value = resultData.theory_marks;
                document.getElementById('edit_total_theory_marks').value = resultData.total_theory_marks;
                document.getElementById('edit_result_status').value = resultData.result_status;
                document.getElementById('edit_result_date').value = resultData.result_date;
                new bootstrap.Modal(document.getElementById('editMarksModal')).show();
            });
        });

        document.querySelectorAll('.delete-confirm-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                document.getElementById('deleteDetails').innerHTML = `
            <div class="row g-2">
                <div class="col-6"><strong>Student:</strong></div>
                <div class="col-6">${this.dataset.student}</div>
                <div class="col-6"><strong>Subject:</strong></div>
                <div class="col-6">${this.dataset.subject}</div>
                <div class="col-6"><strong>Session:</strong></div>
                <div class="col-6">${this.dataset.session}</div>
            </div>
        `;
                document.getElementById('delete_result_id').value = this.dataset.id;
                new bootstrap.Modal(document.getElementById('deleteConfirmModal')).show();
            });
        });
    </script>

    <?php include '../includes/footer.php'; ?>
</body>

</html>
