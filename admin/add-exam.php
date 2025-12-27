<?php
session_start();
include '../includes/db.php';
require_once __DIR__ . '/../includes/anti_inspect.php';

if (!isset($_SESSION['admin'])) {
    include(__DIR__ . '/../forbidden.php');
    exit();
}

// Fetch active courses
$courses_stmt = $pdo->query("SELECT course_code, course_name FROM courses WHERE is_active = 1 ORDER BY course_name");
$courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all subjects grouped by course code
$subjects_stmt = $pdo->query("
    SELECT course_code, subject_code, subject_name 
    FROM subjects 
    WHERE is_active = 1 
    ORDER BY course_code, subject_code
");
$all_subjects = $subjects_stmt->fetchAll(PDO::FETCH_ASSOC);

// Group subjects by course code
$subjects_by_course = [];
foreach ($all_subjects as $subject) {
    $subjects_by_course[$subject['course_code']][] = $subject;
}

// Check if editing [web:45][web:46]
$edit_mode = false;
$edit_data = [];
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $edit_stmt = $pdo->prepare("SELECT * FROM exam_schedule WHERE id = ?");
    $edit_stmt->execute([$_GET['edit_id']]);
    $edit_data = $edit_stmt->fetch();
}

// Handle form submission [web:45][web:46]
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = [
        'course_code' => trim($_POST['course_code']),
        'subject_code' => trim($_POST['subject_code']),
        'exam_date' => trim($_POST['exam_date']),
        'exam_time' => trim($_POST['exam_time']),
        'is_scheduled' => isset($_POST['is_scheduled']) ? 1 : 0
    ];

    if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
        // Update existing schedule [web:46]
        $update_sql = "UPDATE exam_schedule 
                       SET course_code = ?, subject_code = ?, exam_date = ?, exam_time = ?, is_scheduled = ?
                       WHERE id = ?";
        try {
            $stmt = $pdo->prepare($update_sql);
            $stmt->execute([
                $data['course_code'],
                $data['subject_code'],
                $data['exam_date'],
                $data['exam_time'],
                $data['is_scheduled'],
                $_POST['edit_id']
            ]);
            $_SESSION['success'] = "Exam schedule updated successfully!";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $_SESSION['error'] = "Duplicate exam schedule! This combination already exists.";
            } else {
                $_SESSION['error'] = "Error: " . $e->getMessage();
            }
        }
    } else {
        // Check for duplicate exam schedule [web:45]
        $check_stmt = $pdo->prepare("
            SELECT COUNT(*) FROM exam_schedule 
            WHERE course_code = ? AND subject_code = ? AND exam_date = ? AND exam_time = ?
        ");
        $check_stmt->execute([
            $data['course_code'],
            $data['subject_code'],
            $data['exam_date'],
            $data['exam_time']
        ]);

        if ($check_stmt->fetchColumn() > 0) {
            $_SESSION['error'] = "Exam schedule already exists for this course, subject, date and time combination!";
        } else {
            // Insert new schedule [web:45][web:46]
            $sql = "INSERT INTO exam_schedule (course_code, subject_code, exam_date, exam_time, is_scheduled)
                    VALUES (?, ?, ?, ?, ?)";
            try {
                $stmt = $pdo->prepare($sql);
                $stmt->execute(array_values($data));
                $_SESSION['success'] = "Exam schedule added successfully!";
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $_SESSION['error'] = "Duplicate exam schedule! This combination already exists.";
                } else {
                    $_SESSION['error'] = "Error: " . $e->getMessage();
                }
            }
        }
    }
}

// Fetch all exam schedules to display [web:46]
$schedules_stmt = $pdo->query("
    SELECT es.*, c.course_name, s.subject_name 
    FROM exam_schedule es
    LEFT JOIN courses c ON es.course_code = c.course_code
    LEFT JOIN subjects s ON es.subject_code = s.subject_code
    ORDER BY es.exam_date DESC, es.exam_time DESC
");
$schedules = $schedules_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'header.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $edit_mode ? 'Edit' : 'Add' ?> Exam Schedule</title>

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

        .small-muted {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .card-hover:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>

<body class="bg-light">

    <div class="container mt-4 mb-5">
        <div class="row">
            <!-- Form Section -->
            <div class="col-lg-5">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-header bg-gradient bg-primary text-white text-center py-4">
                        <h3 class="mb-1">
                            <i class="bi bi-calendar-plus me-2"></i><?= $edit_mode ? 'Edit' : 'Add' ?> Exam Schedule
                        </h3>
                        <p class="mb-0 opacity-75 mt-2">Schedule examinations for courses</p>
                    </div>

                    <div class="card-body p-4">
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <strong>Success!</strong> <?= $_SESSION['success'] ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['success']); ?>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <strong>Error!</strong> <?= $_SESSION['error'] ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['error']); ?>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <?php if ($edit_mode): ?>
                                <input type="hidden" name="edit_id" value="<?= $edit_data['id'] ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label">Course <span class="required">*</span></label>
                                <select name="course_code" id="course_code" class="form-select" required>
                                    <option value="">Select Course</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?= htmlspecialchars($course['course_code']) ?>"
                                            <?= ($edit_mode && $edit_data['course_code'] == $course['course_code']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($course['course_code']) ?> -
                                            <?= htmlspecialchars($course['course_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Subject <span class="required">*</span></label>
                                <select name="subject_code" id="subject_code" class="form-select" required>
                                    <option value="">First select a course</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Exam Date <span class="required">*</span></label>
                                <input type="date" name="exam_date" class="form-control"
                                    value="<?= $edit_mode ? htmlspecialchars($edit_data['exam_date']) : '' ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Exam Time <span class="required">*</span></label>
                                <input type="time" name="exam_time" class="form-control"
                                    value="<?= $edit_mode ? htmlspecialchars($edit_data['exam_time']) : '' ?>" required>
                            </div>

                    <div class="mb-3 form-check form-switch">
    <input type="checkbox" name="is_scheduled" id="is_scheduled" class="form-check-input"
        <?= (!$edit_mode || $edit_data['is_scheduled'] == 1) ? 'checked' : '' ?>>
    <label for="is_scheduled" class="form-check-label fw-bold schedule-label" id="schedule-label">
        <i class="bi bi-lightning-fill me-1" id="schedule-icon"></i>
        <span id="schedule-text">Scheduled</span>
    </label>
</div>

<style>
.schedule-label {
    transition: all 0.3s ease;
    font-size: 1.1rem;
}

.schedule-label.scheduled {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.schedule-label.not-scheduled {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
</style>

<script>
    const checkbox = document.getElementById('is_scheduled');
    const label = document.getElementById('schedule-label');
    const text = document.getElementById('schedule-text');
    const icon = document.getElementById('schedule-icon');

    function updateScheduleLabel() {
        if (checkbox.checked) {
            text.textContent = 'Scheduled';
            label.className = 'form-check-label fw-bold schedule-label scheduled';
            icon.className = 'bi bi-check-circle-fill me-1';
        } else {
            text.textContent = 'Not Scheduled';
            label.className = 'form-check-label fw-bold schedule-label not-scheduled';
            icon.className = 'bi bi-x-circle me-1';
        }
    }

    // Update on page load [web:78][web:82]
    updateScheduleLabel();

    // Update on toggle change [web:78][web:82]
    checkbox.addEventListener('change', updateScheduleLabel);
</script>


                            <style>
                                .schedule-label {
                                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                    -webkit-background-clip: text;
                                    -webkit-text-fill-color: transparent;
                                    background-clip: text;
                                    font-size: 1.1rem;
                                }
                            </style>

                            <div class="d-flex justify-content-between">
                                <?php if ($edit_mode): ?>
                                    <a href="index" class="btn btn-secondary">
                                        <i class="bi bi-x-circle me-1"></i>Cancel
                                    </a>
                                <?php else: ?>
                                    <a href="index" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left me-1"></i>Back
                                    </a>
                                <?php endif; ?>
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-check-circle me-1"></i><?= $edit_mode ? 'Update' : 'Add' ?> Schedule
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- List Section -->
            <div class="col-lg-7">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-header bg-gradient bg-info text-white py-3">
                        <h4 class="mb-0"><i class="bi bi-list-ul me-2"></i>Existing Exam Schedules</h4>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Course</th>
                                        <th>Subject</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($schedules) > 0): ?>
                                        <?php foreach ($schedules as $schedule): ?>
                                            <tr class="card-hover">
                                                <td><?= htmlspecialchars($schedule['course_code']) ?></td>
                                                <td><?= htmlspecialchars($schedule['subject_code']) ?></td>
                                                <td><?= date('d-M-Y', strtotime($schedule['exam_date'])) ?></td>
                                                <td><?= date('h:i A', strtotime($schedule['exam_time'])) ?></td>
                                                <td>
                                                    <?php if ($schedule['is_scheduled'] == 1): ?>
                                                        <span class="badge bg-success">Scheduled</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Not Scheduled</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="?edit_id=<?= $schedule['id'] ?>" class="btn btn-sm btn-warning">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                No exam schedules found. Add your first schedule!
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Embed subjects data directly in JavaScript
        const subjectsByCourse = <?= json_encode($subjects_by_course) ?>;
        const editMode = <?= $edit_mode ? 'true' : 'false' ?>;
        const editSubject = '<?= $edit_mode ? $edit_data['subject_code'] : '' ?>';

        // Load subjects based on selected course [web:45][web:46]
        function loadSubjects(courseCode, selectedSubject = '') {
            const subjectSelect = document.getElementById('subject_code');

            if (courseCode && subjectsByCourse[courseCode]) {
                subjectSelect.innerHTML = '<option value="">Select Subject</option>';
                subjectsByCourse[courseCode].forEach(subject => {
                    const selected = (selectedSubject && subject.subject_code === selectedSubject) ? 'selected' : '';
                    subjectSelect.innerHTML += `<option value="${subject.subject_code}" ${selected}>
                        ${subject.subject_code} - ${subject.subject_name}
                    </option>`;
                });
            } else {
                subjectSelect.innerHTML = '<option value="">First select a course</option>';
            }
        }

        // On course change [web:46]
        document.getElementById('course_code').addEventListener('change', function () {
            loadSubjects(this.value);
        });

        // Load subjects on page load if in edit mode [web:45]
        window.addEventListener('DOMContentLoaded', function () {
            const courseCode = document.getElementById('course_code').value;
            if (courseCode) {
                loadSubjects(courseCode, editSubject);
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php include '../includes/../includes/footer.php'; ?>
</body>

</html>