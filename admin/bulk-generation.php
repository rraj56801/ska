<?php

?>

<?php include 'header.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Generation Hub - SKA Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="text-center mb-5">
                    <h1 class="display-5 fw-bold text-primary mb-3">
                        <i class="bi bi-gear-fill me-3"></i>
                        Bulk Generation Hub
                    </h1>
                    <p class="lead text-muted">Generate documents for multiple students at once</p>
                </div>

                <!-- Bulk Generation Cards - 4 Cards Grid -->
                <div class="row g-4">
                    <!-- ID Cards -->
                    <div class="col-md-6 col-lg-3">
                        <a href="generate-bulk-id-card" class="text-decoration-none">
                            <div class="card border-0 shadow-lg h-100 hover-card">
                                <div class="card-body text-center p-4">
                                    <div class="bg-info bg-gradient rounded-circle mx-auto mb-3 p-4 d-flex align-items-center justify-content-center"
                                        style="width: 80px; height: 80px;">
                                        <i class="bi bi-person-badge-fill fs-1 text-white"></i>
                                    </div>
                                    <h5 class="card-title fw-bold mb-2">Bulk ID Cards</h5>
                                    <p class="text-muted mb-3 small">Student identification cards</p>
                                    <button class="btn btn-info btn-lg w-100">
                                        <i class="bi bi-arrow-right me-2"></i>Generate Now
                                    </button>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Admit Cards -->
                    <div class="col-md-6 col-lg-3">
                        <a href="generate-bulk-admit-card" class="text-decoration-none">
                            <div class="card border-0 shadow-lg h-100 hover-card">
                                <div class="card-body text-center p-4">
                                    <div class="bg-primary bg-gradient rounded-circle mx-auto mb-3 p-4 d-flex align-items-center justify-content-center"
                                        style="width: 80px; height: 80px;">
                                        <i class="bi bi-file-earmark-text-fill fs-1 text-white"></i>
                                    </div>
                                    <h5 class="card-title fw-bold mb-2">Bulk Admit Cards</h5>
                                    <p class="text-muted mb-3 small">Exam admission slips</p>
                                    <button class="btn btn-primary btn-lg w-100">
                                        <i class="bi bi-arrow-right me-2"></i>Generate Now
                                    </button>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Marksheets -->
                    <div class="col-md-6 col-lg-3">
                        <a href="generate-bulk-marksheet" class="text-decoration-none">
                            <div class="card border-0 shadow-lg h-100 hover-card">
                                <div class="card-body text-center p-4">
                                    <div class="bg-success bg-gradient rounded-circle mx-auto mb-3 p-4 d-flex align-items-center justify-content-center"
                                        style="width: 80px; height: 80px;">
                                        <i class="bi bi-file-earmark-bar-graph-fill fs-1 text-white"></i>
                                    </div>
                                    <h5 class="card-title fw-bold mb-2">Bulk Marksheets</h5>
                                    <p class="text-muted mb-3 small">Academic result sheets</p>
                                    <button class="btn btn-success btn-lg w-100">
                                        <i class="bi bi-arrow-right me-2"></i>Generate Now
                                    </button>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Certificates -->
                    <div class="col-md-6 col-lg-3">
                        <a href="generate-bulk-certificate" class="text-decoration-none">
                            <div class="card border-0 shadow-lg h-100 hover-card">
                                <div class="card-body text-center p-4">
                                    <div class="bg-warning bg-gradient rounded-circle mx-auto mb-3 p-4 d-flex align-items-center justify-content-center"
                                        style="width: 80px; height: 80px;">
                                        <i class="bi bi-award-fill fs-1 text-white"></i>
                                    </div>
                                    <h5 class="card-title fw-bold mb-2">Bulk Certificates</h5>
                                    <p class="text-muted mb-3 small">Course completion awards</p>
                                    <button class="btn btn-warning btn-lg w-100">
                                        <i class="bi bi-arrow-right me-2"></i>Generate Now
                                    </button>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .hover-card {
            transition: all 0.3s ease;
            background: linear-gradient(145deg, #ffffff, #f8fafc);
        }

        .hover-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15) !important;
        }

        .btn {
            font-weight: 600;
        }

        .card-title {
            font-size: 1.1rem;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
<?php include '../includes/footer.php'; ?>
</html>