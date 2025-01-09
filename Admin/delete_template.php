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

        // Step 1: Delete the file from the database
        $query = "DELETE FROM templates WHERE TemplateID = ?";
        if ($stmt = mysqli_prepare($conn, $query)) {
            mysqli_stmt_bind_param($stmt, 'i', $templateId);
            if (mysqli_stmt_execute($stmt)) {
                // Database deletion succeeded, proceed to delete from GitHub

                // Step 2: Validate File Existence on GitHub
                $ch = curl_init($apiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $authHeader);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                if ($httpCode === 200) {
                    $fileData = json_decode($response, true);
                    $sha = $fileData['sha'];

                    // Step 3: Delete the file using its `sha`
                    $deletePayload = json_encode(['message' => "Deleting $filename", 'sha' => $sha]);

                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $deletePayload);

                    $deleteResponse = curl_exec($ch);
                    $deleteCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    if ($deleteCode === 200 || $deleteCode === 204) {
                        echo json_encode(['success' => true, 'message' => 'Template deleted successfully from both database and GitHub.']);
                        exit();
                    } else {
                        // GitHub deletion failed, rollback database deletion
                        $rollbackQuery = "INSERT INTO templates (TemplateID, filename) VALUES (?, ?)";
                        if ($rollbackStmt = mysqli_prepare($conn, $rollbackQuery)) {
                            mysqli_stmt_bind_param($rollbackStmt, 'is', $templateId, $filename);
                            mysqli_stmt_execute($rollbackStmt);
                        }

                        echo json_encode(['success' => false, 'message' => 'Failed to delete the file from GitHub. Database deletion has been rolled back.']);
                        exit();
                    }
                } elseif ($httpCode === 404) {
                    echo json_encode(['success' => false, 'message' => 'File not found on GitHub. Verify the file path.']);
                    curl_close($ch);
                    exit();
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to fetch file data from GitHub. HTTP code: ' . $httpCode]);
                    curl_close($ch);
                    exit();
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete the template from the database.']);
                exit();
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to prepare the SQL statement for database deletion.']);
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
