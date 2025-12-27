<?php

session_start();
include 'includes/db.php'; // Your existing DB connection
require_once __DIR__ . '/includes/anti_inspect.php'; // Your security file

// Auto-trigger verification from QR scan (GET parameters)
if (isset($_GET['reg']) && isset($_GET['course']) && empty($_POST)) {
    $_POST['reg_no'] = $_GET['reg'];
    $_POST['course_name'] = $_GET['course'];
}

$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" || (isset($_POST['reg_no']) && isset($_POST['course_name']))) {
    $reg_no = trim($_POST['reg_no'] ?? '');
    $course_name = trim($_POST['course_name'] ?? '');

    if ($reg_no && $course_name) {
        // Exact same query as your certificate page
        $stmt = $pdo->prepare("
            SELECT s.*, 
                   c.course_name, c.duration,
                   sc.center_name AS center_name, sc.center_code, sc.address AS center_address, 
                   sc.state AS center_state, sc.pincode AS center_pincode
            FROM students s
            LEFT JOIN courses c ON s.course_code = c.course_code
            LEFT JOIN study_centers sc ON s.study_center_code = sc.center_code
            WHERE s.reg_no = ? AND c.course_name = ? AND s.certificate_gen='Yes'
        ");
        $stmt->execute([$reg_no, $course_name]);
        $student = $stmt->fetch();

        if ($student) {
            // Exact same results calculation as certificate
            $results_stmt = $pdo->prepare("
                SELECT r.*, sub.subject_code, sub.subject_name
                FROM results r
                LEFT JOIN subjects sub ON r.subject_code = sub.subject_code
                WHERE r.reg_no = ?
                ORDER BY sub.subject_code
            ");
            $results_stmt->execute([$reg_no]);
            $results = $results_stmt->fetchAll();

            // Exact same totals calculation
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

            // Exact same gender logic
            $gender = strtolower(trim($student['gender'] ?? ''));
            $relation = ($gender === 'female' || $gender === 'f') ? 'D/o' : 'S/o';
            $pronoun1 = ($gender === 'female' || $gender === 'f') ? 'her' : 'him';
            $pronoun2 = ($gender === 'female' || $gender === 'f') ? 'her' : 'his';
            $issue_date = !empty($student['certificate_gen_date'])
                ? date('d-M-Y', strtotime($student['certificate_gen_date']))
                : '';

            $message = "
                <div style='background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); color: #155724; padding: 30px; border: 3px solid #28a745; border-radius: 15px; max-width: 700px; margin: 20px auto; box-shadow: 0 10px 30px rgba(40,167,69,0.3);'>
                    <div style='text-align: center; margin-bottom: 25px;'>
                        <h1 style='color: #155724; font-size: 32px; margin: 0;'>✓ VERIFIED</h1>
                        <div style='font-size: 48px; color: #28a745;'>✅</div>
                    </div>
                    <table style='width: 100%; border-collapse: collapse; font-size: 16px; line-height: 1.6;'>
                        <tr><td style='padding: 8px 0; width: 40%;'><strong>Registration No:</strong></td><td style='padding: 8px 0;'>" . htmlspecialchars($student['reg_no']) . "</td></tr>
                        <tr><td style='padding: 8px 0;'><strong>Student Name:</strong></td><td style='padding: 8px 0;'>" . htmlspecialchars($student['student_name']) . "</td></tr>
                        <tr><td style='padding: 8px 0;'><strong>{$relation}:</strong></td><td style='padding: 8px 0;'>" . htmlspecialchars($student['father_name'] ?: '—') . "</td></tr>
                        <tr><td style='padding: 8px 0;'><strong>Course:</strong></td><td style='padding: 8px 0;'><strong>" . htmlspecialchars($student['course_name']) . "</strong></td></tr>
                        <tr><td style='padding: 8px 0;'><strong>Duration:</strong></td><td style='padding: 8px 0;'>" . htmlspecialchars($student['duration']) . "</td></tr>
                        <tr><td style='padding: 8px 0;'><strong>Percentage:</strong></td><td style='padding: 8px 0;'><strong style='font-size: 20px; color: #28a745;'>" . $percentage . "%</strong></td></tr>
                        <tr><td style='padding: 8px 0;'><strong>Grade:</strong></td><td style='padding: 8px 0;'><strong>" . htmlspecialchars($grade) . "</strong></td></tr>
                        <tr><td style='padding: 8px 0;'><strong>Institute:</strong></td><td style='padding: 8px 0;'>" . htmlspecialchars($student['center_name']) . " (" . htmlspecialchars($student['center_code']) . ")</td></tr>
                        <tr><td style='padding: 8px 0; width: 40%;'><strong>Issue Date:</strong></td><td style='padding: 8px 0;'>" . htmlspecialchars($issue_date) . "</td></tr>
                    </table>
                    <div style='text-align: center; margin-top: 25px; padding-top: 20px; border-top: 2px solid #28a745;'>
                        <p style='font-size: 18px; font-weight: bold; color: #155724; margin: 0;'>This certificate is <span style='color: #dc3545; text-decoration: underline;'>GENUINE</span> and verified from official records.</p>
                        <p style='font-size: 14px; color: #6c757d; margin-top: 10px;'>Verified on: " . date('d-M-Y H:i:s') . "</p>
                    </div>
                </div>";
        } else {
            $error = "
                <div style='background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%); color: #721c24; padding: 30px; border: 3px solid #dc3545; border-radius: 15px; max-width: 600px; margin: 20px auto; box-shadow: 0 10px 30px rgba(220,53,69,0.3);'>
                    <div style='text-align: center; margin-bottom: 20px;'>
                        <h1 style='color: #721c24; font-size: 32px; margin: 0;'>✗ NOT FOUND</h1>
                        <div style='font-size: 48px; color: #dc3545;'>❌</div>
                    </div>
                    <h3 style='margin-top: 0;'>No Record Found</h3>
                    <p style='font-size: 16px; line-height: 1.6;'>No student found with:<br>
                    <strong>Registration No:</strong> " . htmlspecialchars($reg_no) . "<br>
                    <strong>Course:</strong> " . htmlspecialchars($course_name) . "</p>
                </div>";
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>SKA | Certificate Verification</title>
    <link rel="icon" type="image/x-icon" href="assets/images/ska-logo.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Serif+Vithkuqi:wght@500&family=Open+Sans:wght@400;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .header h1 {
            font-family: 'Noto Serif Vithkuqi', serif;
            font-size: 36px;
            margin-bottom: 10px;
        }

        .form-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 16px;
        }

        input[type="text"] {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        input[type="text"]:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        input[type="submit"] {
            width: 100%;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 18px;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        input[type="submit"]:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(40, 167, 69, 0.4);
        }

        .qr-note {
            background: linear-gradient(135deg, #e7f3ff 0%, #d1ecf1 100%);
            padding: 20px;
            border-radius: 12px;
            margin-top: 25px;
            text-align: center;
            border-left: 5px solid #007bff;
        }

        .verify-again {
            display: block;
            margin: 30px auto 0;
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            max-width: 300px;
            transition: all 0.3s ease;
        }

        .verify-again:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 123, 255, 0.4);
        }

        @media (max-width: 768px) {
            .form-container {
                padding: 25px;
                margin: 10px;
            }

            .header h1 {
                font-size: 28px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Certificate Verification</h1>
            <p>Verify authenticity of certificates instantly</p>
        </div>

        <div class="form-container">
            <?php if ($message): ?>
                <?= $message ?>
                <a href="verify-cert" class="verify-again">Verify Another Certificate</a>
            <?php elseif ($error): ?>
                <?= $error ?>
                <div style="text-align: center; margin-top: 25px;">
                    <a href="verify-cert" class="verify-again"
                        style="background: linear-gradient(135deg, #6c757d, #495057);">Try Again</a>
                </div>
            <?php else: ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="reg_no">Registration Number:</label>
                        <input type="text" id="reg_no" name="reg_no" required placeholder="e.g., 12345"
                            value="<?= htmlspecialchars($_GET['reg'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="course_name">Course Name:</label>
                        <input type="text" id="course_name" name="course_name" required
                            placeholder="e.g., Diploma in Computer Science"
                            value="<?= htmlspecialchars($_GET['course'] ?? '') ?>">
                    </div>

                    <input type="submit" value="🔍 Verify Certificate">
                </form>

                <div class="qr-note">
                    <strong>💡 Pro Tip:</strong> Scan the QR code on any certificate to auto-verify instantly!
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>