<?php
session_start();
include '../includes/db.php';
require_once __DIR__ . '/../includes/anti_inspect.php';

// Check if student is logged in
if (!isset($_SESSION['student'])) {
    header("Location: student-login");
    exit();
}

$student = $_SESSION['student'];
$reg_no = $student['reg_no'];

// Fetch student + course + study centre (same query as admin)
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
$student_details = $stmt->fetch();

if (!$student_details)
    die('Student profile not found!');

// Accurate Paid / Due Fees
$paid_stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM fee_payments WHERE reg_no = ?");
$paid_stmt->execute([$reg_no]);
$paid_fees = $paid_stmt->fetchColumn();
$due_fees = $student_details['total_fees'] - $paid_fees;

// Fee payments & results
$payment_stmt = $pdo->prepare("SELECT * FROM fee_payments WHERE reg_no = ? ORDER BY payment_date DESC LIMIT 5");
$payment_stmt->execute([$reg_no]);
$payments = $payment_stmt->fetchAll();

$result_stmt = $pdo->prepare("SELECT * FROM results WHERE reg_no = ? ORDER BY result_date DESC LIMIT 5");
$result_stmt->execute([$reg_no]);
$results = $result_stmt->fetchAll();

// Check if QR code exists
$qr_path = "../assets/images/qrpayment.jpeg"; // or .jpg
$qr_exists = file_exists($qr_path);

// Status + flag for document enabling
$status = $student_details['status'] ?? 'Active';
$color = $status == 'Active' ? 'success' : ($status == 'Completed' ? 'primary' : 'warning');
// Only Active or Completed can download idcard/admit/marksheet/certificate
$status_allowed = in_array($status, ['Active', 'Completed']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Dashboard - <?= htmlspecialchars($student_details['student_name']) ?></title>
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

        .action-btn {
            padding: 12px 24px;
            font-size: 1.1rem;
            border-radius: 12px;
        }

        .qr-modal .modal-content {
            border-radius: 20px;
        }

        .qr-container {
            text-align: center;
            padding: 2rem;
        }

        .qr-code {
            max-width: 300px;
            max-height: 300px;
            border: 4px solid #28a745;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(40, 167, 69, 0.3);
        }
    </style>
</head>

<body>

    <div class="container mt-4 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-primary mb-0">
                <i class="bi bi-person-circle me-2"></i>
                Welcome, <?= htmlspecialchars($student_details['student_name']) ?>
            </h2>
            <a href="../student-login?logout=1" class="btn btn-outline-danger action-btn">
                <i class="bi bi-box-arrow-right me-2"></i>Logout
            </a>
        </div>

        <!-- Personal Information -->
        <div class="card section-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-person me-2"></i>Personal Information</h5>
                <a href="view-full-profile?reg=<?= htmlspecialchars($reg_no) ?>" target="_blank"
                    class="btn btn-sm btn-outline-light">
                    <i class="bi bi-eye"></i> Full Profile
                </a>
            </div>

            <div class="card-body bg-white">
                <div class="row align-items-start">

                                   <div class="col-lg-3 text-center mb-4">
    <?php
    // Proper photo path logic
    $photo_filename = trim($student_details['photo'] ?? '');
    $photo_path = $photo_filename ? "../assets/images/students/" . htmlspecialchars($photo_filename) : "../assets/images/default.jpeg";
    ?>
    
    <div class="position-relative d-inline-block photo-container">
        <img src="<?= $photo_path ?>" alt="Student Photo" class="photo-img"
            onerror="this.src='../assets/images/default.jpeg'; this.alt='Default Photo';">

    <!--Comment this if you want to allow student for photo update -->
<?php if (trim($photo_filename) === ''): ?>
    <div class="photo-overlay"
         title="Contact your branch center to update photo">
        <i class="bi bi-camera-fill text-white mb-2" style="font-size: 2rem;"></i>
        <button type="button" class="btn btn-light btn-sm" onclick="document.getElementById('photoUpload').click()">
            <i class="bi bi-upload me-1"></i> Change Photo
        </button>
        <small class="text-white mt-2 d-block opacity-75">Max 5MB</small>
    </div>
<?php elseif (trim($photo_filename) !== ''): ?>
    <div class="photo-overlay"
         title="Please contact Admin to change your photo">
<small class="mt-2 d-block" style="color: #ffffff; font-weight: 600;">
    Photo already set. Contact Admin to change.
</small>
    </div>
<?php endif; ?>

    </div>
    
    <form action="update-student-photo.php" method="POST" enctype="multipart/form-data" id="photoForm">
        <input type="hidden" name="student_id" value="<?= $student_details['id'] ?>">
        <input type="hidden" name="reg_no" value="<?= $reg_no ?>">
        <input type="hidden" name="redirect_to" value="student-dashboard?reg=<?= urlencode($reg_no) ?>">
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
                        <div class="row g-4">
                            <!-- Row 1: Reg No + Name -->
                            <div class="col-md-6">
                                <span class="info-label d-block mb-1">Reg No:</span>
                                <span class="badge bg-dark fs-5"><?= $reg_no ?></span>
                            </div>
                            <div class="col-md-6">
                                <span class="info-label d-block mb-1">Name:</span>
                                <strong><?= htmlspecialchars($student_details['student_name']) ?></strong>
                            </div>

                            <!-- Row 2: Father + Mother -->
                            <div class="col-md-6">
                                <span class="info-label d-block mb-1">Father's Name:</span>
                                <?= htmlspecialchars($student_details['father_name'] ?: '—') ?>
                            </div>
                            <div class="col-md-6">
                                <span class="info-label d-block mb-1">Mother's Name:</span>
                                <?= htmlspecialchars($student_details['mother_name'] ?: '—') ?>
                            </div>

                            <!-- Row 3: DOB + Gender -->
                            <div class="col-md-6">
                                <span class="info-label d-block mb-1">DOB:</span>
                                <?= $student_details['dob'] ? date('d-M-Y', strtotime($student_details['dob'])) : '—' ?>
                            </div>
                            <div class="col-md-6">
                                <span class="info-label d-block mb-1">Gender:</span>
                                <?= $student_details['gender'] ?: '—' ?>
                            </div>

                            <!-- Row 4: Mobile + Address -->
                            <div class="col-md-6">
                                <span class="info-label d-block mb-1">Mobile:</span>
                                <strong><?= $student_details['mobile'] ?></strong>
                            </div>
                            <div class="col-md-6">
                                <span class="info-label d-block mb-1">Address:</span>
                                <span
                                    style="font-size: 0.9em; line-height: 1.3; display: block; max-height: 3.6em; overflow: hidden;">
                                    <?= htmlspecialchars(substr($student_details['address'] ?: '—', 0, 80)) ?>...
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Academic & Fee Summary -->
        <div class="card section-card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-book me-2"></i>Academic & Fee Details</h5>
            </div>
            <div class="card-body">
                <div class="row g-4 align-items-center">
                    <div class="col-md-4">
                        <span class="info-label">Course:</span>
                        <strong><?= htmlspecialchars($student_details['course_name']) ?></strong>
                        <?= $student_details['duration'] ? "({$student_details['duration']})" : '' ?>
                    </div>
                    <div class="col-md-4">
                        <span class="info-label">Study Centre:</span>
                        <strong><?= htmlspecialchars($student_details['center_name'] ?: 'Main Centre') ?></strong>
                        <?= $student_details['center_code'] ? " ({$student_details['center_code']})" : '' ?>
                    </div>
                    <div class="col-md-4">
                        <span class="info-label">Admission:</span>
                        <?= date('d-M-Y', strtotime($student_details['admission_date'])) ?>
                    </div>

                    <div class="col-md-4">
                        <span class="info-label">Total Fees:</span>
                        <strong
                            class="text-primary fs-5">₹<?= number_format($student_details['total_fees'], 2) ?></strong>
                    </div>
                    <div class="col-md-4">
                        <span class="info-label">Paid:</span>
                        <strong class="text-success fs-5">₹<?= number_format($paid_fees, 2) ?></strong>
                    </div>
                    <div class="col-md-4">
                        <span class="info-label">Due:</span>
                        <strong class="fs-5 <?= $due_fees > 0 ? 'text-danger' : 'text-success' ?>">
                            ₹<?= number_format($due_fees, 2) ?> <?= $due_fees > 0 ? '(Pending)' : '(Cleared)' ?>
                        </strong>
                    </div>

                    <div class="col-md-12 text-end">
                        <span class="info-label me-3">Status:</span>
                        <span class="badge badge-status bg-<?= $color ?>"><?= htmlspecialchars($status) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions / Download Documents -->
        <div class="card section-card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-download me-2"></i>Download Documents</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">

                    <!-- ID Card -->
                    <div class="col-md-2 col-sm-4 col-6">
                        <?php if ($status_allowed && isset($student_details['id_card_generated']) && $student_details['id_card_generated'] === 'Yes'): ?>
                            <a href="../pdf/idcard?reg=<?= htmlspecialchars($reg_no) ?>" target="_blank"
                                class="btn btn-outline-primary w-100 action-btn">
                                <i class="bi bi-card-text fs-4 d-block mb-1"></i>ID Card
                            </a>
                        <?php else: ?>
                            <a href="#" class="btn btn-outline-primary w-100 action-btn disabled"
                                style="opacity:0.6; cursor:not-allowed;"
                                title="ID Card not available. Your status must be Active or Completed.">
                                <i class="bi bi-card-text fs-4 d-block mb-1"></i>ID Card
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Admit Card -->
                    <div class="col-md-2 col-sm-4 col-6">
                        <?php if ($status_allowed && isset($student_details['admit_card_gen']) && $student_details['admit_card_gen'] === 'Yes'): ?>
                            <a href="../pdf/admitcard?reg=<?= htmlspecialchars($reg_no) ?>" target="_blank"
                                class="btn btn-outline-warning w-100 action-btn">
                                <i class="bi bi-file-earmark-text fs-4 d-block mb-1"></i>Admit Card
                            </a>
                        <?php else: ?>
                            <a href="#" class="btn btn-outline-warning w-100 action-btn disabled"
                                style="opacity:0.6; cursor:not-allowed;"
                                title="Admit Card not available. Your status must be Active or Completed.">
                                <i class="bi bi-file-earmark-text fs-4 d-block mb-1"></i>Admit Card
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Marksheet -->
                    <div class="col-md-2 col-sm-4 col-6">
                        <?php if ($status_allowed && isset($student_details['marksheet_gen']) && $student_details['marksheet_gen'] === 'Yes'): ?>
                            <a href="../pdf/marksheet?reg=<?= htmlspecialchars($reg_no) ?>" target="_blank"
                                class="btn btn-outline-info w-100 action-btn">
                                <i class="bi bi-trophy fs-4 d-block mb-1"></i>Marksheet
                            </a>
                        <?php else: ?>
                            <a href="#" class="btn btn-outline-info w-100 action-btn disabled"
                                style="opacity:0.6; cursor:not-allowed;"
                                title="Marksheet not available. Your status must be Active or Completed.">
                                <i class="bi bi-trophy fs-4 d-block mb-1"></i>Marksheet
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Certificate -->
                    <div class="col-md-2 col-sm-4 col-6">
                        <?php if ($status_allowed && isset($student_details['certificate_gen']) && $student_details['certificate_gen'] === 'Yes'): ?>
                            <a href="../pdf/certificate?reg=<?= htmlspecialchars($reg_no) ?>" target="_blank"
                                class="btn btn-outline-success w-100 action-btn">
                                <i class="bi bi-award fs-4 d-block mb-1"></i>Certificate
                            </a>
                        <?php else: ?>
                            <a href="#" class="btn btn-outline-success w-100 action-btn disabled"
                                style="opacity:0.6; cursor:not-allowed;"
                                title="Certificate not available. Your status must be Active or Completed.">
                                <i class="bi bi-award fs-4 d-block mb-1"></i>Certificate
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Payment History -->
                    <div class="col-md-2 col-sm-4 col-6">
                        <a href="payment-history?reg=<?= htmlspecialchars($reg_no) ?>" target="_blank"
                            class="btn btn-outline-secondary w-100 action-btn">
                            <i class="bi bi-receipt fs-4 d-block mb-1"></i>Payment History
                        </a>
                    </div>

                    <!-- Pay Now if Due -->
                    <?php if ($due_fees > 0): ?>
                        <div class="col-md-2 col-sm-4 col-6">
                            <button class="btn btn-danger w-100 action-btn" data-bs-toggle="modal"
                                data-bs-target="#qrModal">
                                <i class="bi bi-credit-card fs-4 d-block mb-1"></i>Pay ₹<?= number_format($due_fees) ?>
                            </button>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <!-- QR Code / Phone Payment Modal -->
        <?php if ($due_fees > 0): ?>
            <div class="modal fade qr-modal" id="qrModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title">
                                <i class="bi bi-qr-code-scan me-2"></i>Pay Fees (₹<?= number_format($due_fees, 2) ?>)
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body qr-container">
                            <?php if ($qr_exists): ?>
                                <p class="lead mb-4">Scan QR Code to complete payment</p>
                                <img src="<?= $qr_path ?>" alt="Payment QR Code" class="qr-code img-fluid mx-auto d-block mb-3">
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle me-2"></i>
                                    After scanning, contact admin for verification.
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <div class="display-4 fw-bold text-primary mb-4" style="font-family: monospace;">
                                        99339<br>98838
                                    </div>
                                    <div class="bg-light p-4 rounded-4 shadow-sm mb-4"
                                        style="max-width: 350px; margin: 0 auto;">
                                        <div class="h4 fw-bold text-dark mb-2">Contact for Payment</div>
                                        <div class="fs-3 fw-bold text-success mb-1">+91 99339 98838</div>
                                        <div class="text-muted">Pay ₹<?= number_format($due_fees, 2) ?> via UPI/GPay/PhonePe
                                        </div>
                                    </div>
                                    <div class="alert alert-info">
                                        <i class="bi bi-telephone-forward me-2"></i>
                                        Send payment screenshot to this number after transaction.
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Recent Fee History -->
        <?php if ($payments): ?>
            <div class="card section-card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="bi bi-receipt me-2"></i>Recent Fee Payments (Last 5)</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Date</th>
                                    <th>Receipt</th>
                                    <th>Amount</th>
                                    <th>Mode</th>
                                    <th>Cashier</th>
                                    <th>View</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $p): ?>
                                    <tr>
                                        <td><?= date('d-M-Y', strtotime($p['payment_date'])) ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($p['receipt_no']) ?></span>
                                        </td>
                                        <td><strong>₹<?= number_format($p['amount'], 2) ?></strong></td>
                                        <td><?= htmlspecialchars($p['payment_mode']) ?></td>
                                        <td><?= htmlspecialchars($p['added_by']) ?></td>
                                        <td>
                                            <span class="btn btn-sm btn-success disabled"
                                                style="opacity: 0.6; cursor: not-allowed;" title="View not available">
                                                View
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card section-card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="bi bi-receipt me-2"></i>Fee Payments</h5>
                </div>
                <div class="card-body text-center py-5">
                    <i class="bi bi-cash-stack display-1 text-muted mb-4"></i>
                    <h4 class="text-muted mb-3">No Fee Payments Found</h4>
                    <p class="lead text-muted mb-4">
                        Your fee payments will appear here after your first transaction is recorded.
                    </p>
                    <div class="alert alert-info d-inline-block text-start">
                        <i class="bi bi-info-circle me-2"></i>
                        If you have already paid, please contact your study centre with your receipt.
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Recent Results -->
        <?php if ($results): ?>
            <div class="card section-card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-clipboard-data me-2"></i>Recent Exam Results (Last 5)</h5>
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
                                        <td><?= htmlspecialchars($r['exam_held_on']) ?></td>
                                        <td><?= $r['subject_code'] ?></td>
                                        <td><?= $r['theory_marks'] ?></td>
                                        <td><?= $r['total_theory_marks'] ?></td>
                                        <td><strong><?= number_format(($r['theory_marks'] / $r['total_theory_marks']) * 100, 1) ?><strong></strong>%</td>
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
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card section-card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-clipboard-data me-2"></i>Exam Results</h5>
                </div>
                <div class="card-body text-center py-5">
                    <i class="bi bi-trophy display-1 text-muted mb-4"></i>
                    <h4 class="text-muted mb-3">No Exam Results Yet</h4>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>