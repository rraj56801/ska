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
    <title>Photo Gallery | Sri Krishna Academy</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #0f172a;
            color: #e5e7eb;
            margin: 0;
            padding: 0;
        }

        .gallery-wrapper {
            max-width: 100%;
            margin: 0;
            padding: 20px 15px;
        }

        .card-gallery {
            border-radius: 20px;
            border: none;
            box-shadow: 0 15px 40px rgba(15, 23, 42, 0.5);
            overflow: hidden;
            background: #020617;
            color: #e5e7eb;
        }

        .card-gallery-header {
            background: linear-gradient(135deg, #dc2626, #991b1b);
            color: #f9fafb;
            padding: 20px 24px 18px;
        }

        .card-gallery-header h3 {
            margin: 0;
            font-weight: 700;
            font-size: 1.5rem;
        }

        .card-gallery-header p {
            margin: 4px 0 0;
            opacity: 0.9;
            font-size: 0.95rem;
        }

        .gallery-item {
            position: relative;
            border-radius: 18px;
            overflow: hidden;
            background: #000000;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.8);
            cursor: pointer;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            height: 260px;
        }

        .gallery-item:hover {
            transform: translateY(-6px);
            box-shadow: 0 18px 44px rgba(15, 23, 42, 1);
        }

        .gallery-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.35s ease;
        }

        .gallery-item:hover .gallery-img {
            transform: scale(1.05);
        }

        .gallery-overlay {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            padding: 12px 14px;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.9), transparent);
            color: #f9fafb;
        }

        .gallery-overlay p {
            font-size: 0.85rem;
            margin-bottom: 6px;
            max-height: 2.6em;
            overflow: hidden;
            color: #e5e7eb;
        }

        .gallery-meta {
            font-size: 0.75rem;
            opacity: 0.9;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
            color: #d1d5db;
        }

        .badge-soft {
            background: rgba(255, 255, 255, 0.16);
            border-radius: 999px;
            padding: 3px 9px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px 40px;
            color: #9ca3af;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 16px;
            color: #6b7280;
        }

        .modal-content.custom-modal {
            border-radius: 20px;
            border: none;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.65);
        }

        .modal-header.custom-header {
            background: #7f1d1d;
            color: #e5e7eb;
            border-bottom: 1px solid rgba(127, 29, 29, 0.7);
        }

        .modal-body.custom-body {
            background: radial-gradient(circle at top, #020617 0%, #000000 65%);
        }

        .modal-footer.custom-footer {
            background: #111827;
            color: #9ca3af;
            border-top: 1px solid rgba(55, 65, 81, 0.7);
        }

        .modal-body img {
            border-radius: 12px;
        }

        .btn-preview {
            background-color: #020617;
            color: #f9fafb;
            border-radius: 999px;
            border: 1px solid #f9fafb;
            font-size: 0.85rem;
            padding: 6px 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background-color 0.2s ease, color 0.2s ease, transform 0.15s ease;
        }

        .btn-preview:hover {
            background-color: #111827;
            color: #ffffff;
            transform: translateY(-1px);
        }

        .btn-view-all {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 28px;
            background: linear-gradient(135deg, #dc2626, #991b1b);
            color: #ffffff;
            text-decoration: none;
            border-radius: 50px;
            font-size: 0.95rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(220, 38, 38, 0.4);
        }

        .btn-view-all:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(220, 38, 38, 0.6);
            color: #ffffff;
            background: linear-gradient(135deg, #ef4444, #b91c1c);
        }

        .carousel-inner .carousel-item {
            padding: 10px 0;
        }

        .slide-row {
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .slide-row .gallery-item {
            flex: 0 0 calc(33.333% - 10px);
        }

        @media (max-width: 992px) {
            .slide-row .gallery-item {
                flex: 0 0 calc(33.333% - 10px);
            }
        }

        @media (max-width: 768px) {
            .slide-row {
                gap: 10px;
            }

            .slide-row .gallery-item {
                flex: 0 0 calc(50% - 8px);
            }
        }

        @media (max-width: 576px) {
            .slide-row .gallery-item {
                flex: 0 0 100%;
            }
            
            .gallery-item {
                height: 180px;
            }
        }
    </style>
</head>

<body>
    <div class="gallery-wrapper">
        <div class="card card-gallery">
            <div class="card-gallery-header">
                <h3 class="mb-1">Campus Moments</h3>
                <p class="mb-0">
                    Every photo tells a story—click to see it unfold.
                </p>
            </div>

            <div class="card-body p-3">
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

                <?php if (!empty($gallery_images)): ?>
                    <div id="galleryCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="3000">
                        <div class="carousel-inner">
                            <?php
                            $chunks = array_chunk($gallery_images, 3);
                            $chunkIndex = 0;
                            foreach ($chunks as $chunk):
                                $isActive = ($chunkIndex === 0) ? 'active' : '';
                                $chunkIndex++;
                                ?>
                                <div class="carousel-item <?= $isActive ?>">
                                    <div class="slide-row">
                                        <?php foreach ($chunk as $image): ?>
                                            <?php
                                            $image_path = 'uploads/photos/' . $image['filename'];
                                            if (!file_exists(__DIR__ . '/' . $image_path)) {
                                                continue;
                                            }
                                            ?>
                                            <div class="gallery-item" data-bs-toggle="modal"
                                                data-bs-target="#imageModal<?= (int) $image['id'] ?>">
                                                <img src="<?= htmlspecialchars($image_path) ?>" alt="Gallery Image" class="gallery-img">

                                                <div class="gallery-overlay">
                                                    <div class="gallery-meta">
                                                        <span class="badge-soft">
                                                            <button type="button" class="btn btn-preview" data-bs-toggle="modal"
                                                                data-bs-target="#imageModal<?= (int) $image['id'] ?>">
                                                                <i class="bi bi-eye me-1"></i>View
                                                            </button>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Modal -->
                                            <div class="modal fade" id="imageModal<?= (int) $image['id'] ?>" tabindex="-1"
                                                aria-hidden="true">
                                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                                    <div class="modal-content custom-modal">
                                                        <div class="modal-header custom-header">
                                                            <h5 class="modal-title">
                                                                <i class="bi bi-image me-2"></i>Preview
                                                            </h5>
                                                            <button type="button" class="btn-close btn-close-white"
                                                                data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body custom-body text-center p-4">
                                                            <img src="<?= htmlspecialchars($image_path) ?>" alt="Gallery Image"
                                                                class="img-fluid" style="max-height: 70vh; object-fit: contain;">
                                                        </div>
                                                        <div
                                                            class="modal-footer custom-footer d-flex justify-content-between flex-wrap">
                                                            <small class="text-muted">
                                                                <?php if (!empty($image['caption'])): ?>
                                                                    <div class="mb-1">
                                                                        <strong>Caption:</strong>
                                                                        <?= nl2br(htmlspecialchars($image['caption'])) ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </small>
                                                            <button type="button" class="btn btn-secondary btn-sm"
                                                                data-bs-dismiss="modal">
                                                                Close
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if (count($gallery_images) > 3): ?>
                            <button class="carousel-control-prev" type="button" data-bs-target="#galleryCarousel"
                                data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#galleryCarousel"
                                data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- View All Button Inside Gallery -->
                    <?php if (count($gallery_images) > 3): ?>
                        <div style="text-align: center; margin-top: 20px; padding-bottom: 10px;">
                            <a href="view-all-gallery" target="_blank" class="btn-view-all">
                                <i class="bi bi-grid-3x3-gap-fill me-2"></i>View All Gallery Images
                            </a>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-images"></i>
                        <h4>No images found</h4>
                        <p>Upload photos from the admin panel to see them here.</p>
                    </div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
