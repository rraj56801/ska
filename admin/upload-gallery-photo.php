<?php
session_start();
include '../includes/db.php';
require_once __DIR__ . '/../includes/anti_inspect.php';

if (!isset($_SESSION['admin'])) {
    include(__DIR__ . '/../forbidden.php');
    exit();
}

// Handle image upload
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES['gallery_image'])) {
    $upload_dir = '/Applications/XAMPP/htdocs/ska/uploads/photos/';

    // Create directory if not exists
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file = $_FILES['gallery_image'];
    $caption = trim($_POST['caption'] ?? '');

    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (in_array($mime_type, $allowed_types) && $file['size'] <= $max_size) {
            $filename = uniqid() . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
            $filepath = $upload_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $stmt = $pdo->prepare("INSERT INTO gallery_uploads (filename, original_name, file_size, caption) VALUES (?, ?, ?, ?)");
                $stmt->execute([$filename, $file['name'], $file['size'], $caption]);
                $_SESSION['success'] = "Image uploaded successfully!";
            } else {
                $_SESSION['error'] = "Failed to upload image";
            }
        } else {
            $_SESSION['error'] = "Invalid file type or size too large (max 5MB)";
        }
    }
}

// Fetch gallery images
$stmt = $pdo->query("SELECT * FROM gallery_uploads ORDER BY upload_date DESC");
$gallery_images = $stmt->fetchAll();

// Handle delete
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $delete_id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("SELECT filename FROM gallery_uploads WHERE id = ?");
    $stmt->execute([$delete_id]);
    $image = $stmt->fetch();

    if ($image) {
        $filepath = '../uploads/photos/' . $image['filename'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        $pdo->prepare("DELETE FROM gallery_uploads WHERE id = ?")->execute([$delete_id]);
        $_SESSION['success'] = "Image deleted successfully!";
        header("Location: upload-gallery-photo");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gallery - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .gallery-item {
            position: relative;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .gallery-item:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .gallery-img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .gallery-item:hover .gallery-img {
            transform: scale(1.05);
        }

        .gallery-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
            color: white;
            padding: 20px;
            transform: translateY(100%);
            transition: transform 0.3s ease;
        }

        .gallery-item:hover .gallery-overlay {
            transform: translateY(0);
        }

        .upload-zone {
            border: 3px dashed #007bff;
            border-radius: 15px;
            padding: 60px;
            text-align: center;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .upload-zone:hover,
        .upload-zone.dragover {
            border-color: #28a745;
            background: #f0f8ff;
        }
    </style>
</head>

<body class="bg-light">

    <div class="container mt-4 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-header bg-gradient bg-primary text-white text-center py-5">
                        <h2 class="mb-0">
                            <i class="bi bi-images me-2"></i>Photo Gallery
                        </h2>
                        <p class="mb-0 opacity-90 mt-2">Manage your image gallery</p>
                    </div>

                    <div class="card-body p-5">
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <?= htmlspecialchars($_SESSION['success']) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['success']); ?>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <?= htmlspecialchars($_SESSION['error']) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['error']); ?>
                        <?php endif; ?>

                        <!-- Upload Form -->
                        <div class="row mb-5">
                            <div class="col-md-8">
                                <h5 class="section-title mb-4">
                                    <i class="bi bi-cloud-upload me-2 text-primary"></i>Add New Image
                                </h5>
                                <form method="POST" enctype="multipart/form-data" id="uploadForm">
                                    <div class="row g-4">
                                        <div class="col-md-8">
                                            <label class="form-label">Choose Image <span
                                                    class="required">*</span></label>
                                            <div class="upload-zone">
                                                <i class="bi bi-cloud-upload display-4 text-primary mb-3"></i>
                                                <div>Drag & drop or click to browse</div>
                                                <small class="text-muted">JPG, PNG, GIF, WebP (Max 5MB)</small>
                                                <input type="file" name="gallery_image" class="form-control mt-3"
                                                    accept="image/*" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Caption (Optional)</label>
                                            <textarea class="form-control" name="caption" rows="4"
                                                placeholder="Enter image caption..."></textarea>
                                        </div>
                                    </div>
                                    <div class="text-end mt-4">
                                        <button type="submit" class="btn btn-success btn-lg px-5">
                                            <i class="bi bi-upload me-2"></i>Upload Image
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Gallery Grid -->
                        <?php if (!empty($gallery_images)): ?>
                            <h5 class="section-title">
                                <i class="bi bi-grid-3x3-gap me-2 text-primary"></i>
                                Gallery (<?= count($gallery_images) ?> images)
                            </h5>
                            <div class="gallery-grid">
                                <?php foreach ($gallery_images as $image): ?>
                                    <?php
                                    $image_path = '../uploads/photos/' . $image['filename'];
                                    if (file_exists($image_path)):
                                        ?>
                                        <div class="gallery-item">
                                            <img src="<?= $image_path ?>" alt="<?= htmlspecialchars($image['original_name']) ?>"
                                                class="gallery-img" data-bs-toggle="modal"
                                                data-bs-target="#imageModal<?= $image['id'] ?>">

                                            <div class="gallery-overlay">
                                                <h6 class="mb-1"><?= htmlspecialchars($image['original_name']) ?></h6>
                                                <?php if ($image['caption']): ?>
                                                    <p class="mb-2"><?= htmlspecialchars($image['caption']) ?></p>
                                                <?php endif; ?>
                                                <div>
                                                    <span class="badge bg-light text-dark me-2">
                                                        <?= number_format($image['file_size'] / 1024, 1) ?> KB
                                                    </span>
                                                    <span
                                                        class="badge bg-secondary"><?= date('d M Y', strtotime($image['upload_date'])) ?></span>
                                                    <a href="?delete=<?= $image['id'] ?>" class="btn btn-sm btn-danger ms-2"
                                                        onclick="return confirm('Delete this image?')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Image Modal -->
                                        <div class="modal fade" id="imageModal<?= $image['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-xl">
                                                <div class="modal-content border-0">
                                                    <div class="modal-header bg-dark text-white">
                                                        <h5 class="modal-title"><?= htmlspecialchars($image['original_name']) ?>
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white"
                                                            data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body text-center p-5">
                                                        <img src="<?= $image_path ?>" class="img-fluid" style="max-height: 70vh;">
                                                        <?php if ($image['caption']): ?>
                                                            <div class="mt-4">
                                                                <p class="lead"><?= htmlspecialchars($image['caption']) ?></p>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="modal-footer bg-light">
                                                        <small class="text-muted">
                                                            Uploaded: <?= date('d M Y H:i', strtotime($image['upload_date'])) ?> |
                                                            <?= number_format($image['file_size'] / 1024, 1) ?> KB
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-images display-1 text-muted mb-4"></i>
                                <h4>No images in gallery yet</h4>
                                <p class="text-muted">Upload your first image above to get started!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const uploadZone = document.querySelector('.upload-zone');
            const fileInput = document.querySelector('input[name="gallery_image"]');

            // Drag & Drop
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadZone.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                uploadZone.addEventListener(eventName, () => uploadZone.classList.add('dragover'), false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                uploadZone.addEventListener(eventName, () => uploadZone.classList.remove('dragover'), false);
            });

            uploadZone.addEventListener('drop', function (e) {
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    fileInput.files = files;
                }
            });
        });
    </script>

</body>

</html>