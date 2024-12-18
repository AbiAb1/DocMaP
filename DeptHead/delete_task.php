<?php
// Include the database connection file
include 'connection.php'; // Update with your actual database connection file

// Set the response header to JSON
header('Content-Type: application/json');

// Initialize the response array
$response = array();

try {
    // Check if task_id is provided in the POST request
    $data = json_decode(file_get_contents("php://input"), true); // Decode JSON input

    if (isset($data['task_id']) && !empty($data['task_id'])) {
        $task_id = intval($data['task_id']); // Sanitize input

        // SQL query to delete a task based on TaskID
        $sql = "DELETE FROM tasks WHERE TaskID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $task_id);

        if ($stmt->execute()) {
            $response = array('success' => true, 'message' => 'Task deleted successfully.');
        } else {
            $response = array('success' => false, 'message' => 'Failed to delete task: ' . $stmt->error);
        }

        $stmt->close();
    } else {
        $response = array('success' => false, 'message' => 'Invalid task ID.');
    }
} catch (Exception $e) {
    // Catch any exceptions and send a meaningful response
    $response = array('success' => false, 'message' => 'An error occurred: ' . $e->getMessage());
}

// Send the response as JSON
echo json_encode($response);

?>