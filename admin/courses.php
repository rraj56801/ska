<?php
session_start();
include '../includes/db.php';
require_once __DIR__ . '/../includes/anti_inspect.php';

if (!isset($_SESSION['admin'])) {
    include(__DIR__ . '/../forbidden.php');
    exit();
}

// HANDLE FORMS FIRST - BEFORE ANY OUTPUT
$message = '';
$message_class = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $code = strtoupper(trim($_POST['code']));
    $name = trim($_POST['name']);
    $duration_num = (int) $_POST['duration_num'];
    $duration_unit = $_POST['duration_unit'];
    $duration = $duration_num . ' ' . $duration_unit;

    // CHECK DUPLICATES
    $check = $pdo->prepare("SELECT id FROM courses WHERE UPPER(course_code) = ? OR UPPER(course_name) = ?");
    $check->execute([$code, $name]);
    if ($check->rowCount() > 0) {
        $message = 'Course with this Code or Name already exists!';
        $message_class = 'alert-danger';
    } else {

        $stmt = $pdo->prepare("INSERT INTO courses (course_code, course_name, duration, fees, is_active) VALUES (?, ?, ?, ?, 1)");
        if ($stmt->execute([$code, $name, $duration, $_POST['fees']])) {
            $message = '✅ Course added successfully!';
            $message_class = 'alert-success';
        } else {
            $message = '❌ Error adding course!';
            $message_class = 'alert-danger';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id = (int) $_POST['id'];
    $duration_num = (int) $_POST['duration_num'];
    $duration_unit = $_POST['duration_unit'];
    $duration = $duration_num . ' ' . $duration_unit;

    $stmt = $pdo->prepare("UPDATE courses SET course_code = ?, course_name = ?, duration = ?, fees = ?, is_active = ? WHERE id = ?");
    if ($stmt->execute([strtoupper(trim($_POST['code'])), trim($_POST['name']), $duration, $_POST['fees'], (int) $_POST['is_active'], $id])) {
        $message = '✅ Course updated successfully!';
        $message_class = 'alert-success';
    } else {
        $message = '❌ Error updating course!';
        $message_class = 'alert-danger';
    }
}

?>
<?php include 'header.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Courses</title>
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
            background: linear-gradient(135deg, #667eea, #764ba2);
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

        .btn-group .btn-rounded+.btn-rounded {
            margin-left: 4px;
        }

        .table thead th {
            vertical-align: middle;
        }

        .add-btn {
            background: linear-gradient(135deg, #28a745, #20c997) !important;
            border: none;
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.3);
            font-weight: 600;
            padding: 12px 30px;
        }

        .add-btn:hover {
            background: linear-gradient(135deg, #218838, #1ea391) !important;
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(40, 167, 69, 0.4);
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

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0">Manage Courses
                <span
                    class="badge bg-primary fs-5"><?= $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn() ?></span>
            </h3>
            <div class="d-flex gap-2">
                <!-- NEW: Subjects Management Button -->
                <a href="subject-management" class="btn btn-outline-primary btn-lg shadow btn-rounded"
                    title="Manage Subjects">
                    <i class="bi bi-journal-text me-2"></i>Manage Subjects
                    <span
                        class="badge bg-light text-dark ms-1"><?= $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn() ?></span>
                </a>
                <!-- Existing Add Course Button -->
                <button class="btn btn-lg add-btn shadow" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="bi bi-plus-circle-fill me-2 fs-5"></i>Add New Course
                </button>
            </div>
        </div>

        <div class="card section-card">
            <div class="card-header card-header-main d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-book-half me-2"></i>All Courses</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th style="width:5%">#</th>
                                <th style="width:10%">Code</th>
                                <th style="width:25%">Course Name</th>
                                <th style="width:15%">Duration</th>
                                <th style="width:10%">Fees</th>
                                <th style="width:10%">Status</th>
                                <th style="width:10%">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $courses = $pdo->query("SELECT * FROM courses ORDER BY id DESC")->fetchAll();
                            foreach ($courses as $i => $c) {
                                $status = $c['is_active'] == 1 ?
                                    '<span class="badge bg-success px-3 py-2">Active</span>' :
                                    '<span class="badge bg-danger px-3 py-2">Inactive</span>';

                                echo "<tr>
                                <td><strong>" . ($i + 1) . "</strong></td>
                                <td><span class='badge bg-primary fs-6'>" . htmlspecialchars($c['course_code']) . "</span></td>
                                <td><strong>" . htmlspecialchars($c['course_name']) . "</strong></td>
                                <td>" . htmlspecialchars($c['duration']) . "</td>
                                <td><strong>₹" . number_format($c['fees']) . "</strong></td>
                                
                                <td>$status</td>
                                <td>
                                    <div class='btn-group' role='group'>
                                        <button class='btn btn-sm btn-warning btn-rounded' 
                                                onclick='editCourse(" . json_encode($c) . ")'
                                                title='Edit Course'>
                                            <i class='bi bi-pencil'></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>";
                            }
                            if (empty($courses)) {
                                echo '<tr><td colspan="8" class="text-center py-4 text-muted fs-5">No courses found</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ADD MODAL -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content section-card">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header card-header-main">
                        <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add New Course</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Course Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control form-control-lg" required maxlength="10">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Course Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-lg" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Duration <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="duration_num" min="1" max="60"
                                    class="form-control form-control-lg" required placeholder="6">
                                <select name="duration_unit" class="form-select form-select-lg" required>
                                    <option value="">Unit</option>
                                    <option value="Months">Months</option>
                                </select>
                            </div>
                            <small class="text-muted">e.g. 6 Months, 12 Months</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Fees (₹) <span class="text-danger">*</span></label>
                            <input type="number" name="fees" min="0" step="0.01" class="form-control form-control-lg"
                                required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add" class="btn btn-success btn-rounded px-4">
                            <i class="bi bi-check-circle me-1"></i>Add Course
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
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header card-header-main">
                        <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Course</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Course Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" id="edit_code" class="form-control form-control-lg" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Course Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="edit_name" class="form-control form-control-lg" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Duration <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="duration_num" id="edit_duration_num" min="1" max="60"
                                    class="form-control form-control-lg" required>
                                <select name="duration_unit" id="edit_duration_unit" class="form-select form-select-lg"
                                    required>
                                    <option value="">Unit</option>
                                    <option value="Months">Months</option>
                                </select>
                            </div>
                            <small class="text-muted">e.g. 6 Months, 12 Months</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Fees (₹) <span class="text-danger">*</span></label>
                            <input type="number" name="fees" id="edit_fees" min="0" step="0.01"
                                class="form-control form-control-lg" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Status <span class="text-danger">*</span></label>
                            <select name="is_active" id="edit_status" class="form-select form-select-lg" required>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update" class="btn btn-primary btn-rounded px-4">
                            <i class="bi bi-save me-1"></i>Update Course
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>

        function editCourse(c) {
            document.getElementById('edit_id').value = c.id;
            document.getElementById('edit_code').value = c.course_code;
            document.getElementById('edit_name').value = c.course_name;
            document.getElementById('edit_fees').value = c.fees;
            document.getElementById('edit_status').value = c.is_active;

            // FIXED REGEX - NO ESCAPED BACKSLASHES
            const durationMatch = c.duration.match(/(\d+)\s*(Months?)/i);
            const numInput = document.getElementById('edit_duration_num');
            const unitInput = document.getElementById('edit_duration_unit');

            if (durationMatch) {
                numInput.value = durationMatch[1];
                unitInput.value = durationMatch[2];
            } else {
                numInput.value = '';
                unitInput.value = '';
            }

            new bootstrap.Modal(document.getElementById('editModal')).show();
        }

    </script>

    <?php include '../includes/footer.php'; ?>
</body>

</html>