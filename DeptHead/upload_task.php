<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'connection.php';

// Log file path
$log_file = 'logfile.log';

// Function to write to log file
function write_log($message) {
    global $log_file;
    $timestamp = date("Y-m-d H:i:s");
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logfile.log');

write_log("Database connected successfully.");

// Get data from form
$UserID = $_SESSION['user_id'];
$ContentIDs = isset($_POST['grade']) ? $_POST['grade'] : []; // Get all selected ContentIDs as an array
$Type = 'Task';
$Title = $_POST['title'];
$DueDate = $_POST['due-date'];
$taskContent = $_POST['instructions'];
$DueTime = $_POST['due-time'];
$timeStamp = date('Y-m-d H:i:s'); // Current timestamp
$ApprovalStatus = "Pending"; // Set ApprovalStatus to Approved

// Get schedule date and time from POST if the action is schedule
if ($_POST['taskAction'] === 'Schedule') {
    $ScheduleDate = $_POST['schedule-date'];
    $ScheduleTime = $_POST['schedule-time'];
    $Status = 'Schedule';
} else {
    $ScheduleDate = null;
    $ScheduleTime = null;
    $Status = $_POST['taskAction'] === 'Draft' ? 'Draft' : 'Assign'; // Set to Draft if action is draft
}

write_log("Received form data: UserID = $UserID, ContentIDs = " . implode(", ", $ContentIDs) . ", Type = $Type, Title = $Title, DueDate = $DueDate, taskContent = $taskContent, DueTime = $DueTime, Status = $Status, Schedule Date = $ScheduleDate, Schedule Time = $ScheduleTime");

// File upload handling
$uploadOk = 1;
$target_dir = __DIR__ . '/Attachments/'; // Absolute path to the directory
$allFilesUploaded = true;

if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true); // Create directory if not exists
}

$uploadedFiles = [];

if (isset($_FILES['file']) && count($_FILES['file']['name']) > 0 && !empty($_FILES['file']['name'][0])) {
    $fileCount = count($_FILES['file']['name']);
    write_log("Number of files to upload: $fileCount");

    for ($i = 0; $i < $fileCount; $i++) {
        $fileTmpName = $_FILES['file']['tmp_name'][$i];
        $fileOriginalName = basename($_FILES['file']['name'][$i]);
        $fileType = strtolower(pathinfo($fileOriginalName, PATHINFO_EXTENSION));
        $fileSize = $_FILES['file']['size'][$i];
        $fileMimeType = mime_content_type($fileTmpName);

        // Generate a 6-digit random number
        $randomNumber = rand(100000, 999999);
        $fileName = $randomNumber . "_" . $fileOriginalName; // Format: random_number_original_filename
        $target_file = $target_dir . $fileName; // Full path to the target file

        write_log("Processing file $fileOriginalName: New Name = $fileName, Type = $fileType, Size = $fileSize, MimeType = $fileMimeType");

        // Check file size
        if ($fileSize > 30000000) { // Limit to 5MB
            write_log("File too large: $fileOriginalName");
            $allFilesUploaded = false;
            continue; // Skip to the next file
        }

        // Allow certain file formats
        $allowedTypes = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'pptx',
    'mp4', 'mov', 'avi', 'wmv', 'mkv');
        if (!in_array($fileType, $allowedTypes)) {
            write_log("Invalid file type: $fileOriginalName");
            $allFilesUploaded = false;
            continue; // Skip to the next file
        }

        // Try to upload file
        if (move_uploaded_file($fileTmpName, $target_file)) {
            write_log("File uploaded: $fileName, Stored at: $target_file");

            // Store file details in an array to insert after task creation
            $uploadedFiles[] = [
                'fileName' => $fileName,
                'fileMimeType' => $fileMimeType,
                'fileSize' => $fileSize,
                'target_file' => $target_file
            ];
        } else {
            write_log("Error uploading file: $fileOriginalName");
            $allFilesUploaded = false;
        }
    }
} else {
    write_log("No files uploaded or file input is empty.");
}

// Insert task into tasks table for each ContentID
foreach ($ContentIDs as $ContentID) {
    $sql = "INSERT INTO tasks (UserID, ContentID, Type, Title, taskContent, DueDate, DueTime, Schedule_Date, Schedule_Time, Status, ApprovalStatus) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("sssssssssss", $UserID, $ContentID, $Type, $Title, $taskContent, $DueDate, $DueTime, $ScheduleDate, $ScheduleTime, $Status, $ApprovalStatus);

        if ($stmt->execute()) {
            $TaskID = $stmt->insert_id;
            write_log("Task added with ID: $TaskID, UserID: $UserID, ContentID: $ContentID");

            // Insert files into attachment table using the fetched TaskID
            foreach ($uploadedFiles as $file) {
                $docuStmt = $conn->prepare("INSERT INTO attachment (UserID, ContentID, TaskID, name, mimeType, size, uri, TimeStamp) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $timestamp = date("Y-m-d H:i:s");
                $docuStmt->bind_param("ssssssss", $UserID, $ContentID, $TaskID, $file['fileName'], $file['fileMimeType'], $file['fileSize'], $file['target_file'], $timestamp);

                if (!$docuStmt->execute()) {
                    write_log("Error inserting into attachment: " . $docuStmt->error);
                }
                $docuStmt->close();
            }
        } else {
            write_log("Error inserting into tasks: " . $stmt->error);
        }

        $stmt->close();
    } else {
        write_log("Error preparing tasks statement: " . $conn->error);
    }
}

// Set response
header('Content-Type: application/json');
$response = array("success" => true, "message" => "Tasks created successfully.");
if (!$allFilesUploaded) {
    $response = array("success" => false, "message" => "Tasks created, but some files may not have been uploaded.");
}
echo json_encode($response);

$conn->close();
write_log("Database connection closed.");
?>
