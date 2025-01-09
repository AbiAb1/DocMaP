<?php
session_start();
include 'connection.php';

// Check if the id and file are set in the GET request
if (isset($_GET['id']) && isset($_GET['file'])) {
    $templateId = $_GET['id'];
    $filename = $_GET['file'];

    // SQL query to delete the record from the database
    $query = "DELETE FROM templates WHERE TemplateID = ?";
    
    // Prepare and execute the statement
    if ($stmt = mysqli_prepare($conn, $query)) {
        mysqli_stmt_bind_param($stmt, 'i', $templateId);
        if (mysqli_stmt_execute($stmt)) {
            // GitHub file deletion logic (using GitHub API)
            $apiUrl = "https://api.github.com/repos/AbiAb1/DocMaP/contents/extra/Admin/Templates/$filename";
            
            // Fetch GitHub Token from Environment Variables
            $githubToken = $_ENV['GITHUB_TOKEN'] ?? null;
            if (!$githubToken) {
                $_SESSION['error'] = 'GitHub token is missing.';
                header("Location: templates.php");
                exit();
            }
            
            $authHeader = "Authorization: token $githubToken";
            
            // Initialize curl for API request
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'User-Agent: DocMaP',
                $authHeader,
            ));
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get HTTP response code
            curl_close($ch);
            
            // Check if GitHub file deletion was successful (200 OK)
            if ($httpCode === 200) {
                // File deleted successfully
                $_SESSION['message'] = 'Template deleted successfully.';
                header("Location: templates.php");
                exit();
            } else {
                // Handle GitHub API error
                $_SESSION['error'] = 'Failed to delete the file from GitHub. HTTP code: ' . $httpCode;
                header("Location: templates.php");
                exit();
            }
        } else {
            // Handle query execution error
            $_SESSION['error'] = 'Failed to delete the template. Please try again.';
            header("Location: templates.php");
            exit();
        }
    } else {
        // Handle statement preparation error
        $_SESSION['error'] = 'Failed to prepare the SQL statement.';
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
