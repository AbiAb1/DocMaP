<?php
// Use constants for better security and maintainability
define('DB_SERVER', 'doc-map2024_sean');
define('DB_USERNAME', 'mysql');
define('DB_PASSWORD', 'qwerty');
define('DB_NAME', 'docmap1');

// Error handling for database connection
try {
    // Improved connection using PDO (Preferred for security)
    $conn = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Set error mode to exception
} catch (PDOException $e) {
    // Log the error for debugging (don't display directly to the user in production)
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection error. Please try again later."); // User-friendly message
}
?>