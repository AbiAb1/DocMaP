<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Include your database connection file here
include 'connection.php';

// Get user_id from session
$user_id = $_SESSION['user_id'];

// Fetch the raw POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(array('status' => 'error', 'message' => 'Invalid JSON input.'));
    exit();
}

// Check if required fields are present in the decoded JSON
if (!isset($data['fname'], $data['mname'], $data['lname'], $data['email'], $data['mobile'], $data['bday'], $data['sex'], $data['address'])) {
    echo json_encode(array('status' => 'error', 'message' => 'Missing required fields.'));
    exit();
}

// Get the user data from the POST request
$fname = mysqli_real_escape_string($conn, $data['fname']);
$mname = mysqli_real_escape_string($conn, $data['mname']);
$lname = mysqli_real_escape_string($conn, $data['lname']);
$email = mysqli_real_escape_string($conn, $data['email']);
$mobile = mysqli_real_escape_string($conn, $data['mobile']);
$bday = mysqli_real_escape_string($conn, $data['bday']);
$sex = mysqli_real_escape_string($conn, $data['sex']);
$address = mysqli_real_escape_string($conn, $data['address']);

// Update user details in the database
$sql = "UPDATE useracc SET fname='$fname', mname='$mname', lname='$lname', email='$email', mobile='$mobile', bday='$bday', sex='$sex', address='$address' WHERE UserID = $user_id";

if (mysqli_query($conn, $sql)) {
    echo json_encode(array('status' => 'success', 'message' => 'User details updated successfully.'));
} else {
    echo json_encode(array('status' => 'error', 'message' => 'Error updating user details: ' . mysqli_error($conn)));
}

// Close database connection
mysqli_close($conn);
?>
