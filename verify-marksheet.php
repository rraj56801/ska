<?php
session_start();
include 'includes/db.php';
require_once __DIR__ . '/includes/anti_inspect.php';

// Auto-trigger verification from QR scan (GET parameters)
if (isset($_GET['reg']) && isset($_GET['course']) && empty($_POST)) {
    $_POST['reg_no'] = $_GET['reg'];
    $_POST['course_name'] = $_GET['course'];
}

$message = '';
$error = '';
$results_table = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" || (isset($_POST['reg_no']) && isset($_POST['course_name']))) {
    $reg_no = trim($_POST['reg_no'] ?? '');
    $course_name = trim($_POST['course_name'] ?? '');

    if ($reg_no && $course_name) {
        // Same query structure as certificate
        $stmt = $pdo->prepare("
            SELECT s.*, 
                   c.course_name, c.duration,
                   sc.center_name AS center_name, sc.center_code, sc.address AS center_address, 
                   sc.state AS center_state, sc.pincode AS center_pincode
            FROM students s
            LEFT JOIN courses c ON s.course_code = c.course_code
            LEFT JOIN study_centers sc ON s.study_center_code = sc.center_code
            WHERE s.reg_no = ? AND c.course_name = ? AND s.marksheet_gen='Yes'
        ");
        $stmt->execute([$reg_no, $course_name]);
        $student = $stmt->fetch();

        if ($student) {
            // Fetch detailed results with subjects
            $results_stmt = $pdo->prepare("
                SELECT r.*, sub.subject_code, sub.subject_name,
                       r.theory_marks, 
                       r.total_theory_marks
                FROM results r
                LEFT JOIN subjects sub ON r.subject_code = sub.subject_code
                WHERE r.reg_no = ?
                ORDER BY sub.subject_code
            ");
            $results_stmt->execute([$reg_no]);
            $results = $results_stmt->fetchAll();

            // Calculate totals (same as certificate)
            $total_theory_max = 0;
            $total_theory_secured = 0;
            $total_practical_max = 0;
            $total_practical_secured = 0;
            $total_max = 0;
            $total_secured = 0;

            foreach ($results as $result) {
                $total_theory_max += (float) $result['total_theory_marks'];
                $total_theory_secured += (float) $result['theory_marks'];
                $total_practical_max += (float) $result['total_practical_marks'];
                $total_practical_secured += (float) $result['practical_marks'];
                $total_max += ((float) $result['total_theory_marks'] + (float) $result['total_practical_marks']);
                $total_secured += ((float) $result['theory_marks'] + (float) $result['practical_marks']);
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
            // Gender logic
            $gender = strtolower(trim($student['gender'] ?? ''));
            $relation = ($gender === 'female' || $gender === 'f') ? 'D/o' : 'S/o';

            // Build detailed marks table
            $results_table = "<table style='width:100%; border-collapse: collapse; margin: 20px 0; font-size: 14px;'>";
            $results_table .= "<thead><tr style='background: #007cba; color: white;'><th style='padding:10px; border:1px solid #ddd;'>Subject Code</th><th style='padding:10px; border:1px solid #ddd;'>Subject Name</th><th style='padding:10px; border:1px solid #ddd;'>Theory (Max)</th><th style='padding:10px; border:1px solid #ddd;'>Practical (Max)</th><th style='padding:10px; border:1px solid #ddd;'>Total Marks</th></tr></thead><tbody>";

            $marksheet_no = "SKA/" . date('Y', strtotime($student['admission_date'])) . "/" . $student['id'];
            $issue_date = !empty($student['marksheet_gen_date'])
                ? date('d-M-Y', strtotime($student['marksheet_gen_date']))
                : '';

            foreach ($results as $result) {
                $theory_max = $result['total_theory_marks'] ?: 0;
                $practical_max = $result['total_practical_marks'] ?: 0;
                $theory_score = $result['theory_marks'] ?: 0;
                $practical_score = $result['practical_marks'] ?: 0;
                $total_score = $theory_score + $practical_score;

                $results_table .= "<tr>";
                $results_table .= "<td style='padding:10px; border:1px solid #ddd; font-weight: bold;'>" . htmlspecialchars($result['subject_code']) . "</td>";
                $results_table .= "<td style='padding:10px; border:1px solid #ddd;'>" . htmlspecialchars($result['subject_name']) . "</td>";
                $results_table .= "<td style='padding:10px; border:1px solid #ddd;'>" . $theory_score . "/" . $theory_max . "</td>";
                $results_table .= "<td style='padding:10px; border:1px solid #ddd;'>" . $practical_score . "/" . $practical_max . "</td>";
                $results_table .= "<td style='padding:10px; border:1px solid #ddd; font-weight: bold; color: #28a745;'>" . $total_score . "/" . ($theory_max + $practical_max) . "</td>";
                $results_table .= "</tr>";
            }
            $results_table .= "</tbody></table>";

            $message = "
                <div style='background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); color: #155724; padding: 30px; border: 3px solid #28a745; border-radius: 15px; max-width: 900px; margin: 20px auto; box-shadow: 0 10px 30px rgba(40,167,69,0.3);'>
                    <div style='text-align: center; margin-bottom: 25px;'>
                        <h1 style='color: #155724; font-size: 32px; margin: 0;'>✓ MARKSHEET VERIFIED</h1>
                        <div style='font-size: 48px; color: #28a745;'>✅</div>
                    </div>
                    <table style='width: 100%; border-collapse: collapse; font-size: 16px; line-height: 1.6; margin-bottom: 20px;'>
                        <tr><td style='padding: 8px 0; width: 40%;'><strong>Registration No:</strong></td><td style='padding: 8px 0;'>" . htmlspecialchars($student['reg_no']) . "</td></tr>
                        <tr><td style='padding: 8px 0;'><strong>Student Name:</strong></td><td style='padding: 8px 0;'>" . htmlspecialchars($student['student_name']) . "</td></tr>
                        <tr><td style='padding: 8px 0;'><strong>{$relation}:</strong></td><td style='padding: 8px 0;'>" . htmlspecialchars($student['father_name'] ?: '—') . "</td></tr>
                        <tr><td style='padding: 8px 0; width: 40%;'><strong>Marksheet No:</strong></td><td style='padding: 8px 0;'>" . htmlspecialchars($marksheet_no) . "</td></tr>
                        <tr><td style='padding: 8px 0; width: 40%;'><strong>Issue Date:</strong></td><td style='padding: 8px 0;'>" . htmlspecialchars($issue_date) . "</td></tr>
                        <tr><td style='padding: 8px 0;'><strong>Course:</strong></td><td style='padding: 8px 0;'><strong>" . htmlspecialchars($student['course_name']) . "</strong></td></tr>
                        <tr><td style='padding: 8px 0;'><strong>Overall Percentage:</strong></td><td style='padding: 8px 0;'><strong style='font-size: 24px; color: #28a745;'>" . $percentage . "%</strong></td></tr>
                        <tr><td style='padding: 8px 0;'><strong>Grade:</strong></td><td style='padding: 8px 0;'><strong>" . htmlspecialchars($grade) . "</strong></td></tr>
                        <tr><td style='padding: 8px 0;'><strong>Study Center:</strong></td><td style='padding: 8px 0;'>" . htmlspecialchars($student['center_code']) . " - " . htmlspecialchars($student['center_name']) . "</td></tr>

                    </table>
                    
                    <h3 style='color: #155724; border-bottom: 3px solid #28a745; padding-bottom: 10px;'>📋 Subject-wise Marks:</h3>
                    " . $results_table . "
                    
                    <div style='text-align: center; margin-top: 25px; padding-top: 20px; border-top: 2px solid #28a745;'>
                        <p style='font-size: 18px; font-weight: bold; color: #155724; margin: 0;'>This marksheet is <span style='color: #dc3545; text-decoration: underline;'>AUTHENTIC</span> and verified from official records.</p>
                        <p style='font-size: 14px; color: #6c757d; margin-top: 10px;'>Verified on: " . date('d-M-Y H:i:s') . "</p>
                    </div>
                </div>";
        } else {
            $error = "
                <div style='background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%); color: #721c24; padding: 30px; border: 3px solid #dc3545; border-radius: 15px; max-width: 700px; margin: 20px auto; box-shadow: 0 10px 30px rgba(220,53,69,0.3);'>
                    <div style='text-align: center; margin-bottom: 20px;'>
                        <h1 style='color: #721c24; font-size: 32px; margin: 0;'>✗ MARKSHEET NOT FOUND</h1>
                        <div style='font-size: 48px; color: #dc3545;'>❌</div>
                    </div>
                    <h3 style='margin-top: 0;'>No Record Found</h3>
                    <p style='font-size: 16px; line-height: 1.6;'>No marksheet found for:<br>
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
    <title>SKA | Marksheet Verification</title>
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
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 50%, #fecfef 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            color: #333;
            margin-bottom: 40px;
        }

        .header h1 {
            font-family: 'Noto Serif Vithkuqi', serif;
            font-size: 36px;
            margin-bottom: 10px;
            color: #d63384;
        }

        .form-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
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
            border-color: #d63384;
            outline: none;
            box-shadow: 0 0 0 3px rgba(214, 51, 132, 0.1);
            transform: translateY(-2px);
        }

        input[type="submit"] {
            width: 100%;
            background: linear-gradient(135deg, #d63384, #c44569);
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
            box-shadow: 0 10px 30px rgba(214, 51, 132, 0.4);
        }

        .qr-note {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            padding: 20px;
            border-radius: 12px;
            margin-top: 25px;
            text-align: center;
            border-left: 5px solid #fdcb6e;
        }

        .verify-again {
            display: block;
            margin: 30px auto 0;
            background: linear-gradient(135deg, #28a745, #20c997);
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
            box-shadow: 0 10px 30px rgba(40, 167, 69, 0.4);
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
            <h1>📋 Marksheet Verification</h1>
            <p>Verify detailed marks and subject-wise performance</p>
        </div>

        <div class="form-container">
            <?php if ($message): ?>
                <?= $message ?>
                <a href="verify-marksheet" class="verify-again">Verify Another Marksheet</a>
            <?php elseif ($error): ?>
                <?= $error ?>
                <div style="text-align: center; margin-top: 25px;">
                    <a href="verify-marksheet" class="verify-again"
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

                    <input type="submit" value="📋 Verify Marksheet">
                </form>

                <div class="qr-note">
                    <strong>💡 Pro Tip:</strong> Scan the QR code on any marksheet to view complete subject-wise marks
                    instantly!
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>