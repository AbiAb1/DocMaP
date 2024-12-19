<?php
session_start();
include 'connection.php'; // Ensure this file includes proper DB connection details

// Decode incoming JSON data
$data = json_decode(file_get_contents('php://input'), true);

try {
    foreach ($data['users'] as $user) {
        $userID = $user['UserID'];
        $firstName = $user['firstName'];
        $middleName = $user['middleName'];
        $lastName = $user['lastName'];
        $rank = $user['rank'];
        $address = $user['address'];
        $mobile = $user['mobile'];
        $email = $user['email'];

        $sql = "UPDATE useracc 
                SET fname = ?, mname = ?, lname = ?, Rank = ?, address = ?, mobile = ?, email = ?
                WHERE UserID = ?";
        
        // Prepare the statement
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            // Log SQL preparation error
            echo json_encode(['success' => false, 'error' => 'Failed to prepare statement: ' . $conn->error]);
            exit;
        }

        // Bind parameters
        $stmt->bind_param('sssssssi', $firstName, $middleName, $lastName, $rank, $address, $mobile, $email, $userID);

        // Execute the query
        if (!$stmt->execute()) {
            // Check if the error is due to a duplicate email
            if ($stmt->errno === 1062) { // Error code 1062 corresponds to a duplicate entry
                echo json_encode(['success' => false, 'error' => 'The email address already exists in the database.']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Execution failed: ' . $stmt->error]);
            }
            exit;
        }

        // Close the statement after each user update
        $stmt->close();
    }

    // If all updates succeed, send a success response
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'An unexpected error occurred: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>
