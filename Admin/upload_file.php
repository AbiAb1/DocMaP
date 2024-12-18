<?php

// Define GitHub repository details
$githubRepo = "AbiAb1/DocMaP"; // Your GitHub username/repo
$branch = "extra"; // Branch where you want to upload
$folderPath = "Admin/TeacherData"; // Folder path in the repository

// Check if the file was uploaded via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES["file"])) {
    // Get the uploaded file details
    $fileTmpName = $_FILES["file"]["tmp_name"];
    $originalFileName = $_FILES["file"]["name"];

    // Sanitize the original file name
    $sanitizedFileName = preg_replace('/[^a-zA-Z0-9_.]/', '', str_replace([' ', '-'], '_', $originalFileName));

    // Add underscore to the sanitized file name
    $sanitizedFileName = "_" . $sanitizedFileName;

    $fileType = strtolower(pathinfo($sanitizedFileName, PATHINFO_EXTENSION));

    // Validate file type
    if ($fileType != "xls" && $fileType != "xlsx") {
        echo json_encode(['status' => 'error', 'message' => 'Only Excel files are allowed.']);
        exit;
    }

    // Prepare GitHub API URL
    $uploadUrl = "https://api.github.com/repos/$githubRepo/contents/$folderPath/$sanitizedFileName";

    // Get GitHub token from environment variables
    $githubToken = getenv('GITHUB_TOKEN');
    if (!$githubToken) {
        echo json_encode(['status' => 'error', 'message' => 'GitHub token is not set in the environment variables.']);
        exit;
    }

    // Prepare the headers
    $headers = [
        "Authorization: token $githubToken",
        "Content-Type: application/json",
        "User-Agent: DocMaP"
    ];

    // Delete the old file from the folder
    $listUrl = "https://api.github.com/repos/$githubRepo/contents/$folderPath?ref=$branch";
    $ch = curl_init($listUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200) {
        $files = json_decode($response, true);
        foreach ($files as $file) {
            // Delete all existing files in the folder
            $deleteUrl = $file['url'];
            $deleteData = json_encode([
                "message" => "Deleting old file before adding a new one",
                "sha" => $file['sha'],
                "branch" => $branch
            ]);
            $ch = curl_init($deleteUrl);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $deleteData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_exec($ch);
            curl_close($ch);
        }
    }

    // Read the file content
    $content = base64_encode(file_get_contents($fileTmpName));

    // Prepare the request body for the new file upload
    $data = json_encode([
        "message" => "Adding a new file to TeacherData folder",
        "content" => $content,
        "branch" => $branch
    ]);

    // Initialize cURL for file upload
    $ch = curl_init($uploadUrl);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute the upload request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Handle the upload response
    if ($httpCode == 201) {
        $responseData = json_decode($response, true);
        $githubDownloadUrl = $responseData['content']['download_url'];
        echo json_encode(['status' => 'success', 'message' => 'File uploaded to GitHub successfully.', 'url' => $githubDownloadUrl]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error uploading file to GitHub: ' . $response]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded.']);
}
?>
