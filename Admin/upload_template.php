<?php
session_start();
include 'connection.php';

$response = ['status' => 'error', 'message' => 'Something went wrong'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['template_file'])) {
    $userId = $_SESSION['user_id']; // Assume logged-in UserID
    $name = mysqli_real_escape_string($conn, $_POST['template_name']);
    $file = $_FILES['template_file'];
    $fileTmpName = $file['tmp_name'];
    $newFileName = uniqid() . '_' . basename($file['name']); // Unique filename
    $mimetype = $file['type'];
    $size = $file['size'];

    // Create the 'Templates' folder if it doesn't exist
    $uploadDir = 'Templates/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755); // Create folder with appropriate permissions
    }

    $target_file = $uploadDir . $newFileName;
    $created_at = date('Y-m-d H:i:s');

    // Move file to local folder
    if (move_uploaded_file($fileTmpName, $target_file)) {
        // GitHub Repository Details
        $githubRepo = "AbiAb1/DocMaP"; // GitHub username/repo
        $branch = "extra";
        $uploadUrl = "https://api.github.com/repos/$githubRepo/contents/Admin/Templates/$newFileName";

        // Fetch GitHub Token from Environment Variables
        $githubToken = $_ENV['GITHUB_TOKEN'] ?? null;
        if (!$githubToken) {
            $response['message'] = 'GitHub token is missing';
            echo json_encode($response);
            exit;
        }

        // Prepare File Data for GitHub
        $content = base64_encode(file_get_contents($target_file));
        $data = json_encode([
            "message" => "Adding a new file to upload folder",
            "content" => $content,
            "branch" => $branch
        ]);

        $headers = [
            "Authorization: token $githubToken",
            "Content-Type: application/json",
            "User-Agent: DocMaP"
        ];

        // GitHub API Call
        $ch = curl_init($uploadUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $responseGitHub = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($responseGitHub === false || $httpCode !== 201) {
            $response['message'] = 'GitHub upload failed';
        } else {
            $responseData = json_decode($responseGitHub, true);
            $githubDownloadUrl = $responseData['content']['download_url'];

            // Save File Information to Database
            $query = "INSERT INTO `templates`(`UserID`, `name`, `filename`, `mimetype`, `size`, `uri`, `created_at`) 
                      VALUES ('$userId', '$name', '$newFileName', '$mimetype', '$size', '$target_file', '$githubDownloadUrl', '$created_at')";
            if (mysqli_query($conn, $query)) {
                $response = ['status' => 'success', 'message' => 'Template uploaded successfully', 'github_url' => $githubDownloadUrl];
            } else {
                $response['message'] = 'Database insertion failed';
            }

            // Optionally delete local file
            if (file_exists($target_file)) {
                unlink($target_file);
            }
        }
    } else {
        $response['message'] = 'File upload failed';
    }
}

echo json_encode($response);
?>
