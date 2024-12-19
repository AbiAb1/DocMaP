<?php

session_start();

include 'connection.php';

// Fetch page number from query string, default to page 1
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10; // Number of records per page
$offset = ($page - 1) * $records_per_page;

// SQL query to count the total records
$result = $conn->query("SELECT COUNT(*) AS total FROM useracc WHERE role IN ('Department Head', 'Teacher')");
$total_records = $result->fetch_assoc()['total'];

// Fetch users for the current page
$query = "SELECT UserID, fname, mname, lname, 'Rank', address, mobile, email 
          FROM useracc 
          WHERE role IN ('Department Head', 'Teacher') 
          LIMIT $offset, $records_per_page";
$result = $conn->query($query);

// Fetch the users into an array
$users = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Concatenate first, middle, and last names to form the full name
        $row['fullname'] = $row['fname'] . ' ' . $row['mname'] . ' ' . $row['lname'];
        $users[] = $row;
    }
}

// Calculate total pages
$total_pages = ceil($total_records / $records_per_page);

// Close the connection
$conn->close();

// Return data as JSON
header('Content-Type: application/json');
echo json_encode([
    'users' => $users,
    'total_pages' => $total_pages,
    'current_page' => $page
]);




?>
