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

// Fetch student info and current status FIRST
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

// Handle toggle requests - Enable if disabled, Disable if enabled
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [];
    $params = [];
    $toggled = 0;

    // ID Card Toggle
    if (isset($_POST['toggle_id_card'])) {
        $newValue = ($student['id_card_generated'] === 'Yes') ? 'No' : 'Yes';
        $fields[] = "id_card_generated = ?";
        $params[] = $newValue;
        $toggled++;
    }

    // Certificate Toggle - Always update date
    if (isset($_POST['toggle_certificate'])) {
        $newValue = ($student['certificate_gen'] === 'Yes') ? 'No' : 'Yes';
        $fields[] = "certificate_gen = ?";
        $fields[] = "certificate_gen_date = NOW()";
        $params[] = $newValue;
        $toggled++;
    }

    // Marksheet Toggle - Always update date
    if (isset($_POST['toggle_marksheet'])) {
        $newValue = ($student['marksheet_gen'] === 'Yes') ? 'No' : 'Yes';
        $fields[] = "marksheet_gen = ?";
        $fields[] = "marksheet_gen_date = NOW()";
        $params[] = $newValue;
        $toggled++;
    }


    // Admit Card Toggle
    if (isset($_POST['toggle_admit_card'])) {
        $newValue = ($student['admit_card_gen'] === 'Yes') ? 'No' : 'Yes';
        $fields[] = "admit_card_gen = ?";
        $params[] = $newValue;
        $toggled++;
    }

    if ($toggled > 0) {
        $sql = "UPDATE students SET " . implode(', ', $fields) . " WHERE reg_no = ?";
        $params[] = $reg_no;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        header("Location: " . $_SERVER['PHP_SELF'] . "?reg=" . urlencode($reg_no) . "&updated=$toggled");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toggle Documents - <?= htmlspecialchars($student['student_name']) ?></title>
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

        /* STUDENT STATUS GRID */
        .status-preview {
            flex: 1;
        }

        .status-container {
            margin: 0 auto;
            padding: 0 10px;
        }

        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .status-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px 25px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            border: 3px solid rgba(255, 255, 255, 0.5);
            text-align: center;
            position: relative;
            transition: all 0.3s ease;
        }

        .status-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.2);
        }

        .status-icon {
            font-size: 50px;
            margin-bottom: 15px;
        }

        .status-card.active .status-icon {
            color: #10b981;
        }

        .status-card.inactive .status-icon {
            color: #6b7280;
        }

        .status-name {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .status-label {
            font-size: 14px;
            color: #6b7280;
            font-weight: 500;
        }

        .status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-badge.active {
            background: #10b981;
            color: white;
        }

        .status-badge.inactive {
            background: #e5e7eb;
            color: #6b7280;
        }

        /* TOGGLE SECTION */
        .disable-section {
            flex: 1;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(25px);
            border-radius: 30px;
            padding: 50px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            height: fit-content;
        }

        .disable-title {
            color: white;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 24px;
            text-shadow: 0 3px 12px rgba(0, 0, 0, 0.3);
        }

        .disable-subtitle {
            color: rgba(255, 255, 255, 0.92);
            font-size: 18px;
            margin-bottom: 40px;
            line-height: 1.6;
        }

        .doc-checkboxes {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .doc-item {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 16px;
            padding: 25px;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .doc-item:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
        }

        .doc-item input[type="checkbox"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .doc-item-content {
            display: flex;
            align-items: center;
            gap: 20px;
            pointer-events: none;
        }

        .doc-icon {
            font-size: 32px;
            width: 60px;
            text-align: center;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
        }

        .doc-icon.enabled {
            color: #10b981;
        }

        .doc-icon.disabled {
            color: #ef4444;
        }

        .doc-info h4 {
            color: white;
            font-size: 20px;
            font-weight: 600;
            margin: 0 0 4px 0;
        }

        .doc-info p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
            margin: 0;
        }

        .doc-item input:checked+.doc-item-content .doc-icon {
            color: #fbbf24;
        }

        .doc-item input:checked+.doc-item-content .doc-info h4 {
            color: #fbbf24;
        }

        .btn-group {
            display: flex;
            gap: 25px;
            justify-content: center;
            margin-top: 30px;
        }

        .btn-toggle {
            background: linear-gradient(135deg, #8b5cf6, #6366f1);
            border: none;
            color: white;
            padding: 20px 50px;
            border-radius: 20px;
            font-size: 20px;
            font-weight: 700;
            box-shadow: 0 15px 40px rgba(139, 92, 246, 0.4);
            min-width: 280px;
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

        .alert-success {
            background: rgba(16, 185, 129, 0.2);
            border: 2px solid rgba(16, 185, 129, 0.5);
            color: white;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 30px;
            text-align: center;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .content-row {
                flex-direction: column;
                gap: 30px;
            }

            .doc-checkboxes {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="main-container">
        <!-- PAGE HEADER -->
        <div class="page-header">
            <h1 class="page-title">Toggle Documents Status</h1>
            <p class="page-subtitle">Enable or disable documents for <?= htmlspecialchars($student['student_name']) ?>
            </p>
        </div>

        <?php if (isset($_GET['updated'])): ?>
            <div class="alert-success">
                <i class="bi bi-check-circle-fill me-2"></i>
                Successfully updated <?= intval($_GET['updated']) ?> document(s)!
            </div>
        <?php endif; ?>

        <div class="content-row">
            <!-- STUDENT DOCUMENTS STATUS -->
            <div class="status-preview">
                <div class="status-container">
                    <h3
                        style="color: white; text-align: center; margin-bottom: 30px; font-size: 24px; font-weight: 600;">
                        Current Document Status
                    </h3>
                    <div class="status-grid">
                        <!-- ID Card Status -->
                        <div class="status-card <?= $student['id_card_generated'] == 'Yes' ? 'active' : 'inactive' ?>">
                            <div
                                class="status-badge <?= $student['id_card_generated'] == 'Yes' ? 'active' : 'inactive' ?>">
                                <?= $student['id_card_generated'] == 'Yes' ? 'ENABLED' : 'DISABLED' ?>
                            </div>
                            <i class="bi bi-card-text status-icon"></i>
                            <div class="status-name">ID Card</div>
                            <div class="status-label">
                                <?= $student['id_card_generated'] == 'Yes' ? 'Generated' : 'Not Generated' ?>
                            </div>
                        </div>

                        <!-- Certificate Status -->
                        <div class="status-card <?= $student['certificate_gen'] == 'Yes' ? 'active' : 'inactive' ?>">
                            <div
                                class="status-badge <?= $student['certificate_gen'] == 'Yes' ? 'active' : 'inactive' ?>">
                                <?= $student['certificate_gen'] == 'Yes' ? 'ENABLED' : 'DISABLED' ?>
                            </div>
                            <i class="bi bi-award status-icon"></i>
                            <div class="status-name">Certificate</div>
                            <div class="status-label">
                                <?= $student['certificate_gen'] == 'Yes' ? 'Generated' : 'Not Generated' ?>
                            </div>
                        </div>

                        <!-- Marksheet Status -->
                        <div class="status-card <?= $student['marksheet_gen'] == 'Yes' ? 'active' : 'inactive' ?>">
                            <div class="status-badge <?= $student['marksheet_gen'] == 'Yes' ? 'active' : 'inactive' ?>">
                                <?= $student['marksheet_gen'] == 'Yes' ? 'ENABLED' : 'DISABLED' ?>
                            </div>
                            <i class="bi bi-file-earmark-bar-graph status-icon"></i>
                            <div class="status-name">Marksheet</div>
                            <div class="status-label">
                                <?= $student['marksheet_gen'] == 'Yes' ? 'Generated' : 'Not Generated' ?>
                            </div>
                        </div>

                        <!-- Admit Card Status -->
                        <div class="status-card <?= $student['admit_card_gen'] == 'Yes' ? 'active' : 'inactive' ?>">
                            <div
                                class="status-badge <?= $student['admit_card_gen'] == 'Yes' ? 'active' : 'inactive' ?>">
                                <?= $student['admit_card_gen'] == 'Yes' ? 'ENABLED' : 'DISABLED' ?>
                            </div>
                            <i class="bi bi-door-open status-icon"></i>
                            <div class="status-name">Admit Card</div>
                            <div class="status-label">
                                <?= $student['admit_card_gen'] == 'Yes' ? 'Generated' : 'Not Generated' ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TOGGLE CONTROLS -->
            <div class="disable-section">
                <h2 class="disable-title">Toggle Document Status</h2>
                <p class="disable-subtitle">
                    Select documents to toggle their status. Enabled documents will be disabled, and disabled documents
                    will be enabled.
                </p>

                <form method="POST">
                    <div class="doc-checkboxes">
                        <!-- ID Card Checkbox -->
                        <label class="doc-item">
                            <input type="checkbox" name="toggle_id_card" value="1">
                            <div class="doc-item-content">
                                <i
                                    class="bi bi-card-text doc-icon <?= $student['id_card_generated'] == 'Yes' ? 'enabled' : 'disabled' ?>"></i>
                                <div class="doc-info">
                                    <h4>ID Card</h4>
                                    <p>
                                        <?php if ($student['id_card_generated'] === 'Yes'): ?>
                                            Currently Enabled - Click to Disable
                                        <?php else: ?>
                                            Currently Disabled - Click to Enable
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </label>

                        <!-- Certificate Checkbox -->
                        <label class="doc-item">
                            <input type="checkbox" name="toggle_certificate" value="1">
                            <div class="doc-item-content">
                                <i
                                    class="bi bi-award doc-icon <?= $student['certificate_gen'] == 'Yes' ? 'enabled' : 'disabled' ?>"></i>
                                <div class="doc-info">
                                    <h4>Certificate</h4>
                                    <p>
                                        <?php if ($student['certificate_gen'] === 'Yes'): ?>
                                            Currently Enabled - Click to Disable
                                        <?php else: ?>
                                            Currently Disabled - Click to Enable
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </label>

                        <!-- Marksheet Checkbox -->
                        <label class="doc-item">
                            <input type="checkbox" name="toggle_marksheet" value="1">
                            <div class="doc-item-content">
                                <i
                                    class="bi bi-file-earmark-bar-graph doc-icon <?= $student['marksheet_gen'] == 'Yes' ? 'enabled' : 'disabled' ?>"></i>
                                <div class="doc-info">
                                    <h4>Marksheet</h4>
                                    <p>
                                        <?php if ($student['marksheet_gen'] === 'Yes'): ?>
                                            Currently Enabled - Click to Disable
                                        <?php else: ?>
                                            Currently Disabled - Click to Enable
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </label>

                        <!-- Admit Card Checkbox -->
                        <label class="doc-item">
                            <input type="checkbox" name="toggle_admit_card" value="1">
                            <div class="doc-item-content">
                                <i
                                    class="bi bi-door-open doc-icon <?= $student['admit_card_gen'] == 'Yes' ? 'enabled' : 'disabled' ?>"></i>
                                <div class="doc-info">
                                    <h4>Admit Card</h4>
                                    <p>
                                        <?php if ($student['admit_card_gen'] === 'Yes'): ?>
                                            Currently Enabled - Click to Disable
                                        <?php else: ?>
                                            Currently Disabled - Click to Enable
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </label>
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn btn-toggle">
                            <i class="bi bi-arrow-repeat me-2"></i>
                            Apply Selected Changes
                        </button>
                        <a href="students" class="btn btn-cancel">
                            <i class="bi bi-arrow-left me-2"></i>Back to Students
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>