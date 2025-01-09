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
            // Check if the file exists and delete it
            $filePath = "https://raw.githubusercontent.com/AbiAb1/DocMaP/extra/Admin/Templates/" . $filename; // Path to the file
            if (file_exists($filePath)) {
                unlink($filePath); // Delete the file
            }
            // Set success message
            $_SESSION['message'] = 'Template deleted successfully.';
            header("Location: templates.php"); // Redirect to the templates page
            exit();
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
