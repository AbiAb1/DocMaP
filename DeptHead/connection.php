<?php
$servername = "doc-map2024_sean";
$username = "mysql";
$password = "";
$dbname = "docmap2";
// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
