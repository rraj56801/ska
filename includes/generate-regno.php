<?php

// If this file is being accessed directly (not included)
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    include(__DIR__ . '/../forbidden.php');
    exit;
}

require_once __DIR__ . '/../includes/anti_inspect.php';

// =====================================================
// AUTO REGISTRATION NUMBER GENERATOR
// Format: CEC20250001, CEC20250002, etc.
// Change "CEC" to your center code if needed
// =====================================================

function generateRegistrationNumber($pdo)
{
    $prefix = "CEC";                    // Change this to your center code (e.g., ABC, SKC, etc.)
    $current_year = date("Y");          // 2025
    $short_year = substr($current_year, -2);  // 25

    // Final prefix = CEC25
    $final_prefix = $prefix . $short_year;

    // Lock table to prevent duplicate in high traffic
    $pdo->exec("LOCK TABLES students WRITE");

    // Find the last registration number with this prefix
    $stmt = $pdo->prepare("SELECT reg_no FROM students 
                           WHERE reg_no LIKE ? 
                           ORDER BY id DESC LIMIT 1");
    $like = $final_prefix . '%';
    $stmt->execute([$like]);
    $last = $stmt->fetchColumn();

    if ($last) {
        // Example last = CEC250089 → extract 0089
        $last_number = substr($last, strlen($final_prefix)); // 0089
        $new_number = str_pad($last_number + 1, 4, "0", STR_PAD_LEFT); // 0090
    } else {
        // No record found → start from 0001
        $new_number = "0001";
    }

    $new_reg_no = $final_prefix . $new_number;  // CEC250090

    // Unlock table
    $pdo->exec("UNLOCK TABLES");

    return $new_reg_no;
}
?>