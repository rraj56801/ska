<?php
session_start();
include '../includes/db.php';
require_once __DIR__ . '/../includes/anti_inspect.php';

if (!isset($_SESSION['admin'])) {
    include(__DIR__ . '/../forbidden.php');
    exit();
}

// Get student by reg_no
if (!isset($_GET['reg']) || empty($_GET['reg'])) {
    die("Invalid student");
}
$reg_no = $_GET['reg'];

$stmt = $pdo->prepare("
    SELECT s.*, c.course_name, c.fees as course_fee,
           sc.center_name, sc.center_code
    FROM students s
    LEFT JOIN courses c ON s.course_code = c.course_code
    LEFT JOIN study_centers sc ON s.study_center_code = sc.center_code
    WHERE s.reg_no = ?
");
$stmt->execute([$reg_no]);
$student = $stmt->fetch();

if (!$student)
    die("Student not found");

// Accurate Paid / Due Fees
$paid_stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM fee_payments WHERE reg_no = ?");
$paid_stmt->execute([$reg_no]);
$paid_fees = $paid_stmt->fetchColumn();
$due_fees = $student['total_fees'] - $paid_fees;

// Handle update
if ($_POST) {
    $data = [
        'student_name' => trim($_POST['student_name']),
        'father_name' => trim($_POST['father_name']),
        'mother_name' => trim($_POST['mother_name']),
        'dob' => $_POST['dob'] ?: null,
        'gender' => $_POST['gender'],
        'category' => $_POST['category'],
        'marital_status' => $_POST['marital_status'],
        'identity_type' => $_POST['identity_type'],
        'id_number' => trim($_POST['id_number']),
        'qualification' => trim($_POST['qualification']),
        'mobile' => trim($_POST['mobile']),
        'email' => trim($_POST['email']),
        'address' => trim($_POST['address']),
        'pincode' => trim($_POST['pincode']),
        'state' => trim($_POST['state']),
        'district' => trim($_POST['district']),
        'city' => trim($_POST['city']),
        'study_center_code' => trim($_POST['study_center_code']),
        'religion' => trim($_POST['religion']),
        'course_code' => trim($_POST['course_code']),
        'session_year' => trim($_POST['session_year']),
        'enquiry_source' => trim($_POST['enquiry_source']),
        'total_fees' => (float) $_POST['total_fees'],
        'admission_date' => $_POST['admission_date'],
        'status' => $_POST['status']
    ];

    $sql = "UPDATE students SET 
        student_name=?, father_name=?, mother_name=?, dob=?, gender=?, category=?,
        marital_status=?, identity_type=?, id_number=?, qualification=?,
        mobile=?, email=?, address=?, pincode=?, state=?, district=?, city=?,
        study_center_code=?, religion=?, course_code=?, session_year=?,
        enquiry_source=?, total_fees=?, admission_date=?, status=?
        WHERE reg_no=?";

    $update = $pdo->prepare($sql);
    $update->execute(array_merge(array_values($data), [$reg_no]));

    $_SESSION['success'] = "Student updated successfully!";
    header("Location: edit-student?reg=$reg_no");
    exit();
}

// Load dropdowns
$courses = $pdo->query("SELECT course_code, course_name, duration, fees FROM courses where is_active=1 ORDER BY course_name")->fetchAll();
$centres = $pdo->query("SELECT id, center_name, center_code FROM study_centers where is_active=1 ORDER BY center_name")->fetchAll();
?>

<?php include 'header.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Student - <?= htmlspecialchars($student['student_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .form-label {
            font-weight: 600;
            color: #2c3e50;
        }

        .required {
            color: red;
        }

        .section-title {
            color: #007bff;
            border-bottom: 2px solid #007bff;
            padding-bottom: 8px;
            margin: 25px 0 20px;
        }

        .photo-preview {
            width: 150px;
            height: 170px;
            object-fit: cover;
            border: 4px solid #007bff;
            border-radius: 10px;
        }

        .badge-reg {
            font-size: 1.2rem;
            padding: 0.7rem 1.5rem;
        }
    </style>
</head>

<body class="bg-light">

    <div class="container mt-4 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-11">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-header bg-gradient bg-primary text-white text-center py-4">
                        <h3 class="mb-0">
                            Edit Student Profile
                            <span class="badge bg-light text-dark badge-reg ms-3"><?= $reg_no ?></span>
                        </h3>
                    </div>

                    <div class="card-body p-5">
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <strong>Success!</strong> <?= $_SESSION['success'] ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                <?php unset($_SESSION['success']); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <!-- Personal Info -->
                            <h5 class="section-title">Personal Information</h5>
                            <div class="row g-4 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">Student Name</label>
                                    <input type="text" class="form-control" name="student_name"
                                        value="<?= htmlspecialchars($student['student_name']) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Father's Name</label>
                                    <input type="text" class="form-control" name="father_name"
                                        value="<?= htmlspecialchars($student['father_name']) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Mother's Name</label>
                                    <input type="text" class="form-control" name="mother_name"
                                        value="<?= htmlspecialchars($student['mother_name']) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" name="dob" id="dob" class="form-control"
                                        min="1980-01-01" onkeydown="return false" onclick="this.showPicker()" value="<?= $student['dob'] ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Gender</label>
                                    <select class="form-select" name="gender">
                                        <option value="">Select</option>
                                        <option <?= $student['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
                                        <option <?= $student['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Category</label>
                                    <select class="form-select" name="category">
                                        <option value="">Select</option>
                                        <option <?= $student['category'] == 'SC' ? 'selected' : '' ?>>SC</option>
                                        <option <?= $student['category'] == 'ST' ? 'selected' : '' ?>>ST</option>
                                        <option <?= $student['category'] == 'OBC' ? 'selected' : '' ?>>OBC</option>
                                        <option <?= $student['category'] == 'GEN' ? 'selected' : '' ?>>GEN</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Marital Status</label>
                                    <select class="form-select" name="marital_status">
                                        <option value="">Select</option>
                                        
                                        <option <?= $student['marital_status'] == 'Married' ? 'selected' : '' ?>>Married
                                        </option>
                                        <option <?= $student['marital_status'] == 'Unmarried' ? 'selected' : '' ?>>Unmarried
                                        </option>
                                        <option <?= $student['marital_status'] == 'Divorced' ? 'selected' : '' ?>>Divorced
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Identity & Education -->
                            <h5 class="section-title">Identity & Education</h5>
                            <div class="row g-4 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">Identity Type</label>
                                    <select class="form-select" name="identity_type">
                                        <option value="">Select</option>
                                        <option <?= $student['identity_type'] == 'AADHAR' ? 'selected' : '' ?>>AADHAR</option>
                                        <option <?= $student['identity_type'] == 'PAN' ? 'selected' : '' ?>>PAN</option>
                                        <option <?= $student['identity_type'] == 'Voter ID' ? 'selected' : '' ?>>Voter ID</option>
                                        <option <?= $student['identity_type'] == 'Other' ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">ID Number</label>
                                    <input type="text" class="form-control" name="id_number"
                                        value="<?= htmlspecialchars($student['id_number']) ?>">
                                </div>
                                <div class="col-md-4">
                                               <label class="form-label">Qualification</label>
                         <select name="qualification" class="form-select">
    <option value="">Select Qualification</option>
    <option value="Below 1st" <?= $student['qualification'] == 'Below 1st' ? 'selected' : '' ?>>Below 1st</option>
    <option value="1st Pass" <?= $student['qualification'] == '1st Pass' ? 'selected' : '' ?>>1st Pass</option>
    <option value="2nd Pass" <?= $student['qualification'] == '2nd Pass' ? 'selected' : '' ?>>2nd Pass</option>
    <option value="3rd Pass" <?= $student['qualification'] == '3rd Pass' ? 'selected' : '' ?>>3rd Pass</option>
    <option value="4th Pass" <?= $student['qualification'] == '4th Pass' ? 'selected' : '' ?>>4th Pass</option>
    <option value="5th Pass" <?= $student['qualification'] == '5th Pass' ? 'selected' : '' ?>>5th Pass</option>
    <option value="6th Pass" <?= $student['qualification'] == '6th Pass' ? 'selected' : '' ?>>6th Pass</option>
    <option value="7th Pass" <?= $student['qualification'] == '7th Pass' ? 'selected' : '' ?>>7th Pass</option>
    <option value="8th Pass" <?= $student['qualification'] == '8th Pass' ? 'selected' : '' ?>>8th Pass</option>
    <option value="Others" <?= $student['qualification'] == 'Others' ? 'selected' : '' ?>>Others</option>
</select>

                                </div>
                               <div class="col-md-4">
                                    <label class="form-label">Religion</label>
                                    <select name="religion" class="form-select">
                                        <option value="">Select Religion</option>
                                        <option <?= $student['religion'] == 'Hinduism' ? 'selected' : '' ?>>Hinduism</option>
                                        <option <?= $student['religion'] == 'Islam' ? 'selected' : '' ?>>Islam</option>
                                        <option <?= $student['religion'] == 'Buddhism' ? 'selected' : '' ?>>Buddhism</option>
                                        <option <?= $student['religion'] == 'Christian' ? 'selected' : '' ?>>Christian</option>
                                        <option <?= $student['religion'] == 'Jainism' ? 'selected' : '' ?>>Jainism</option>
                                        <option <?= $student['religion'] == 'Sikhism' ? 'selected' : '' ?>>Sikhism</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Enquiry Source</label>
                                    <input type="text" class="form-control" name="enquiry_source"
                                        value="<?= htmlspecialchars($student['enquiry_source']) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Session Year</label>
                                    <input type="text" class="form-control" name="session_year"
                                        value="<?= $student['session_year'] ?>" placeholder="2024-25">
                                </div>
                            </div>

                            <!-- Contact & Address -->
                            <h5 class="section-title">Contact & Address</h5>
                            <div class="row g-4 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">Mobile</label>
                                    <input type="text" class="form-control" name="mobile"
                                        value="<?= $student['mobile'] ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email"
                                        value="<?= htmlspecialchars($student['email']) ?>">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Address</label>
                                    <textarea class="form-control" name="address"
                                        rows="2"><?= htmlspecialchars($student['address']) ?></textarea>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">City</label>
                                    <input type="text" class="form-control" name="city"
                                        value="<?= htmlspecialchars($student['city']) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">District</label>
                                    <input type="text" class="form-control" name="district"
                                        value="<?= htmlspecialchars($student['district']) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">State</label>
                                    <input type="text" class="form-control" name="state"
                                        value="<?= htmlspecialchars($student['state']) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Pincode</label>
                                    <input type="text" class="form-control" name="pincode"
                                        value="<?= $student['pincode'] ?>">
                                </div>
                            </div>

                            <!-- Course & Fees -->
                            <h5 class="section-title">Course & Fees</h5>
                            <div class="row g-4 mb-5">
                                <div class="col-md-5">
                                    <label class="form-label">Course</label>
                                    <select class="form-select" name="course_code" id="courseSelect">
                                        <option value="">-- Select Course --</option>
                                        <?php foreach ($courses as $c): ?>
                                            <option value="<?= $c['course_code'] ?>" data-fees="<?= $c['fees'] ?>"
                                                <?= $c['course_code'] == $student['course_code'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($c['course_name']) ?>
                                                <?= $c['duration'] ? " ({$c['duration']})" : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Study Center</label>
                                    <select class="form-select" name="study_center_code">
                                        <option value="">-- Select Centre --</option>
                                        <?php foreach($centres as $c): ?>
                                        <option value="<?= $c['center_code'] ?>" 
                                            <?= $c['center_code'] == $student['study_center_code'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['center_name']) ?>
                                            <?= $c['center_code'] ? " ({$c['center_code']})" : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Total Fees (₹)</label>
                                    <input type="number" step="0.01" class="form-control fw-bold text-primary"
                                        name="total_fees" id="totalFeesInput" value="<?= $student['total_fees'] ?>"
                                        >
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Admission Date</label>
                                    <input type="date" class="form-control" name="admission_date"
                                        value="<?= $student['admission_date'] ?>">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status">
                                        <option value="Active" <?= ($student['status'] ?? 'Active') == 'Active' ? 'selected' : '' ?>>Active</option>
                                        <option value="Completed" <?= ($student['status'] ?? '') == 'Completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="Dropped" <?= ($student['status'] ?? '') == 'Dropped' ? 'selected' : '' ?>>Dropped</option>
                                        <option value="Suspended" <?= ($student['status'] ?? '') == 'Suspended' ? 'selected' : '' ?>>Suspended</option>
                                        <option value="Pending Approval" <?= ($student['status'] ?? '') == 'Pending Approval' ? 'selected' : '' ?>>Pending Approval</option>
                                    </select>
                                </div>
                            </div>

                            <div class="text-end mt-5">
                                <a href="view-student?reg=<?= $reg_no ?>"
                                    class="btn btn-secondary btn-lg px-5">Cancel</a>
                                <button type="submit" class="btn btn-success btn-lg px-5">Update Student</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Auto-fill Total Fees from course + add pending dues only if class changes
    document.addEventListener("DOMContentLoaded", function () {
        const courseSelect = document.getElementById("courseSelect");
        const feesInput = document.getElementById("totalFeesInput");
        
        // Current class info from PHP
        const currentClassCode = "<?= $student['course_code'] ?>";
        const currentPendingDues = <?= $due_fees ?>;

        function updateFees() {
            const selected = courseSelect.options[courseSelect.selectedIndex];
            if (selected && selected.value !== "") {
                const newCourseFees = parseFloat(selected.getAttribute("data-fees")) || 0;
                const selectedClassCode = selected.value;
                
                // Only add pending dues if changing to a different class
                if (selectedClassCode !== currentClassCode) {
                    const totalFees = newCourseFees + currentPendingDues;
                    feesInput.value = totalFees.toFixed(2);
                } else {
                    // Same class - just show the course fees
                    feesInput.value = newCourseFees.toFixed(2);
                }
            }
        }

        courseSelect.addEventListener("change", updateFees);
        updateFees(); // Run on load
    });
</script>

<script>
    const dobInput = document.getElementById('dob');

    const today = new Date();
    const maxYear = today.getFullYear() - 10; // 10 years less than current year [web:102][web:104]
    const month = String(today.getMonth() + 1).padStart(2, '0'); // months 0–11 [web:106]
    const day = String(today.getDate()).padStart(2, '0');

    dobInput.max = `${maxYear}-${month}-${day}`; // e.g. 2015-12-21
</script>
    <?php include '../includes/footer.php'; ?>
</body>

</html>