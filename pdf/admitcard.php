<?php
session_start();
include '../includes/db.php';
require_once __DIR__ . '/../includes/anti_inspect.php';

if (!isset($_SESSION['admin']) && !isset($_SESSION['student'])) {
    die('Access Denied');
}

// Make sure reg number is provided
if (!isset($_GET['reg']) || empty($_GET['reg'])) {
  die('Invalid Registration Number');
}
$reg_no = $_GET['reg'];

// Fetch student along with course and study centre details
$stmt = $pdo->prepare("
    SELECT s.*, 
           c.course_name, c.course_code, c.duration,
           sc.center_name, sc.center_code, sc.address AS center_address
    FROM students s
    LEFT JOIN courses c ON s.course_code = c.course_code
    LEFT JOIN study_centers sc ON s.study_center_code = sc.center_code
    WHERE s.reg_no = ?
");

$stmt->execute([$reg_no]);
$student = $stmt->fetch();

if (!$student)
  die('Student not found');

// Fetch exam schedule - only today and future dates
$exam_stmt = $pdo->prepare("SELECT * FROM exam_schedule WHERE course_code = ? AND is_scheduled = 1 AND exam_date >= CURDATE() ORDER BY exam_date ASC, exam_time ASC");
$exam_stmt->execute([$student['course_code']]);
$subjects = $exam_stmt->fetchAll();

// Check if no exams scheduled [web:105][web:106]
if (count($subjects) == 0) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>No Exam Scheduled</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card shadow-lg border-0">
                        <div class="card-body text-center p-5">
                            <i class="bi bi-calendar-x text-danger" style="font-size: 5rem;"></i>
                            <h2 class="mt-4 text-danger">No Exam Scheduled</h2>
                            <p class="text-muted mb-4">
                                There are no upcoming exams scheduled for<br>
                                <strong><?= htmlspecialchars($student['course_name']) ?></strong>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Admit Card - <?= htmlspecialchars($student['student_name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background: #f8f9fa;
    }

    .profile-card {
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
      border-radius: 20px;
      overflow: hidden;
      border: none;
    }

    .profile-header {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      padding: 1.5rem 1rem;
    }

    .text-gradient {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .table th,
    .table td {
      vertical-align: middle !important;
      padding: 0.4rem !important;
      font-size: 0.9rem;
    }

    .print-btn {
      background: #009900;
      color: #fff;
      font-weight: 600;
      border: none;
      padding: 10px 20px;
    }

    .print-btn:hover {
      background: #007f00;
      color: #fff;
    }

    /* Print-specific styles */
    @media print {
      body {
        margin: 0;
        padding: 0;
      }

      .profile-card {
        box-shadow: none;
        border-radius: 0;
        page-break-inside: avoid;
      }

      .profile-header {
        padding: 1rem;
      }

      .profile-header h2 {
        font-size: 1.5rem;
        margin-bottom: 0.3rem;
      }

      .profile-header p {
        font-size: 0.9rem;
        margin-bottom: 0.3rem;
      }

      .card-body {
        padding: 1rem !important;
      }

      hr {
        margin: 0.8rem 0 !important;
      }

      h4 {
        font-size: 1.1rem;
        margin-bottom: 0.5rem !important;
      }

      .table {
        font-size: 0.85rem;
      }

      img {
        max-width: 140px !important;
        max-height: 140px !important;
      }

      .invig-box {
        min-height: 60px !important;
        padding: 0.5rem !important;
      }

      .row.g-4 {
        margin-top: 0 !important;
        margin-bottom: 0 !important;
      }

      .mt-4,
      .my-4 {
        margin-top: 0.8rem !important;
      }

      .mb-3 {
        margin-bottom: 0.5rem !important;
      }

      .mt-2 {
        margin-top: 0.5rem !important;
      }
    }

    @page {
      size: A4;
      margin: 10mm;
    }
  </style>
</head>

<body>

  <!-- Printable Area -->
  <div id="printableArea" class="card profile-card">
    <div class="profile-header text-center">
      <i class="bi bi-mortarboard display-4 d-block mb-2"></i>
      <h2 class="fw-bold mb-1"><?= htmlspecialchars($student['student_name']) ?></h2>
      <p class="mb-1"><?= htmlspecialchars($student['father_name']) ?></p>
      <span class="badge bg-light text-dark px-3 py-2 fs-6">
        Reg No: <?= htmlspecialchars($student['reg_no']) ?>
      </span>
    </div>

    <div class="card-body p-3 bg-white">
      <div class="row g-3">
        <div class="col-md-8">
          <table class="table table-borderless mb-0">
            <tbody>
              <tr>
                <th style="width: 35%;">Course Code:</th>
                <td class="text-primary"><?= htmlspecialchars($student['course_code'] ?: '—') ?></td>
              </tr>
              <tr>
                <th>Course Name:</th>
                <td class="text-primary"><?= htmlspecialchars($student['course_name'] ?: '—') ?></td>
              </tr>
              <tr>
                <th>Duration:</th>
                <td class="text-primary"><?= htmlspecialchars($student['duration'] ?: '—') ?></td>
              </tr>
              <tr>
                <th>Father's Name:</th>
                <td class="text-primary"><?= htmlspecialchars($student['father_name'] ?: '—') ?></td>
              </tr>
              <tr>
                <th>Mother's Name:</th>
                <td class="text-primary"><?= htmlspecialchars($student['mother_name'] ?: '—') ?></td>
              </tr>
              <tr>
                <th>DOB:</th>
                <td class="text-primary"><?= $student['dob'] ? date('d-M-Y', strtotime($student['dob'])) : '—' ?></td>
              </tr>
              <tr>
                <th>Address:</th>
                <td class="text-primary"><?= nl2br(htmlspecialchars($student['address'] ?: '—')) ?></td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="col-md-4 text-center">
          <?php
          // Proper photo path logic
          $photo_filename = trim($student['photo'] ?? '');
          $photo_path = $photo_filename ? "../assets/images/students/" . htmlspecialchars($photo_filename) : "../assets/images/default.jpeg";
          ?>
          <img src="<?= $photo_path ?>" alt="Student Photo" class="rounded-circle" width="160" height="160"
            onerror="this.onerror=null;this.src='../assets/images/default.jpeg';">
        </div>
      </div>

      <hr class="my-3">

      <h4 class="fw-bold text-center mb-2">Exam Schedule</h4>

      <div class="table-responsive">
        <table class="table table-bordered text-center" style="border: 2px solid #000;">
          <thead class="table-primary">
            <tr>
              <th style="width:33%; border: 1px solid #000; padding: 0.5rem;">Theory Exam</th>

              <th style="width:34%; border: 1px solid #000; padding: 0.5rem;">Center Details</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $rowspan = count($subjects); // Get total number of subjects
            $first = true;

            foreach ($subjects as $index => $exam):
              ?>
              <tr>
                <td style="border: 1px solid #000; padding: 0.5rem;">
                  <strong>Subject:</strong> <?= htmlspecialchars($exam['subject_code'] ?: '—') ?> |
                  <strong>Date:</strong> <?= htmlspecialchars($exam['exam_date'] ?: '—') ?> |
                  <strong>Time:</strong>
                  <?= $exam['exam_time'] ? date('g:i A', strtotime($exam['exam_time'])) : '—' ?>

                </td>

                <?php if ($first): ?>
                  <td style="border: 1px solid #000; padding: 0.5rem; vertical-align: middle;" rowspan="<?= $rowspan ?>">
                    <div><strong>Center Code:</strong> <?= htmlspecialchars($student['center_code'] ?: '—') ?></div>
                    <div><strong>Center Name:</strong> <?= htmlspecialchars($student['center_name'] ?: '—') ?></div>
                    <div><strong>Address:</strong> <?= nl2br(htmlspecialchars($student['center_address'] ?: '—')) ?></div>
                  </td>
                  <?php $first = false; ?>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>

        </table>
      </div>

      <p class="text-center text-muted mt-2 mb-0 small">
        <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
        Appear only at the mentioned examination center with a valid ID.
      </p>
    </div>
  </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    function printPageArea(areaID) {
      var printContent = document.getElementById(areaID).innerHTML;
      var w = window.open('', '', 'width=1100,height=750');
      w.document.write('<html><head><title>Print</title>');
      w.document.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">');
      w.document.write('<style>');
      w.document.write('@page { size: A4; margin: 10mm; }');
      w.document.write('body { margin: 0; padding: 0; }');
      w.document.write('.profile-header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 1rem; }');
      w.document.write('.card-body { padding: 1rem !important; }');
      w.document.write('.table th, .table td { padding: 0.4rem !important; font-size: 0.85rem; }');
      w.document.write('img { max-width: 140px !important; max-height: 140px !important; }');
      w.document.write('.invig-box { min-height: 60px !important; }');
      w.document.write('hr { margin: 0.8rem 0 !important; }');
      w.document.write('h2 { font-size: 1.5rem; }');
      w.document.write('h4 { font-size: 1.1rem; margin-bottom: 0.5rem !important; }');
      w.document.write('</style>');
      w.document.write('</head><body>');
      w.document.write(printContent);
      w.document.write('</body></html>');
      w.document.close();
      w.focus();
      w.print();
      w.close();
    }
  </script>
</body>

</html>