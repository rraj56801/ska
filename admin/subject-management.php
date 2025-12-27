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

/* ------------------- ADD SUBJECT ------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $course_code   = trim($_POST['course_code'] ?? '');
    $subject_code  = strtoupper(trim($_POST['subject_code'] ?? '')); // fixed name
    $name          = trim($_POST['name'] ?? '');
    $theory        = (int) ($_POST['theory_marks'] ?? 0);

    // Basic server-side validation
    if ($course_code === '' || $subject_code === '' || $name === '') {
        $message       = 'Please fill in all required fields.';
        $message_class = 'alert-danger';
    } else {
        // Check duplicates within the same course
        $check = $pdo->prepare(
            "SELECT id 
             FROM subjects 
             WHERE course_code = ? 
               AND (UPPER(subject_code) = ? OR UPPER(subject_name) = ?)"
        );
        $check->execute([$course_code, $subject_code, strtoupper($name)]);

        if ($check->rowCount() > 0) {
            $message       = 'Subject already exists for this course!';
            $message_class = 'alert-danger';
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO subjects (course_code, subject_code, subject_name, theory_marks)
                 VALUES (?, ?, ?, ?)"
            );
            if ($stmt->execute([$course_code, $subject_code, $name, $theory])) {
                $message       = '✅ Subject added successfully!';
                $message_class = 'alert-success';
            } else {
                $message       = '❌ Error adding subject!';
                $message_class = 'alert-danger';
            }
        }
    }
}

/* ------------------- UPDATE SUBJECT ------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id           = (int) ($_POST['id'] ?? 0);
    $course_code  = trim($_POST['course_code'] ?? '');
    $subject_code = strtoupper(trim($_POST['subject_code'] ?? ''));
    $name         = trim($_POST['name'] ?? '');
    $theory       = (int) ($_POST['theory_marks'] ?? 0);
    $is_active = isset($_POST['is_active']) ? (int) $_POST['is_active'] : 1; // default Active

    if ($id <= 0 || $course_code === '' || $subject_code === '' || $name === '') {
        $message       = 'Invalid data for update.';
        $message_class = 'alert-danger';
    } else {
        // Optional: prevent duplicates on update as well
        $check = $pdo->prepare(
            "SELECT id 
             FROM subjects 
             WHERE course_code = ? 
               AND (UPPER(subject_code) = ? OR UPPER(subject_name) = ?)
               AND id <> ?"
        );
        $check->execute([$course_code, $subject_code, strtoupper($name), $id]);

        if ($check->rowCount() > 0) {
            $message       = 'Another subject with same code/name already exists for this course!';
            $message_class = 'alert-danger';
        } else {
            $stmt = $pdo->prepare(
                "UPDATE subjects 
                 SET course_code = ?, subject_code = ?, subject_name = ?, theory_marks = ?, is_active = ?
                 WHERE id = ?"
            );
            if ($stmt->execute([$course_code, $subject_code, $name, $theory, $is_active, $id])) {
                $message       = '✅ Subject updated successfully!';
                $message_class = 'alert-success';
            } else {
                $message       = '❌ Error updating subject!';
                $message_class = 'alert-danger';
            }
        }
    }
}

/* ------------------- PAGINATION + FILTER ------------------- */
$per_page = 10;
$page     = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset   = ($page - 1) * $per_page;

// Empty string = All Courses
$course_filter = isset($_GET['course_filter']) ? trim($_GET['course_filter']) : '';

// Get courses for dropdown (single query)
$courses_stmt = $pdo->query(
    "SELECT c.id, c.course_name, c.course_code,
            COUNT(s.id) AS subject_count
     FROM courses c
     LEFT JOIN subjects s ON s.course_code = c.course_code
     GROUP BY c.id, c.course_name, c.course_code
     ORDER BY c.course_name"
);
$courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);

// COUNT TOTAL RESULTS (for pagination), with safe binding
$count_query  = "SELECT COUNT(*) AS total FROM subjects s";
$count_params = [];

if ($course_filter !== '') {
    $count_query .= " WHERE s.course_code = ?";
    $count_params[] = $course_filter;
}

$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($count_params);
$total_subjects = (int) $count_stmt->fetchColumn();
$total_pages    = max(1, (int) ceil($total_subjects / $per_page));

// FETCH SUBJECTS using prepared statement for filter + pagination
$subjects_sql = "SELECT s.*, c.course_name, c.course_code
                 FROM subjects s
                 LEFT JOIN courses c ON s.course_code = c.course_code";

$subjects_params = [];

if ($course_filter !== '') {
    $subjects_sql     .= " WHERE s.course_code = ?";
    $subjects_params[] = $course_filter;
}

$subjects_sql .= " ORDER BY c.course_name, s.subject_code DESC
                   LIMIT ? OFFSET ?";

$subjects_params[] = $per_page;
$subjects_params[] = $offset;

// Force correct parameter types (int) for LIMIT/OFFSET
$stmt = $pdo->prepare($subjects_sql);
$stmt->bindValue(1, $course_filter !== '' ? $subjects_params[0] : null, $course_filter !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
if ($course_filter !== '') {
    $stmt->bindValue(2, $per_page, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
} else {
    $stmt->bindValue(1, $per_page, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
}
$stmt->execute();
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Subjects</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .section-card { box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12); border-radius: 16px; border: none; }
        .card-header-main {
            background: linear-gradient(135deg, #36d1dc, #5b86e5);
            color: white;
            font-weight: 600;
            border-radius: 16px 16px 0 0;
        }
        .btn-rounded { border-radius: 50px; transition: all 0.3s ease; min-width: 44px; height: 44px; display: inline-flex; align-items: center; justify-content: center; }
        .btn-rounded:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.2) !important; }
        .add-btn {
            background: linear-gradient(135deg, #28a745, #20c997) !important;
            border: none;
            box-shadow: 0 6px 20px rgba(40,167,69,0.3);
            font-weight: 600;
            padding: 12px 30px;
        }
        .add-btn:hover {
            background: linear-gradient(135deg, #218838, #1ea391) !important;
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(40,167,69,0.4);
        }
        .pagination-btn { min-width: 45px; }
        .page-item.active .page-link { background: #36d1dc !important; border-color: #36d1dc !important; }
    </style>
</head>
<body>

<div class="container-fluid mt-4 mb-5">

    <?php if ($message): ?>
        <div class="alert <?= htmlspecialchars($message_class) ?> alert-dismissible fade show mx-auto mb-4" style="max-width:600px;">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- FILTER + PAGINATION INFO -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h3 class="mb-2">
                Manage Subjects
                <span class="badge bg-primary fs-5"><?= $total_subjects ?></span>
                <span class="badge bg-warning fs-6 ms-2">Page <?= $page ?> of <?= $total_pages ?></span>
            </h3>
            <small class="text-muted">
                Showing <?= count($subjects) ?> of <?= $total_subjects ?> subjects
                (<?= $page ?> of <?= $total_pages ?> pages)
            </small>
        </div>
        <div class="col-md-6">
            <div class="d-flex justify-content-end gap-2 align-items-center">
                <form method="GET" class="d-flex me-3" style="max-width: 450px;">
                    <input type="hidden" name="page" value="1">
                    <select name="course_filter"
                            class="form-select form-select-lg"
                            onchange="this.form.submit()"
                            style="min-width: 300px;">
                        <option value="" <?= $course_filter === '' ? 'selected' : '' ?>>
                            🎯 All Courses (<?= $total_subjects ?>)
                        </option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= htmlspecialchars($c['course_code']) ?>"
                                <?= $course_filter === $c['course_code'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['course_code']) ?> - <?= htmlspecialchars($c['course_name']) ?>
                                (<?= (int) $c['subject_count'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <?php if ($course_filter !== ''): ?>
                    <a href="?" class="btn btn-outline-danger btn-rounded" title="Clear Filter">
                        <i class="bi bi-x-circle"></i>
                    </a>
                <?php endif; ?>

                <button class="btn btn-lg add-btn shadow" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="bi bi-plus-circle-fill me-2 fs-5"></i>Add Subject
                </button>
            </div>
        </div>
    </div>

    <div class="card section-card">
        <div class="card-header card-header-main d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-journal-text me-2"></i>
                Subjects <?= $course_filter !== '' ? '(Filtered)' : '' ?>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:5%">#</th>
                            <th style="width:12%">Subject Code</th>
                            <th style="width:28%">Subject Name</th>
                            <th style="width:20%">Course</th>
                            <th style="width:10%">Theory</th>
                            <th style="width:10%">Status</th>
                            <th style="width:7%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjects as $i => $s): ?>
                            <tr>
                                <td><strong><?= (($page - 1) * $per_page + $i + 1) ?></strong></td>
                                <td><span class="badge bg-primary fs-6"><?= htmlspecialchars($s['subject_code']) ?></span></td>
                                <td><strong><?= htmlspecialchars($s['subject_name']) ?></strong></td>
                                <td>
                                    <span class="badge bg-info fs-6"><?= htmlspecialchars($s['course_code']) ?></span><br>
                                    <small><?= htmlspecialchars($s['course_name']) ?></small>
                                </td>
                                <td><strong><?= (int) $s['theory_marks'] ?></strong></td>
                                <td>
    <?php
        $isActive = (int)$s['is_active'] === 1;
        $statusText  = $isActive ? 'Active' : 'Inactive';
        $statusClass = $isActive ? 'text-success' : 'text-danger';
    ?>
    <strong class="<?= $statusClass ?>">
        <?= $statusText ?>
    </strong>
</td>

                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-warning btn-rounded"
                                            onclick='editSubject(<?= json_encode($s, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($subjects)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted fs-5">
                                    <?= $course_filter !== '' ? 'No subjects found for selected course' : 'No subjects found' ?>
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
                <nav aria-label="Subjects pagination">
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
                        $end_page   = min($total_pages, $page + 2);

                        if ($start_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link pagination-btn"
                                   href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">1</a>
                            </li>
                            <?php if ($start_page > 2): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link pagination-btn"
                                   href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link pagination-btn"
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

<!-- ADD MODAL -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content section-card">
            <form method="POST">
                <div class="modal-header card-header-main">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add Subject</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Course <span class="text-danger">*</span></label>
                        <select name="course_code" class="form-select form-select-lg" required>
                            <option value="">Select Course</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= htmlspecialchars($c['course_code']) ?>">
                                    <?= htmlspecialchars($c['course_code'] . ' - ' . $c['course_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Subject Code <span class="text-danger">*</span></label>
                        <input type="text" name="subject_code" class="form-control form-control-lg" required maxlength="10">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Subject Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-lg" required>
                    </div>
                    <div class="mb-3 row">
                        <div class="col">
                            <label class="form-label fw-bold">Theory Marks</label>
                            <input type="number" name="theory_marks" class="form-control form-control-lg" min="0" max="100" value="70" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal" type="button">Cancel</button>
                    <button class="btn btn-success btn-rounded px-4" name="add" type="submit">
                        <i class="bi bi-check-circle me-1"></i>Add Subject
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content section-card">
            <form method="POST">
                <div class="modal-header card-header-main">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Subject</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Course <span class="text-danger">*</span></label>
                        <select name="course_code" id="edit_course_code" class="form-select form-select-lg" required>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= htmlspecialchars($c['course_code']) ?>">
                                    <?= htmlspecialchars($c['course_code'] . ' - ' . $c['course_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Subject Code <span class="text-danger">*</span></label>
                        <input type="text" name="subject_code" id="edit_code" class="form-control form-control-lg" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Subject Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_name" class="form-control form-control-lg" required>
                    </div>
                    <div class="mb-3 row">
                        <div class="col">
                            <label class="form-label fw-bold">Theory Marks</label>
                            <input type="number" name="theory_marks" id="edit_theory"
                                   class="form-control form-control-lg" min="0" max="100" required>
                        </div>
                        <div class="col">
    <label class="form-label fw-bold">Status</label>
    <select name="is_active" id="edit_status" class="form-select form-select-lg" required>
        <option value="1">Active</option>
        <option value="0">Inactive</option>
    </select>
</div>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update" class="btn btn-primary btn-rounded px-4">
                        <i class="bi bi-save me-1"></i>Update Subject
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editSubject(s) {
    document.getElementById('edit_id').value         = s.id;
    document.getElementById('edit_code').value       = s.subject_code;
    document.getElementById('edit_name').value       = s.subject_name;
    document.getElementById('edit_course_code').value= s.course_code;
    document.getElementById('edit_theory').value     = s.theory_marks;
    document.getElementById('edit_status').value = String(s.is_active ? 1 : 0);

    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
