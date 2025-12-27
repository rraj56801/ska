<?php
// If this file is being accessed directly (not included)
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    include(__DIR__ . '/../forbidden.php');
    exit;
}


include 'db.php';
require_once __DIR__ . '/../includes/anti_inspect.php';

// 1. Generate Registration Number
function generateRegNo(PDO $pdo)
{
    $prefix = date('ym'); // e.g. 2512

    // Get the latest auto-increment id
    $stmt = $pdo->query("SELECT MAX(id) AS max_id FROM students");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $lastId = (int) ($row['max_id'] ?? 0);

    // Next sequence number based on id
    $nextSeq = $lastId + 1;

    // Pad sequence to 4 digits (change 4 if needed)
    $seq = str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

    // Final format: YYYYMM<SEQ>
    return $prefix . $seq;
}

// 3. Get Center Name
function getCenterName($pdo)
{
    $stmt = $pdo->query("SELECT center_name FROM settings WHERE id=1");
    return $stmt->fetchColumn();
}

function getAllStudyCenters(PDO $pdo): array
{
    $stmt = $pdo->prepare("
        SELECT id, center_code, center_name, district, state, pincode 
        FROM study_centers 
        WHERE is_active = '1' 
        ORDER BY state, district, center_name
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get all active courses for dropdowns/admin panels
 */
function getAllCourses(PDO $pdo): array
{
    $stmt = $pdo->prepare("
        SELECT id, course_code, course_name, duration, fees, is_active 
        FROM courses 
        WHERE is_active = 1 
        ORDER BY course_name
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>