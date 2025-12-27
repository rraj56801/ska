<?php
session_start();
include '../includes/db.php';
require_once __DIR__ . '/../includes/anti_inspect.php';

if (!isset($_SESSION['admin'])) {
    include(__DIR__ . '/../forbidden.php');
    exit();
}

$id = (int) $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM study_centers WHERE id = ?");
$stmt->execute([$id]);
$center = $stmt->fetch();

if (!$center) {
    header("Location: all-study-centers?error=notfound");
    exit;
}

// HANDLE UPDATE FIRST
$success_redirect = false;

if ($_POST) {
    $code = strtoupper(trim($_POST['code']));

    // Check duplicate code (except current centre)
    $check = $pdo->prepare("SELECT id FROM study_centers WHERE center_code = ? AND id != ?");
    $check->execute([$code, $id]);
    if ($check->rowCount() > 0) {
        header("Location: edit-center?id=$id&error=duplicate");
        exit;
    } else {
        $stmt = $pdo->prepare("UPDATE study_centers SET 
            center_code = ?, center_name = ?, district = ?, state = ?, 
            address = ?, pincode = ?, phone = ?, is_active = ? 
            WHERE id = ?");

        $result = $stmt->execute([
            $code,
            trim($_POST['name']),
            trim($_POST['district']),
            trim($_POST['state']),
            trim($_POST['address']),
            $_POST['pincode'],
            $_POST['phone'],
            $_POST['is_active'],
            $id
        ]);

        if ($result) {
            // SUCCESSFUL UPDATE - REDIRECT TO ALL CENTRES
            header("Location: all-study-centers?updated=1");
            exit;
        } else {
            header("Location: edit-center?id=$id&error=update");
            exit;
        }
    }
}

include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Study Centre - <?= htmlspecialchars($center['center_name']) ?></title>
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
            <div>
                <h3>Edit Study Centre <i class="bi bi-pencil-square"></i></h3>
                <small class="text-muted">Code: <strong><?= htmlspecialchars($center['center_code']) ?></strong></small>
            </div>
            <a href="all-study-centers" class="btn btn-outline-secondary btn-lg shadow-sm">
                <i class="bi bi-arrow-left"></i> Back to Centres
            </a>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <?php if ($_GET['error'] == 'duplicate'): ?>
                <div class="alert alert-danger alert-dismissible fade show mx-auto mb-4" style="max-width:600px;">
                    Centre code already exists for another centre!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($_GET['error'] == 'update'): ?>
                <div class="alert alert-danger alert-dismissible fade show mx-auto mb-4" style="max-width:600px;">
                    ❌ Error updating centre!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($_GET['error'] == 'notfound'): ?>
                <div class="alert alert-danger alert-dismissible fade show mx-auto mb-4" style="max-width:600px;">
                    Centre not found!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card form-section">
                    <div class="card-header card-header-main px-4 py-3">
                        <h5 class="mb-0"><i class="bi bi-building-gear me-2"></i>
                            <?= htmlspecialchars($center['center_name']) ?></h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST">
                            <div class="row g-4">
                                <!-- Basic Info -->
                                <div class="col-md-4">
                                    <label class="form-label">Centre Code <span class="text-danger">*</span></label>
                                    <input type="text" name="code" class="form-control form-control-lg"
                                        value="<?= htmlspecialchars($center['center_code']) ?>" required maxlength="10">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Centre Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control form-control-lg"
                                        value="<?= htmlspecialchars($center['center_name']) ?>" required>
                                </div>

                                <!-- Location Section -->
                                <div class="col-12">
                                    <h6 class="section-title"><i class="bi bi-geo-alt"></i> Location Details</h6>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">PINCODE</label>
                                    <input type="text" name="pincode" class="form-control form-control-lg"
                                        value="<?= htmlspecialchars($center['pincode']) ?>" maxlength="6" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">STATE</label>
                                    <input type="text" name="state" class="form-control form-control-lg"
                                        value="<?= htmlspecialchars($center['state']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">DISTRICT</label>
                                    <input type="text" name="district" class="form-control form-control-lg"
                                        value="<?= htmlspecialchars($center['district']) ?>" required>
                                </div>

                                <!-- Contact -->
                                <div class="col-md-6">
                                    <label class="form-label">Phone <span class="text-danger">*</span></label>
                                    <input type="tel" name="phone" class="form-control form-control-lg"
                                        value="<?= htmlspecialchars($center['phone']) ?>" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Full Address <span class="text-danger">*</span></label>
                                    <textarea name="address" class="form-control form-control-lg" rows="3"
                                        required><?= htmlspecialchars($center['address']) ?></textarea>
                                </div>

                                <!-- is_active -->
                                <div class="col-md-6">
                                    <label class="form-label">Status <span class="text-danger">*</span></label>
                                    <select name="is_active" class="form-select form-select-lg" required>
                                        <option value="1" <?= $center['is_active'] == '1' ? 'selected' : '' ?>>
                                            Active</option>
                                        <option value="0" <?= $center['is_active'] == '0' ? 'selected' : '' ?>>
                                            Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <div class="text-center mt-5">
                                <button type="submit" class="btn btn-primary btn-lg px-5 shadow me-3">
                                    <i class="bi bi-save"></i> Update Centre
                                </button>
                                <a href="all-study-centers" class="btn btn-outline-secondary btn-lg px-5 shadow">
                                    <i class="bi bi-list"></i> View All Centres
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php include '../includes/../includes/footer.php'; ?>
</body>

</html>