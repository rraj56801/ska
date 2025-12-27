<?php
// If this file is being accessed directly (not included)
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    include(__DIR__ . '/../forbidden.php');
    exit;
}


// =============================================
// DATABASE CONNECTION - Simple & Secure
// For Student Management System (Mahaveer Clone)
// =============================================
require_once __DIR__ . '/../includes/anti_inspect.php';

$host = 'localhost';          // Usually localhost
$dbname = 'ska_db';           // Your database name
$username = 'root';           // Default in localhost (XAMPP/WAMP)
$password = '';               // Default empty in localhost

// ---- FOR LIVE SERVER (Hosting) ---- UNCOMMENT BELOW LINES WHEN GOING LIVE ----
// $username = 'your_cpanel_username';
// $password = 'your_cpanel_password';

try {
    // PDO Connection (Best & Secure)
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);

    // Set PDO to throw exceptions on error (very helpful in development)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch data as associative array by default
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Show clean message in production, detailed in development
    die("Connection failed! Please check database details.<br>Error: " . $e->getMessage());
}

// Optional: Also create old-style mysqli connection (many developers still use it)
$mysqli = new mysqli($host, $username, $password, $dbname);

if ($mysqli->connect_error) {
    die("MySQLi Connection failed: " . $mysqli->connect_error);
}

// Set character set
$mysqli->set_charset("utf8mb4");

// =============================================
// HOW TO USE IN OTHER PAGES
// =============================================
// Just add this line at top of any page:
// include 'includes/db.php';
// Then use $pdo or $mysqli as needed
?>