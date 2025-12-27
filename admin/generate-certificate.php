<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['admin'])) {
    include(__DIR__ . '/../forbidden.php');
    exit();
}

if (!isset($_GET['reg']) || empty($_GET['reg'])) {
    header("Location: students");
    die();
}

$reg_no = $_GET['reg'];

// Handle POST requests - NO POPUP CONFIRMATION
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm'])) {
        // Generate Certificate
        $stmt = $pdo->prepare("UPDATE students SET certificate_gen = 'Yes', certificate_gen_date = NOW() WHERE reg_no = ?");
        $stmt->execute([$reg_no]);
        header("Location: students?success=1");
        exit();
    }

    if (isset($_POST['disable'])) {
        // Disable Certificate
        $stmt = $pdo->prepare("UPDATE students SET certificate_gen = 'No' WHERE reg_no = ?");
        $stmt->execute([$reg_no]);
        header("Location: students?disabled=1");
        exit();
    }
}

// Fetch student info
$stmt = $pdo->prepare("
    SELECT s.*, c.course_name, c.duration,
           sc.center_name AS center_name, sc.center_code
    FROM students s 
    LEFT JOIN courses c ON s.course_code = c.course_code 
    LEFT JOIN study_centers sc ON s.study_center_code = sc.center_code 
    WHERE s.reg_no = ?
");
$stmt->execute([$reg_no]);
$student = $stmt->fetch();

if (!$student) {
    header("Location: students");
    die();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Management - <?= htmlspecialchars($student['student_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .main-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .page-title {
            color: white;
            font-size: 42px;
            font-weight: 700;
            text-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
            margin-bottom: 12px;
        }

        .page-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 20px;
            font-weight: 500;
        }

        .content-row {
            display: flex;
            gap: 50px;
            align-items: flex-start;
        }

        /* SIMPLIFIED STATUS CARD */
        .status-preview {
            flex: 1;
        }

        .status-container {
            max-width: 500px;
            margin: 0 auto;
            padding: 0 10px;
        }

        .status-card {
            width: 500px;
            height: 300px;
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
            border: 4px solid #ffffff;
            overflow: hidden;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 40px 30px;
        }

        .status-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background:
                <?= $student['certificate_gen'] == 'Yes' ? '#10b981' : '#3b82f6' ?>
            ;
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 700;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
            z-index: 10;
        }

        .status-icon {
            font-size: 80px;
            margin-bottom: 20px;
            color:
                <?= $student['certificate_gen'] == 'Yes' ? '#10b981' : '#6b7280' ?>
            ;
        }

        .status-title {
            font-size: 32px;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 12px;
            letter-spacing: 1px;
        }

        .status-subtitle {
            font-size: 18px;
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 8px;
        }

        .student-name-status {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 20px;
        }

        /* ADMIN CONTROLS */
        .confirm-section {
            flex: 1;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(25px);
            border-radius: 30px;
            padding: 50px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            height: fit-content;
        }

        .confirm-title {
            color: white;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 24px;
            text-shadow: 0 3px 12px rgba(0, 0, 0, 0.3);
        }

        .confirm-subtitle {
            color: rgba(255, 255, 255, 0.92);
            font-size: 18px;
            margin-bottom: 40px;
            line-height: 1.6;
        }

        .form-check {
            margin-bottom: 40px;
        }

        .form-check-input {
            width: 28px;
            height: 28px;
            border: 3px solid #ffffff;
        }

        .form-check-label {
            font-size: 22px;
            font-weight: 600;
            color: white;
            cursor: pointer;
            padding: 20px 30px;
            border-radius: 16px;
            transition: all 0.3s ease;
            border: 2px solid rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.1);
            display: block;
        }

        .form-check-label:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
        }

        .btn-group {
            display: flex;
            gap: 25px;
            justify-content: center;
            margin-top: 30px;
        }

        .btn-confirm {
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            color: white;
            padding: 20px 50px;
            border-radius: 20px;
            font-size: 20px;
            font-weight: 700;
            box-shadow: 0 15px 40px rgba(16, 185, 129, 0.4);
            min-width: 240px;
        }

        .btn-cancel {
            background: transparent;
            color: white;
            padding: 20px 50px;
            border: 3px solid rgba(255, 255, 255, 0.6);
            border-radius: 20px;
            font-size: 20px;
            font-weight: 700;
            min-width: 240px;
        }

        .already-generated,
        .disable-section {
            text-align: center;
            padding: 80px 60px;
            border-radius: 25px;
            border: 3px solid;
        }

        .already-generated {
            color: #10b981;
            background: rgba(16, 185, 129, 0.15);
            border-color: #10b981;
        }

        .disable-section {
            color: #ef4444;
            background: rgba(239, 68, 68, 0.15);
            border-color: #ef4444;
        }

        .btn-disable {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border: none;
            color: white;
            padding: 20px 50px;
            border-radius: 20px;
            font-size: 20px;
            font-weight: 700;
            box-shadow: 0 15px 40px rgba(239, 68, 68, 0.4);
            min-width: 240px;
        }
    </style>
</head>

<body>
    <div class="main-container">
        <!-- PAGE HEADER -->
        <div class="page-header">
            <h1 class="page-title">Certificate Management</h1>
            <p class="page-subtitle">Generate or disable student certificates</p>
        </div>

        <div class="content-row">
            <!-- SIMPLIFIED STATUS PREVIEW -->
            <div class="status-preview">
                <div class="status-container">
                    <div class="status-card position-relative">
                        <div class="status-badge">
                            <?= $student['certificate_gen'] == 'Yes' ? 'ACTIVE' : 'PENDING' ?>
                        </div>

                        <?php if ($student['certificate_gen'] == 'Yes'): ?>
                            <i class="bi bi-award status-icon"></i>
                            <div class="status-title">Certificate Active</div>
                            <div class="status-subtitle">Certificate generated successfully</div>
                        <?php else: ?>
                            <i class="bi bi-file-earmark-text status-icon"></i>
                            <div class="status-title">Certificate Pending</div>
                            <div class="status-subtitle">Ready for generation</div>
                        <?php endif; ?>

                        <div class="student-name-status"><?= htmlspecialchars($student['student_name']) ?></div>
                        <div style="font-size: 16px; color: #6b7280; font-weight: 500;">
                            <?= htmlspecialchars($student['reg_no']) ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ADMIN CONTROLS -->
            <div class="confirm-section">
                <?php if ($student['certificate_gen'] != 'Yes'): ?>
                    <h2 class="confirm-title">Confirm Generation</h2>
                    <p class="confirm-subtitle">
                        Confirm certificate generation for this student.
                    </p>

                    <form method="POST">
                        <input type="hidden" name="confirm" value="1">
                        <div class="form-check d-flex align-items-center p-4 border rounded-3 mb-4"
                            style="background: rgba(255,255,255,0.1); border: 2px solid rgba(255,255,255,0.3);">
                            <input class="form-check-input me-4" type="checkbox" name="generate_cert" id="confirm_yes"
                                value="yes" required>
                            <label class="form-check-label flex-grow-1 mb-0 p-3" for="confirm_yes">
                                <i class="bi bi-check-lg"
                                    style="font-size: 24px; color: #10b981; margin-right: 12px; opacity: 0; transition: opacity 0.3s ease;"></i>
                                Yes, Generate Certificate for this student
                            </label>
                        </div>

                        <div class="btn-group">
                            <button type="submit" class="btn btn-confirm" id="generateBtn" disabled>
                                <i class="bi bi-file-earmark-text me-2"></i>Generate Certificate
                            </button>
                            <a href="students" class="btn btn-cancel">
                                <i class="bi bi-x-circle me-2"></i>Cancel
                            </a>
                        </div>
                    </form>

                <?php else: ?>
                    <div class="disable-section">
                        <i class="bi bi-check-circle-fill fs-1 mb-4" style="color: #10b981;"></i>
                        <h2 style="color: #10b981;">Certificate Active</h2>
                        <p class="fs-5 mb-4">Certificate has been generated for this student.</p>

                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="disable" value="1">
                            <div class="btn-group">
                                <button type="submit" class="btn btn-disable">
                                    <i class="bi bi-ban me-2"></i>Disable Certificate
                                </button>
                                <a href="students" class="btn btn-success"
                                    style="padding: 20px 50px; border-radius: 20px; font-size: 20px; font-weight: 700; min-width: 240px; background: linear-gradient(135deg, #10b981, #059669);">
                                    <i class="bi bi-list-ul me-2"></i>Back to Students
                                </a>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Checkbox animation
        document.getElementById('confirm_yes').addEventListener('change', function () {
            const generateBtn = document.getElementById('generateBtn');
            const tickIcon = document.querySelector('#confirm_yes + .form-check-label i');

            generateBtn.disabled = !this.checked;
            generateBtn.style.opacity = this.checked ? '1' : '0.6';

            if (tickIcon) {
                tickIcon.style.opacity = this.checked ? '1' : '0';
            }
        });

        document.getElementById('confirm_yes')?.dispatchEvent(new Event('change'));
    </script>
</body>

</html>