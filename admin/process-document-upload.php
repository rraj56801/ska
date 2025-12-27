<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['admin'])) {
    include(__DIR__ . '/../forbidden.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES['document'])) {
    $reg_no = $_POST['reg_no'];
    $student_id = (int)$_POST['student_id'];
    $photo_type = $_POST['photo_type'];
    $file = $_FILES['document'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 
                         'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $max_size = 5 * 1024 * 1024; // 5MB

        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (in_array($mime_type, $allowed_types) && $file['size'] <= $max_size) {
            
                // For other documents, insert/update in student_documents
                $upload_dir = __DIR__ . '/../assets/documents/students/';
                
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Check if document type already exists
                $check_stmt = $pdo->prepare("SELECT id, filename FROM student_documents WHERE reg_no = ? AND photo_type = ?");
                $check_stmt->execute([$reg_no, $photo_type]);
                $existing = $check_stmt->fetch();
                
                // Generate filename
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = strtolower($photo_type) . $student_id . '_' . date('Ymd') . '_' . time() . '.' . $extension;
                $filepath = $upload_dir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    
                    if ($existing) {
                        // Update existing record
                        $stmt = $pdo->prepare("UPDATE student_documents SET filename = ?, original_name = ?, file_size = ?, upload_date = NOW() WHERE id = ?");
                        $stmt->execute([$filename, $file['name'], $file['size'], $existing['id']]);
                        
                        // Delete old file
                        if (file_exists($upload_dir . $existing['filename'])) {
                            unlink($upload_dir . $existing['filename']);
                        }
                        
                        $_SESSION['success'] = ucfirst($photo_type) . " updated successfully!";
                    } else {
                        // Insert new record
                        $stmt = $pdo->prepare("INSERT INTO student_documents (reg_no, photo_type, filename, original_name, file_size) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$reg_no, $photo_type, $filename, $file['name'], $file['size']]);
                        
                        $_SESSION['success'] = ucfirst($photo_type) . " uploaded successfully!";
                    }
                } else {
                    $_SESSION['error'] = "Failed to upload document";
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

// Redirect back
header("Location: upload-student-document.php?reg=" . urlencode($reg_no));
exit();
?>
