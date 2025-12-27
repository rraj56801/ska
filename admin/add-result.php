<?php
session_start();
include '../includes/db.php';
require_once __DIR__ . '/../includes/anti_inspect.php';

if (!isset($_SESSION['admin'])) {
    include(__DIR__ . '/../forbidden.php');
    exit();
}

// Validate registration number
if (!isset($_GET['reg']) || empty($_GET['reg'])) {
    die("Invalid student");
}
$reg_no = $_GET['reg'];

// Fetch student data with their enrolled course
$stmt = $pdo->prepare("
    SELECT s.*, c.course_code, c.course_name 
    FROM students s
    LEFT JOIN courses c ON s.course_code = c.course_code
    WHERE s.reg_no = ?
");
$stmt->execute([$reg_no]);
$student = $stmt->fetch();

if (!$student)
    die("Student not found");

// Fetch subjects for this course
$subjects_stmt = $pdo->prepare("
    SELECT course_code, subject_code, subject_name, theory_marks
    FROM subjects
    WHERE course_code = ?
    ORDER BY subject_code, subject_name
");
$subjects_stmt->execute([$student['course_code']]);
$subjects = $subjects_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = [
        'reg_no' => trim($_POST['reg_no']),
        'course_code' => trim($_POST['course_code']),
        'subject_code' => trim($_POST['subject_code']),
        'exam_held_on' => trim($_POST['exam_held_on']),
        'theory_marks' => (float) $_POST['theory_marks'],
        'total_theory_marks' => (float) $_POST['total_theory_marks'],
        'result_status' => trim($_POST['result_status']),
        'result_date' => $_POST['result_date']
    ];

    $sql = "INSERT INTO results 
        (reg_no, course_code, subject_code, exam_held_on, theory_marks, total_theory_marks, 
         result_status, result_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($data));

    $_SESSION['success'] = "Result added successfully for " . htmlspecialchars($student['student_name']);
    header("Location: add-result?reg=" . urlencode($reg_no));
    exit();
}
?>

<?php include 'header.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add Result - <?= htmlspecialchars($student['student_name']) ?></title>

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

        .badge-reg {
            font-size: 1.1rem;
            padding: 0.5rem 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .course-info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 10px;
            border-left: 5px solid #2196f3;
        }

        .small-muted {
            font-size: 0.85rem;
            color: #6c757d;
        }
    </style>
</head>

<body class="bg-light">

    <div class="container mt-4 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-lg border-0 rounded-4">
           <div class="card-header bg-gradient bg-primary text-white text-center py-4">
    <h3 class="mb-1">
        Add Result 
        <span class="badge bg-light text-dark badge-reg ms-3">
            <?= htmlspecialchars($reg_no) ?>
        </span>
    </h3>
    <p class="mb-0 opacity-75 mt-2 h4">
        <?= htmlspecialchars($student['student_name']) ?>
    </p>
</div>


                    <div class="card-body p-5">
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <strong>Success!</strong> <?= $_SESSION['success'] ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['success']); ?>
                        <?php endif; ?>

                        <!-- Enrolled Course Display -->
                        <div class="course-info mb-4">
                            <h6 class="mb-2"><i class="bi bi-book text-primary me-2"></i>Enrolled Course:</h6>
                            <h5 class="text-primary mb-0"><?= htmlspecialchars($student['course_name']) ?></h5>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="reg_no" value="<?= htmlspecialchars($reg_no) ?>">
                            <input type="hidden" name="course_code" value="<?= $student['course_code'] ?>">

                            <h5 class="section-title">Exam Details</h5>
                            <div class="row g-4 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">Course</label>
                                    <input type="text" class="form-control"
                                        value="<?= htmlspecialchars($student['course_name']) ?>"
                                        data-bs-toggle="tooltip" data-bs-placement="top" data-bs-container="body"
                                        title="<?= htmlspecialchars($student['course_name']) ?>" readonly>

                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Subject <span class="required">*</span></label>
                                    <select name="subject_code" id="subject_code" class="form-select" required>
                                        <option value="">Select Subject</option>
                                        <?php foreach ($subjects as $sub): ?>
                                            <option value="<?= $sub['subject_code'] ?>"
                                                data-total-theory="<?= (float) $sub['theory_marks'] ?>">
                                                <?= htmlspecialchars($sub['subject_code']) ?> -
                                                <?= htmlspecialchars($sub['subject_name']) ?>
                                                (Th: <?= (float) $sub['theory_marks'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="small-muted mt-1">
                                        Total marks will auto-populate based on subject.
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Exam Held On <span class="required">*</span></label>
                                    <input type="date" class="form-control" name="exam_held_on"  value="<?= date('Y-m-d') ?>" required>  
                                    <small class="text-muted">Select the date when exam was conducted</small>
                                </div>
                                
                            </div>

                            <h5 class="section-title">Marks Details</h5>
                            <div class="row g-4 mb-4">
                                <div class="col-md-3">
                                    <label class="form-label">Theory Marks <span class="required">*</span></label>
                                    <input type="number" step="0.01" min="0" name="theory_marks" class="form-control"
                                        required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Total Theory <span class="required">*</span></label>
                                    <input type="number" step="0.01" min="1" name="total_theory_marks"
                                        id="total_theory_marks" class="form-control" required>
                                </div>

                            </div>

                            <h5 class="section-title">Result</h5>
                            <div class="row g-4 mb-5">

                                <div class="col-md-4">
                                    <label class="form-label">Result Status <span class="required">*</span></label>
                                    <select name="result_status" class="form-select" required>
                                        <option value="">Select Status</option>
                                        <option value="PASS">PASS</option>
                                        <option value="FAIL">FAIL</option>
                                        <option value="PENDING">PENDING</option>
                                        <option value="ABSENT">ABSENT</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Result Date <span class="required">*</span></label>
                                    <input type="datetime-local" name="result_date" class="form-control"
                                        value="<?= date('Y-m-d\TH:i') ?>" required>
                                </div>
                            </div>

                            <div class="text-end">
                                <a href="view-student?reg=<?= urlencode($reg_no) ?>"
                                    class="btn btn-secondary btn-lg px-5">
                                    <i class="bi bi-arrow-left me-2"></i>Back to Student
                                </a>
                                <button type="submit" class="btn btn-success btn-lg px-5 ms-3">
                                    <i class="bi bi-check-circle me-2"></i>Add Result
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.getElementById('subject_code').addEventListener('change', function () {
            const selectedOption = this.options[this.selectedIndex];
            const totalTheory = selectedOption.getAttribute('data-total-theory');

            document.getElementById('total_theory_marks').value = totalTheory || '';
        });
    </script>

    <?php include '../includes/footer.php'; ?>
</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    });
</script>

</html>