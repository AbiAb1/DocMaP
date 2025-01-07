<?php
session_start();
include 'connection.php';

// Fetch the user ID from the session
$user_id = $_SESSION['user_id'];

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Debug: Print all POST data to ensure it's being received correctly
    error_log("POST data: " . print_r($_POST, true)); // Log POST data for debugging

    // Check if all required fields are set
    if (isset($_POST['content_id'], $_POST['quarter_id'], $_POST['total_items'], $_POST['total_students'], $_POST['total_tested'], $_POST['total_scores'], $_POST['mps'])) {
        
        // Get data from the form
        $contentID = $_POST['content_id'];
        $quarterID = $_POST['quarter_id'];
        $totalItems = $_POST['total_items'];
        $totalNumOfStudents = $_POST['total_students'];
        $totalNumTested = $_POST['total_tested'];
        $totalScores = $_POST['total_scores'];
        $mps = $_POST['mps'];

        // Debug: Ensure all variables are being correctly assigned
        error_log("Form Data: contentID=$contentID, quarterID=$quarterID, totalItems=$totalItems, totalNumOfStudents=$totalNumOfStudents, totalNumTested=$totalNumTested, totalScores=$totalScores, mps=$mps");

        // Calculate MPSBelow75 based on MPS value
        $mpsBelow75 = $mps < 75 ? 'Yes' : 'No'; // Update to use ENUM values

        // Prepare the SQL statement
        $stmt = $conn->prepare("INSERT INTO mps (UserID, ContentID, Quarter_ID, TotalNumOfItems, TotalNumOfStudents, TotalNumTested, TotalScores, MPS, MPSBelow75) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiiidiss", $user_id, $contentID, $quarterID, $totalItems, $totalNumOfStudents, $totalNumTested, $totalScores, $mps, $mpsBelow75);

        // Execute the statement
        if ($stmt->execute()) {
            // Successful insertion
            echo json_encode(['status' => 'success']);
        } else {
            // Database error
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $stmt->error]);
        }

        // Close the statement
        $stmt->close();
    } else {
        // Missing required fields
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
    }
} else {
    // Invalid request method
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}

// Close the connection
$conn->close();

?>
