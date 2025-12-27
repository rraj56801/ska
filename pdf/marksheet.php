<?php
session_start();
include '../includes/db.php';
require_once __DIR__ . '/../includes/anti_inspect.php';

if (!isset($_SESSION['admin']) && !isset($_SESSION['student']) || !isset($_GET['reg']) || empty($_GET['reg'])) {
    die('Access Denied');
}

$reg_no = $_GET['reg'];

// Fetch student details
$student_stmt = $pdo->prepare("
    SELECT s.*, sc.center_code, sc.center_name, c.course_name, c.duration
    FROM students s
    LEFT JOIN study_centers sc ON s.study_center_code = sc.center_code
    LEFT JOIN courses c ON s.course_code = c.course_code
    WHERE s.reg_no = ?
");
$student_stmt->execute([$reg_no]);
$student = $student_stmt->fetch();

if (!$student)
    die("Student not found");

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
$total_practical_max = 0;
$total_practical_secured = 0;
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

// Generate marksheet number and dates
$marksheet_no = "SKA/" . date('Y', strtotime($student['admission_date'])) . "/" . $student['id'];

// Handle marksheet_gen_date - use current date if empty
if (empty($student['marksheet_gen_date']) || $student['marksheet_gen_date'] == '0000-00-00' || $student['marksheet_gen_date'] == null) {
    $issue_date = date('d-M-Y');
} else {
    $issue_date = date('d-M-Y', strtotime($student['marksheet_gen_date']));
}



preg_match('/\d+/', $student['duration'], $matches);
$months = (int) $matches[0];

$session_start = date('d-M-Y', strtotime($student['admission_date']));
$session_end = date('d-M-Y', strtotime($student['admission_date'] . " +{$months} months"));
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Marksheet - <?= htmlspecialchars($student['student_name']) ?></title>
    <style>
        table,
        td,
        th {
            border: 2px solid;
            border-collapse: collapse;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .print-only {
            display: block;
        }

        .screen-only {
            display: none;
        }

        @media print {
            .print-only {
                display: block;
            }

            .screen-only {
                display: none;
            }
        }
    </style>
</head>

<body class="print-only">
    <div
        style="width:1005px; background:url(../assets/images/marksheet-bg.jpg) no-repeat; min-height:1420px; font-family:Arial; margin:0 auto;">

        <h1 style="font-size: 17px; float: right; margin-right: 74px; padding-top: 55px;">
            Marksheet No : <?= htmlspecialchars($marksheet_no) ?>
        </h1>

        <div style="padding-top:355px; height: 33px;">

            <table width="85%" border="0" align="center" style="border:none;font-size: 18px;height:170px;">
                <tbody>
                    <tr>
                        <td width="21%" style="border:none;">Center Code </td>
                        <td width="1%" style="border:none;">:</td>
                        <td width="61%" style="border:none;"><?= htmlspecialchars($student['center_code']) ?></td>
                        <td width="17%" rowspan="7" align="right" style="border:none;">
                                         <?php
                        $photo_filename = trim($student['photo'] ?? '');
                        $photo_src = $photo_filename ? "../assets/images/students/" . htmlspecialchars($photo_filename) : "../assets/images/default.jpeg";
                        ?>
                        <img src="<?= $photo_src ?>"
                            style="height:120px; width:112px; margin-top:-80px; border-radius: 10px; border:2px solid #000000;"
                            onerror="this.src='../assets/images/default.jpeg'">
                        </td>
                    </tr>
                    <tr>
                        <td style="border:none;">Student Name </td>
                        <td style="border:none;">:</td>
                        <td style="border:none;text-transform:uppercase;">
                            <?= htmlspecialchars($student['student_name']) ?></td>
                    </tr>
                    <tr>
                        <td style="border:none;">Student Regd No. </td>
                        <td style="border:none;">:</td>
                        <td style="border:none;text-transform:uppercase;"><?= htmlspecialchars($reg_no) ?></td>
                    </tr>
                    <tr>
                        <td style="border:none;">Gaurdian Name</td>
                        <td style="border:none;">:</td>
                        <td style="border:none;text-transform:uppercase;">
                            <?= htmlspecialchars($student['father_name']) ?></td>
                    </tr>
                    <tr>
                        <td style="border:none;">Date of Birth</td>
                        <td style="border:none;">:</td>
                        <td style="border:none;"><?= date('d-M-Y', strtotime($student['dob'])) ?></td>
                    </tr>
                    <tr>
                        <td style="border:none;">Course</td>
                        <td style="border:none;">:</td>
                        <td style="border:none;text-transform:uppercase;">
                            <?= htmlspecialchars($student['course_name']) ?></td>
                    </tr>
                    <tr>
                        <td style="border:none;">Duration </td>
                        <td style="border:none;">:</td>
                        <td style="border:none;"><?= htmlspecialchars($student['duration']) ?></td>
                    </tr>
                    <tr>
                        <td style="border:none;">Session</td>
                        <td style="border:none;">:</td>
                        <td style="border:none;"><?= $session_start ?> to <?= $session_end ?></td>
                    </tr>
                    <tr>
                        <td style="border:none;">Centre Name</td>
                        <td style="border:none;">:</td>
                        <td colspan="2" style="border:none;"><?= htmlspecialchars($student['center_name']) ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- MARKS TABLE WITH PRACTICAL COLUMNS -->
            <table width="85%" border="0" align="center"
                style="border:none;font-size: 18px;text-align:center;margin-top:40px;height:430px;">
                <tbody>
                    <tr>
                        <td width="10%">Sr. No.</td>
                        <td width="30%">Papers/Modules</td>
                        <td width="15%">Theory Total</td>
                        <td width="15%">Theory Obtained</td>
                    </tr>
                    <?php $sr_no = 1;
                    foreach ($results as $result): ?>
                        <tr>
                            <td><?= $sr_no++ ?></td>
                            <td><?= htmlspecialchars($result['subject_code'] . ' - ' . $result['subject_name']) ?></td>
                            <td><?= (int) $result['total_theory_marks'] ?></td>
                            <td><?= (int) $result['theory_marks'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="2"><strong style="color:red">Total</strong></td>
                        <td><?= (int) $total_theory_max ?></td>
                        <td><?= (int) $total_theory_secured ?></td>
                    </tr>
                </tbody>
            </table>

            <table width="85%" border="0" align="center"
                style="border:none;font-size: 18px;text-align:center;margin-top:20px;">
                <tbody>
                    <tr>
                        <td><strong style="color:red">Grand Total</strong></td>
                        <td><?= (int) $total_secured ?>/<?= (int) $total_max ?></td>
                        <td><strong style="color:red">Marks In Percentage</strong></td>
                        <td><?= $percentage ?> %</td>
                        <td><strong style="color:red">Grade</strong></td>
                        <td><?= $grade ?></td>
                    </tr>
                </tbody>
            </table>

            <table width="85%" border="0" align="center"
                style="border:none;font-size: 18px;text-align:center;margin-top:12px;font-weight:bold;">
                <tbody>
                    <tr>
                        <td colspan="10" style="text-align: left;border:none;"><u style="color:blue;">GRADE CHART</u>
                        </td>
                    </tr>
                    <tr>
                        <td><strong style="color:red;">A+</strong></td>
                        <td>Above 90%</td>
                        <td><strong style="color:red;">A</strong></td>
                        <td>80-90%</td>
                        <td><strong style="color:red;">B+</strong></td>
                        <td>70-80%</td>
                        <td><strong style="color:red;">B</strong></td>
                        <td>60-70%</td>
                        <td><strong style="color:red;">C</strong></td>
                        <td>50-60%</td>
                    </tr>
                </tbody>
            </table>

            <table width="35%" border="0" align="left" style="border:none; margin-left: -13px; margin-top: 40px;">
                <tbody>
                    <tr>
                        <td style="border:none;">
                            <center>
                                <img style="padding:0px; text-align:center;background:none; margin-top:10px;height:100px; width:100px;"
                                    src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=<?= urlencode('https://localhost/ska/verify-marksheet?reg=' . urlencode($reg_no) . '&course=' . urlencode($student['course_name'])) ?>"
                                    title="Scan to Verify Marksheet">
                            </center>
                        </td>
                    </tr>
                    <tr>
                        <td style="border:none;text-align:center;"><?= $issue_date ?></td>
                    </tr>
                    <tr>
                        <td style="border:none;text-align:center;"><strong>Issue Date</strong></td>
                    </tr>
                </tbody>
            </table>

        </div>
    </div>

    <!-- Print Button for Screen -->
    <div class="screen-only"
        style="position:fixed; top:20px; right:20px; z-index:9999; background:white; padding:20px; border-radius:10px; box-shadow:0 5px 20px rgba(0,0,0,0.3);">
        <a href="#" onclick="window.print(); return false;"
            style="text-decoration:none; padding:10px 20px; background:#007bff; color:white; border-radius:5px; display:inline-block;">
            <i class="bi bi-printer"></i> Print Marksheet
        </a>
        <a href="view-student?reg=<?= urlencode($reg_no) ?>"
            style="text-decoration:none; padding:10px 20px; background:#6c757d; color:white; border-radius:5px; margin-left:10px; display:inline-block;">
            Back to Student
        </a>
    </div>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</body>

</html>