<?php
// Start the session at the beginning of the script
session_start();

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
    $user_id = $_SESSION['user_id']; // Get user ID from session

    // Prepare SQL statement to get DueDate and DueTime from Tasks table
    $sqlDueDate = "SELECT DueDate, DueTime FROM Tasks WHERE TaskID = ?";
    $stmtDueDate = $conn->prepare($sqlDueDate);
    $stmtDueDate->bind_param("i", $taskID);
    $stmtDueDate->execute();
    $resultDueDate = $stmtDueDate->get_result();

    if ($resultDueDate->num_rows > 0) {
        $row = $resultDueDate->fetch_assoc();
        $dueDate = $row['DueDate'];
        $dueTime = $row['DueTime'];

        // Combine DueDate and DueTime
        $dueDateTime = new DateTime($dueDate . ' ' . $dueTime);
        $currentDateTime = new DateTime();

        // Determine the status based on due date and time
        if ($currentDateTime < $dueDateTime) {
            $newStatus = 'Assigned'; // Update status to assigned if not past due
        } else {
            $newStatus = 'Missing'; // Update status to missing if past due
        }

        // Prepare SQL statements to update task status and user task status
        $sqlUpdateDocuments = "UPDATE documents SET status = ? WHERE TaskID = ? AND ContentID = ?";
        $sqlUpdateTaskUser = "UPDATE task_user SET Status = ? WHERE TaskID = ? AND UserID = ?";

        // Use prepared statements to prevent SQL injection
        $stmtDocuments = $conn->prepare($sqlUpdateDocuments);
        $stmtDocuments->bind_param("sii", $status, $taskID, $contentID);
        
        if ($stmtDocuments->execute()) {
            // Update the task_user status
            $stmtTaskUser = $conn->prepare($sqlUpdateTaskUser);
            $stmtTaskUser->bind_param("sii", $newStatus, $taskID, $user_id);
            
            if ($stmtTaskUser->execute()) {
                // Return success response
                $response = [
                    'success' => true,
                    'message' => 'Task status updated successfully!'
                ];
                echo json_encode($response);
            } else {
                // Return error response for task_user update
                $response = [
                    'success' => false,
                    'message' => 'Error updating task_user status: ' . $stmtTaskUser->error
                ];
                echo json_encode($response);
            }

            // Close the task_user statement
            $stmtTaskUser->close();
        } else {
            // Return error response for Documents update
            $response = [
                'success' => false,
                'message' => 'Error updating document status: ' . $stmtDocuments->error
            ];
            echo json_encode($response);
        }

        // Close the documents statement
        $stmtDocuments->close();
    } else {
        // Return error response if task not found
        $response = [
            'success' => false,
            'message' => 'Task not found'
        ];
        echo json_encode($response);
    }

    // Close the due date statement and connection
    $stmtDueDate->close();
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
