<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['student'])) {
    include(__DIR__ . '/../forbidden.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES['photo']) && isset($_POST['student_id'])) {
    $student_id = (int)$_POST['student_id'];
    $reg_no = $_POST['reg_no'] ?? '';
    $upload_dir = __DIR__ . '/../assets/images/students/';

    // Create directory if not exists
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file = $_FILES['photo'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB

        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (in_array($mime_type, $allowed_types) && $file['size'] <= $max_size) {
            // Get old photo to delete
            $stmt = $pdo->prepare("SELECT photo FROM students WHERE id = ?");
            $stmt->execute([$student_id]);
            $old_photo = $stmt->fetchColumn();

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'Photo_' . $student_id . '_' . date('Ymd') . '_' . time() . '.' . $extension;
            $filepath = $upload_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Update database
                $stmt = $pdo->prepare("UPDATE students SET photo = ? WHERE id = ?");
                $stmt->execute([$filename, $student_id]);

                // Delete old photo if exists
                if ($old_photo && $old_photo !== 'default.jpeg' && file_exists($upload_dir . $old_photo)) {
                    unlink($upload_dir . $old_photo);
                }

                $_SESSION['success'] = "Photo updated successfully!";
            } else {
                $_SESSION['error'] = "Failed to upload photo. Check directory permissions.";
            }
        } else {
            $_SESSION['error'] = "Invalid file type or size too large (max 5MB)";
        }
    } else {
        $_SESSION['error'] = "Upload error occurred";
    }
} else {
    $_SESSION['error'] = "Invalid request";
}

// Redirect back with proper URL
$redirect_url = $_POST['redirect_to'] ?? 'student-dashboard.php';
header("Location: " . $redirect_url);
exit();
?>
