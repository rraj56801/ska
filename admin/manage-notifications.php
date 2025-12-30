<?php
session_start();
include '../includes/db.php';
require_once __DIR__ . '/../includes/anti_inspect.php';

$message = '';
$message_class = '';

// Handle add notification
if (isset($_POST['add_notification'])) {
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($title === '' || $message === '') {
        $message = 'Title and message are required!';
        $message_class = 'alert-danger';
    } else {
        $stmt = $pdo->prepare("INSERT INTO notifications (title, message) VALUES (?, ?)");
        if ($stmt->execute([$title, $message])) {
            $message = 'Notification added successfully!';
            $message_class = 'alert-success';
        } else {
            $message = 'Failed to add notification.';
            $message_class = 'alert-danger';
        }
    }
}

// Handle enable/disable
if (isset($_POST['toggle_notification'])) {
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($id > 0 && in_array($action, ['enable', 'disable'])) {
        $isEnabled = $action === 'enable' ? 1 : 0;
        $stmt = $pdo->prepare("UPDATE notifications SET is_enabled = ? WHERE id = ?");
        if ($stmt->execute([$isEnabled, $id])) {
            $message = 'Notification updated.';
            $message_class = 'alert-success';
        } else {
            $message = 'Failed to update notification.';
            $message_class = 'alert-danger';
        }
    }
}

// Fetch all notifications
$stmt = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC");
$notifications = $stmt->fetchAll();

include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .section-card {
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
            border-radius: 16px;
            border: none;
        }
        .card-header-main {
            background: linear-gradient(135deg, #00c9ff, #92fe9d);
            color: white;
            font-weight: 600;
            border-radius: 16px 16px 0 0;
        }
        .btn-rounded {
            border-radius: 50px;
            transition: all 0.3s ease;
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
                    <i class="bi bi-bell-fill me-2"></i>Manage Notifications
                </h3>
            </div>
        </div>

        <!-- ADD NOTIFICATION FORM -->
        <div class="card section-card mb-4">
            <div class="card-header card-header-main">
                <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Add New Notification</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text"
                                   class="form-control form-control-lg"
                                   name="title"
                                   placeholder="Enter title"
                                   required>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button type="submit"
                                    name="add_notification"
                                    class="btn btn-primary btn-lg btn-rounded w-100">
                                <i class="bi bi-plus-lg me-1"></i>Add Notification
                            </button>
                        </div>
                        <div class="col-12">
                            <label class="form-label mt-3">Message <span class="text-danger">*</span></label>
                            <textarea class="form-control form-control-lg"
                                      name="message"
                                      rows="3"
                                      placeholder="Enter message"
                                      required></textarea>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- NOTIFICATIONS LIST -->
        <div class="card section-card">
            <div class="card-header card-header-main">
                <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Active Notifications</h5>
            </div>
            <div class="card-body">
                <?php if (empty($notifications)): ?>
                    <p class="text-muted">No notifications yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Message</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($notifications as $n): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($n['title']) ?></td>
                                        <td><?= htmlspecialchars($n['message']) ?></td>
                                        <td>
                                            <span class="badge <?= $n['is_enabled'] ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= $n['is_enabled'] ? 'Enabled' : 'Disabled' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="id" value="<?= $n['id'] ?>">
                                                <?php if ($n['is_enabled']): ?>
                                                    <button type="submit" name="toggle_notification" value="disable"
                                                            class="btn btn-sm btn-outline-secondary"
                                                            onclick="return confirm('Disable this notification?')">
                                                        <i class="bi bi-eye-slash"></i> Disable
                                                    </button>
                                                <?php else: ?>
                                                    <button type="submit" name="toggle_notification" value="enable"
                                                            class="btn btn-sm btn-outline-success">
                                                        <i class="bi bi-eye"></i> Enable
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php include '../includes/footer.php'; ?>
</body>
</html>
