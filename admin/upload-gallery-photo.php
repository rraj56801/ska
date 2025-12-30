<?php
session_start();
include '../includes/db.php';
require_once __DIR__ . '/../includes/anti_inspect.php';

if (!isset($_SESSION['admin'])) {
    include(__DIR__ . '/../forbidden.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES['gallery_image'])) {
    $upload_dir = __DIR__ . '/../uploads/photos/';

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $files = $_FILES['gallery_image'];
    $caption = trim($_POST['caption'] ?? '');
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB
    $uploaded_count = 0;
    $error_count = 0;

    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $tmp_name = $files['tmp_name'][$i];
            $name = $files['name'][$i];
            $size = $files['size'][$i];

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $tmp_name);
            finfo_close($finfo);

            if (in_array($mime_type, $allowed_types) && $size <= $max_size) {
                $filename = uniqid() . '_' . time() . '.' . pathinfo($name, PATHINFO_EXTENSION);
                $filepath = $upload_dir . $filename;

                if (move_uploaded_file($tmp_name, $filepath)) {
                    $stmt = $pdo->prepare("INSERT INTO gallery_uploads (filename, original_name, file_size, caption) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$filename, $name, $size, $caption]);
                    $uploaded_count++;
                } else {
                    $error_count++;
                }
            } else {
                $error_count++;
            }
        }
    }

    if ($uploaded_count > 0) {
        $_SESSION['success'] = "$uploaded_count image(s) uploaded successfully!";
    }
    if ($error_count > 0) {
        $_SESSION['error'] = "$error_count file(s) failed to upload. Check file type or size.";
    }
}

$stmt = $pdo->query("SELECT * FROM gallery_uploads ORDER BY upload_date DESC");
$gallery_images = $stmt->fetchAll();

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
        .gallery-item:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);}
        .gallery-img { width: 100%; height: 250px; object-fit: cover; transition: transform 0.3s ease;}
        .gallery-item:hover .gallery-img { transform: scale(1.05);}
        .upload-zone { border: 3px dashed #007bff; border-radius: 15px; padding: 60px; text-align: center;background: #f8f9fa; }
    </style>
</head>
<body class="bg-light">

<div class="container mt-4 mb-5">
    <div class="card shadow-lg border-0 rounded-4">
        <div class="card-header bg-gradient bg-primary text-white text-center py-5">
            <h2 class="mb-0"><i class="bi bi-images me-2"></i>Photo Gallery</h2>
            <p class="mb-0 opacity-90 mt-2">Manage your image gallery</p>
        </div>
        <div class="card-body p-5">

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="row g-4">
                    <div class="col-md-8">
                        <label class="form-label">Choose Images <span class="text-danger">*</span></label>
                        <div class="upload-zone">
                            <i class="bi bi-cloud-upload display-4 text-primary mb-3"></i>
                            <div>Drag & drop or click to select multiple files</div>
                            <small class="text-muted">JPG, PNG, GIF, WebP (Max 5MB each)</small>
                            <input type="file" name="gallery_image[]" class="form-control mt-3" accept="image/*" multiple required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Caption (Optional)</label>
                        <textarea class="form-control" name="caption" rows="4" placeholder="Enter caption (applies to all uploads)"></textarea>
                    </div>
                </div>
                                    <div class="d-flex justify-content-end align-items-center gap-3 mt-4 mb-3">
                                        <a href="index" class="btn btn-outline-secondary btn-lg px-5">
                                            <i class="bi bi-arrow-left me-2"></i>Back to Home
                                        </a>
                                        <button type="submit" class="btn btn-success btn-lg px-5">
                                            <i class="bi bi-upload me-2"></i>Upload Image
                                        </button>
                                    </div>
                                    </form>

            <?php if (!empty($gallery_images)): ?>
                <h5 class="mt-5"><i class="bi bi-grid-3x3-gap me-2 text-primary"></i>Gallery (<?= count($gallery_images) ?> images)</h5>
                <div class="gallery-grid">
                    <?php foreach ($gallery_images as $image): 
                        $image_path = '../uploads/photos/' . $image['filename'];
                        if (file_exists($image_path)): ?>
                            <div class="gallery-item">
                                <img src="<?= $image_path ?>" alt="<?= htmlspecialchars($image['original_name']) ?>" class="gallery-img">
                                <div class="p-3 bg-light bg-opacity-75 position-absolute bottom-0 w-100">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?= htmlspecialchars($image['original_name']) ?></strong><br>
                                            <small><?= htmlspecialchars($image['caption']) ?></small>
                                        </div>
                                        <a href="?delete=<?= $image['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this image?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endif;
                    endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-images display-1 text-muted mb-4"></i>
                    <h4>No images uploaded yet</h4>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>