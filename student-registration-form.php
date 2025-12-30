<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/anti_inspect.php';

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
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
                NULL,
                (SELECT fees FROM courses WHERE course_code = ?),
                CURDATE(), 'Pending Approval'
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
            header("Location: student/registration-success?reg_no=" . urlencode($reg_no)
                . "&mobile=" . urlencode($_POST['mobile'])
                . "&name=" . urlencode($_POST['name']));
            exit();
        } else {
            $message = 'Error adding student. Please try again.';
            $message_class = 'alert-danger';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Registration | Mahaveer CEC</title>
    <link rel="icon" type="image/x-icon" href="assets/images/ska-logo.jpeg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Pacifico&display=swap"
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

        /* LOGO */
        .logo-institute {
            display: inline-flex;
            align-items: center;
            gap: 18px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(14px);
            padding: 12px 26px;
            border-radius: 999px;
            box-shadow: 0 14px 45px rgba(0, 0, 0, 0.3);
            margin-bottom: 2rem;
        }

        .institute-logo {
            width: 82px;
            height: 82px;
            border-radius: 18px;
            object-fit: contain;
            background: #ffffff;
            padding: 6px;
        }

        .institute-name {
            font-family: 'Pacifico', cursive;
            font-size: 2rem;
            letter-spacing: 0.08em;
            color: #f9fafb;
            text-shadow: 0 0 12px rgba(255, 255, 255, 0.5);
        }

        /* MAIN FORM CONTAINER */
        .form-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
            color: #fff;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        /* GLASSMORPHIC FORM */
        .form-card {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 3rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }

        .form-card::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 24px;
            padding: 3px;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.6), rgba(59, 130, 246, 0.6));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            z-index: -1;
        }

        .form-title {
            color: #fff;
            font-size: 2rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .form-subtitle {
            color: rgba(255, 255, 255, 0.9);
            text-align: center;
            font-size: 1.1rem;
            margin-bottom: 3rem;
        }

        /* SECTIONS */
        .section-title {
            color: #fff;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* FORM CONTROLS */
        .form-control,
        .form-select {
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 16px;
            color: #1e293b;
            padding: 16px 20px;
            font-size: 1rem;
            transition: all 0.3s ease;
            height: 60px;
        }

        .form-control:focus,
        .form-select:focus {
            background: #fff;
            border-color: #10b981;
            box-shadow: 0 0 0 0.25rem rgba(16, 185, 129, 0.25);
            transform: translateY(-2px);
        }

        .form-control::placeholder {
            color: #64748b;
        }

        .form-label {
            color: #fff;
            font-weight: 600;
            margin-bottom: 0.75rem;
            font-size: 1rem;
        }

        /* BUTTON */
        .submit-btn {
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            color: #ecfdf5;
            padding: 20px 60px;
            font-size: 1.2rem;
            font-weight: 700;
            border-radius: 50px;
            width: 100%;
            height: 70px;
            transition: all 0.3s ease;
            box-shadow: 0 12px 40px rgba(16, 185, 129, 0.4);
        }

        .submit-btn:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 50px rgba(16, 185, 129, 0.6);
            color: #ecfdf5;
        }

        /* DECLARATION */
        .declaration {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(12px);
            border-radius: 16px;
            padding: 2rem;
            border-left: 5px solid #10b981;
            color: #fff;
            margin: 2rem 0;
        }

        /* ALERTS */
        .alert-custom {
            border-radius: 16px;
            border: none;
            backdrop-filter: blur(12px);
        }

        @media (max-width: 992px) {
            .form-card {
                padding: 2rem;
                margin: 0 10px;
            }

            .page-title {
                font-size: 2rem;
            }

            .logo-institute {
                padding: 10px 20px;
            }

            .institute-logo {
                width: 64px;
                height: 64px;
            }

            .institute-name {
                font-size: 1.6rem;
            }
        }
    </style>
</head>

<body>
    <div class="form-container">
        <!-- HEADER WITH LOGO -->
        <div class="logo-institute">
            <img src="assets/images/ska-logo.jpeg" alt="Sri Krishna Academy Logo" class="institute-logo">
            <div class="institute-name">Sri Krishna Academy</div>
        </div>

        <!-- PAGE HEADER -->
        <div class="page-header">
            <h1 class="page-title">Student Registration</h1>
            <a href="/" class="btn"
                style="background: rgba(255,255,255,0.2); color: #fff; border: 2px solid rgba(255,255,255,0.4); padding: 14px 28px; border-radius: 50px; backdrop-filter: blur(10px); font-weight: 600; text-decoration: none;">
                <i class="bi bi-arrow-left me-2"></i>Back
            </a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-danger alert-custom alert-dismissible fade show mb-4 mx-auto" style="max-width: 800px;"
                role="alert">
                <?= $message ?>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- MAIN FORM -->
        <div class="form-card">
            <h2 class="form-title"><i class="bi bi-person-fill-add"></i> Registration Form</h2>
            <p class="form-subtitle">Complete all fields to register new student</p>

            <form method="POST" enctype="multipart/form-data">
                <!-- 1. Personal Information -->
                <h6 class="section-title"><i class="bi bi-person"></i> Personal Information</h6>
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Full Name <span class="text-warning">*</span></label>
                        <input type="text" name="name" class="form-control form-control-lg" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Father's Name <span class="text-warning">*</span></label>
                        <input type="text" name="father" class="form-control form-control-lg" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Mother's Name <span class="text-warning">*</span></label>
                        <input type="text" name="mother" class="form-control form-control-lg" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date of Birth <span class="text-warning">*</span></label>
                        <input type="date" name="dob" id="dob" class="form-control form-control-lg" min="1980-01-01"
                            onkeydown="return false" onclick="this.showPicker()" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Gender <span class="text-warning">*</span></label>
                        <select name="gender" class="form-select form-select-lg" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Category <span class="text-warning">*</span></label>
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
                        <label class="form-label">Religion <span class="text-warning">*</span></label>
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
                        <label class="form-label">Marital Status <span class="text-warning">*</span></label>
                        <select name="marital_status" class="form-select form-select-lg" required>
                            <option value="">Select Status</option>
                            <option value="Unmarried">Unmarried</option>
                            <option value="Married">Married</option>
                            <option value="Divorced">Divorced</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Identity Type <span class="text-warning">*</span></label>
                        <select name="identity_type" class="form-select form-select-lg" required>
                            <option value="">Choose Identity Type</option>
                            <option value="AADHAR">AADHAR</option>
                            <option value="PAN">PAN</option>
                            <option value="Voter ID">Voter ID</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">ID Number <span class="text-warning">*</span></label>
                        <input type="text" name="id_number" class="form-control form-control-lg" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Qualification <span class="text-warning">*</span></label>
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

                <!-- Contact Information -->
                <h6 class="section-title"><i class="bi bi-telephone"></i> Contact Information</h6>
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">
                            Mobile <span class="text-warning">*</span>
                        </label>
                        <input type="tel" name="mobile" class="form-control form-control-lg" required
                            pattern="[0-9]{10}" inputmode="numeric" title="Enter 10 digit mobile number">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Email <span class="text-warning">*</span></label>
                        <input type="email" name="email" class="form-control form-control-lg" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Address <span class="text-warning">*</span></label>
                        <textarea name="address" class="form-control form-control-lg" rows="3"
                            placeholder="H.No. 45/2, Supaul Road, Amar Nagar, Near Bus Stand" required></textarea>
                        <div class="form-text mt-1" style="color: rgba(255,255,255,0.8);">
                            <i class="bi bi-geo-alt-fill text-warning me-1"></i>
                            <small>Include Ward No, Mohalla, Landmark</small>
                        </div>
                    </div>
                </div>

                <!-- Location Details -->
                <h6 class="section-title"><i class="bi bi-geo-alt"></i> Location Details</h6>
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">PINCODE <small style="color: rgba(255,255,255,0.7);">(6 digits - auto
                                fills below)</small></label>
                        <input type="text" name="pincode" id="pincode" class="form-control form-control-lg"
                            maxlength="6" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">STATE</label>
                        <input type="text" name="state" id="state" class="form-control form-control-lg" readonly
                            required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">DISTRICT</label>
                        <input type="text" name="district" id="district" class="form-control form-control-lg" readonly
                            required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">CITY</label>
                        <input type="text" name="city" id="city" class="form-control form-control-lg" readonly required>
                    </div>
                </div>

                <!-- Academic & Course Details -->
                <h6 class="section-title"><i class="bi bi-book"></i> Academic & Course Details</h6>
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Study Centre <span class="text-warning">*</span></label>
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
                        <label class="form-label">Course <span class="text-warning">*</span></label>
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
                        <label class="form-label">Session Year <span class="text-warning">*</span></label>
                        <select name="session_year" class="form-select form-select-lg" required>
                            <option value="">Select Session</option>
                            <?php for ($y = 2025; $y <= 2030; $y++): ?>
                                <option value="<?= $y ?>-<?= $y + 1 ?>"><?= $y ?>-<?= $y + 1 ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Enquiry Source <span class="text-warning">*</span></label>
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
                <div class="declaration">
                    <strong><i class="bi bi-info-circle-fill me-2"></i> DECLARATION BY STUDENT:</strong><br>
                    I hereby declare that all the above statements are true and correct to the best of my knowledge and
                    belief.
                    I shall obey all the Rules and Regulations of the organization.
                </div>

                <!-- Submit Button -->
                <div class="text-center">
                    <button type="submit" class="submit-btn">
                        <i class="bi bi-check-circle-fill me-3"></i>
                        Complete Registration
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
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
                    url: 'admin/fetch-pincode.php',
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