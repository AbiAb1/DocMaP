<?php
// Include your database connection script
include 'connection.php';

// Check if the request is a POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Decode the JSON data sent from the front-end
    $data = json_decode(file_get_contents("php://input"));

    // Extract taskID, contentID, and status from the decoded data
    $taskID = mysqli_real_escape_string($conn, $data->taskID);
    $contentID = mysqli_real_escape_string($conn, $data->contentID);
    $status = mysqli_real_escape_string($conn, $data->status);

    // Prepare SQL statement to update task status
    $sql = "UPDATE Documents SET status = ? WHERE TaskID = ? AND ContentID = ?";

    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $status, $taskID, $contentID);

    // Execute the statement
    if ($stmt->execute()) {
        // Return success response
        $response = [
            'success' => true,
            'message' => 'Task status updated successfully!'
        ];
        echo json_encode($response);
    } else {
        // Return error response
        $response = [
            'success' => false,
            'message' => 'Error updating task status'
        ];
        echo json_encode($response);
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
} else {
    // Return error if not a POST request
    $response = [
        'success' => false,
        'message' => 'Invalid request method'
    ];
    echo json_encode($response);
}
?>
