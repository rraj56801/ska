<?php
session_start();
include '../includes/db.php';
require_once __DIR__ . '/../includes/anti_inspect.php';

if (!isset($_SESSION['admin'])) {
    include(__DIR__ . '/../forbidden.php');
    exit();
}

if (isset($_GET['id']) && isset($_GET['reg'])) {
    $doc_id = (int)$_GET['id'];
    $reg_no = $_GET['reg'];
    
    try {
        // Get document details with verification
        $stmt = $pdo->prepare("SELECT * FROM student_documents WHERE id = ? AND reg_no = ?");
        $stmt->execute([$doc_id, $reg_no]);
        $document = $stmt->fetch();
        
        if ($document) {
            // Delete file from server
            $filepath = __DIR__ . '/../assets/documents/students/' . $document['filename'];
            
            if (file_exists($filepath)) {
                if (unlink($filepath)) {
                    // File deleted successfully, now delete database record
                    $delete_stmt = $pdo->prepare("DELETE FROM student_documents WHERE id = ?");
                    
                    if ($delete_stmt->execute([$doc_id])) {
                        $_SESSION['success'] = ucfirst(strtolower($document['photo_type'])) . " deleted successfully!";
                    } else {
                        $_SESSION['error'] = "Failed to delete database record";
                    }
                } else {
                    $_SESSION['error'] = "Failed to delete file from server";
                }
            } else {
                // File doesn't exist, but delete database record anyway
                $delete_stmt = $pdo->prepare("DELETE FROM student_documents WHERE id = ?");
                $delete_stmt->execute([$doc_id]);
                $_SESSION['success'] = "Document record deleted (file was already missing)";
            }
        } else {
            $_SESSION['error'] = "Document not found or access denied!";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error occurred";
        error_log("Delete document error: " . $e->getMessage());
    }
} else {
    $_SESSION['error'] = "Invalid request - missing parameters";
    $reg_no = isset($_GET['reg']) ? $_GET['reg'] : 'unknown';
}

// Redirect back to upload documents page
if (isset($reg_no) && !empty($reg_no) && $reg_no !== 'unknown') {
    header("Location: upload-student-document.php?reg=" . urlencode($reg_no));
} else {
    header("Location: students.php");
}
exit();
?>
