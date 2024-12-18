<?php

// Define GitHub repository details
$githubRepo = "AbiAb1/DocMaP"; // Your GitHub username/repo
$branch = "extra"; // Branch where you want to upload

// Define the target directory for the uploaded file
$targetDir = realpath(__DIR__ . '/Attachments') . '/'; // Absolute path to the director
$targetFile = $targetDir . "LNHS-Teachers.xlsx";

// Check if the file was uploaded via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES["file"])) {
    // Get the uploaded file details
    $fileTmpName = $_FILES["file"]["tmp_name"];
    $originalFileName = $_FILES["file"]["name"];
    
    $fileType = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));

    // Validate file type
    if ($fileType != "xls" && $fileType != "xlsx") {
        echo json_encode(['status' => 'error', 'message' => 'Only Excel files are allowed.']);
        exit;
    }

    // Save the file locally
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true); // Create directory if it doesn't exist
    }

   if (!move_uploaded_file($fileTmpName, $targetFile)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to save file locally.',
        'debug' => [
            'tmp_name' => $fileTmpName,
            'targetFile' => $targetFile,
            'is_dir' => is_dir($targetDir),
            'is_writable' => is_writable($targetDir)
        ]
    ]);
    exit;
}

if (!file_exists($targetFile)) {
    echo json_encode(['status' => 'error', 'message' => 'File does not exist locally after upload.']);
    exit;
}


    // Prepare GitHub API URL
    $uploadUrl = "https://api.github.com/repos/$githubRepo/contents/Admin/TeacherData/$originalFileName";

    // Read the file content
    $content = base64_encode(file_get_contents($targetFile));

    // Prepare the request body for adding the new file
    $data = json_encode([
        "message" => "Adding a new file to TeacherData folder",
        "content" => $content,
        "branch" => $branch
    ]);

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

    // Initialize cURL for listing existing files in the directory
    $listUrl = "https://api.github.com/repos/$githubRepo/contents/Admin/TeacherData?ref=$branch";
    $ch = curl_init($listUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $listResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200) {
        $files = json_decode($listResponse, true);
        foreach ($files as $file) {
            // Delete old files in the directory
            $deleteUrl = $file['url'];
            $deleteSha = $file['sha'];
            $deleteData = json_encode([
                "message" => "Deleting old file",
                "sha" => $deleteSha,
                "branch" => $branch
            ]);

            $ch = curl_init($deleteUrl);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $deleteData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            curl_exec($ch);
            curl_close($ch);
        }
    }

    // Initialize cURL for uploading the new file
    $ch = curl_init($uploadUrl);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute the request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Handle the response
    if ($response === false) {
        echo json_encode(['status' => 'error', 'message' => 'cURL error: ' . curl_error($ch)]);
        exit;
    } else {
        $responseData = json_decode($response, true);
        if ($httpCode == 201) {
            // File uploaded successfully to GitHub
            $githubDownloadUrl = $responseData['content']['download_url'];
            echo json_encode([
                'status' => 'success',
                'message' => 'File uploaded successfully.',
                'localPath' => $targetFile,
                'githubUrl' => $githubDownloadUrl
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error uploading file to GitHub: ' . $response]);
        }
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded.']);
}
?>
