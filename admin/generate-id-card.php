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
        // Generate ID card
        $stmt = $pdo->prepare("UPDATE students SET id_card_generated = 'Yes' WHERE reg_no = ?");
        $stmt->execute([$reg_no]);
        header("Location: students?success=1");
        exit();
    }

    if (isset($_POST['disable'])) {
        // Disable ID card
        $stmt = $pdo->prepare("UPDATE students SET id_card_generated = 'No' WHERE reg_no = ?");
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
    <title>ID Card Management - <?= htmlspecialchars($student['student_name']) ?></title>
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

        /* ✅ PERFECT ID CARD FROM YOUR CODE */
        .id-preview {
            flex: 1;
        }

        .id-container {
            max-width: 400px;
            margin: 0 auto;
            padding: 0 10px;
        }

        .id-card {
            width: 400px;
            height: 250px;
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 16px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.25);
            border: 3px solid #ffffff;
            overflow: hidden;
            position: relative;
            padding-top: 5px;
            margin: 0 auto;
        }

        .id-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #3b82f6, #ec4899, #f59e0b);
        }

        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background:
                <?= $student['id_card_generated'] == 'Yes' ? '#10b981' : '#3b82f6' ?>
            ;
            color: white;
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 11px;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            z-index: 10;
        }

        .header-section {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            padding: 12px 16px 8px;
            text-align: center;
            color: white;
            height: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .logo-institute-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 2px;
        }

        .institute-logo {
            width: 32px;
            height: 32px;
            border-radius: 4px;
            object-fit: contain;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .center-name {
            font-size: 14px;
            font-weight: 700;
            line-height: 1.1;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
            margin-bottom: 2px;
        }

        .card-title {
            font-size: 10px;
            font-weight: 500;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .content-section {
            padding: 12px 16px 8px;
            height: 110px;
            display: flex;
            flex-direction: column;
        }

        .profile-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 8px;
            height: 65px;
        }

        .student-name {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
            line-height: 1.1;
            margin-bottom: 4px;
            flex: 1;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .reg-badge {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            padding: 4px 10px;
            border-radius: 16px;
            font-size: 10px;
            font-weight: 600;
            flex-shrink: 0;
        }

        .photo-container {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            flex-shrink: 0;
            border: 3px solid #ffffff;
            overflow: hidden;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
        }

        .photo-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        .photo-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            background: linear-gradient(135deg, #f3f4f6, #d1d5db);
            color: #6b7280;
            font-weight: 600;
        }

        .info-list {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            font-size: 9px;
            height: 35px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 3px;
            flex: 1 1 48%;
        }

        .info-label {
            font-weight: 600;
            color: #4b5563;
            font-size: 8px;
            text-transform: uppercase;
            flex-shrink: 0;
            width: 18px;
        }

        .info-value {
            color: #1f2937;
            font-weight: 500;
            font-size: 8px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .footer-section {
            border-top: 2px solid rgba(255, 255, 255, 0.8);
            padding: 10px 16px 12px;
            background: rgba(255, 255, 255, 0.95);
            height: 60px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .address-line {
            color: #374151;
            font-size: 9px;
            line-height: 1.2;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .barcode-line {
            font-family: 'Courier New', monospace;
            font-weight: 700;
            letter-spacing: 1px;
            color: #1f2937;
            text-align: center;
            font-size: 9px;
            padding: 3px 6px;
            border-radius: 4px;
            background: linear-gradient(90deg, #f3f4f6, #e5e7eb);
        }

        /* CONFIRMATION SECTION */
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

        .review-link {
            display: block;
            font-size: 20px;
            font-weight: 600;
            color: #ffffff !important;
            text-decoration: none;
            padding: 20px 25px;
            border-radius: 20px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.1));
            margin-bottom: 30px;
            border: 3px solid rgba(255, 255, 255, 0.6);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .review-link:hover {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.3), rgba(255, 255, 255, 0.15));
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.9);
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
            <h1 class="page-title">ID Card Management</h1>
            <p class="page-subtitle">Generate or disable student ID cards</p>
        </div>

        <div class="content-row">
            <!-- ✅ YOUR PERFECT ID CARD PREVIEW -->
            <div class="id-preview">
                <div class="id-container">
                    <div class="id-card position-relative">
                        <div class="status-badge">
                            <?= $student['id_card_generated'] == 'Yes' ? 'ACTIVE' : 'PENDING' ?>
                        </div>

                        <!-- HEADER -->
                        <div class="header-section">
                            <div class="logo-institute-row">
                                <img src="../assets/images/ska-logo.png" alt="Logo" class="institute-logo">
                                <div class="center-name">
                                    <?= htmlspecialchars($student['center_name'] ?: 'Main Study Centre') ?>
                                </div>
                            </div>
                            <div class="card-title">
                                <?= $student['center_code'] ? htmlspecialchars($student['center_code']) . ' | ' : '' ?>ID
                                CARD
                            </div>
                        </div>

                        <!-- CONTENT -->
                        <div class="content-section">
                            <div class="profile-row">
                                <div style="flex: 1;">
                                    <div class="student-name"><?= htmlspecialchars($student['student_name']) ?></div>
                                    <div class="reg-badge"><?= $student['reg_no'] ?></div>
                                </div>

                                <?php
                                // Proper photo path logic
                                $photo_filename = trim($student['photo'] ?? '');
                                $photo_path = $photo_filename ? "../assets/images/students/" . htmlspecialchars($photo_filename) : "../assets/images/default.jpeg";
                                ?>

                                <div class="photo-container">
                                    <?php if (!empty($student['photo'])): ?>
                                        <img src="<?= $photo_path ?>" alt="Student Photo"
                                            onerror="this.onerror=null;this.src='../assets/images/default.jpeg';">
                                    <?php else: ?>
                                        <div class="photo-placeholder">👤</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="info-list">
                                <div class="info-item">
                                    <span class="info-label">C</span>
                                    <span
                                        class="info-value"><?= htmlspecialchars($student['course_name'] ?: 'N/A') ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">M</span>
                                    <span class="info-value"><?= htmlspecialchars($student['mobile']) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">F</span>
                                    <span
                                        class="info-value"><?= htmlspecialchars(substr($student['father_name'] ?: '—', 0, 12)) ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">D</span>
                                    <span
                                        class="info-value"><?= $student['dob'] ? date('d-M-Y', strtotime($student['dob'])) : '—' ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">V</span>
                                    <span
                                        class="info-value"><?= date('M\'y', strtotime($student['admission_date'] . ' +1 year')) ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- FOOTER -->
                        <div class="footer-section">
                            <div class="address-line"><?= htmlspecialchars(substr($student['address'] ?: '—', 0, 45)) ?>
                            </div>
                            <div class="barcode-line"><?= $student['reg_no'] ?> |
                                <?= date('M Y', strtotime($student['admission_date'])) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ADMIN CONTROLS -->
            <div class="confirm-section">
                <?php if ($student['id_card_generated'] != 'Yes'): ?>
                    <h2 class="confirm-title">Confirm Generation</h2>
                    <p class="confirm-subtitle">
                        Review the student details and ID card preview above.
                        Click "Review Details" to see complete student profile, then confirm generation.
                    </p>

                    <a href="view-student?reg=<?= urlencode($reg_no) ?>" target="_blank" class="review-link">
                        <i class="bi bi-eye me-3"></i>👁️ Review Student Details
                    </a>

                    <form method="POST">
                        <input type="hidden" name="confirm" value="1">
                        <div class="form-check d-flex align-items-center p-4 border rounded-3 mb-4"
                            style="background: rgba(255,255,255,0.1); border: 2px solid rgba(255,255,255,0.3);">
                            <input class="form-check-input me-4" type="checkbox" name="generate_id" id="confirm_yes"
                                value="yes" required>
                            <label class="form-check-label flex-grow-1 mb-0 p-3" for="confirm_yes">
                                <i class="bi bi-check-lg"
                                    style="font-size: 24px; color: #10b981; margin-right: 12px; opacity: 0; transition: opacity 0.3s ease;"></i>
                                Yes, Generate ID Card for this student
                            </label>
                        </div>

                        <div class="btn-group">
                            <button type="submit" class="btn btn-confirm" id="generateBtn" disabled>
                                <i class="bi bi-card-text me-2"></i>Generate ID Card
                            </button>
                            <a href="students.php" class="btn btn-cancel">
                                <i class="bi bi-x-circle me-2"></i>Cancel
                            </a>
                        </div>
                    </form>

                <?php else: ?>
                    <div class="disable-section">
                        <i class="bi bi-check-circle-fill fs-1 mb-4" style="color: #10b981;"></i>
                        <h2 style="color: #10b981;">ID Card Active</h2>
                        <p class="fs-5 mb-4">ID Card has been generated for this student.</p>

                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="disable" value="1">
                            <div class="btn-group">
                                <button type="submit" class="btn btn-disable">
                                    <i class="bi bi-ban me-2"></i>Disable ID Card
                                </button>
                                <a href="students.php" class="btn btn-success"
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