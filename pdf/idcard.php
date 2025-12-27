<?php
session_start();
include '../includes/db.php';
require_once __DIR__ . '/../includes/anti_inspect.php';

if (!isset($_SESSION['admin']) && !isset($_SESSION['student'])) {
    die('Access Denied');
}

$user_role = isset($_SESSION['admin']) ? 'admin' : 'student';



if (!isset($_GET['reg']) || empty($_GET['reg'])) {
    die('Invalid Registration Number');
}

$reg_no = $_GET['reg'];

$stmt = $pdo->prepare("
    SELECT s.*, 
           c.course_name, c.duration,
           sc.center_name AS center_name, sc.center_code
    FROM students s
    LEFT JOIN courses c ON s.course_code = c.course_code
    LEFT JOIN study_centers sc ON s.study_center_code = sc.center_code
    WHERE s.reg_no = ?
");
$stmt->execute([$reg_no]);
$student = $stmt->fetch();

if (!$student) {
    die('Student not found!');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student ID Card - <?= htmlspecialchars($student['student_name']) ?></title>
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
            padding: 20px 0;
        }

        /* ✅ FIXED MARGINS - MATCHES ORIGINAL ID CARD */
        .id-container {
            max-width: 360px;
            margin: 0 auto;
            padding: 0 10px;
        }

        /* ✅ PERFECT SINGLE PAGE SIZE */
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

        /* ✅ FIXED HEIGHT SECTIONS WITH LINE SPACE */
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
            height: 64px;
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
            background: linear-gradient(135deg, #0932e8ff, #dc2626);
            color: white;
            padding: 4px 10px;
            border-radius: 16px;
            font-size: 10px;
            font-weight: 600;
            flex-shrink: 0;
        }

        .photo-container {
            width: 80px;
            height: 80px;
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

        /* ✅ COMPACT 2-COLUMN INFO */
        .info-list {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            margin-top: -16px;
            font-size: 9px;
            height: 5px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 1px;
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
            height: 80px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .address-line {
            color: #374151;
            font-size: 9px;
            line-height: 4;
            overflow: hidden;
            margin-top: -16px;
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

        /* ✅ SINGLE PRINT BUTTON - FULL WIDTH CENTERED */
        .print-button-container {
            display: flex;
            justify-content: center;
            margin: 24px auto 0;
            width: 400px;
            padding: 0 10px;
        }

        .print-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 28px;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            line-height: 1.2;
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            color: white;
            cursor: pointer;
            width: 100%;
            max-width: 280px;
        }

        .print-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 30px rgba(16, 185, 129, 0.5);
            color: white;
        }

        .print-btn i {
            margin-right: 8px;
        }

        /* ✅ PERFECT PRINT - 1 PAGE ONLY */
        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
                font-size: 10px !important;
            }

            .id-container {
                max-width: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .id-card {
                box-shadow: none !important;
                border: 2px solid #000 !important;
                width: 85mm !important;
                height: 55mm !important;
                margin: 2mm !important;
                page-break-inside: avoid !important;
                page-break-after: avoid !important;
            }

            .print-button-container,
            .text-center:not(.id-card),
            h1 {
                display: none !important;
            }

            @page {
                margin: 0 !important;
                size: 90mm 60mm !important;
            }
        }

        .logo-institute-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 2px;
        }

        .institute-logo {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            object-fit: contain;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .institute-logo {
            width: 32px;
            /* increased from 20px */
            height: 32px;
            /* increased from 20px */
            border-radius: 4px;
            object-fit: contain;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
    </style>
</head>

<body>
    <div class="id-container">
        <div class="text-center mb-4">
            <h1 class="text-white mb-2"
                style="font-weight: 700; font-size: 24px; text-shadow: 0 4px 12px rgba(0,0,0,0.3);">
                Student ID Card
            </h1>
        </div>

        <div class="id-card">
            <!-- HEADER -->
            <div class="header-section">
                <div class="logo-institute-row">
                    <img src="../assets/images/ska-logo.png" alt="Logo" class="institute-logo">
                    <div class="center-name"><?= htmlspecialchars($student['center_name'] ?: 'Main Study Centre') ?>
                    </div>
                </div>
                <div class="card-title">
                    <?= $student['center_code'] ? htmlspecialchars($student['center_code']) . ' | ' : '' ?>ID CARD
                </div>
            </div>


            <!-- CONTENT -->
            <div class="content-section">
                <div class="profile-row">
                    <div style="flex: 1;">
                        <div class="student-name"><?= htmlspecialchars($student['student_name']) ?></div>
                        <div class="reg-badge"><?= $student['reg_no'] ?></div>
                    </div>
                    <div class="photo-container">
                        <?php
                        // Proper photo path logic
                        $photo_filename = trim($student['photo'] ?? '');
                        $photo_path = $photo_filename ? "../assets/images/students/" . htmlspecialchars($photo_filename) : "../assets/images/default.jpeg";
                        ?>
                        <img src="<?= $photo_path ?>" alt="Student Photo" class="rounded-circle" width="160"
                            height="160" onerror="this.onerror=null;this.src='../assets/images/default.jpeg';">
                    </div>

                </div>

                <div class="info-list">
                    <div class="info-item">
                        <span class="info-label">C</span>
                        <span class="info-value"><?= htmlspecialchars($student['course_name'] ?: 'N/A') ?></span>
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
                <div class="address-line"><?= htmlspecialchars(substr($student['address'] ?: '—', 0, 45)) ?></div>
                <div class="barcode-line"><?= $student['reg_no'] ?> |
                    <?= date('M Y', strtotime($student['admission_date'])) ?>
                </div>
            </div>
        </div>

        <!-- ✅ SINGLE PRINT BUTTON ONLY -->
        <div class="print-button-container">
            <button onclick="window.print()" class="print-btn">
                <i class="bi bi-printer"></i> Print ID Card
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>