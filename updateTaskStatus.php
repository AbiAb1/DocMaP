<?php
session_start();
include 'connection.php';

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        if (isset($data['taskID'], $data['contentID'], $data['status'])) {
            $taskID = $conn->quote($data['taskID']);
            $contentID = $conn->quote($data['contentID']);
            $status = $conn->quote($data['status']);
            $userID = $_SESSION['user_id'];

            $sqlUpdateDocuments = "UPDATE Documents SET status = ? WHERE TaskID = ? AND ContentID = ?";
            $stmtDocuments = $conn->prepare($sqlUpdateDocuments);
            $stmtDocuments->execute([$status, $taskID, $contentID]);

            $sqlUpdateTaskUser = "UPDATE task_user SET Status = ? WHERE TaskID = ? AND UserID = ?";
            $stmtTaskUser = $conn->prepare($sqlUpdateTaskUser);
            $stmtTaskUser->execute([$status, $taskID, $userID]);

            $response['success'] = true;
            $response['message'] = 'Task status updated successfully!';
        } else {
            $response['message'] = 'Missing taskID, contentID, or status.';
        }
    } else {
        $response['message'] = 'Invalid request method.';
    }
} catch (PDOException $e) {
    $response['message'] = "Database error: " . $e->getMessage();
} catch (Exception $e) {
    $response['message'] = "An error occurred: " . $e->getMessage();
}

echo json_encode($response);
$conn = null; // Always close the connection, even in case of error
?>