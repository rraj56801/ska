<?php
session_start();
include '../includes/db.php';
require_once __DIR__ . '/../includes/anti_inspect.php';

// Check if Super Admin is logged in
if (!isset($_SESSION['admin']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    include(__DIR__ . '/../forbidden.php');
    exit();
}

$message = '';
$message_class = '';

// Handle DELETE request
if (isset($_POST['delete_admin'])) {
    $id = $_POST['id'];

    if ($id == $_SESSION['admin']['id']) {
        $message = 'You cannot delete your own account!';
        $message_class = 'alert-danger';
    } else {
        $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Admin deleted successfully!';
        $message_class = 'alert-success';
    }
}

// Handle UPDATE request
if (isset($_POST['update_admin'])) {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $role = $_POST['role'];
    $password = $_POST['password'];

    if (empty($name) || empty($username) || empty($role)) {
        $message = 'Name, username and role are required!';
        $message_class = 'alert-danger';
    } else {
        $check_stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
        $check_stmt->execute([$username, $id]);

        if ($check_stmt->rowCount() > 0) {
            $message = 'Username already exists!';
            $message_class = 'alert-danger';
        } else {
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE admins SET name = ?, username = ?, password = ?, role = ? WHERE id = ?");
                $stmt->execute([$name, $username, $hashed_password, $role, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE admins SET name = ?, username = ?, role = ? WHERE id = ?");
                $stmt->execute([$name, $username, $role, $id]);
            }

            $message = 'Admin updated successfully!';
            $message_class = 'alert-success';
        }
    }
}

// Handle ADD request
if (isset($_POST['add_admin'])) {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (empty($name) || empty($username) || empty($password) || empty($role)) {
        $message = 'All fields are required!';
        $message_class = 'alert-danger';
    } else {
        $check_stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
        $check_stmt->execute([$username]);

        if ($check_stmt->rowCount() > 0) {
            $message = 'Username already exists!';
            $message_class = 'alert-danger';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admins (name, username, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$name, $username, $hashed_password, $role]);

            $message = 'Admin added successfully!';
            $message_class = 'alert-success';
        }
    }
}

// Fetch all admins
$admins = $pdo->query("SELECT * FROM admins ORDER BY created_at DESC")->fetchAll();

include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Admins</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }

        .section-card {
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
            border-radius: 16px;
            border: none;
        }

        .card-header-main {
            background: linear-gradient(135deg, #ff6b6b, #feca57);
            color: white;
            font-weight: 600;
            border-radius: 16px 16px 0 0;
        }

        .card-header-secondary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            font-weight: 600;
            border-radius: 16px 16px 0 0;
        }

        .btn-rounded {
            border-radius: 50px;
            transition: all 0.3s ease;
        }

        .btn-rounded:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2) !important;
        }

        .role-badge {
            font-weight: 600;
            padding: 0.5rem 1rem;
        }
    </style>
</head>

<body>
    <div class="container-fluid mt-4 mb-5">

        <?php if ($message): ?>
            <div class="alert <?= $message_class ?> alert-dismissible fade show mx-auto mb-4" style="max-width:800px;">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-12">
                <h3 class="mb-2">
                    <i class="bi bi-shield-lock me-2"></i>Admin Management
                    <span class="badge bg-primary fs-5"><?= count($admins) ?></span>
                </h3>
            </div>
        </div>

        <!-- ADD ADMIN FORM -->
        <div class="card section-card mb-4">
            <div class="card-header card-header-secondary">
                <h5 class="mb-0"><i class="bi bi-person-plus-fill me-2"></i>Add New Admin</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg" name="name"
                                placeholder="Enter full name" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg" name="username"
                                placeholder="Enter username" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control form-control-lg" id="addPassword"
                                    name="password" placeholder="Password" required>
                                <button class="btn btn-outline-secondary" type="button"
                                    onclick="togglePassword('addPassword', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select form-select-lg" name="role" required>
                                <option value="">Select Role</option>
                                <option value="Super Admin">Super Admin</option>
                                <option value="Admin">Admin</option>
                                <option value="Cashier">Cashier</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" name="add_admin" class="btn btn-success btn-lg btn-rounded w-100">
                                <i class="bi bi-plus-circle me-1"></i>Add Admin
                            </button>
                        </div>
                    </div>

                </form>
            </div>
        </div>

        <!-- LIST OF ADMINS -->
        <div class="card section-card">
            <div class="card-header card-header-main">
                <h5 class="mb-0"><i class="bi bi-people-fill me-2"></i>All Admins</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th style="width:5%">#</th>
                                <th style="width:20%">Name</th>
                                <th style="width:15%">Username</th>
                                <th style="width:15%">Role</th>
                                <th style="width:15%">Created At</th>
                                <th style="width:30%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $i => $admin): ?>
                                <tr>
                                    <td><strong><?= $i + 1 ?></strong></td>
                                    <td><strong><?= htmlspecialchars($admin['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($admin['username']) ?></td>
                                    <td>
                                        <span
                                            class="badge role-badge bg-<?= $admin['role'] == 'Super Admin' ? 'danger' : ($admin['role'] == 'Admin' ? 'primary' : 'warning') ?> fs-6">
                                            <?= htmlspecialchars($admin['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?= date('d-M-Y', strtotime($admin['created_at'])) ?></strong><br>
                                        <small><?= date('h:i A', strtotime($admin['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary btn-sm btn-rounded" data-bs-toggle="modal"
                                            data-bs-target="#editModal<?= $admin['id'] ?>">
                                            <i class="bi bi-pencil-square me-1"></i>Edit
                                        </button>

                                        <?php if ($admin['id'] != $_SESSION['admin']['id']): ?>
                                            <form method="POST" style="display:inline;"
                                                onsubmit="return confirm('Are you sure you want to delete this admin?');">
                                                <input type="hidden" name="id" value="<?= $admin['id'] ?>">
                                                <button type="submit" name="delete_admin"
                                                    class="btn btn-danger btn-sm btn-rounded">
                                                    <i class="bi bi-trash me-1"></i>Delete
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge bg-info">Current User</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                                <!-- EDIT MODAL -->
                                <div class="modal fade" id="editModal<?= $admin['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header"
                                                style="background: linear-gradient(135deg, #667eea, #764ba2); color: white;">
                                                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Admin
                                                </h5>
                                                <button type="button" class="btn-close btn-close-white"
                                                    data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="id" value="<?= $admin['id'] ?>">

                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Full Name</label>
                                                        <input type="text" class="form-control form-control-lg" name="name"
                                                            value="<?= htmlspecialchars($admin['name']) ?>" required>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Username</label>
                                                        <input type="text" class="form-control form-control-lg"
                                                            name="username"
                                                            value="<?= htmlspecialchars($admin['username']) ?>" required>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">New Password</label>
                                                        <input type="password" class="form-control form-control-lg"
                                                            name="password"
                                                            placeholder="Leave blank to keep current password">
                                                        <small class="text-muted">Only enter if you want to change the
                                                            password</small>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Role</label>
                                                        <select class="form-select form-select-lg" name="role" required>
                                                            <option value="Super Admin" <?= $admin['role'] == 'Super Admin' ? 'selected' : '' ?>>Super Admin</option>
                                                            <option value="Admin" <?= $admin['role'] == 'Admin' ? 'selected' : '' ?>>Admin</option>
                                                            <option value="Cashier" <?= $admin['role'] == 'Cashier' ? 'selected' : '' ?>>Cashier</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary btn-rounded"
                                                        data-bs-dismiss="modal">
                                                        <i class="bi bi-x-circle me-1"></i>Cancel
                                                    </button>
                                                    <button type="submit" name="update_admin"
                                                        class="btn btn-primary btn-rounded">
                                                        <i class="bi bi-check-circle me-1"></i>Update Admin
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <?php if (empty($admins)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted fs-5">
                                        No admins found
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }
    </script>
    <?php include '../includes/footer.php'; ?>
</body>

</html>