<?php
session_start();
include '../includes/db.php';
require_once __DIR__ . '/../includes/anti_inspect.php';

if (!isset($_SESSION['admin'])) {
    include(__DIR__ . '/../forbidden.php');
    exit();
}

if (!isset($_GET['reg']) || empty($_GET['reg'])) {
    die('Invalid Registration Number');
}
$reg_no = $_GET['reg'];

// Fetch student + course + study centre
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

if (!$student)
    die('Student not found!');

// Accurate Paid / Due Fees
$paid_stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM fee_payments WHERE reg_no = ?");
$paid_stmt->execute([$reg_no]);
$paid_fees = $paid_stmt->fetchColumn();
$due_fees = $student['total_fees'] - $paid_fees;

// Fee payments & results
$payment_stmt = $pdo->prepare("SELECT * FROM fee_payments WHERE reg_no = ? ORDER BY payment_date DESC");
$payment_stmt->execute([$reg_no]);
$payments = $payment_stmt->fetchAll();

$result_stmt = $pdo->prepare("SELECT * FROM results WHERE reg_no = ? ORDER BY result_date DESC");
$result_stmt->execute([$reg_no]);
$results = $result_stmt->fetchAll();

?>
<?php include 'header.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($student['student_name']) ?> - <?= $reg_no ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }

        .section-card {
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
            border-radius: 16px;
            margin-bottom: 30px;
            border: none;
        }

        .card-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            font-weight: 600;
        }

        .photo-img {
            width: 170px;
            height: 190px;
            object-fit: cover;
            border: 6px solid white;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            border-radius: 12px;
        }

        .badge-status {
            font-size: 1.1rem;
            padding: 0.7rem 1.4rem;
            border-radius: 50px;
        }

        .info-label {
            font-weight: 600;
            color: #2c3e50;
        }
    </style>
</head>

<body>

    <div class="container mt-4 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-primary mb-0">Student Complete Profile</h2>
            <a href="students" class="btn btn-outline-secondary">Back</a>
        </div>

        <!-- Personal Information -->
        <div class="card section-card">
            <div class="card-header">
                <h5 class="mb-0">Personal Information</h5>
            </div>
            <div class="card-body bg-white">
                <div class="row align-items-start">




                    <div class="col-lg-3 text-center mb-4">
    <?php
    // Proper photo path logic
    $photo_filename = trim($student['photo'] ?? '');
    $photo_path = $photo_filename ? "../assets/images/students/" . htmlspecialchars($photo_filename) : "../assets/images/default.jpeg";
    ?>
    
    <div class="position-relative d-inline-block photo-container">
        <img src="<?= $photo_path ?>" alt="Student Photo" class="photo-img"
            onerror="this.src='../assets/images/default.jpeg'; this.alt='Default Photo';">
        
        <div class="photo-overlay">
            <i class="bi bi-camera-fill text-white mb-2" style="font-size: 2rem;"></i>
            <button type="button" class="btn btn-light btn-sm" onclick="document.getElementById('photoUpload').click()">
                <i class="bi bi-upload me-1"></i> Change Photo
            </button>
            <small class="text-white mt-2 d-block opacity-75">Max 5MB</small>
        </div>
    </div>
    
    <form action="update-student-photo.php" method="POST" enctype="multipart/form-data" id="photoForm">
        <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
        <input type="hidden" name="reg_no" value="<?= $reg_no ?>">
        <input type="hidden" name="redirect_to" value="view-student?reg=<?= urlencode($reg_no) ?>">
        <input type="file" name="photo" id="photoUpload" accept="image/jpeg,image/png,image/gif,image/webp" 
               style="display:none;" onchange="validateAndSubmit(this)">
    </form>

   <div class="mt-4">
    
    <!-- Upload Documents Button -->
  <a href="upload-student-document?reg=<?= $reg_no ?>" 
   class="btn btn-outline-primary px-4 shadow">
    <i class="bi bi-file-earmark-arrow-up"></i> Manage Documents
</a>

</div>

</div>

<style>
.photo-container {
    position: relative;
    overflow: hidden;
    display: inline-block;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.photo-img {
    display: block;
    width: 170px;
    height: 190px;
    object-fit: cover;
    border: 6px solid white;
    border-radius: 12px;
}

.photo-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
    border-radius: 15px;
}

.photo-container:hover .photo-overlay {
    opacity: 1;
}

.photo-overlay .btn {
    transform: translateY(10px);
    transition: transform 0.3s ease;
}

.photo-container:hover .photo-overlay .btn {
    transform: translateY(0);
}
</style>

<script>
function validateAndSubmit(input) {
    const file = input.files[0];
    if (!file) return;

    // Validate file size (5MB)
    const maxSize = 5 * 1024 * 1024;
    if (file.size > maxSize) {
        alert('File size must be less than 5MB\n\nYour file: ' + (file.size / 1024 / 1024).toFixed(2) + 'MB');
        input.value = '';
        return;
    }

    // Validate file type
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        alert('Invalid file type!\n\nAllowed: JPG, PNG, GIF, WebP\nYour file: ' + file.type);
        input.value = '';
        return;
    }

    // Show loading overlay
    const overlay = document.querySelector('.photo-overlay');
    const originalContent = overlay.innerHTML;
    overlay.innerHTML = '<div class="spinner-border text-light" role="status"><span class="visually-hidden">Uploading...</span></div><small class="text-white mt-2">Please wait...</small>';
    overlay.style.opacity = '1';
    
    // Submit form
    document.getElementById('photoForm').submit();
}
</script>



                    <div class="col-lg-9">
                        <div class="row g-3">
                            <div class="col-lg-3 col-md-6 mb-2">
                                <span class="info-label">Reg No:</span>
                                <span class="badge bg-dark fs-6"><?= $reg_no ?></span>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-2">
                                <span class="info-label">Name:</span>
                                <strong><?= htmlspecialchars($student['student_name']) ?></strong>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-2">
                                <span class="info-label">Father's Name:</span>
                                <?= htmlspecialchars($student['father_name'] ?: '—') ?>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-2">
                                <span class="info-label">Mother's Name:</span>
                                <?= htmlspecialchars($student['mother_name'] ?: '—') ?>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-2">
                                <span class="info-label">DOB:</span>
                                <?= $student['dob'] ? date('d-M-Y', strtotime($student['dob'])) : '—' ?>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-2">
                                <span class="info-label">Gender:</span>
                                <?= $student['gender'] ?: '—' ?>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-2">
                                <span class="info-label">Category:</span>
                                <?= $student['category'] ?: '—' ?>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-2">
                                <span class="info-label">Religion:</span>
                                <?= htmlspecialchars($student['religion'] ?: '—') ?>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-2">
                                <span class="info-label">Marital:</span>
                                <?= $student['marital_status'] ?: '—' ?>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-2">
                                <span class="info-label">ID Proof:</span>
                                <?= $student['identity_type'] ?> - <?= htmlspecialchars($student['id_number'] ?: '—') ?>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-2">
                                <span class="info-label">Qualification:</span>
                                <?= htmlspecialchars($student['qualification'] ?: '—') ?>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-2">
                                <span class="info-label">Enquiry Source:</span>
                                <?= htmlspecialchars($student['enquiry_source'] ?: '—') ?>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Contact & Address -->
        <div class="card section-card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Contact & Address</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-3 col-md-6 mb-2"><span class="info-label">Mobile:</span>
                        <strong><?= $student['mobile'] ?></strong>
                    </div>
                    <div class="col-lg-5 col-md-6 mb-2"><span class="info-label">Email:</span>
                        <?= $student['email'] ? htmlspecialchars($student['email']) : '—' ?></div>
                    <div class="col-lg-12 mb-2"><span class="info-label">Address:</span>
                        <?= nl2br(htmlspecialchars($student['address'] ?: '—')) ?></div>
                    <div class="col-lg-3 col-md-6 mb-2"><span class="info-label">City:</span>
                        <?= htmlspecialchars($student['city'] ?: '—') ?></div>
                    <div class="col-lg-3 col-md-6 mb-2"><span class="info-label">District:</span>
                        <?= htmlspecialchars($student['district'] ?: '—') ?></div>
                    <div class="col-lg-3 col-md-6 mb-2"><span class="info-label">State:</span>
                        <?= htmlspecialchars($student['state'] ?: '—') ?></div>
                    <div class="col-lg-3 col-md-6 mb-2"><span class="info-label">Pincode:</span>
                        <?= $student['pincode'] ?: '—' ?></div>
                </div>
            </div>
        </div>

        <!-- Academic & Fee Summary -->
        <div class="card section-card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Academic & Fee Details</h5>
            </div>
            <div class="card-body">
                <div class="row g-3 align-items-start">
                    <div class="col-lg-3 col-md-6 mb-2">
                        <small class="info-label d-block text-muted mb-1">Course:</small>
                        <strong class="d-block"><?= htmlspecialchars($student['course_name']) ?></strong>
                        <small
                            class="d-block text-muted"><?= $student['duration'] ? "({$student['duration']})" : '' ?></small>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-2">
                        <small class="info-label d-block text-muted mb-1">Study Centre:</small>
                        <strong
                            class="d-block"><?= htmlspecialchars($student['center_name'] ?: 'Main Centre') ?></strong>
                        <small
                            class="d-block text-muted"><?= $student['center_code'] ? "({$student['center_code']})" : '' ?></small>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-2">
                        <small class="info-label d-block text-muted mb-1">Admission Date:</small>
                        <span class="d-block"><?= date('d-M-Y', strtotime($student['admission_date'])) ?></span>
                    </div>

                    <div class="col-lg-3 col-md-6 mb-2">
                        <small class="info-label d-block text-muted mb-1">Session:</small>
                        <?= $student['session_year'] ?: '—' ?>
                    </div>

                    <div class="col-lg-3 col-md-6 mb-2">
                        <small class="info-label d-block text-muted mb-1">Total Fees:</small>
                        <strong
                            class="text-primary fs-5 d-block">₹<?= number_format($student['total_fees'], 2) ?></strong>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-2">
                        <small class="info-label d-block text-muted mb-1">Paid:</small>
                        <strong class="text-success fs-5 d-block">₹<?= number_format($paid_fees, 2) ?></strong>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-2">
                        <small class="info-label d-block text-muted mb-1">Due:</small>
                        <strong class="text-danger fs-5 d-block">₹<?= number_format($due_fees, 2) ?></strong>
                    </div>

                    <div class="col-12 text-end mb-3">
                        <small class="info-label d-inline-block text-muted me-3">Status:</small>
                        <?php
                        $status = $student['status'] ?? 'Active';
                        if ($status == 'Active')
                            $color = 'success';
                        elseif ($status == 'Completed')
                            $color = 'primary';
                        elseif ($status == 'Dropped')
                            $color = 'danger';
                        else
                            $color = 'warning';
                        ?>
                        <span class="badge bg-<?= $color ?> fs-6"><?= $status ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fee History -->
        <div class="card section-card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">Fee Payment History</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Date</th>
                                <th>Receipt No</th>
                                <th>Amount</th>
                                <th>Mode</th>
                                <th>Cashier</th>
                                <th>PDF</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $p): ?>
                                <tr>
                                    <td><?= date('d-M-Y', strtotime($p['payment_date'])) ?></td>
                                    <td><span class="badge bg-secondary"><?= $p['receipt_no'] ?></span></td>
                                    <td><strong>₹<?= number_format($p['amount'], 2) ?></strong></td>
                                    <td><?= htmlspecialchars($p['payment_mode']) ?></td>
                                    <td><?= htmlspecialchars($p['added_by']) ?></td>
                                    <!-- Replace this link: -->
                                    <td>
                                        <span class="btn btn-sm btn-success disabled"
                                            style="pointer-events: none; cursor: not-allowed;" title="Not available">
                                            View
                                        </span>
                                    </td>

                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($payments)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No payments yet</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Results -->
        <div class="card section-card">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">Exam Results</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Exam Month</th>
                                <th>Subject Code</th>
                                <th>Theory</th>
                                <th>Total</th>
                                <th>%</th>
                                <th>Status</th>
                                <th>Result Date</th>

                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $r): ?>
                                <tr>
                                    <td><?= $r['exam_held_on'] ?></td>
                                    <td><?= $r['subject_code'] ?></td>
                                    <td><?= $r['theory_marks'] ?></td>
                                    <td><strong><?= $r['total_theory_marks'] ?></strong></td>
                                    <td><?= number_format(($r['theory_marks'] / $r['total_theory_marks']) * 100, 1) ?>%</td>
                                    <td>
                                        <span class="badge 
                                        <?= $r['result_status'] == 'PASS' ? 'bg-success' :
                                            ($r['result_status'] == 'ABSENT' ? 'bg-secondary' :
                                                ($r['result_status'] == 'FAIL' ? 'bg-danger' :
                                                    ($r['result_status'] == 'PENDING' ? 'bg-warning' : 'bg-info')))
                                            ?>">
                                            <?= htmlspecialchars($r['result_status']) ?>
                                        </span>
                                    </td>
                                    <td><?= $r['result_date'] ? date('d-M-Y', strtotime($r['result_date'])) : '—' ?></td>

                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($results)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No results yet</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
<div class="text-center mt-5">
    <div class="btn-group btn-group-lg gap-2" role="group">
        <a href="edit-student?reg=<?= urlencode($reg_no) ?>" class="btn btn-outline-warning px-4 shadow">
            <i class="bi bi-pencil-square"></i> Edit
        </a>
        <a href="add-fee?reg=<?= urlencode($reg_no) ?>" class="btn btn-outline-success px-4 shadow">
            <i class="bi bi-cash-coin"></i> Pay Fee
        </a>
        <a href="../pdf/idcard?reg=<?= $reg_no ?>" target="_blank" class="btn btn-outline-primary px-4 shadow">
            <i class="bi bi-person-vcard"></i> ID Card
        </a>
        <a href="../pdf/certificate?reg=<?= $reg_no ?>" target="_blank"
           class="btn btn-outline-success px-4 shadow">
            <i class="bi bi-award-fill"></i> Certificate
        </a>
        <a href="../pdf/marksheet?reg=<?= $reg_no ?>" target="_blank"
           class="btn btn-outline-secondary px-4 shadow">
            <i class="bi bi-clipboard-data"></i> Marksheet
        </a>
        <a href="../pdf/admitcard?reg=<?= $reg_no ?>" target="_blank"
           class="btn btn-outline-info px-4 shadow">
            <i class="bi bi-file-earmark-check"></i> Admit Card
        </a>
        <a href="add-result?reg=<?= $reg_no ?>" target="_blank"
           class="btn btn-outline-danger px-4 shadow">
            <i class="bi bi-clipboard-check"></i> Add Result
        </a>
    </div>
</div>

    </div>

    <?php include '../includes/../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>