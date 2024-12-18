<?php
session_start();
include 'connection.php';

header('Content-Type: application/json'); // Important: Set the correct header

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['taskID'], $data['contentID'], $data['status'])) {
        $taskID = $data['taskID'];
        $contentID = $data['contentID'];
        $status = $data['status'];
        $userID = $_SESSION['user_id'];

        try {
            $stmtDocuments = $conn->prepare("UPDATE Documents SET status = ? WHERE TaskID = ? AND ContentID = ?");
            $stmtDocuments->bind_param("iii", $status, $taskID, $contentID);
            $stmtDocuments->execute();

            $stmtTaskUser = $conn->prepare("UPDATE task_user SET Status = ? WHERE TaskID = ? AND UserID = ?");
            $stmtTaskUser->bind_param("sii", $status, $taskID, $userID);
            $stmtTaskUser->execute();

            $response['success'] = true;
            $response['message'] = 'Task status updated successfully!';
        } catch (mysqli_sql_exception $e) {
            $response['success'] = false;
            $response['message'] = "Database error: " . $e->getMessage();
        } catch (Exception $e) {
            $response['success'] = false;
            $response['message'] = "An error occurred: " . $e->getMessage();
        }
    } else {
        $response['success'] = false;
        $response['message'] = 'Missing taskID, contentID, or status.';
    }
} else {
    $response['success'] = false;
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response); // Ensure this is the ONLY output
$conn->close();
?>