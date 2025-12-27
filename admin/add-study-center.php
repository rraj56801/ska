<?php
session_start();
include '../includes/db.php';
require_once __DIR__ . '/../includes/anti_inspect.php';

if (!isset($_SESSION['admin'])) {
    include(__DIR__ . '/../forbidden.php');
    exit();
}

// HANDLE FORM FIRST
$message = '';
$message_class = '';
$success_redirect = false;

if ($_POST) {
    $code = strtoupper(trim($_POST['code']));
    $name = trim($_POST['name']);

    // Check duplicate code
    $check = $pdo->prepare("SELECT id FROM study_centers WHERE center_code = ?");
    $check->execute([$code]);
    if ($check->rowCount() > 0) {
        $message = 'Centre code already exists!';
        $message_class = 'alert-danger';
    } else {
        $stmt = $pdo->prepare("INSERT INTO study_centers 
            (center_code, center_name, district, state, address, pincode, phone, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        $result = $stmt->execute([
            $code,
            $name,
            $_POST['district'],
            $_POST['state'],
            trim($_POST['address']),
            $_POST['pincode'],
            $_POST['phone']
        ]);

        if ($result) {
            $success_redirect = true; // Set flag for redirect
        } else {
            $message = '❌ Error adding centre!';
            $message_class = 'alert-danger';
        }
    }
}

// REDIRECT TO ALL CENTRES ON SUCCESS
if ($success_redirect) {
    header("Location: all-study-centers?added=1");
    exit;
}

?>
<?php include 'header.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add Study Centre</title>
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

        .card-header-main {
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
            <h3>Add New Study Centre <i class="bi bi-building-add"></i></h3>
            <a href="all-study-centers" class="btn btn-outline-secondary btn-lg shadow-sm">
                <i class="bi bi-arrow-left"></i> Back to Centres
            </a>
        </div>

        <?php if ($message): ?>
            <div class="alert <?= $message_class ?> alert-dismissible fade show mx-auto mb-4" style="max-width:600px;">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card form-section">
                    <div class="card-header card-header-main px-4 py-3">
                        <h5 class="mb-0"><i class="bi bi-building-gear me-2"></i> Centre Details</h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST">
                            <div class="row g-4">
                                <!-- Basic Info -->
                                <div class="col-md-4">
                                    <label class="form-label">Centre Code <span class="text-danger">*</span></label>
                                    <input type="text" name="code" class="form-control form-control-lg"
                                        value="<?= htmlspecialchars($_POST['code'] ?? '') ?>" required maxlength="10">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Centre Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control form-control-lg"
                                        value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                                </div>

                                <!-- Location Section -->
                                <div class="col-12">
                                    <h6 class="section-title"><i class="bi bi-geo-alt"></i> Location Details</h6>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">PINCODE <small class="text-muted">(6 digits - auto fills
                                            below)</small></label>
                                    <input type="text" name="pincode" id="pincode" class="form-control form-control-lg"
                                        value="<?= htmlspecialchars($_POST['pincode'] ?? '') ?>" maxlength="6" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">STATE</label>
                                    <input type="text" name="state" id="state" class="form-control form-control-lg"
                                        readonly required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">DISTRICT</label>
                                    <input type="text" name="district" id="district"
                                        class="form-control form-control-lg" readonly required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">CITY</label>
                                    <input type="text" name="city" id="city" class="form-control form-control-lg"
                                        readonly>
                                </div>

                                <!-- Contact -->
                                <div class="col-md-6">
                                    <label class="form-label">Phone <span class="text-danger">*</span></label>
                                    <input type="tel" name="phone" class="form-control form-control-lg"
                                        value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Full Address <span class="text-danger">*</span></label>
                                    <textarea name="address" class="form-control form-control-lg" rows="3"
                                        required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <div class="text-center mt-5">
                                <button type="submit" class="btn btn-success btn-lg px-5 shadow me-3">
                                    <i class="bi bi-check-circle"></i> Add Centre
                                </button>
                                <a href="all-study-centers" class="btn btn-outline-primary btn-lg px-5 shadow">
                                    <i class="bi bi-list"></i> View All
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery & Pincode API Script -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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

    <?php include '../includes/footer.php'; ?>
</body>

</html>