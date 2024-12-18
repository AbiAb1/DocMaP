<?php
$servername = "doc-map2024_sean";
$username = "mysql";
$password = "qwerty";
$dbname = "docmap1";

try {
    // PDO connection string
    $dsn = "mysql:host=$servername;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Set error mode to exception
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Fetch as associative arrays
        PDO::ATTR_EMULATE_PREPARES => false, // Disable emulated prepared statements (for security)
    ];

    // Create PDO connection
    $conn = new PDO($dsn, $username, $password, $options);
    // Connection successful
} catch (PDOException $e) {
    // Handle connection errors
    die("Connection failed: " . $e->getMessage());
}
?>