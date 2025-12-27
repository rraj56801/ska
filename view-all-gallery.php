<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/anti_inspect.php';

// Fetch gallery images
$stmt = $pdo->query("SELECT * FROM gallery_uploads ORDER BY upload_date DESC");
$gallery_images = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Gallery Photos | Sri Krishna Academy</title>
    <link rel="icon" type="image/x-icon" href="assets/images/ska-logo.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 50%, #dc2626 100%);
            color: #1f2937;
            min-height: 100vh;
            padding: 40px 0;
        }

        .gallery-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .page-header {
            background: linear-gradient(135deg, #dc2626, #991b1b);
            color: #ffffff;
            padding: 40px 30px;
            border-radius: 24px;
            margin-bottom: 40px;
            box-shadow: 0 20px 50px rgba(220, 38, 38, 0.3);
            text-align: center;
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .page-header p {
            font-size: 1.1rem;
            opacity: 0.95;
            margin: 0;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 24px;
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            text-decoration: none;
            border-radius: 50px;
            font-size: 0.95rem;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-top: 16px;
        }

        .back-button:hover {
            background: rgba(255, 255, 255, 0.3);
            color: #ffffff;
            transform: translateX(-4px);
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        .gallery-item {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            background: #ffffff;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 280px;
        }

        .gallery-item:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 50px rgba(220, 38, 38, 0.3);
        }

        .gallery-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .gallery-item:hover .gallery-img {
            transform: scale(1.08);
        }

        .gallery-overlay {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            padding: 16px;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.85), transparent);
            color: #ffffff;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .gallery-item:hover .gallery-overlay {
            opacity: 1;
        }

        .gallery-caption {
            font-size: 0.9rem;
            margin-bottom: 8px;
            max-height: 3em;
            overflow: hidden;
        }

        .gallery-meta {
            font-size: 0.8rem;
            opacity: 0.9;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-preview {
            background: rgba(220, 38, 38, 0.9);
            color: #ffffff;
            border: none;
            border-radius: 50px;
            padding: 8px 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
        }

        .btn-preview:hover {
            background: #dc2626;
            transform: scale(1.05);
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #dc2626;
        }

        .empty-state h3 {
            color: #1f2937;
            margin-bottom: 12px;
        }

        .modal-content.custom-modal {
            border-radius: 24px;
            border: none;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.5);
        }

        .modal-header.custom-header {
            background: linear-gradient(135deg, #dc2626, #991b1b);
            color: #ffffff;
            border-bottom: none;
            padding: 20px 24px;
        }

        .modal-body.custom-body {
            background: #1f2937;
            padding: 30px;
        }

        .modal-footer.custom-footer {
            background: #111827;
            color: #9ca3af;
            border-top: 1px solid rgba(75, 85, 99, 0.5);
            padding: 16px 24px;
        }

        .modal-body img {
            border-radius: 16px;
        }

        .image-count {
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 20px;
            border-radius: 50px;
            display: inline-block;
            margin-top: 12px;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .gallery-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 16px;
            }

            .gallery-item {
                height: 240px;
            }

            .page-header h1 {
                font-size: 2rem;
            }
        }

        @media (max-width: 576px) {
            .gallery-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .gallery-item {
                height: 300px;
            }
        }
    </style>
</head>

<body>
    <div class="gallery-container">
      <div class="page-header">
    <div style="display: flex; align-items: center; justify-content: center; gap: 20px; flex-wrap: wrap;">
        <img src="assets/images/ska-logo.png" alt="Sri Krishna Academy Logo" 
             style="width: 80px; height: 80px; border-radius: 16px; object-fit: cover; background: #ffffff; padding: 8px; box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);">
        <div style="text-align: center;">
            <h1 style="margin-bottom: 8px;">Complete Photo Gallery</h1>
            <p>Browse through all our captured moments and memories</p>
        </div>
    </div>
    <div class="image-count">
        <i class="bi bi-camera-fill me-2"></i><?= count($gallery_images) ?> Photos
    </div>
</div>


        <?php if (!empty($gallery_images)): ?>
            <div class="gallery-grid">
                <?php foreach ($gallery_images as $image): ?>
                    <?php
                    $image_path = 'uploads/photos/' . $image['filename'];
                    if (!file_exists(__DIR__ . '/' . $image_path)) {
                        continue;
                    }
                    ?>
                    <div class="gallery-item" data-bs-toggle="modal" data-bs-target="#imageModal<?= (int) $image['id'] ?>">
                        <img src="<?= htmlspecialchars($image_path) ?>" alt="Gallery Image" class="gallery-img">

                        <div class="gallery-overlay">
                            <?php if (!empty($image['caption'])): ?>
                                <div class="gallery-caption">
                                    <?= htmlspecialchars($image['caption']) ?>
                                </div>
                            <?php endif; ?>
                            <div class="gallery-meta">
                                <button type="button" class="btn-preview" data-bs-toggle="modal"
                                    data-bs-target="#imageModal<?= (int) $image['id'] ?>">
                                    <i class="bi bi-eye-fill"></i>View Full Size
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Modal -->
                    <div class="modal fade" id="imageModal<?= (int) $image['id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-centered">
                            <div class="modal-content custom-modal">
                                <div class="modal-header custom-header">
                                    <h5 class="modal-title">
                                        <i class="bi bi-image me-2"></i>Image Preview
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body custom-body text-center">
                                    <img src="<?= htmlspecialchars($image_path) ?>" alt="Gallery Image" class="img-fluid"
                                        style="max-height: 75vh; object-fit: contain;">
                                </div>
                                <div class="modal-footer custom-footer d-flex justify-content-between flex-wrap">
                                    <small class="text-light">
                                        <?php if (!empty($image['caption'])): ?>
                                            <div class="mb-1">
                                                <strong>Caption:</strong>
                                                <?= nl2br(htmlspecialchars($image['caption'])) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($image['upload_date'])): ?>
                                            <div>
                                                <i class="bi bi-calendar3 me-1"></i>
                                                <?= date('F d, Y', strtotime($image['upload_date'])) ?>
                                            </div>
                                        <?php endif; ?>
                                    </small>
                                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">
                                        Close
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-images"></i>
                <h3>No Images Available</h3>
                <p>There are currently no photos in the gallery. Please check back later!</p>
                <a href="index.php" class="btn btn-primary mt-3">
                    <i class="bi bi-house-door me-2"></i>Go to Homepage
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
