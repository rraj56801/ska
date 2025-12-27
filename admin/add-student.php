<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/anti_inspect.php';

if (!isset($_SESSION['admin'])) {
    include(__DIR__ . '/../forbidden.php');
    exit();
}

$studyCenters = getAllStudyCenters($pdo);
$courses = getAllCourses($pdo);

$message = '';
if ($_POST) {

    // 1) Check if student already exists (example: same mobile + course)
    $checkStmt = $pdo->prepare("
        SELECT id 
        FROM students 
        WHERE mobile = ? 
          AND course_code = ?
        LIMIT 1
    ");
    $checkStmt->execute([
        $_POST['mobile'],
        $_POST['course_code']
    ]);

    if ($checkStmt->fetch()) {
        // Student already exists
        $message = 'A student with this mobile number is already registered for this course.';
        $message_class = 'alert-danger';
    } else {

        // 2) Only insert if not exists
        $rawRegNo = generateRegNo($pdo);
        $reg_no = $_POST['study_center_code'] . '/' . $rawRegNo;

        $stmt = $pdo->prepare("
            INSERT INTO students (
                reg_no, student_name, father_name, mother_name, dob, gender, category,
                marital_status, identity_type, id_number, qualification, mobile, email,
                address, pincode, state, district, city, study_center_code, religion,
                course_code, session_year, enquiry_source, photo, total_fees,
                admission_date, status
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
                NULL,
                (SELECT fees FROM courses WHERE course_code = ?),
                CURDATE(), 'Active'
            )
        ");

        $result = $stmt->execute([
            $reg_no,
            $_POST['name'],
            $_POST['father'],
            $_POST['mother'],
            $_POST['dob'],
            $_POST['gender'],
            $_POST['category'],
            $_POST['marital_status'],
            $_POST['identity_type'],
            $_POST['id_number'],
            $_POST['qualification'],
            $_POST['mobile'],
            $_POST['email'],
            $_POST['address'],
            $_POST['pincode'],
            $_POST['state'],
            $_POST['district'],
            $_POST['city'],
            $_POST['study_center_code'],
            $_POST['religion'],
            $_POST['course_code'],
            $_POST['session_year'],
            $_POST['enquiry_source'],
            $_POST['course_code']  // for total_fees subquery
        ]);

        if ($result) {
            header("Location: view-student?reg=" . urlencode($reg_no));
            exit();

        } else {
            $message = 'Error adding student. Please try again.';
            $message_class = 'alert-danger';
        }
    }
}
?>
<?php include 'header.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add New Student</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/ska-logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }

        .form-section {
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
            border-radius: 16px;
            border: none;
        }

        .card-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            font-weight: 600;
        }

        .form-label {
            font-weight: 600;
            color: #2c3e50;
        }

        .section-title {
            color: #495057;
            font-weight: 600;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 0.5rem;
        }
    </style>
</head>

<body>
    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>Add New Student <i class="bi bi-person-plus"></i></h3>
            <a href="students" class="btn btn-outline-secondary btn-lg shadow-sm">
                <i class="bi bi-arrow-left"></i> Back to Students
            </a>
        </div>

        <?php if ($message): ?>
            <div class="alert <?= $message_class ?? 'alert-danger' ?> alert-dismissible fade show" role="alert">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card form-section">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-person-fill-add"></i> Student Registration Form</h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" enctype="multipart/form-data">

                            <!-- 1. Personal Information (FIRST) -->
                            <h6 class="section-title"><i class="bi bi-person"></i> Personal Information</h6>
                            <div class="row g-4 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control form-control-lg" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Father's Name <span class="text-danger">*</span></label>
                                    <input type="text" name="father" class="form-control form-control-lg" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Mother's Name <span class="text-danger">*</span></label>
                                    <input type="text" name="mother" class="form-control form-control-lg" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                                    <input type="date" name="dob" id="dob" class="form-control form-control-lg"
                                        min="1980-01-01" onkeydown="return false" onclick="this.showPicker()" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Gender <span class="text-danger">*</span></label>
                                    <select name="gender" class="form-select form-select-lg" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Category <span class="text-danger">*</span></label>
                                    <select name="category" class="form-select form-select-lg" required>
                                        <option value="">Select Category</option>
                                        <option value="SC">SC</option>
                                        <option value="ST">ST</option>
                                        <option value="OBC">OBC</option>
                                        <option value="GEN">GEN</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Religion <span class="text-danger">*</span></label>
                                    <select name="religion" class="form-select form-select-lg" required>
                                        <option value="">Select Religion</option>
                                        <option value="Hinduism">Hinduism</option>
                                        <option value="Islam">Islam</option>
                                        <option value="Buddhism">Buddhism</option>
                                        <option value="Christian">Christian</option>
                                        <option value="Jainism">Jainism</option>
                                        <option value="Sikhism">Sikhism</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Marital Status <span class="text-danger">*</span></label>
                                    <select name="marital_status" class="form-select form-select-lg" required>
                                        <option value="">Select Status</option>
                                        <option value="Married">Married</option>
                                        <option value="Unmarried">Unmarried</option>
                                        <option value="Divorced">Divorced</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Identity Type <span class="text-danger">*</span></label>
                                    <select name="identity_type" class="form-select form-select-lg" required>
                                        <option value="">Choose Identity Type</option>

                                        <option value="AADHAR">AADHAR</option>
                                        <option value="PAN">PAN</option>
                                        <option value="Voter ID">Voter ID</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">ID Number <span class="text-danger">*</span></label>
                                    <input type="text" name="id_number" class="form-control form-control-lg" required
                                        placeholder="ABC123DEF or 123456">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Qualification <span class="text-danger">*</span></label>
                                    <select name="qualification" class="form-select form-select-lg" required>
                                        <option value="">Select Last Qualification</option>
                                        <option value="Below 1st">Below 1st Class</option>
                                        <option value="1st Pass">1st Class Pass</option>
                                        <option value="2nd Pass">2nd Class Pass</option>
                                        <option value="3rd Pass">3rd Class Pass</option>
                                        <option value="4th Pass">4th Class Pass</option>
                                        <option value="5th Pass">5th Class Pass</option>
                                        <option value="6th Pass">6th Class Pass</option>
                                        <option value="7th Pass">7th Class Pass</option>
                                        <option value="8th Pass">8th Class Pass</option>
                                        <option value="Others">Others</option>
                                    </select>

                                </div>
                            </div>

                            <!-- 2. Contact Information -->
                            <h6 class="section-title"><i class="bi bi-telephone"></i> Contact Information</h6>
                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Mobile <span class="text-danger">*</span></label>
                                    <input type="tel" name="mobile" class="form-control form-control-lg" required
                                        pattern="[0-9]{10}" inputmode="numeric"
                                        placeholder="Enter 10 digit mobile number">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control form-control-lg"
                                        placeholder="Student's Email" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Address <span class="text-danger">*</span></label>
                                    <textarea name="address" class="form-control form-control-lg" rows="3"
                                        placeholder="H.No. 45/2, Supaul Road, Amar Nagar, Near Bus Stand"
                                        required></textarea>
                                </div>
                            </div>

                            <!-- 3. Location Details -->
                            <h6 class="section-title"><i class="bi bi-geo-alt"></i> Location Details</h6>
                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">PINCODE <small class="text-muted">(6 digits - auto fills
                                            below)</small></label>
                                    <input type="text" name="pincode" id="pincode" class="form-control form-control-lg"
                                        maxlength="6" placeholder="852201" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">STATE</label>
                                    <input type="text" name="state" id="state" class="form-control form-control-lg"
                                        readonly placeholder="Student's State" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">DISTRICT</label>
                                    <input type="text" name="district" id="district"
                                        class="form-control form-control-lg" placeholder="Student's District" readonly
                                        required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">CITY</label>
                                    <input type="text" name="city" id="city" class="form-control form-control-lg"
                                        placeholder="Student's City" readonly required>
                                </div>
                            </div>

                            <!-- 4. Academic & Course Details -->
                            <h6 class="section-title"><i class="bi bi-book"></i> Academic & Course Details</h6>
                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Study Centre <span class="text-danger">*</span></label>
                                    <select name="study_center_code" class="form-select form-select-lg" required>
                                        <option value="">-- Select Study Centre --</option>
                                        <?php foreach ($studyCenters as $center): ?>
                                            <option value="<?= htmlspecialchars($center['center_code']) ?>">
                                                <?= htmlspecialchars($center['center_code']) ?> -
                                                <?= htmlspecialchars($center['center_name']) ?>
                                                <?php if (isset($center['district'])): ?>
                                                    (<?= htmlspecialchars($center['district']) ?>)
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Course <span class="text-danger">*</span></label>
                                    <select name="course_code" class="form-select form-select-lg" required>
                                        <option value="">Select Course</option>
                                        <?php foreach ($courses as $course): ?>
                                            <option value="<?= $course['course_code'] ?>">
                                                <?= htmlspecialchars($course['course_code']) ?> -
                                                <?= htmlspecialchars($course['course_name']) ?>
                                                (<?= $course['duration'] ?> - ₹<?= number_format($course['fees'], 2) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Session Year <span class="text-danger">*</span></label>
                                    <select name="session_year" class="form-select form-select-lg" required>
                                        <option value="">Select Session</option>
                                        <?php for ($y = 2025; $y <= 2030; $y++): ?>
                                            <option value="<?= $y ?>-<?= $y + 1 ?>"><?= $y ?>-<?= $y + 1 ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- 5. Enquiry Source -->
                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Enquiry Source <span class="text-danger">*</span></label>
                                    <select name="enquiry_source" class="form-select form-select-lg" required>
                                        <option value="">Select Source</option>
                                        <option value="Banner">Banner</option>
                                        <option value="Social Media">Social Media</option>
                                        <option value="Liflet">Liflet</option>
                                        <option value="Poster">Poster</option>
                                        <option value="News Paper">News Paper</option>
                                        <option value="Others">Others</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Declaration -->
                            <div class="alert alert-info mb-4">
                                <strong><i class="bi bi-info-circle"></i> DECLARATION BY STUDENT:</strong><br>
                                I hereby declare that all the above statements are true and correct to the best of my
                                knowledge and belief.
                                I shall obey all the Rules and Regulations of the organization.
                            </div>

                            <div class="text-center">
                                <button type="submit" class="btn btn-success btn-lg px-5 shadow">
                                    <i class="bi bi-check-circle"></i> Add Student
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include '../includes/footer.php'; ?>
</body>

</html>

<!-- jQuery & Pincode API Script -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function () {
        $('#pincode').on('blur', function () {
            var pincode = $(this).val().trim();
            if (pincode.length === 6 && /^\d{6}$/.test(pincode)) {
                $('#state, #district, #city').val('Loading...');

                $.ajax({
                    url: 'fetch-pincode.php',
                    method: 'POST',
                    data: { pincode: pincode },
                    dataType: 'json',
                    success: function (data) {
                        if (data.status) {
                            $('#state').val(data.state);
                            $('#district').val(data.district);
                            $('#city').val(data.city);
                        } else {
                            alert('Invalid Pincode: ' + pincode);
                            $('#state, #district, #city').val('');
                        }
                    },
                    error: function () {
                        alert('API Error - Please check pincode and try again');
                        $('#state, #district, #city').val('');
                    }
                });
            }
        });
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