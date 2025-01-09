<?php
session_start();
include 'connection.php';

$response = array();  // Initialize an array to hold the response

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userID = $_POST['userID'];
    $gradeID = $_POST['gradeID'];

    // SQL query to insert a new chairperson
    $sql = "INSERT INTO chairperson (UserID, Grade_ID) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userID, $gradeID);

    // Execute the query
    if ($stmt->execute()) {
        // Success - send success response
        $response['status'] = 'success';
        $response['message'] = 'Chairperson assigned successfully!';
    } else {
        // Failure - send error message
        $response['status'] = 'error';
        $response['message'] = 'Error: ' . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    // Return the response as JSON
    echo json_encode($response);

    exit();
}
?>
