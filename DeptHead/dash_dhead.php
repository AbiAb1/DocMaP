<?php
session_start();
include 'connection.php';

// User ID and department ID should already be in the session from login
$userID = $_SESSION['user_id'];
$deptID = $_SESSION['dept_ID'];

echo "<h1>Dashboard</h1>";
echo "<p>User ID: " . htmlspecialchars($userID) . "</p>";
echo "<p>Department ID: " . htmlspecialchars($deptID) . "</p>";

$conn = null; // Close the connection
?>