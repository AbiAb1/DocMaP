<?php
session_start();
include 'connection.php';

header('Content-Type: application/json'); // Set response type to JSON

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $templateId = $input['templateId'] ?? null;
    $filename = $input['filename'] ?? null;

    if ($templateId && $filename) {
        $apiUrl = "https://api.github.com/repos/AbiAb1/DocMaP/contents/Admin/Templates/$filename?ref=extra";
        $githubToken = $_ENV['GITHUB_TOKEN'] ?? null;

        // Log the API URL and inputs for debugging
        error_log("GitHub API URL: $apiUrl");
        error_log("Filename: $filename");
        error_log("Template ID: $templateId");

        if (!$githubToken) {
            error_log("GitHub token is missing.");
            echo json_encode(['success' => false, 'message' => 'GitHub token is missing.']);
            exit();
        }

        $authHeader = [
            "Authorization: token $githubToken",
            "Content-Type: application/json",
            "User-Agent: DocMaP"
        ];

        // Step 1: Validate File Existence on GitHub
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $authHeader);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Log response and HTTP code for debugging
        error_log("HTTP Code on Fetch: $httpCode");
        error_log("GitHub API Response (Fetch): $response");

        if (curl_errno($ch)) {
            error_log("cURL error (Fetch): " . curl_error($ch));
        }

        if ($httpCode === 200) {
            $fileData = json_decode($response, true);
            $sha = $fileData['sha'];

            // Log fetched SHA for debugging
            error_log("Fetched SHA: $sha");

            // Step 2: Delete the file using its `sha`
            $deletePayload = json_encode(['message' => "Deleting $filename", 'sha' => $sha]);

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $deletePayload);

            $deleteResponse = curl_exec($ch);
            $deleteCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            // Log response and HTTP code for deletion
            error_log("HTTP Code on Delete: $deleteCode");
            error_log("GitHub API Response (Delete): $deleteResponse");

            if (curl_errno($ch)) {
                error_log("cURL error (Delete): " . curl_error($ch));
            }

            curl_close($ch);

            if ($deleteCode === 200 || $deleteCode === 204) {
                // File deleted from GitHub, delete from database
                $query = "DELETE FROM templates WHERE TemplateID = ?";
                if ($stmt = mysqli_prepare($conn, $query)) {
                    mysqli_stmt_bind_param($stmt, 'i', $templateId);
                    if (mysqli_stmt_execute($stmt)) {
                        echo json_encode(['success' => true, 'message' => 'Template deleted successfully from both GitHub and database.']);
                        exit();
                    } else {
                        error_log("Failed to execute database query.");
                        echo json_encode(['success' => false, 'message' => 'Failed to delete the template from the database.']);
                        exit();
                    }
                } else {
                    error_log("Failed to prepare database query.");
                    echo json_encode(['success' => false, 'message' => 'Failed to prepare the SQL statement for database deletion.']);
                    exit();
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete the file from GitHub. HTTP code: ' . $deleteCode]);
                exit();
            }
        } elseif ($httpCode === 404) {
            error_log("File not found on GitHub.");
            echo json_encode(['success' => false, 'message' => 'File not found on GitHub. Verify the file path.']);
            curl_close($ch);
            exit();
        } else {
            error_log("Failed to fetch file data from GitHub. HTTP code: $httpCode");
            curl_close($ch);
            echo json_encode(['success' => false, 'message' => 'Failed to fetch file data from GitHub. HTTP code: ' . $httpCode]);
            exit();
        }
    } else {
        error_log("Invalid request data. Template ID or filename missing.");
        echo json_encode(['success' => false, 'message' => 'Invalid request data.']);
        exit();
    }
} else {
    error_log("Invalid request method.");
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}
?>
