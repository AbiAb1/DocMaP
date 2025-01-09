<?php
session_start();
include 'connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $templateId = $input['templateId'] ?? null;
    $filename = $input['filename'] ?? null;

    // Input Validation and Sanitization
    if (!is_numeric($templateId) || !preg_match('/^[a-zA-Z0-9._-]+$/', $filename)) {
        echo json_encode(['success' => false, 'message' => 'Invalid input data.']);
        exit();
    }


    $apiUrl = "https://api.github.com/repos/AbiAb1/DocMaP/contents/Admin/Templates/$filename?ref=extra";
    $githubToken = $_ENV['GITHUB_TOKEN'] ?? null;

    error_log("GitHub API URL: $apiUrl"); // Log for debugging

    if (!$githubToken) {
        echo json_encode(['success' => false, 'message' => 'GitHub token is missing.']);
        exit();
    }

    $authHeader = [
        "Authorization: token $githubToken",
        "Content-Type: application/json",
        "User-Agent: DocMaP"
    ];

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $authHeader);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);


    if ($curlError) {
        error_log("cURL Error: " . $curlError);
        echo json_encode(['success' => false, 'message' => 'cURL request failed: ' . $curlError]);
        exit();
    }

    if ($httpCode === 200) {
        // ... (rest of the code remains largely the same, but with added error handling in the database section)
        $fileData = json_decode($response, true);
        $sha = $fileData['sha'];

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $authHeader);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        $deletePayload = json_encode(['message' => "Deleting $filename", 'sha' => $sha]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $deletePayload);

        $deleteResponse = curl_exec($ch);
        $deleteCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log("cURL Error (Delete): " . $curlError);
            echo json_encode(['success' => false, 'message' => 'cURL delete request failed: ' . $curlError]);
            exit();
        }

        if ($deleteCode === 200 || $deleteCode === 204) {
            // ... (database deletion with error handling)
            $query = "DELETE FROM templates WHERE TemplateID = ?";
            if ($stmt = mysqli_prepare($conn, $query)) {
                mysqli_stmt_bind_param($stmt, 'i', $templateId);
                if (mysqli_stmt_execute($stmt)) {
                    echo json_encode(['success' => true, 'message' => 'Template deleted successfully from both GitHub and database.']);
                    exit();
                } else {
                    $dbError = mysqli_error($conn);
                    error_log("Database Error: " . $dbError);
                    echo json_encode(['success' => false, 'message' => 'Failed to delete the template from the database: ' . $dbError]);
                    exit();
                }
            } else {
                $dbError = mysqli_error($conn);
                error_log("Database Error: " . $dbError);
                echo json_encode(['success' => false, 'message' => 'Failed to prepare the SQL statement for database deletion: ' . $dbError]);
                exit();
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete the file from GitHub. HTTP code: ' . $deleteCode]);
            exit();
        }
    } elseif ($httpCode === 404) {
        echo json_encode(['success' => false, 'message' => 'File not found on GitHub. Verify the file path.']);
        exit();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch file data from GitHub. HTTP code: ' . $httpCode]);
        exit();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}
?>
