<?php
// enquiry.php - WhatsApp-green + Dynamic Courses from DB
include 'includes/db.php';
require_once __DIR__ . '/includes/anti_inspect.php';

$courses = [];
$course_error = '';

// Fetch courses from database
try {
    $conn = new mysqli($host, $username, $password, $dbname);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $sql = "SELECT id, course_name FROM courses ORDER BY course_name ASC";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }
    } else {
        $course_error = 'No courses found in database.';
    }

    $conn->close();
} catch (Exception $e) {
    $course_error = 'Database connection error. Please check courses table.';
}

$name = $email = $service = $message = '';
$whatsappUrl = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $service = trim($_POST['service'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($message) || empty($service)) {
        $error = 'Please fill in all required fields.';
    } else {
        $whatsappNumber = '+919939424787';

        $text = "I'm interested in your coaching.\n"
            . "Name: $name\n"
            . "Email: $email\n"
            . "Course: $service\n"
            . "Message: $message";

        $encodedText = urlencode($text);

        $whatsappUrl = "https://api.whatsapp.com/send/?phone="
            . rawurlencode($whatsappNumber)
            . "&text=" . $encodedText
            . "&type=phone_number&app_absent=0";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKA | Enquire on WhatsApp</title>
    <link rel="icon" type="image/x-icon" href="assets/images/ska-logo.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            line-height: 1.6;
            color: #0f172a;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 50%, #14532d 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            width: 100%;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(16px);
            border-radius: 24px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.4);
            overflow: hidden;
        }

        .hero {
            background: radial-gradient(circle at top left, #22c55e, #16a34a, #14532d);
            color: #ecfdf5;
            text-align: center;
            padding: 44px 32px;
            position: relative;
        }

        .hero::before {
            content: '';
            position: absolute;
            inset: -40%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.18) 0%, transparent 60%);
            opacity: 0.9;
            pointer-events: none;
        }

        .hero-inner {
            position: relative;
            z-index: 1;
        }

        .hero h1 {
            font-size: 2.3rem;
            margin-bottom: 8px;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .hero p {
            font-size: 1.02rem;
            opacity: 0.94;
        }

        .form-section {
            padding: 40px 32px 30px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-bottom: 18px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 14px;
            font-size: 0.98rem;
            transition: all 0.2s ease;
            background: #f9fafb;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #16a34a;
            background: #ffffff;
            box-shadow: 0 10px 25px rgba(22, 163, 74, 0.25);
            transform: translateY(-1px);
        }

        .form-group textarea {
            min-height: 110px;
            resize: vertical;
        }

        .btn-primary {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: #ecfdf5;
            padding: 16px 28px;
            border: none;
            border-radius: 16px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 40px rgba(22, 163, 74, 0.45);
            filter: brightness(1.03);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 14px;
            margin-bottom: 18px;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .alert-error {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .whatsapp-subtext {
            margin-top: 8px;
            font-size: 0.85rem;
            color: #6b7280;
            text-align: center;
        }

        .call-info {
            margin-top: 16px;
            padding: 14px 18px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 14px;
            text-align: center;
            font-size: 0.95rem;
            color: #166534;
        }

        .call-info a {
            color: #16a34a;
            font-weight: 600;
            text-decoration: none;
        }

        .call-info a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .hero {
                padding: 34px 20px;
            }

            .hero h1 {
                font-size: 1.9rem;
            }

            .form-section {
                padding: 28px 20px 22px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="hero">
            <div class="hero-inner">
                <h1>WhatsApp Coaching Enquiry</h1>
                <p>Fill your details and tap "Send Enquiry" to open WhatsApp with everything pre-filled.</p>
            </div>
        </div>

        <div class="form-section">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (!empty($course_error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($course_error) ?></div>
            <?php endif; ?>

            <form method="POST" id="enquiryForm">
                <div class="form-row">
                    <div class="form-group">
                        <input type="text" name="name" placeholder="Full Name *" value="<?= htmlspecialchars($name) ?>"
                            required>
                    </div>
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Email Address *"
                            value="<?= htmlspecialchars($email) ?>" required>
                    </div>
                </div>

                <div class="form-row">

                    <div class="form-group">
                        <select name="service" required>
                            <option value="">Select Course *</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= htmlspecialchars($course['course_name']) ?>"
                                    <?= $service === $course['course_name'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($course['course_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 8px;">
                    <textarea name="message" placeholder="Tell us about your goals and challenges... *"
                        required><?= htmlspecialchars($message) ?></textarea>
                </div>

                <button type="submit" class="btn-primary" id="sendBtn">
                    Send Enquiry on WhatsApp
                </button>

                <div class="whatsapp-subtext">
                    After you click, WhatsApp will open in a new tab with your enquiry ready to send.
                </div>

                <div class="call-info">
                    If you do not have WhatsApp, call us at <a href="tel:+918405913144">+91-8405913144</a>
                </div>
            </form>
        </div>
    </div>

    <?php if ($whatsappUrl): ?>
        <script>
            window.addEventListener('load', function () {
                const url = <?= json_encode($whatsappUrl) ?>;
                if (url) {
                    window.open(url, '_blank');
                }
            });
        </script>
    <?php endif; ?>

    <script>
        const form = document.getElementById('enquiryForm');
        const sendBtn = document.getElementById('sendBtn');

        form.addEventListener('submit', function () {
            sendBtn.textContent = 'Opening WhatsApp...';
            sendBtn.disabled = true;
        });
    </script>
</body>

</html>
