<?php
session_start();
include 'connection.php';

// Check if the id and file are set in the GET request
if (isset($_GET['id']) && isset($_GET['file'])) {
    $templateId = $_GET['id'];
    $filename = $_GET['file'];

    // GitHub file deletion logic (using GitHub API)
    $apiUrl = "https://api.github.com/repos/AbiAb1/DocMaP/contents/extra/Admin/Templates/$filename";
    
    // Fetch GitHub Token from Environment Variables
    $githubToken = $_ENV['GITHUB_TOKEN'] ?? null;
    if (!$githubToken) {
        $_SESSION['error'] = 'GitHub token is missing.';
        header("Location: templates.php");
        exit();
    }
    
    $authHeader = [
                "Authorization: token $githubToken",
                "Content-Type: application/json",
                "User-Agent: DocMaP"
    ];

    // Initialize curl for API request
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_HTTPHEADER, $authHeader);
            
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Check if GitHub file deletion was successful (200 OK)
    if ($httpCode === 200) {
        // File deleted from GitHub successfully, now proceed with database deletion
        $query = "DELETE FROM templates WHERE TemplateID = ?";
        
        // Prepare and execute the statement for database deletion
        if ($stmt = mysqli_prepare($conn, $query)) {
            mysqli_stmt_bind_param($stmt, 'i', $templateId);
            if (mysqli_stmt_execute($stmt)) {
                // Template deleted from database
                $_SESSION['message'] = 'Template deleted successfully from both GitHub and database.';
                header("Location: templates.php");
                exit();
            } else {
                // Handle database deletion error
                $_SESSION['error'] = 'Failed to delete the template from the database. Please try again.';
                header("Location: templates.php");
                exit();
            }
        } else {
            // Handle statement preparation error for database
            $_SESSION['error'] = 'Failed to prepare the SQL statement for database deletion.';
            header("Location: templates.php");
            exit();
        }
    } else {
        // Handle GitHub API error
        $_SESSION['error'] = 'Failed to delete the file from GitHub. HTTP code: ' . $httpCode;
        header("Location: templates.php");
        exit();
    }
} else {
    // Redirect if id or file is not set
    $_SESSION['error'] = 'Invalid request.';
    header("Location: templates.php");
    exit();
}
?>
