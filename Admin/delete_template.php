<?php
session_start();
include 'connection.php';

header('Content-Type: application/json'); // Set response type to JSON

// Log debug messages to the server error log
function debug_message($message) {
    error_log($message);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $templateId = $input['templateId'] ?? null;
    $filename = $input['filename'] ?? null;

    if ($templateId && $filename) {
        $apiUrl = "https://api.github.com/repos/AbiAb1/DocMaP/contents/Admin/Templates/$filename?ref=extra";
        $githubToken = $_ENV['GITHUB_TOKEN'] ?? null;

        debug_message("GitHub API URL: $apiUrl");
        debug_message("Filename: $filename");
        debug_message("Template ID: $templateId");

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

        debug_message("HTTP Code on Fetch: $httpCode");
        debug_message("GitHub API Response (Fetch): $response");

        if (curl_errno($ch)) {
            debug_message("cURL error (Fetch): " . curl_error($ch));
        }

        if ($httpCode === 200) {
            $fileData = json_decode($response, true);
            $sha = $fileData['sha'];

            debug_message("Fetched SHA: $sha");

            $deletePayload = json_encode(['message' => "Deleting $filename", 'sha' => $sha]);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $deletePayload);

            $deleteResponse = curl_exec($ch);
            $deleteCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            debug_message("HTTP Code on Delete: $deleteCode");
            debug_message("GitHub API Response (Delete): $deleteResponse");

            curl_close($ch);

            if ($deleteCode === 200 || $deleteCode === 204) {
                $query = "DELETE FROM templates WHERE TemplateID = ?";
                if ($stmt = mysqli_prepare($conn, $query)) {
                    mysqli_stmt_bind_param($stmt, 'i', $templateId);
                    if (mysqli_stmt_execute($stmt)) {
                        echo json_encode(['success' => true, 'message' => 'Template deleted successfully from both GitHub and database.']);
                        exit();
                    } else {
                        debug_message("Failed to execute database query.");
                        echo json_encode(['success' => false, 'message' => 'Failed to delete the template from the database.']);
                        exit();
                    }
                } else {
                    debug_message("Failed to prepare database query.");
                    echo json_encode(['success' => false, 'message' => 'Failed to prepare the SQL statement for database deletion.']);
                    exit();
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete the file from GitHub. HTTP code: ' . $deleteCode]);
                exit();
            }
        } elseif ($httpCode === 404) {
            debug_message("File not found on GitHub.");
            echo json_encode(['success' => false, 'message' => 'File not found on GitHub. Verify the file path.']);
            curl_close($ch);
            exit();
        } else {
            debug_message("Failed to fetch file data from GitHub. HTTP code: $httpCode");
            curl_close($ch);
            echo json_encode(['success' => false, 'message' => 'Failed to fetch file data from GitHub. HTTP code: ' . $httpCode]);
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
