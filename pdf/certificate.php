<?php
session_start();
include '../includes/db.php';
require_once __DIR__ . '/../includes/anti_inspect.php';

if (!isset($_SESSION['admin']) && !isset($_SESSION['student'])) {
    die('Access Denied');
}


if (!isset($_GET['reg']) || empty($_GET['reg'])) {
    die('Invalid Registration Number');
}

$reg_no = $_GET['reg'];

// Fetch student + course + study centre
$stmt = $pdo->prepare("
    SELECT s.*, 
           c.course_name, c.duration,
           sc.center_name AS center_name, sc.center_code, sc.address AS center_address, sc.state AS center_state, sc.pincode AS center_pincode
    FROM students s
    LEFT JOIN courses c ON s.course_code = c.course_code
    LEFT JOIN study_centers sc ON s.study_center_code = sc.center_code
    WHERE s.reg_no = ?
");
$stmt->execute([$reg_no]);
$student = $stmt->fetch();

if (!$student)
    die('Student not found!');



// Fetch results for this student
$results_stmt = $pdo->prepare("
    SELECT r.*, sub.subject_code, sub.subject_name
    FROM results r
    LEFT JOIN subjects sub ON r.subject_code = sub.subject_code
    WHERE r.reg_no = ?
    ORDER BY sub.subject_code
");
$results_stmt->execute([$reg_no]);
$results = $results_stmt->fetchAll();

// Calculate totals
$total_theory_max = 0;
$total_theory_secured = 0;
$total_max = 0;
$total_secured = 0;

foreach ($results as $result) {
    $total_theory_max += (float) $result['total_theory_marks'];
    $total_theory_secured += (float) $result['theory_marks'];
    $total_max += ((float) $result['total_theory_marks']);
    $total_secured += ((float) $result['theory_marks']);
}

$percentage = $total_max > 0 ? round(($total_secured / $total_max) * 100, 1) : 0;

// Generate grade
$grade = 'F';
if ($percentage >= 90)
    $grade = 'A+';
elseif ($percentage >= 80)
    $grade = 'A';
elseif ($percentage >= 70)
    $grade = 'B+';
elseif ($percentage >= 60)
    $grade = 'B';
elseif ($percentage >= 50)
    $grade = 'C';
// Gender-based S/O or D/O and pronoun
$gender = strtolower(trim($student['gender'] ?? ''));
if ($gender === 'female' || $gender === 'f') {
    $relation = 'D/o';
    $pronoun1 = 'her';
    $pronoun2 = 'her';
} else {
    $relation = 'S/o';
    $pronoun1 = 'him';
    $pronoun2 = 'his';
}

// Handle certificate_gen_date - use current date if empty
if (empty($student['certificate_gen_date']) || $student['certificate_gen_date'] == '0000-00-00' || $student['certificate_gen_date'] == null) {
    $issue_date = date('d-M-Y');
} else {
    $issue_date = date('d-M-Y', strtotime($student['certificate_gen_date']));
}


?>
<html>

<head>
    <meta name="emotion-insertion-point" content="">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Serif+Vithkuqi:wght@500&family=Open+Sans&display=swap');
    </style>
</head>

<body>
    <div
        style="background: url(../assets/images/cert-bg.jpg) no-repeat; background-size: cover; min-height:776px; width:1110px; font-family: 'Noto Serif Vithkuqi', serif; margin: 0 auto;">

        <div style="padding-top: 135px; height: 116px;">
            <table width="85%" align="center">
                <tr>
                    <td style="width:88%;">&nbsp;</td>
                    <td style="text-align: center;">
                        <?php
                        $photo_filename = trim($student['photo'] ?? '');
                        $photo_src = $photo_filename ? "../assets/images/students/" . htmlspecialchars($photo_filename) : "../assets/images/default.jpeg";
                        ?>
                        <img src="<?= $photo_src ?>"
                            style="height:153px; width:153px; border: 2px solid #000000; border-radius: 81px; display: block; margin: 0 auto;"
                            onerror="this.src='../assets/images/default.jpeg'">
                    </td>
                </tr>
                <tr>
                    <td style="width:88%;">&nbsp;</td>
                    <td style="text-align: center;">
                        <strong>Regd No. :
                            <?= htmlspecialchars($student['reg_no']) ?></strong>
                    </td>
                </tr>
            </table>


            <div style="text-align: center; margin-top: -100px; line-height: 25px; font-size: 18px;">
                This is to certify that<br>

                <strong>
                    <u style="text-transform: uppercase;">
                        <?= htmlspecialchars($student['student_name']) ?>
                    </u>,
                    <br>
                    <?= $relation ?>
                    <u style="text-transform: uppercase;">
                        <?= htmlspecialchars($student['father_name'] ?: '—') ?>
                    </u>
                </strong>
                <br>
                has Successfully completed the<br>
                <strong style="text-transform:uppercase;">
                    <?= htmlspecialchars($student['course_name']) ?>
                </strong><br>
                Course Duration <?= htmlspecialchars($student['duration']) ?><br>

                <!-- Center-aligned block for Institute name and address -->
                <div style="text-align:center; margin-top:5px; line-height:28px;">
                    at
                    <strong style="display:block; font-size:18px;">
                        <?= htmlspecialchars($student['center_name']) ?>
                        <?= $student['center_code'] ? ' (' . htmlspecialchars($student['center_code']) . ')' : '' ?>
                    </strong>
                    <strong style="display:block;">
                        <?= htmlspecialchars($student['center_address'] ?: '') ?>,
                        <?= htmlspecialchars($student['center_state'] ?: '') ?>
                        <?= htmlspecialchars($student['center_pincode'] ? ' (' . $student['center_pincode'] . ')' : '') ?>
                    </strong>
                </div>
                <br>

                held between
                <?php
                preg_match('/\d+/', $student['duration'], $matches);
                $months = (int) $matches[0];

                $startDate = date('d-M-Y', strtotime($student['admission_date']));
                $endDate = date('d-M-Y', strtotime($student['admission_date'] . " +{$months} months"));
                ?>
                <strong><?= $startDate ?></strong> to <strong><?= $endDate ?></strong>

                and has secured <strong><?= $percentage ?>%</strong><br>
                with <strong><?= htmlspecialchars($grade ?: '—') ?></strong> Grade.
                We wish <?= $pronoun1 ?> best of luck for <?= $pronoun2 ?> future endeavors.
            </div>

            <table width="25%" border="0" align="left" style="margin-left: 72px; font-size: 14px; text-align: center;">
                <tr>
                    <td width="45%">
                        <img style="text-align:center;background:none; height:100px; width:100px;" align="center"
                            src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=<?= urlencode('https://localhost/ska/verify-cert?reg=' . urlencode($student['reg_no']) . '&course=' . urlencode($student['course_name'])) ?>"
                            title="Scan to Verify Certificate">
                    </td>




                    <td width="55%"></td>
                </tr>
                <tr>

                    <td><?= $issue_date ?><br>
                        <strong>Date of Issue</strong>
                    </td>
                </tr>
            </table>

        </div>
    </div>
</body>

</html>