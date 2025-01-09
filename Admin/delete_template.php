<?php
session_start();
include 'connection.php';

header('Content-Type: application/json'); // Set response type to JSON

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Parse JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $templateId = $input['templateId'] ?? null;
    $filename = $input['filename'] ?? null;

    if ($templateId && $filename) {
        // GitHub file deletion logic (using GitHub API)
        $apiUrl = "https://api.github.com/repos/AbiAb1/DocMaP/contents/Admin/Templates/$filename?ref=extra";


        // Fetch GitHub Token from Environment Variables
        $githubToken = $_ENV['GITHUB_TOKEN'] ?? null;

        // Log the API URL for debugging
        error_log("GitHub API URL: $apiUrl");
        
        if (!$githubToken) {
            echo json_encode(['success' => false, 'message' => 'GitHub token is missing.']);
            exit();
        }

        $authHeader = [
            "Authorization: token $githubToken",
            "Content-Type: application/json",
            "User-Agent: DocMaP"
        ];

        // Initialize cURL for API request
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $authHeader);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Check if GitHub file deletion was successful
        if ($httpCode === 200 || $httpCode === 204) {
            // File deleted from GitHub successfully, now delete from database
            $query = "DELETE FROM templates WHERE TemplateID = ?";
            
            if ($stmt = mysqli_prepare($conn, $query)) {
                mysqli_stmt_bind_param($stmt, 'i', $templateId);
                if (mysqli_stmt_execute($stmt)) {
                    echo json_encode(['success' => true, 'message' => 'Template deleted successfully from both GitHub and database.']);
                    exit();
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to delete the template from the database.']);
                    exit();
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to prepare the SQL statement for database deletion.']);
                exit();
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete the file from GitHub. HTTP code: ' . $httpCode]);
            exit();
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request data.']);
        exit();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}
?>
