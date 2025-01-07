<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include your database connection file
include 'connection.php';

// Check if the mps_id is provided in the GET request
if (isset($_GET['mps_id'])) {
    // Get the mpsID from the request
    $mpsID = $_GET['mps_id'];

    // Prepare the SQL statement
    $sql = "SELECT m.mpsID, m.UserID, m.ContentID, q.School_Year_ID, q.Quarter_Name, 
                   CONCAT(fc.Title, ' - ', fc.Captions) AS GradeSection, 
                   m.TotalNumOfStudents, m.TotalNumTested, m.HighestScore, m.TotalNumOfItems,
                    m.MPS, sy.Year_Range AS SY
            FROM mps m
            INNER JOIN quarter q ON m.Quarter_ID = q.Quarter_ID
            INNER JOIN feedcontent fc ON m.ContentID = fc.ContentID
            INNER JOIN schoolyear sy ON q.School_Year_ID = sy.School_Year_ID
            WHERE m.mpsID = ?";

    // Check if the connection is successful
    if ($conn->connect_error) {
        // If there is a connection error, display it
        die("Connection failed: " . $conn->connect_error);
    }

    // Prepare and execute the SQL query using a prepared statement
    if ($stmt = $conn->prepare($sql)) {
        // Bind the parameter to the SQL query (mpsID should be an integer)
        $stmt->bind_param("i", $mpsID);

        // Execute the statement
        $stmt->execute();

        // Get the result of the query
        $result = $stmt->get_result();

        // Check if the result contains any rows
        if ($result->num_rows > 0) {
            // Fetch the row as an associative array
            $row = $result->fetch_assoc();

            // Return the result as a JSON response
            echo json_encode($row);
        } else {
            // If no data is found, return an empty JSON array
            echo json_encode([]);
        }

        // Close the statement
        $stmt->close();
    } else {
        // If there was an issue preparing the statement, output an error message
        echo json_encode(["error" => "Failed to prepare SQL statement."]);
    }
} else {
    // If mps_id is not set in the GET request, return an error message
    echo json_encode(["error" => "No mps_id received."]);
}

// Close the database connection
$conn->close();
?>

