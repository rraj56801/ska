<?php
session_start();
include '../includes/db.php';
require_once __DIR__ . '/../includes/anti_inspect.php';

if (!isset($_SESSION['admin'])) {
    include(__DIR__ . '/../forbidden.php');
    exit();
}

// Fetch all courses
$courses_stmt = $pdo->prepare("SELECT course_code, course_name FROM courses ORDER BY course_name");
$courses_stmt->execute();
$courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);


$sessions_stmt = $pdo->query("SELECT DISTINCT session_year FROM students ORDER BY session_year DESC");
$sessions = $sessions_stmt->fetchAll(PDO::FETCH_COLUMN);

// Handle bulk save
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['bulk_save'])) {
    $course_code = trim($_POST['course_code']);
    $subject_code = trim($_POST['subject_code']);
    $result_date = $_POST['result_date'] ?? date('Y-m-d H:i:s');
    $exam_held_on = trim($_POST['exam_held_on'] ?? '');

    $success_count = 0;
    $error_count = 0;

    if (!empty($_POST['students'])) {
        foreach ($_POST['students'] as $reg_no => $data) {
            $reg_no = trim($reg_no);
            $theory_marks = (float) ($data['theory_marks'] ?? 0);
            $total_theory_marks = (float) ($data['total_theory_marks'] ?? 0);
            $result_status = trim($data['result_status'] ?? 'PENDING');

            if ($theory_marks <= 0 && $result_status === 'PENDING') {
                continue;
            }

            // Check if result exists
            $check_stmt = $pdo->prepare("
                SELECT id FROM results 
                WHERE reg_no = ? AND course_code = ? AND subject_code = ? AND exam_held_on = ?
            ");
            $check_stmt->execute([$reg_no, $course_code, $subject_code, $exam_held_on]);
            $existing = $check_stmt->fetch();

            if ($existing) {
                $update_stmt = $pdo->prepare("
                    UPDATE results SET 
                        theory_marks = ?, total_theory_marks = ?, result_status = ?, result_date = ?, exam_held_on = ?
                    WHERE reg_no = ? AND course_code = ? AND subject_code = ? AND exam_held_on = ?
                ");
                $res = $update_stmt->execute([
                    $theory_marks,
                    $total_theory_marks,
                    $result_status,
                    $result_date,
                    $exam_held_on,
                    $reg_no,
                    $course_code,
                    $subject_code,
                    $exam_held_on
                ]);
            } else {
                $insert_stmt = $pdo->prepare("
                    INSERT INTO results 
                    (reg_no, course_code, subject_code, theory_marks, total_theory_marks, result_status, result_date, exam_held_on)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $res = $insert_stmt->execute([
                    $reg_no,
                    $course_code,
                    $subject_code,
                    $theory_marks,
                    $total_theory_marks,
                    $result_status,
                    $result_date,
                    $exam_held_on
                ]);
            }

            if ($res) {
                $success_count++;
            } else {
                $error_count++;
            }
        }
    }

    if ($success_count > 0) {
        $_SESSION['success'] = "Bulk update completed: $success_count result(s) saved for subject $subject_code.";
    }
    if ($error_count > 0) {
        $_SESSION['error'] = "$error_count result(s) could not be saved.";
    }

    header("Location: add-bulk-result?course_code=" . urlencode($course_code) . "&subject_code=" . urlencode($subject_code));
    exit();
}

// Pagination
$per_page = 20;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * $per_page;

// Initial state from GET
$selected_course_code = $_GET['course_code'] ?? '';
$selected_subject_code = $_GET['subject_code'] ?? '';
$selected_session = $_GET['session'] ?? '';

// State from POST (for Load Subjects / Load Students)
$action = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_course = $_POST['course_code'] ?? '';
    $posted_subject = $_POST['subject_code'] ?? '';
    $posted_session = $_POST['session'] ?? '';
    $action = $_POST['action'] ?? '';

    // Update course and session
    $selected_course_code = $posted_course;
    $selected_session = $posted_session;

    // Handle actions
    if ($action === 'load_subjects') {
        // When loading subjects, clear the subject selection
        $selected_subject_code = '';
    } elseif ($action === 'load_students') {
        // When loading students, use the posted subject
        $selected_subject_code = $posted_subject;
    } else {
        // For other POST requests (like bulk_save), keep the posted subject
        $selected_subject_code = $posted_subject;
    }
}

$subjects = [];
$students = [];
$total_students = 0;
$total_pages = 1;

// Load subjects if a course is selected
if ($selected_course_code !== '') {
    $subjects_stmt = $pdo->prepare("
        SELECT subject_code, subject_name, theory_marks
        FROM subjects
        WHERE course_code = ? AND is_active = 1
        ORDER BY subject_code
    ");
    $subjects_stmt->execute([$selected_course_code]);
    $subjects = $subjects_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Load students only when Load Students is clicked and subject + session are chosen
if ($action === 'load_students' && $selected_course_code !== '' && $selected_subject_code !== '' && $selected_session !== '') {
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE course_code = ? AND session_year = ?");
    $count_stmt->execute([$selected_course_code, $selected_session]);
    $total_students = (int) $count_stmt->fetchColumn();
    $total_pages = max(1, ceil($total_students / $per_page));

    $students_stmt = $pdo->prepare("
        SELECT reg_no, student_name, course_code
        FROM students
        WHERE course_code = ? AND session_year = ?
        ORDER BY student_name
        LIMIT ? OFFSET ?
    ");
    $students_stmt->bindValue(1, $selected_course_code, PDO::PARAM_STR);
    $students_stmt->bindValue(2, $selected_session, PDO::PARAM_STR);
    $students_stmt->bindValue(3, $per_page, PDO::PARAM_INT);
    $students_stmt->bindValue(4, $offset, PDO::PARAM_INT);
    $students_stmt->execute();
    $students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);
}

include 'header.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bulk Update Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #f3f6ff;
        }

        .form-label {
            font-weight: 600;
            color: #34495e;
            font-size: 0.9rem;
        }

        .section-title {
            color: #0d6efd;
            border-left: 4px solid #0d6efd;
            padding-left: 10px;
            margin: 25px 0 20px;
            font-weight: 600;
        }

        .card {
            border-radius: 1.25rem;
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #0d6efd, #6610f2);
        }

        .card-body {
            background: #ffffff;
        }

        .table th {
            background-color: #f8fafc;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .table td {
            vertical-align: middle;
            font-size: 0.92rem;
        }

        .pagination {
            margin: 20px 0;
            justify-content: center;
        }

        .badge {
            border-radius: 50rem;
            padding: 0.4rem 0.75rem;
            font-size: 0.75rem;
        }

        .filter-card {
            background: #f8f9ff;
            border: 1px solid #e0e4ff;
        }

        .form-select-sm,
        .form-control {
            border-radius: 0.7rem;
        }

        .btn {
            border-radius: 0.7rem;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container mt-4 mb-5">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-11">
                <div class="card shadow-lg border-0 rounded-4">
           <div class="card-header text-white py-3">
    <div class="d-flex align-items-center justify-content-between">
        <div class="text-center text-md-start flex-grow-1">
            <h3 class="mb-1 text-white">Bulk Update Results</h3>

            <!-- Full steps on md and up -->
            <p class="mb-0 d-none d-md-block" style="font-size: 0.9rem;">
                Step 1: Load subjects · Step 2: Load students · Step 3: Save results
            </p>

            <!-- Short text on small screens -->
            <p class="mb-0 d-block d-md-none" style="font-size: 0.8rem;">
                Steps: Load subjects, students, then save.
            </p>
        </div>

        <a href="manage-marks.php" class="btn btn-light btn-sm ms-3 ms-md-4">
            <i class="bi bi-list-check me-1"></i> Manage Marks
        </a>
    </div>
</div>


                    <div class="card-body p-4 p-md-5">

                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show mb-3">
                                <strong>Success!</strong> <?= $_SESSION['success'] ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['success']); ?>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show mb-3">
                                <strong>Error!</strong> <?= $_SESSION['error'] ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['error']); ?>
                        <?php endif; ?>

                        <!-- Step helper text -->
                        <div class="mb-4">
                            <div class="d-flex flex-wrap gap-2 small">
                                <span class="badge bg-primary">Step 1</span>
                                <span>Select a course and click "Load Subjects".</span>
                            </div>
                            <div class="d-flex flex-wrap gap-2 small mt-1">
                                <span class="badge bg-success">Step 2</span>
                                <span>Select a subject and session, then click "Load Students".</span>
                            </div>
                        </div>

                        <!-- Filter Form -->
                        <form method="POST" class="filter-card rounded-4 p-3 p-md-4 mb-4">
                            <div class="row g-3 align-items-end">

                                <!-- Course -->
                                <div class="col-md-4">
                                    <label class="form-label mb-1">Course <span class="text-danger">*</span></label>
                                    <select name="course_code" class="form-select form-select-sm" required>
                                        <option value="">Select Course</option>
                                        <?php foreach ($courses as $course): ?>
                                            <option value="<?= htmlspecialchars($course['course_code']) ?>"
                                                <?= $selected_course_code === $course['course_code'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($course['course_code']) ?> -
                                                <?= htmlspecialchars($course['course_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Choose course, then click Load Subjects.</small>
                                </div>

                                <!-- Subject -->
                                <div class="col-md-4">
                                    <label class="form-label mb-1">Subject <span class="text-danger">*</span></label>
                                    <select name="subject_code"
                                        class="form-select form-select-sm"
                                        <?= empty($subjects) ? 'disabled' : '' ?>>
                                    <option value="">
                                        <?= empty($subjects) ? 'Load subjects first' : 'Select Subject' ?>
                                    </option>
                                    <?php foreach ($subjects as $subject): ?>
                                        <option value="<?= htmlspecialchars($subject['subject_code']) ?>"
                                            <?= $selected_subject_code === $subject['subject_code'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($subject['subject_code']) ?> - <?= htmlspecialchars($subject['subject_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                    <small class="text-muted">
                                        Subjects appear after clicking Load Subjects.
                                    </small>
                                </div>

                                <!-- Session -->
                                <div class="col-md-4">
                                    <label class="form-label mb-1">Session <span class="text-danger">*</span></label>
                                    <select name="session" class="form-select form-select-sm" required>
                                        <option value="">Select Session</option>
                                        <?php foreach ($sessions as $session): ?>
                                            <option value="<?= htmlspecialchars($session) ?>"
                                                <?= $selected_session === $session ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($session) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Students will be filtered by this session.</small>
                                </div>

                                <!-- Buttons -->
                                <div class="col-12">
                                    <div class="d-flex flex-column flex-md-row gap-2">
                                        <button type="submit" name="action" value="load_subjects"
                                            class="btn btn-outline-primary w-100">
                                            <i class="bi bi-journal-text me-1"></i> Load Subjects
                                        </button>
                                        <button type="submit" name="action" value="load_students"
                                            class="btn btn-primary w-100" <?= empty($subjects) ? 'disabled' : '' ?>>
                                            <i class="bi bi-people-fill me-1"></i> Load Students
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <!-- Students + Save form -->
                        <?php if (!empty($students) && $selected_course_code && $selected_subject_code && $selected_session): ?>
                            <h5 class="section-title">
                                Update Results for Subject: <?= htmlspecialchars($selected_subject_code) ?>
                            </h5>
                            <form method="POST">
                                <input type="hidden" name="course_code"
                                    value="<?= htmlspecialchars($selected_course_code) ?>">
                                <input type="hidden" name="subject_code"
                                    value="<?= htmlspecialchars($selected_subject_code) ?>">
                                <input type="hidden" name="bulk_save" value="1">

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Result Date <span class="text-danger">*</span></label>
                                        <input type="datetime-local" name="result_date" class="form-control form-control-sm"
                                            value="<?= date('Y-m-d\TH:i') ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Exam Month <span class="text-danger">*</span></label>
                                        <input type="date" name="exam_held_on" class="form-control form-control-sm"
                                            value="<?= date('Y-m-d') ?>" required>
                                        <small class="text-muted">Select the date when exam was conducted</small>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Reg No</th>
                                                <th>Student Name</th>
                                                <th>Theory Marks</th>
                                                <th>Total Theory</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($students as $student): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($student['reg_no']) ?></td>
                                                    <td><?= htmlspecialchars($student['student_name']) ?></td>
                                                    <td>
                                                        <input type="number" step="0.5" min="0"
                                                            name="students[<?= htmlspecialchars($student['reg_no']) ?>][theory_marks]"
                                                            class="form-control form-control-sm" value="0">
                                                    </td>
                                                    <?php $selected_subject_theory_marks = 0;
                                                    foreach ($subjects as $subject) {
                                                        if ($subject['subject_code'] === $selected_subject_code) {
                                                            $selected_subject_theory_marks = $subject['theory_marks'];
                                                            break;
                                                        }
                                                    }
                                                    ?>

                                                    <td>
                                                        <input type="number" step="0.05" min="1"
                                                            name="students[<?= htmlspecialchars($student['reg_no']) ?>][total_theory_marks]"
                                                            class="form-control form-control-sm"
                                                            value="<?= htmlspecialchars($selected_subject_theory_marks) ?>">
                                                    </td>

                                                    <td>
                                                        <select
                                                            name="students[<?= htmlspecialchars($student['reg_no']) ?>][result_status]"
                                                            class="form-select form-select-sm">
                                                            <option value="PASS">PASS</option>
                                                            <option value="FAIL">FAIL</option>
                                                            <option value="PENDING">PENDING</option>
                                                            <option value="ABSENT">ABSENT</option>
                                                        </select>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <?php if ($total_pages > 1): ?>
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination">
                                            <?php if ($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link"
                                                        href="?course_code=<?= urlencode($selected_course_code) ?>&subject_code=<?= urlencode($selected_subject_code) ?>&session=<?= urlencode($selected_session) ?>&page=<?= $page - 1 ?>">
                                                        Previous
                                                    </a>
                                                </li>
                                            <?php else: ?>
                                                <li class="page-item disabled"><span class="page-link">Previous</span></li>
                                            <?php endif; ?>

                                            <?php
                                            $start = max(1, $page - 2);
                                            $end = min($total_pages, $start + 4);
                                            if ($end - $start < 4) {
                                                $start = max(1, $end - 4);
                                            }
                                            for ($i = $start; $i <= $end; $i++): ?>
                                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                                    <a class="page-link"
                                                        href="?course_code=<?= urlencode($selected_course_code) ?>&subject_code=<?= urlencode($selected_subject_code) ?>&session=<?= urlencode($selected_session) ?>&page=<?= $i ?>">
                                                        <?= $i ?>
                                                    </a>
                                                </li>
                                            <?php endfor; ?>

                                            <?php if ($page < $total_pages): ?>
                                                <li class="page-item">
                                                    <a class="page-link"
                                                        href="?course_code=<?= urlencode($selected_course_code) ?>&subject_code=<?= urlencode($selected_subject_code) ?>&session=<?= urlencode($selected_session) ?>&page=<?= $page + 1 ?>">
                                                        Next
                                                    </a>
                                                </li>
                                            <?php else: ?>
                                                <li class="page-item disabled"><span class="page-link">Next</span></li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                <?php endif; ?>

                                <div class="text-end">
                                    <a href="dashboard.php" class="btn btn-secondary btn-lg px-4">
                                        <i class="bi bi-arrow-left me-2"></i>Back
                                    </a>
                                    <button type="submit" class="btn btn-success btn-lg px-4 ms-3">
                                        <i class="bi bi-save me-2"></i>Save All Results
                                    </button>
                                </div>
                            </form>

                        <?php elseif ($action === 'load_students' && $selected_course_code && $selected_subject_code && $selected_session && empty($students)): ?>
                            <div class="alert alert-info mt-3">No students found for this course and session.</div>

                        <?php elseif ($selected_course_code && empty($subjects) && $action !== ''): ?>
                            <div class="alert alert-warning mt-3">No active subjects found for this course.</div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>