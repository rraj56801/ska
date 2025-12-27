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

// Fetch student details
$stmt = $pdo->prepare("SELECT * FROM students WHERE reg_no = ?");
$stmt->execute([$reg_no]);
$student = $stmt->fetch();

if (!$student) {
    die('Student not found!');
}

// Fetch existing documents
$doc_stmt = $pdo->prepare("SELECT * FROM student_documents WHERE reg_no = ? ORDER BY upload_date DESC");
$doc_stmt->execute([$reg_no]);
$documents = $doc_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Upload Documents - <?= htmlspecialchars($student['student_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .upload-card {
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
            border-radius: 15px;
        }
        .document-item {
            padding: 15px;
            border-radius: 10px;
            background: #f8f9fa;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }
        .document-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
    </style>
</head>
<body class="bg-light">

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle me-2"></i>
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Back Button -->
            
    <div class="d-flex justify-content-end mb-3">
    <a href="view-student?reg=<?= urlencode($reg_no) ?>"
       class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Student Details
    </a>
</div>
            <!-- Upload Form Card -->
            <div class="card upload-card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-cloud-upload me-2"></i>Upload Documents for <?= htmlspecialchars($student['student_name']) ?>
                    </h5>
                    <small>Reg No: <?= htmlspecialchars($reg_no) ?></small>
                </div>
                <div class="card-body p-4">
                    <form action="process-document-upload.php" method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
                        <input type="hidden" name="reg_no" value="<?= htmlspecialchars($reg_no) ?>">
                        <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                        
                        <div class="row g-3">
                            <!-- Document Type Dropdown -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-tag me-1"></i>Document Type <span class="text-danger">*</span>
                                </label>
                                <select class="form-select form-select-lg" name="photo_type" id="photoType" required>
                                    <option value="">-- Select Document Type --</option>
                                    <option value="AADHAR">Aadhar Card</option>
                                    <option value="PAN">PAN Card</option>
                                    <option value="VOTER_ID">Voter ID</option>
                                    <option value="SIGNATURE">Signature</option>
                                    <option value="OTHERS">Others</option>
                                </select>
                            </div>

                            <!-- File Upload -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-file-earmark me-1"></i>Select File <span class="text-danger">*</span>
                                </label>
                                <input type="file" class="form-control form-control-lg" name="document" id="documentFile" 
                                       accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                                <small class="text-muted">Max 5MB (PDF, JPG, PNG, DOC, DOCX)</small>
                            </div>

                            <!-- Submit Button -->
                            <div class="col-12 text-center mt-4">
                                <button type="submit" class="btn btn-primary btn-lg px-5">
                                    <i class="bi bi-upload me-2"></i>Upload Document
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Existing Documents -->
            <div class="card upload-card mt-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-files me-2"></i>Uploaded Documents (<?= count($documents) ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($documents)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-inbox display-4 d-block mb-3"></i>
                            <p>No documents uploaded yet</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($documents as $doc): ?>
                            <div class="document-item d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-info me-2"><?= htmlspecialchars($doc['photo_type']) ?></span>
                                    <strong><?= htmlspecialchars($doc['original_name']) ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <i class="bi bi-calendar me-1"></i><?= date('d-M-Y H:i', strtotime($doc['upload_date'])) ?>
                                        | <i class="bi bi-hdd me-1"></i><?= number_format($doc['file_size'] / 1024, 2) ?> KB
                                    </small>
                                </div>
                                <div>
                                    <a href="../assets/documents/students/<?= htmlspecialchars($doc['filename']) ?>" 
                                       class="btn btn-sm btn-outline-primary me-2" target="_blank">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    <a href="delete-document.php?id=<?= $doc['id'] ?>&reg=<?= urlencode($reg_no) ?>" 
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Delete this document?')">
                                        <i class="bi bi-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function validateForm() {
    const file = document.getElementById('documentFile').files[0];
    const photoType = document.getElementById('photoType').value;
    
    if (!file) {
        alert('Please select a file');
        return false;
    }
    
    if (!photoType) {
        alert('Please select document type');
        return false;
    }
    
    // Check file size (5MB)
    const maxSize = 5 * 1024 * 1024;
    if (file.size > maxSize) {
        alert('File size must be less than 5MB\n\nYour file: ' + (file.size / 1024 / 1024).toFixed(2) + 'MB');
        return false;
    }
    
    // Check file type
    const allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
    const fileExtension = file.name.split('.').pop().toLowerCase();
    
    if (!allowedExtensions.includes(fileExtension)) {
        alert('Invalid file type!\n\nAllowed: PDF, JPG, PNG, DOC, DOCX');
        return false;
    }
    
    return true;
}

// Auto-hide alerts
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 3000);
</script>

</body>
</html>
