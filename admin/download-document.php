<?php
session_start();
include '../includes/db.php';
require_once __DIR__ . '/../includes/anti_inspect.php';

// Ensure admin is logged in
if (!isset($_SESSION['admin'])) {
    include(__DIR__ . '/../forbidden.php');
    exit();
}

// Base directory where files are stored
$basePath = realpath(__DIR__ . '/../assets/documents/students');
if ($basePath === false) {
    die('Storage path not found');
}

// Check if we are downloading a single file or all files
if (isset($_GET['file']) && !empty($_GET['file'])) {
    // Single file download (original logic)
    singleFileDownload($_GET['file'], $basePath, $pdo);
} elseif (isset($_GET['all']) && $_GET['all'] == '1') {
    // Download all documents as ZIP
    downloadAllAsZip($basePath, $pdo);
} else {
    die('Invalid request');
}

// Function: Download a single file (original logic)
function singleFileDownload($requestedFile, $basePath, $pdo) {
    $requested = basename($requestedFile);

    // Fetch document + student to build friendly filename
    $doc_stmt = $pdo->prepare("
        SELECT sd.*, s.student_name, s.reg_no
        FROM student_documents sd
        JOIN students s ON s.reg_no = sd.reg_no
        WHERE sd.filename = ?
        LIMIT 1
    ");
    $doc_stmt->execute([$requested]);
    $doc = $doc_stmt->fetch();

    if (!$doc) {
        die('File not found');
    }

    $filePath = $basePath . DIRECTORY_SEPARATOR . $requested;
    $realFilePath = realpath($filePath);

    // Security: ensure resolved path is inside base directory
    if ($realFilePath === false || strpos($realFilePath, $basePath) !== 0) {
        die('File not found');
    }

    if (!is_file($realFilePath)) {
        die('File not found');
    }

    // Build friendly download name: Student_Name_Reg_No_DOCTYPE.ext
    $studentNameSlug = preg_replace('/\s+/', '_', trim($doc['student_name']));
    $regNoSlug       = preg_replace('/\s+/', '_', trim($doc['reg_no']));
    $docTypeSlug     = preg_replace('/\s+/', '_', trim($doc['photo_type']));

    $ext = pathinfo($requested, PATHINFO_EXTENSION);
    $ext = $ext ? ('.' . $ext) : '';

    $downloadName = $studentNameSlug . '_' . $regNoSlug . '_' . $docTypeSlug . $ext;

    // Clean any previous output
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Detect MIME type (fallback to octet-stream)
    $mime = function_exists('mime_content_type')
        ? mime_content_type($realFilePath)
        : 'application/octet-stream';

    // Send headers for download
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($realFilePath));

    // Stream file
    $fp = fopen($realFilePath, 'rb');
    if ($fp !== false) {
        fpassthru($fp);
        fclose($fp);
    }

    exit;
}

// Function: Download all documents as a ZIP
function downloadAllAsZip($basePath, $pdo) {
    // Fetch all documents with student info
    $stmt = $pdo->prepare("
        SELECT sd.filename, sd.photo_type, s.student_name, s.reg_no
        FROM student_documents sd
        JOIN students s ON s.reg_no = sd.reg_no
    ");
    $stmt->execute();
    $documents = $stmt->fetchAll();

    if (empty($documents)) {
        die('No documents found');
    }

    // Use the first student's name and regno for the ZIP filename
    $firstDoc = $documents[0];
    $studentNameSlug = preg_replace('/\s+/', '_', trim($firstDoc['student_name']));
    $regNoSlug       = preg_replace('/[^a-zA-Z0-9]/', '_', trim($firstDoc['reg_no'])); // Replace / and other special chars with _

    // ZIP filename: Student_Name_RegNo.zip
    $zipFileName = $studentNameSlug . '_' . $regNoSlug . '.zip';

    // Create ZIP in memory
    $zip = new ZipArchive();
    $tempZipPath = sys_get_temp_dir() . '/' . uniqid('docs_', true) . '.zip';

    if ($zip->open($tempZipPath, ZipArchive::CREATE) !== true) {
        die('Cannot create ZIP archive');
    }

    foreach ($documents as $doc) {
        $filePath = $basePath . DIRECTORY_SEPARATOR . $doc['filename'];
        $realFilePath = realpath($filePath);

        // Skip if file doesn't exist or is outside base path
        if ($realFilePath === false || strpos($realFilePath, $basePath) !== 0 || !is_file($realFilePath)) {
            continue;
        }

        // Build filename inside ZIP: Photo_Type.ext (no folder)
        $docTypeSlug = preg_replace('/\s+/', '_', trim($doc['photo_type']));
        $ext = pathinfo($doc['filename'], PATHINFO_EXTENSION);
        $ext = $ext ? ('.' . $ext) : '';
        $fileInZip = $docTypeSlug . $ext;

        // Add file directly to ZIP root
        $zip->addFile($realFilePath, $fileInZip);
    }

    $zip->close();

    // Stream ZIP as download
    if (!file_exists($tempZipPath)) {
        die('ZIP file not created');
    }

    // Clean any previous output
    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Description: File Transfer');
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
    header('Content-Length: ' . filesize($tempZipPath));
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');

    // Stream the ZIP file
    readfile($tempZipPath);

    // Delete temp file after sending
    unlink($tempZipPath);

    exit;
}

