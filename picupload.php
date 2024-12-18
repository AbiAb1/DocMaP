<?php
session_start();
require 'connection.php'; // Ensure this path is correct relative to the mysql of your GitHub repository

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Ensure the upload directory exists in the correct location relative to the repository<?php
session_start();
require 'connection.php'; // Ensure this path is correct

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Ensure the upload directory exists
$upload_dir = 'img/UserProfile/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Check if a file was uploaded
if (isset($_FILES['file']) && $_FILES['file']['error'] == UPLOAD_ERR_OK) {
    $file_tmp = $_FILES['file']['tmp_name'];
    $file_ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
    $unique_filename = uniqid() . '.' . $file_ext;
    $file_path = $upload_dir . $unique_filename;

    // Move the uploaded file to the desired directory
    if (move_uploaded_file($file_tmp, $file_path)) {
        // Update the user's profile column in the database
        $stmt = $conn->prepare("UPDATE useracc SET profile = ? WHERE UserID = ?");
        $stmt->bind_param("si", $unique_filename, $user_id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully', 'filename' => $unique_filename]);

            // Upload the file to GitHub
            $githubRepo = "AbiAb1/DocMaP"; // Your GitHub username/repo
            $branch = "extra"; // Branch where you want to upload
            $uploadUrl = "https://api.github.com/repos/$githubRepo/contents/img/UserProfile/$unique_filename";

            $content = base64_encode(file_get_contents($file_path));
            $data = json_encode([
                "message" => "Adding a new profile image",
                "content" => $content,
                "branch" => $branch
            ]);

            $githubToken = getenv('GITHUB_TOKEN');

            if (!$githubToken) {
                echo json_encode(['status' => 'error', 'message' => 'GitHub token is not set in the environment variables.']);
                exit();
            }

            $headers = [
                "Authorization: token $githubToken",
                "Content-Type: application/json",
                "User-Agent: DocMaP"
            ];

            $ch = curl_init($uploadUrl);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $responseGitHub = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode != 201) {
                echo json_encode(['status' => 'error', 'message' => 'Error uploading file to GitHub']);
                exit();
            }

            $responseData = json_decode($responseGitHub, true);
            $githubDownloadUrl = $responseData['content']['download_url'];

            echo json_encode(['status' => 'success', 'message' => 'File uploaded to GitHub successfully', 'download_url' => $githubDownloadUrl]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database update failed']);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to move uploaded file']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded or upload error']);
}

$conn->close();
?>

$upload_dir = $_SERVER['DOCUMENT_mysql'] . '/img/UserProfile/';

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Check if a file was uploaded
if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $file_tmp = $_FILES['file']['tmp_name'];
    $file_name = $_FILES['file']['name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

    // Validate file extension
    if (!in_array($file_ext, $allowed_ext)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.']);
        exit;
    }

    // Generate a unique filename
    $unique_filename = uniqid() . '.' . $file_ext;
    $file_path = $upload_dir . $unique_filename;

    // Move the uploaded file to the desired directory
    if (move_uploaded_file($file_tmp, $file_path)) {
        // Update the user's profile picture in the database
        $stmt = $conn->prepare("UPDATE useracc SET profile = ? WHERE UserID = ?");
        $stmt->bind_param("si", $unique_filename, $user_id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully', 'filename' => $unique_filename]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database update failed']);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to move uploaded file']);
    }
} else {
    // Handle file upload errors
    $error_message = 'Unknown error';
    if (isset($_FILES['file']['error'])) {
        switch ($_FILES['file']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $error_message = 'File size exceeds the allowed limit.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message = 'File was only partially uploaded.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message = 'No file uploaded.';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $error_message = 'Missing temporary folder.';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $error_message = 'Failed to write file to disk.';
                break;
            case UPLOAD_ERR_EXTENSION:
                $error_message = 'A PHP extension stopped the file upload.';
                break;
        }
    }
    echo json_encode(['status' => 'error', 'message' => $error_message]);
}

$conn->close();
?>
