<?php
session_start();
include '../includes/db.php';
require_once __DIR__ . '/../includes/anti_inspect.php';

if (!isset($_SESSION['admin'])) {
    include(__DIR__ . '/../forbidden.php');
    exit();
}

include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>All Study Centres</title>
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
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            font-weight: 600;
            border-radius: 16px 16px 0 0;
        }

        .btn-rounded {
            border-radius: 50px;
            transition: all 0.3s ease;
            min-width: 44px;
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-rounded:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2) !important;
        }

        .btn-group .btn-rounded+.btn-rounded {
            margin-left: 4px;
        }

        .table thead th {
            vertical-align: middle;
        }

        .add-btn {
            background: linear-gradient(135deg, #28a745, #20c997) !important;
            border: none;
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.3);
            font-weight: 600;
            padding: 12px 30px;
        }

        .add-btn:hover {
            background: linear-gradient(135deg, #218838, #1ea391) !important;
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(40, 167, 69, 0.4);
        }
    </style>
</head>

<body>

    <div class="container-fluid mt-4 mb-5">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0">All Study Centres
                <span
                    class="badge bg-primary fs-5"><?= $pdo->query("SELECT COUNT(*) FROM study_centers")->fetchColumn() ?></span>
            </h3>
            <a href="add-study-center" class="btn btn-lg add-btn shadow">
                <i class="bi bi-plus-circle-fill me-2 fs-5"></i>Add New Centre
            </a>
        </div>

        <div class="card section-card">
            <div class="card-header card-header-main d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-building me-2"></i>All Study Centres / Franchises</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th style="width:8%">Code</th>
                                <th style="width:25%">Centre Name</th>
                                <th style="width:15%">District</th>
                                <th style="width:15%">State</th>
                                <th style="width:12%">Phone</th>
                                <th style="width:10%">Status</th>
                                <th style="width:15%">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $centers = $pdo->query("SELECT * FROM study_centers ORDER BY id DESC")->fetchAll();
                            foreach ($centers as $c) {
                                $status = $c['is_active'] == '1' ?
                                    '<span class="badge bg-success px-3 py-2">Active</span>' :
                                    '<span class="badge bg-danger px-3 py-2">Inactive</span>';

                                echo "<tr>
                                <td><span class='badge bg-primary fs-6'>" . htmlspecialchars($c['center_code']) . "</span></td>
                                <td><strong>" . htmlspecialchars($c['center_name']) . "</strong></td>
                                <td>" . htmlspecialchars($c['district']) . "</td>
                                <td>" . htmlspecialchars($c['state']) . "</td>
                                <td><a href='tel:" . htmlspecialchars($c['phone']) . "' class='text-primary'>" . htmlspecialchars($c['phone']) . "</a></td>
                                <td>$status</td>
                                <td>
                                    <div class='btn-group' role='group'>
                                        <a href='edit-center?id={$c['id']}' 
                                           class='btn btn-sm btn-warning btn-rounded' 
                                           title='Edit Centre'>
                                            <i class='bi bi-pencil'></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>";
                            }
                            if (empty($centers)) {
                                echo '<tr><td colspan="7" class="text-center py-4 text-muted fs-5">No study centres found</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php include '../includes/footer.php'; ?>
</body>

</html>