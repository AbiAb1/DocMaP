<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Include your database connection file here
include 'connection.php';

// Log file path
$log_file = __DIR__ . '/logfile.log'; // Ensure the log file path is correct

// Function to log messages
function logMessage($message) {
    global $log_file; // Use global variable to access log file
    error_log(date('Y-m-d H:i:s') . " - " . $message . PHP_EOL, 3, $log_file);
}

ini_set('log_errors', 1);
ini_set('error_log', $log_file);

// Initialize variables
$task_title = "";
$task_description = "";
$task_type = "";
$task_user_fname = ""; // To store the user's first name
$task_user_lname = ""; // To store the user's last name
$task_timestamp = ""; 
$task_due = "";// To store the timestamp
$task_time = "";

// Check if task_id is provided in URL
if (isset($_GET['task_id'])) {
    $task_id = $_GET['task_id'];

    // Query to fetch task details and user information based on TaskID
    $sql_task_details = "
        SELECT t.*, u.fname, u.lname 
        FROM tasks t
        JOIN useracc u ON t.UserID = u.UserID 
        WHERE t.TaskID = '$task_id'
    ";

    $result_task_details = mysqli_query($conn, $sql_task_details);

    // Check if task details are found
    if (mysqli_num_rows($result_task_details) > 0) {
        $row_task_details = mysqli_fetch_assoc($result_task_details);
        $task_title = $row_task_details['Title'];
        $task_description = $row_task_details['taskContent'];
        $task_type = $row_task_details['Type'];
        $task_timestamp = $row_task_details['TimeStamp']; // Fetch the timestamp
        $task_user_fname = $row_task_details['fname']; // Fetch the first name
        $task_user_lname = $row_task_details['lname'];
        $task_due = $row_task_details['DueDate']; 
        $task_time = $row_task_details['DueTime']; // Fetch the last name
    } else {
        echo "Task details not found.";
        exit(); // Exit if task details are not found
    }
} else {
    echo "Task ID not provided.";
    exit(); // Exit if task ID is not provided
}

// Format Timestamp
$formatted_timestamp = date('F j, Y \a\t g:i A', strtotime($task_timestamp)); // Format as desired
// Format the date to mm/dd/yyyy
// Format the date to m/d/Y
$date_obj = DateTime::createFromFormat('Y-m-d', $task_due);
$formatted_date = $date_obj ? $date_obj->format('m/d/Y') : 'Invalid Date';

// Format the time to HH:mm with AM/PM
$time_obj = DateTime::createFromFormat('H:i:s', $task_time);
$formatted_time = $time_obj ? $time_obj->format('g:i A') : 'Invalid Time'; // 'g:i A' gives time in 12-hour format with AM/PM

// Combine the formatted date and time
$combined_date_time = $formatted_date . ' ' . $formatted_time;


// Fetch documents associated with the task
$sql_attachment = "SELECT Attach_ID, name FROM attachment WHERE TaskID = '$task_id'";
$result_attachment = mysqli_query($conn, $sql_attachment);
$attachments = [];
if (mysqli_num_rows($result_attachment) > 0) {
    while ($row = mysqli_fetch_assoc($result_attachment)) {
        $attachments[] = $row;
    }
}

// Fetch users associated with the specific task (based on TaskID)
if (isset($_GET['task_id'])) {
    $task_id = $_GET['task_id'];

    // Combined query to fetch users and their submitted documents
    $sql_users_documents = "
        SELECT 
            u.UserID, 
            u.fname, 
            u.lname, 
            u.profile, 
            tu.Status,
            tu.Task_User_ID, tu.SubmitDate, tu.ApproveDate, tu.RejectDate,
            d.name AS file_name,
            d.uri AS file_path,
            d.mimeType,
            d.ContentID,
            d.TaskID
        FROM 
            task_user tu
        JOIN 
            useracc u ON tu.UserID = u.UserID
        LEFT JOIN 
            documents d ON tu.TaskID = d.TaskID AND u.UserID = d.UserID
        WHERE 
            tu.TaskID = ? 
    ";

    $stmt = $conn->prepare($sql_users_documents);
    $stmt->bind_param("i", $task_id); // "i" for integer
    $stmt->execute();
    $result_users_documents = $stmt->get_result();

    $users = [];
    while ($row = $result_users_documents->fetch_assoc()) {
        $users[] = $row;
    }

    $stmt->close();
}

// Check if taskID and contentID are set
if (isset($_GET['task_id']) && isset($_GET['content_id'])) {
    $taskID = $_GET['task_id'];
    $contentID = $_GET['content_id'];

    // Log all received parameters
    logMessage("Received parameters: " . json_encode($_GET));

    // Check for empty parameters and log specifically
    if (empty($taskID)) {
        logMessage("TaskID is missing or empty.");
    }

    if (empty($contentID)) {
        logMessage("ContentID is missing or empty.");
    }

    if (!empty($taskID) && !empty($contentID)) {
        logMessage("Fetching counts for TaskID: $taskID and ContentID: $contentID");

        // Query to count handed-in users
        $queryHandedIn = "
            SELECT COUNT(*) AS handedInCount 
            FROM task_user 
            WHERE TaskID = '$taskID' AND ContentID = '$contentID' AND Status = 'Submitted'
        ";

        // Query to count assigned users
        $queryAssigned = "
            SELECT COUNT(*) AS assignedCount 
            FROM task_user 
            WHERE TaskID = '$taskID' AND ContentID = '$contentID' AND Status = 'Assigned'
        ";

        // Query to count missing users
        $queryMissing = "
            SELECT COUNT(*) AS missingCount 
            FROM task_user 
            WHERE TaskID = '$taskID' AND ContentID = '$contentID' AND Status = 'Missing'
        ";

        // Query to count approved users
        $queryApproved = "
            SELECT COUNT(*) AS approvedCount 
            FROM task_user 
            WHERE TaskID = '$taskID' AND ContentID = '$contentID' AND Status = 'Approved'
        ";

        // Query to count rejected users
        $queryRejected = "
            SELECT COUNT(*) AS rejectedCount 
            FROM task_user 
            WHERE TaskID = '$taskID' AND ContentID = '$contentID' AND Status = 'Rejected'
        ";

        // Execute queries to get counts
        if ($stmtHandedIn = $conn->prepare($queryHandedIn)) {
            if ($stmtHandedIn->execute()) {
                $resultHandedIn = $stmtHandedIn->get_result();
                $handedInCount = $resultHandedIn->fetch_assoc()['handedInCount'];
                logMessage("Handed In Count: $handedInCount");
            } else {
                logMessage("Error executing handed in query: " . $stmtHandedIn->error);
            }
            $stmtHandedIn->close();
        } else {
            logMessage("Error preparing handed in query: " . $conn->error);
        }

        if ($stmtAssigned = $conn->prepare($queryAssigned)) {
            if ($stmtAssigned->execute()) {
                $resultAssigned = $stmtAssigned->get_result();
                $assignedCount = $resultAssigned->fetch_assoc()['assignedCount'];
                logMessage("Assigned Count: $assignedCount");
            } else {
                logMessage("Error executing assigned query: " . $stmtAssigned->error);
            }
            $stmtAssigned->close();
        } else {
            logMessage("Error preparing assigned query: " . $conn->error);
        }

        // Execute query to get missing count
        if ($stmtMissing = $conn->prepare($queryMissing)) {
            if ($stmtMissing->execute()) {
                $resultMissing = $stmtMissing->get_result();
                $missingCount = $resultMissing->fetch_assoc()['missingCount'];
                logMessage("Missing Count: $missingCount");
            } else {
                logMessage("Error executing missing query: " . $stmtMissing->error);
            }
            $stmtMissing->close();
        } else {
            logMessage("Error preparing missing query: " . $conn->error);
        }

        // Execute query to get approved count
        if ($stmtApproved = $conn->prepare($queryApproved)) {
            if ($stmtApproved->execute()) {
                $resultApproved = $stmtApproved->get_result();
                $approvedCount = $resultApproved->fetch_assoc()['approvedCount'];
                logMessage("Approved Count: $approvedCount");
            } else {
                logMessage("Error executing approved query: " . $stmtApproved->error);
            }
            $stmtApproved->close();
        } else {
            logMessage("Error preparing approved query: " . $conn->error);
        }

        // Execute query to get rejected count
        if ($stmtRejected = $conn->prepare($queryRejected)) {
            if ($stmtRejected->execute()) {
                $resultRejected = $stmtRejected->get_result();
                $rejectedCount = $resultRejected->fetch_assoc()['rejectedCount'];
                logMessage("Rejected Count: $rejectedCount");
            } else {
                logMessage("Error executing rejected query: " . $stmtRejected->error);
            }
            $stmtRejected->close();
        } else {
            logMessage("Error preparing rejected query: " . $conn->error);
        }
    }
} else {
    // Log specifically which parameter is missing
    if (!isset($_GET['task_id'])) {
        logMessage("TaskID parameter is missing.");
    }

    if (!isset($_GET['content_id'])) {
        logMessage("ContentID parameter is missing.");
    }

    // Set fallback values
    $assignedCount = 0; // Or a fallback value
    $handedInCount = 0; // Or a fallback value
    $missingCount = 0; // Or a fallback value
    $approvedCount = 0; // Or a fallback value
    $rejectedCount = 0; // Or a fallback value
}

// Close database connection
mysqli_close($conn);
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Details</title>
        <link rel="icon" type="image/png" href="../img/Logo/docmap-logo-1.png">
    <!-- ======= Styles ====== -->
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .taskDetails {
            padding: 20px;
            border-radius: 8px;
            margin-left: 50px; /* Margin only on the left */
            margin-right: 50px; /* Margin only on the right */
            margin-top: 30px;
        }
        .taskDetails h2 {
            font-size: 36px;
            margin-bottom: 10px;
            display: flex;
            align-items: center; /* Align items vertically */
            justify-content: space-between; /* Distribute items evenly */
        }
        .taskDetails p {
            font-size: 16px;
            line-height: 1.6;
            color: #666;
        }
        .taskDetails .taskType {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .icon-circle {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 60px; /* Adjust circle size */
            height: 60px; /* Adjust circle size */
            background-color: #9B2035; /* Circle background color */
            border-radius: 50%; /* Make it a circle */
        }
        .icon1 {
            color: white; /* Icon color */
            font-size: 24px; /* Adjust icon size */
        }
        .taskDueDate, .taskUser {
            margin-top: -15px;
            font-size: 14px; /* Adjusted font size */
            color: #999; /* Adjusted color */
            margin-right: 10px;
        }
        .taskUserContainer {
            
            align-items: center; /* Aligns items vertically centered */
            display: block; /* Ensures each <p> element is on a new line */
            margin: 0; /* Optional: Customize margin if additional spacing is needed */
            padding: 5px 0; /* Optional: Adds vertical padding for spacing */
        }
        .taskUserContainer p {
            font-size:13px;
            font-weight:bold;
            color:grey;
        }
        .nav-tabs .nav-link {
            position: relative;
            border: none; /* Remove the default border */
            color: black;
        }

        .nav-tabs .nav-link.active {
            font-weight: bold; /* Make the active tab bold */
            background-color: transparent;
            color: #9b2035;
        }

        .nav-tabs .nav-link.active::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            bottom: -5px; /* Distance from the bottom of the tab */
            height: 2px; /* Thickness of the underline */
            background-color: #9b2035; /* Change this color to your desired underline color */
        }
        .attachment-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 20px;
        }

        .attachment-item {
            display: flex;
            align-items: center;
            padding: 10px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: background-color 0.2s ease;
        }

        .attachment-item:hover {
            background-color: #e0e0e0;
        }

        .file-link {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: inherit;
            width: 100%;
        }

        .file-icon {
            font-size: 24px;
            margin-right: 15px;
            color: #ff5722; /* Customize color for file icon */
        }

        .file-info {
            flex-grow: 1;
        }

        .file-name {
            font-size: 16px;
            font-weight: 500;
            color: #333;
        }

        .pin-icon {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    width: 40px; /* Adjust size of the circle */
    height: 40px; /* Adjust size of the circle */
    border-radius: 50%; /* Makes the shape a circle */
    background-color: #9b2035; /* Circle color */
    color: white; /* Icon color */
    font-size: 20px; /* Adjust icon size */
    margin-left: 40px;

}

.pin-icon i {
    margin: 0; /* Remove any margin around the icon */
}

        .user-list, .user-attachments {
            padding: 20px; /* General padding for both sections */
        }

        .user-list h6, .user-attachments h6 {
            margin-top: 20px; /* Space before section titles */
            font-weight: bold; /* Make the title bold */
        }

        .user-attachments {
            padding-left: 20px; /* Space to the left of the attachments */
        }
        .document-container {
    background-color: white;
    border-radius: 8px;
    padding: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); /* Optional: For a slight shadow */
    border: 1px solid #ddd; /* Optional: Border for better visibility */
  
}

.document-link {
    text-decoration: none;
    color: #007bff;
    display: flex;
    justify-content: space-between;
    width: 100%;
    align-items: center;
}

.document-name {
    flex-grow: 1;
    font-size: 14px;
    color: black;
}

.document-icon-container {
    width: 30px;
    height: 30px;
    background-color: #9b2035;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    margin-left: 10px;
}

.document-icon {
    font-size: 18px;
    color: white;
}

.comment-icon {
    font-size: 18px;
    color: white;
}
.status-title{
    font-weight:bold;
    color:#9b2035;
}


    </style>
</head>
<body style="background-color:#F1F0F6;">
    <!-- SIDEBAR -->
    <section id="sidebar">
        <?php include 'navbar.php'; ?>
    </section>
    <!-- SIDEBAR -->


    
    <section id="content">
        <!-- NAVBAR -->
        <?php include 'topbar.php'; ?>
        <!-- NAVBAR -->

        <!-- MAIN -->
        <main>
            
        <h1 class="title" style="position: relative; display: flex; justify-content: space-between; align-items: center;">
    Outputs
    <button id="generateReportBtn" class="btn btn-primary" style="position: absolute; right: 0; top: 10px; display: none;"
    data-taskid="<?php echo htmlspecialchars($taskID, ENT_QUOTES, 'UTF-8'); ?>" data-contentid="<?php echo htmlspecialchars($contentID, ENT_QUOTES, 'UTF-8'); ?>">
    Generate Report
</button>
</h1>
            
            <!-- Tabs for Instructions and Teacher -->
            <ul class="nav nav-tabs" id="myTab" role="tablist" style="margin-left: 50px; margin-top:20px;">
                <li class="nav-item" role="presentation">
                    <a class="nav-link active" id="instructions-tab" data-toggle="tab" href="#instructions" role="tab" aria-controls="instructions" aria-selected="true">Instructions</a>
                </li>
                 <?php if ($task_type === 'Task'): ?>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" id="teacher-tab" data-toggle="tab" href="#teacher" role="tab" aria-controls="teacher" aria-selected="false">Teacher Output</a>
                    </li>
                <?php endif; ?>
            </ul>

            
            <div class="tab-content" id="myTabContent" style="margin-left: 50px;">
                <div class="tab-pane fade show active" id="instructions" role="tabpanel" aria-labelledby="instructions-tab">
                    <!-- ======================= Task Details ================== -->
                    <div class="taskDetails">
                        <h2>
                            <?php echo $task_title; ?>
                            <!-- Icon Logic -->
                            <?php 
                                $iconClass = '';
                                switch ($task_type) {
                                    case 'Task':
                                        $iconClass = 'document-outline';
                                        break;
                                    case 'Reminder':
                                        $iconClass = 'calendar-clear-outline';
                                        break;
                                    case 'Announcement':
                                        $iconClass = 'notifications-outline';
                                        break;
                                    default:
                                        $iconClass = 'alert-circle-outline'; // Default icon for unknown type
                                        break;
                                }
                            ?>
                            <div class="icon-circle">
                                <ion-icon class="icon1" name="<?php echo $iconClass; ?>"></ion-icon>
                            </div>
                        </h2>
                        <?php if (!empty($task_user_fname) && !empty($task_user_lname) && !empty($task_timestamp)): ?>
                            <div class="taskUserContainer">
                                <p class="taskUser"><?php echo htmlspecialchars($task_user_fname . ' ' . $task_user_lname); ?></p>
                                <p class="taskDueDate">Posted: <?php echo htmlspecialchars($formatted_timestamp); ?></p>
                                <p class="taskDueDate">Due: <?php echo htmlspecialchars($combined_date_time); ?></p>
                            </div>
                        <?php endif; ?>
                        <br>
                        <p style="font-size: 18px;"><?php echo $task_description; ?></p>
                        


                         <?php
if (!empty($attachments)) {
    echo '<div class="Attachment-container row">';
    foreach ($attachments as $attachment) {
        // Remove leading numbers and underscore
        $displayName = preg_replace('/^\d+_/', '', $attachment['name']);
        // Local file path (replace with your actual path if stored locally)
        $filePath = "Attachments/" . $attachment['name'];

        // Check if the file exists locally
        if (file_exists($filePath)) {
            $fileUrl = $filePath; // Serve from the local server
        } else {
            // GitHub raw URL fallback
            $fileUrl = "https://raw.githubusercontent.com/AbiAb1/DocMaP2/extra/Attachments/" . urlencode($attachment['name']);
        }

        echo '<div class="col-md-3">';
        echo '<a href="' . htmlspecialchars($fileUrl) . '" target="_blank" class="file">';
        echo '<span>' . htmlspecialchars($displayName) . '</span>';
        echo '<div class="pin-icon" >';
        echo '<i class="bx bx-paperclip"></i>';
        echo '</div>';
        echo '</a>';
        echo '</div>';
    }
    echo '</div>';
} else {
    echo '<p>No attachments available.</p>';
}
?>

                    </div>
                </div>
           
                <div class="tab-pane fade" id="teacher" role="tabpanel" aria-labelledby="teacher-tab">
                    <?php if (!empty($users)): ?>
                        <div class="row">
                            <!-- User and Attachments Columns -->
                            <div class="col-md-12">
                                <!-- Header Row -->
                                <div class="row">
                                    <!-- User List Column Header -->
                                    <div class="col-md-4 user-list" style="border-right: 1px solid #e0e0e0; padding-right: 20px;">
                                        
                                    </div>

                                    <!-- Attachments Column Header with Counts -->
                                    <div class="col-md-5 user-attachments">
                                        <div style="display: flex; justify-content: space-between; text-align: right;">
                                            <div style="display: flex; flex-direction: column; align-items: center; ">
                                                <div style="font-size: 24px; font-weight: bold; margin-bottom: 0; ">
                                                    <?php echo isset($assignedCount) ? $assignedCount : 'N/A'; ?>
                                                </div>
                                                <span style="color: #888; margin-top: 0;">Assigned</span>
                                            </div>

                                            <div style="display: flex; flex-direction: column; align-items: center; margin-right: 10px;">
                                                <div style="font-size: 24px; font-weight: bold; margin-bottom: 0;color:#006400;">
                                                    <?php echo isset($handedInCount) ? $handedInCount : 'N/A'; ?>
                                                </div>
                                                <span style="color: #888; margin-top: 0;">Submitted</span>
                                            </div>
                                            
                                            <div style="display: flex; flex-direction: column; align-items: center; ">
                                                <div style="font-size: 24px; font-weight: bold; margin-bottom: 0; color: #9b2035;">
                                                    <?php echo isset($missingCount) ? $missingCount : 'N/A'; ?>
                                                </div>
                                                <span style="color: #888; margin-top: 0;">Missing</span>
                                            </div>

                                            <div style="display: flex; flex-direction: column; align-items: center; ">
                                                <div style="font-size: 24px; font-weight: bold; margin-bottom: 0; color: #006400;">
                                                    <?php echo isset($approvedCount) ? $approvedCount : 'N/A'; ?>
                                                </div>
                                                <span style="color: #888; margin-top: 0;">Approved</span>
                                            </div>
                                            <div style="display: flex; flex-direction: column; align-items: center; ">
                                                <div style="font-size: 24px; font-weight: bold; margin-bottom: 0; color: #9b2035;">
                                                    <?php echo isset($rejectedCount) ? $rejectedCount : 'N/A'; ?>
                                                </div>
                                                <span style="color: #888; margin-top: 0;">Rejected</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Assigned Section -->
                                <h6 class="status-title">Assigned</h6>

                                <?php 
                                // Initialize an array to track displayed user IDs
                                $displayedUsers = [];

                                foreach ($users as $user): ?>
                                    <?php 
                                    // Check if the status is 'Assigned' and if the user hasn't been displayed yet
                                    if ($user['Status'] == 'Assigned' && !in_array($user['UserID'], $displayedUsers)): 
                                        // Add the UserID to the displayed array to ensure it is not displayed again
                                        $displayedUsers[] = $user['UserID'];
                                    ?>
                                        <div class="row" style="margin-bottom: 5px;">
                                            <!-- User List Column -->
                                            <div class="col-md-4 user-list" style="display: flex; align-items: center;">
                                                <!-- Display User Profile Picture -->
                                                <?php if (!empty($user['profile'])): ?>
                                                    <img src="../img/UserProfile/<?php echo htmlspecialchars($user['profile']); ?>" alt="Profile Image" style="width: 50px; height: 50px; border-radius: 50%; margin-right: 15px;">
                                                <?php else: ?>
                                                    <img src="../img/UserProfile/profile.jpg" alt="Default Profile Image" style="width: 50px; height: 50px; border-radius: 50%; margin-right: 15px;">
                                                <?php endif; ?>

                                                <!-- Display User Information -->
                                                <div style="flex-grow: 1;">
                                                    <strong><?php echo htmlspecialchars($user['fname'] . ' ' . $user['lname']); ?></strong>
                                                </div>
                                                <!-- Comment Icon -->
                                            </div>

                                            <!-- Attachments Column -->
                                            <div class="col-md-4 user-attachments">
                                                <span style="color: #888;">Not yet submitted.</span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>


                               <!-- Handed In Section -->
                                <?php 
                                $usersGroupedByID = [];
                                $hasHandedIn = false;

                                // Group users by their userID for the Handed In section
                                foreach ($users as $user) {
                                    if ($user['Status'] == 'Submitted') {
                                        $hasHandedIn = true;
                                        $usersGroupedByID[$user['UserID']][] = $user; // Grouping by userID
                                    }
                                }

                                if ($hasHandedIn): ?>
                                    <h6 class="status-title " style="margin-top: 20px;">Submitted</h6>
                                    <?php foreach ($usersGroupedByID as $userID => $documents): ?>
                                        <div class="row" style="margin-bottom: 20px;">
                                            <!-- User List Column -->
                                            <div class="col-md-4 user-list" style="display: flex; align-items: center;">
                                                <?php $user = $documents[0]; // Get the first document's user data to show profile ?>
                                                
                                                <!-- Display User Profile Picture -->
                                                <?php if (!empty($user['profile'])): ?>
                                                    <img src="../img/UserProfile/<?php echo htmlspecialchars($user['profile']); ?>" alt="Profile Image" style="width: 50px; height: 50px; border-radius: 50%; margin-right: 15px;">
                                                <?php else: ?>
                                                    <img src="../img/UserProfile/profile.jpg" alt="Default Profile Image" style="width: 50px; height: 50px; border-radius: 50%; margin-right: 15px;">
                                                <?php endif; ?>
                                
                                                <!-- Display User Information -->
                                                <div style="flex-grow: 1;">
                                                    <strong><?php echo htmlspecialchars($user['fname'] . ' ' . $user['lname']); ?></strong>
                                                    
                                                </div>
                                                
                                
                                                <!-- Comment Icon -->
                                                <span class="document-icon-container">
                                                    <i 
                                                        class="bx bx-message-dots comment-icon" 
                                                        data-toggle="modal" 
                                                        data-target="#commentModal<?php echo $userID; ?>" 
                                                        style="cursor: pointer;">
                                                    </i>
                                                </span>
                                                <i class="bx bx-chevron-down" data-toggle="dropdown" style="cursor: pointer; margin-left: 15px;font-size: 24px;"></i>
                                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton" style="width: auto; padding: 5px 10px; min-width: 100px;">
                                                    <a class="dropdown-item" href="#" 
                                                        data-toggle="modal" 
                                                        data-target="#approveModal<?php echo $userID; ?>" 
                                                        data-task-user-id="<?php echo $user['Task_User_ID']; ?>" 
                                                        data-content-id="<?php echo $user['ContentID']; ?>" 
                                                        data-task-id="<?php echo $user['TaskID']; ?>">Approve</a>
                                                    <a class="dropdown-item" href="#" 
                                                        data-toggle="modal" 
                                                        data-target="#rejectModal<?php echo $userID; ?>" 
                                                        data-task-user-id="<?php echo $user['Task_User_ID']; ?>" 
                                                        data-content-id="<?php echo $user['ContentID']; ?>" 
                                                        data-task-id="<?php echo $user['TaskID']; ?>">Reject</a>                                                
                                                </div>

                                            </div>
                                
                                            <!-- Comment Modal for Each User -->
                                            <div class="modal fade" id="commentModal<?php echo $userID; ?>" tabindex="-1" role="dialog" aria-labelledby="commentModalLabel<?php echo $userID; ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="commentModalLabel<?php echo $userID; ?>">
                                                                Comments from 
                                                                <span style="font-weight: bold; color: #9b2035;">
                                                                    <?php echo htmlspecialchars($user['fname'] . ' ' . $user['lname']); ?>
                                                                </span>
                                                            </h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <h6>Conversation:</h6>
                                                            <!-- Comments Container -->
                                                            <div id="commentsContainer<?php echo $userID; ?>" class="comments-container" style="margin-bottom: 15px;">
                                                                <!-- Comments will be dynamically loaded here -->
                                                            </div>

                                                            <!-- Comment Form -->
                                                            <form id="commentForm<?php echo $userID; ?>">
                                                                <div class="mb-3">
                                                                    <label for="commentText<?php echo $userID; ?>" class="form-label">Add a comment</label>
                                                                    <textarea class="form-control" id="commentText<?php echo $userID; ?>" name="comment" rows="3" placeholder = "Add your comment here." required></textarea>
                                                                </div>
                                                                <!-- Hidden Fields to Pass IDs -->
                                                                <input type="hidden" name="task_id" value="<?php echo htmlspecialchars($task_id); ?>" />
                                                                <input type="hidden" name="content_id" value="<?php echo isset($_GET['content_id']) ? htmlspecialchars($_GET['content_id']) : ''; ?>" />
                                                                <input type="hidden" name="outgoing_id" value="<?php echo $userID; ?>" />
                                                                <button type="submit" class="btn btn-primary">Send</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Approve Modal for Each User -->
                                            <div class="modal fade" id="approveModal<?php echo $userID; ?>" tabindex="-1" role="dialog" aria-labelledby="approveModalLabel<?php echo $userID; ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="approveModalLabel<?php echo $userID; ?>">
                                                                Approve Document 
                                                                <span style="font-weight: bold; color: #9b2035;">
                                                                    <?php echo htmlspecialchars($user['fname'] . ' ' . $user['lname']); ?>
                                                                </span>
                                                            </h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Are you sure you want to approve the submission?</p>

                                                            <!-- Files Display Section -->
                                                            <h6>Files:</h6>
                                                            <div class="row" style="margin-left: 10px; margin-right: 10px;">
                                                                <?php foreach ($documents as $doc): ?>
                                                                    <?php if (!empty($doc['file_name'])): ?>
                                                                        <div class="col-md-12 document-container" style="margin-bottom: 15px;">
                                                                            <span class="document-name" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; display: inline-block;">
                                                                                <?php echo htmlspecialchars($displayName); ?>
                                                                            </span>
                                                                            <span class="document-icon-container" style="margin-left: 15px;">
                                                                                <i class="bx bxs-file document-icon"></i>
                                                                            </span>
                                                                        </div>
                                                                    <?php else: ?>
                                                                        <div class="col-md-12" style="margin-bottom: 15px;">
                                                                            <span style="color: #888;">No documents submitted</span>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                <?php endforeach; ?>
                                                            </div>

                                                            <!-- Approval Comment Textarea -->
                                                            <form id="approveForm<?php echo $userID; ?>">
                                                                <div class="mb-3">
                                                                    <label for="approveComment<?php echo $userID; ?>" class="form-label">Add a Comment (Optional):</label>
                                                                    <textarea class="form-control" id="approveComment<?php echo $userID; ?>" rows="3" name="approveComment"></textarea>
                                                                </div>

                                                                <!-- Hidden Fields -->
                                                                <input type="hidden" name="taskUserID" value="<?php echo $user['Task_User_ID']; ?>">
                                                                <input type="hidden" name="contentID" value="<?php echo $user['ContentID']; ?>">
                                                                <input type="hidden" name="taskID" value="<?php echo $user['TaskID']; ?>">

                                                                <button type="submit" class="btn btn-success">Approve</button>
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Reject Modal for Each User -->
                                            <div class="modal fade" id="rejectModal<?php echo $userID; ?>" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel<?php echo $userID; ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="rejectModalLabel<?php echo $userID; ?>">Reject Document </h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Are you sure you want to reject the submission from 
                                                                <strong><?php echo htmlspecialchars($user['fname'] . ' ' . $user['lname']); ?></strong>?
                                                            </p>

                                                            <!-- Files Display Section -->
                                                            <h6>Files:</h6>
                                                            <div class="row" style="margin-left: 10px; margin-right: 10px;">
                                                                <?php foreach ($documents as $doc): ?>
                                                                    <?php if (!empty($doc['file_name'])): ?>
                                                                        <div class="col-md-12 document-container" style="margin-bottom: 15px;">
                                                                            <span class="document-name" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; display: inline-block;">
                                                                                <?php echo htmlspecialchars($displayName); ?>
                                                                            </span>
                                                                            <span class="document-icon-container" style="margin-left: 15px;">
                                                                                <i class="bx bxs-file document-icon"></i>
                                                                            </span>
                                                                        </div>
                                                                    <?php else: ?>
                                                                        <div class="col-md-12" style="margin-bottom: 15px;">
                                                                            <span style="color: #888;">No documents submitted</span>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                <?php endforeach; ?>
                                                            </div>

                                                            <!-- Comment Textarea -->
                                                            <form id="rejectForm<?php echo $userID; ?>">
                                                                <div class="mb-3">
                                                                    <label for="rejectComment<?php echo $userID; ?>" class="form-label">Add a Comment (Optional):</label>
                                                                    <textarea class="form-control" id="rejectComment<?php echo $userID; ?>" rows="3" name="rejectComment"></textarea>
                                                                </div>

                                                                <!-- Hidden Fields -->
                                                                <input type="hidden" name="taskUserID" value="<?php echo $user['Task_User_ID']; ?>">
                                                                <input type="hidden" name="contentID" value="<?php echo $user['ContentID']; ?>">
                                                                <input type="hidden" name="taskID" value="<?php echo $user['TaskID']; ?>">

                                                                <button type="submit" class="btn btn-danger">Reject</button>
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                
                                            <!-- Attachments Column -->
                                            <div class="col-md-8 user-attachments">
                                                <div class="row">
                                                    <?php foreach ($documents as $index => $doc): ?>
                                                        <?php if (!empty($doc['file_name'])): ?>
                                                            <?php 
                                                                // Clean up document display name
                                                                $displayName = preg_replace('/^\d+_/', '', $doc['file_name']);
                                                            ?>
                                                            <div class="col-md-4 document-container" style="margin-bottom: 10px;">
                                                                <a href="../Documents/<?php echo htmlspecialchars($doc['file_name']); ?>" target="_blank" class="document-link" style="display: flex; align-items: center;">
                                                                    <span class="document-name" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; display: inline-block;">
                                                                        <?php echo htmlspecialchars($displayName); ?>
                                                                    </span>
                                                                    <span class="document-icon-container" style="margin-left: 15px;">
                                                                        <i class="bx bxs-file document-icon"></i>
                                                                    </span>
                                                                </a>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="col-md-4" style="margin-bottom: 10px;  margin-left:20px;">
                                                                <span style="color: #888;">No documents submitted</span>
                                                            </div>
                                                        <?php endif; ?>
                                
                                                        <?php 
                                                        // Insert a row break after every 3 documents
                                                        if (($index + 1) % 3 == 0): ?>
                                                            </div><div class="row">
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                


                                <!-- Missing Section -->
                                <?php $hasMissing = false; ?>
                                <?php foreach ($users as $user): ?>
                                    <?php if ($user['Status'] == 'Missing'): ?>
                                        <?php $hasMissing = true; break; ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>

                                <?php if ($hasMissing): ?>
                                    <h6 class="status-title" style="margin-top: 20px;">Missing</h6>
                                    <?php foreach ($users as $user): ?>
                                        <?php if ($user['Status'] == 'Missing'): ?>
                                            <div class="row" style="margin-bottom: 20px;">
                                                <!-- User List Column -->
                                                <div class="col-md-4 user-list" style="display: flex; align-items: center;">
                                                    <!-- Display User Profile Picture -->
                                                    <?php if (!empty($user['profile'])): ?>
                                                        <img src="../img/UserProfile/<?php echo htmlspecialchars($user['profile']); ?>" alt="Profile Image" style="width: 50px; height: 50px; border-radius: 50%; margin-right: 15px;">
                                                    <?php else: ?>
                                                        <img src="../img/UserProfile/profile.jpg" alt="Default Profile Image" style="width: 50px; height: 50px; border-radius: 50%; margin-right: 15px;">
                                                    <?php endif; ?>

                                                    <!-- Display User Information -->
                                                    <div style="flex-grow: 1;">
                                                        <strong><?php echo htmlspecialchars($user['fname'] . ' ' . $user['lname']); ?></strong>
                                                    </div>
                                                </div>

                                                <!-- Attachments Column -->
                                                <div class="col-md-4 user-attachments">
                                                    <span style="color: #888;">No documents submitted</span>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                                <!-- Approved Section -->
                                <?php 
                                $usersGroupedByID = [];
                                $hasApproved = false;

                                // Group users by their userID for the Approved section
                                foreach ($users as $user) {
                                    if ($user['Status'] == 'Approved') {
                                        $hasApproved = true;
                                        $usersGroupedByID[$user['UserID']][] = $user; // Grouping by userID
                                    }
                                }

                                if ($hasApproved): ?>
                                    <h6 class="status-title " style="margin-top: 20px;">Approved</h6>
                                    <?php foreach ($usersGroupedByID as $userID => $documents): ?>
                                        <div class="row" style="margin-bottom: 20px;">
                                            <!-- User List Column -->
                                            <div class="col-md-4 user-list" style="display: flex; align-items: center;">
                                                <?php $user = $documents[0]; // Get the first document's user data to show profile ?>
                                                
                                                <!-- Display User Profile Picture -->
                                                <?php if (!empty($user['profile'])): ?>
                                                    <img src="../img/UserProfile/<?php echo htmlspecialchars($user['profile']); ?>" alt="Profile Image" style="width: 50px; height: 50px; border-radius: 50%; margin-right: 15px;">
                                                <?php else: ?>
                                                    <img src="../img/UserProfile/profile.jpg" alt="Default Profile Image" style="width: 50px; height: 50px; border-radius: 50%; margin-right: 15px;">
                                                <?php endif; ?>

                                                <!-- Display User Information -->
                                                <div style="flex-grow: 1;">
                                                    <strong><?php echo htmlspecialchars($user['fname'] . ' ' . $user['lname']); ?></strong>
                                                    
                                                </div>
                                                

                                                <!-- Comment Icon -->
                                                <span class="document-icon-container">
                                                    <i 
                                                        class="bx bx-message-dots comment-icon" 
                                                        data-toggle="modal" 
                                                        data-target="#commentModal<?php echo $userID; ?>" 
                                                        style="cursor: pointer;">
                                                    </i>
                                                </span>
                                                <i class="bx bx-chevron-down" data-toggle="dropdown" style="cursor: pointer; margin-left: 15px;font-size: 24px;"></i>
                                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton" style="width: auto; padding: 5px 10px; min-width: 100px;">
                                                
                                                <a class="dropdown-item" href="#" 
                                                        data-toggle="modal" 
                                                        data-target="#rejectModal<?php echo $userID; ?>" 
                                                        data-task-user-id="<?php echo $user['Task_User_ID']; ?>" 
                                                        data-content-id="<?php echo $user['ContentID']; ?>" 
                                                        data-task-id="<?php echo $user['TaskID']; ?>">Reject</a>                                                  </div>

                                            </div>

                                            <!-- Comment Modal for Each User -->
                                            <div class="modal fade" id="commentModal<?php echo $userID; ?>" tabindex="-1" role="dialog" aria-labelledby="commentModalLabel<?php echo $userID; ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="commentModalLabel<?php echo $userID; ?>">
                                                                Comments from 
                                                                <span style="font-weight: bold; color: #9b2035;">
                                                                    <?php echo htmlspecialchars($user['fname'] . ' ' . $user['lname']); ?>
                                                                </span>
                                                            </h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <h6>Conversation:</h6>
                                                            <!-- Comments Container -->
                                                            <div id="commentsContainer<?php echo $userID; ?>" class="comments-container" style="margin-bottom: 15px;">
                                                                <!-- Comments will be dynamically loaded here -->
                                                            </div>

                                                            <!-- Comment Form -->
                                                            <form id="commentForm<?php echo $userID; ?>">
                                                                <div class="mb-3">
                                                                    <label for="commentText<?php echo $userID; ?>" class="form-label">Add a comment</label>
                                                                    <textarea class="form-control" id="commentText<?php echo $userID; ?>" name="comment" rows="3" placeholder = "Add your comment here." required></textarea>
                                                                </div>
                                                                <!-- Hidden Fields to Pass IDs -->
                                                                <input type="hidden" name="task_id" value="<?php echo htmlspecialchars($task_id); ?>" />
                                                                <input type="hidden" name="content_id" value="<?php echo isset($_GET['content_id']) ? htmlspecialchars($_GET['content_id']) : ''; ?>" />
                                                                <input type="hidden" name="outgoing_id" value="<?php echo $userID; ?>" />
                                                                <button type="submit" class="btn btn-primary">Send</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Approve Modal for Each User -->
                                        

                                            <!-- Reject Modal for Each User -->
                                            <div class="modal fade" id="rejectModal<?php echo $userID; ?>" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel<?php echo $userID; ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="rejectModalLabel<?php echo $userID; ?>">Reject Document </h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Are you sure you want to reject the submission from 
                                                                <strong><?php echo htmlspecialchars($user['fname'] . ' ' . $user['lname']); ?></strong>?
                                                            </p>

                                                            <!-- Files Display Section -->
                                                            <h6>Files:</h6>
                                                            <div class="row" style="margin-left: 10px; margin-right: 10px;">
                                                                <?php foreach ($documents as $doc): ?>
                                                                    <?php if (!empty($doc['file_name'])): ?>
                                                                        <div class="col-md-12 document-container" style="margin-bottom: 15px;">
                                                                            <span class="document-name" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; display: inline-block;">
                                                                                <?php echo htmlspecialchars($displayName); ?>
                                                                            </span>
                                                                            <span class="document-icon-container" style="margin-left: 15px;">
                                                                                <i class="bx bxs-file document-icon"></i>
                                                                            </span>
                                                                        </div>
                                                                    <?php else: ?>
                                                                        <div class="col-md-12" style="margin-bottom: 15px;">
                                                                            <span style="color: #888;">No documents submitted</span>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                <?php endforeach; ?>
                                                            </div>

                                                            <!-- Comment Textarea -->
                                                            <form id="rejectForm<?php echo $userID; ?>">
                                                                <div class="mb-3">
                                                                    <label for="rejectComment<?php echo $userID; ?>" class="form-label">Add a Comment (Optional):</label>
                                                                    <textarea class="form-control" id="rejectComment<?php echo $userID; ?>" rows="3" name="rejectComment"></textarea>
                                                                </div>

                                                                <!-- Hidden Fields -->
                                                                <input type="hidden" name="taskUserID" value="<?php echo $user['Task_User_ID']; ?>">
                                                                <input type="hidden" name="contentID" value="<?php echo $user['ContentID']; ?>">
                                                                <input type="hidden" name="taskID" value="<?php echo $user['TaskID']; ?>">

                                                                <button type="submit" class="btn btn-danger">Reject</button>
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>


                                            <!-- Attachments Column -->
                                            <div class="col-md-8 user-attachments">
                                                <div class="row">
                                                    <?php foreach ($documents as $index => $doc): ?>
                                                        <?php if (!empty($doc['file_name'])): ?>
                                                            <?php 
                                                                // Clean up document display name
                                                                $displayName = preg_replace('/^\d+_/', '', $doc['file_name']);
                                                            ?>
                                                            <div class="col-md-4 document-container" style="margin-bottom: 10px;">
                                                                <a href="../Documents/<?php echo htmlspecialchars($doc['file_name']); ?>" target="_blank" class="document-link" style="display: flex; align-items: center;">
                                                                    <span class="document-name" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; display: inline-block;">
                                                                        <?php echo htmlspecialchars($displayName); ?>
                                                                    </span>
                                                                    <span class="document-icon-container" style="margin-left: 15px;">
                                                                        <i class="bx bxs-file document-icon"></i>
                                                                    </span>
                                                                </a>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="col-md-4" style="margin-bottom: 10px;  margin-left:20px;">
                                                                <span style="color: #888;">No documents submitted</span>
                                                            </div>
                                                        <?php endif; ?>

                                                        <?php 
                                                        // Insert a row break after every 3 documents
                                                        if (($index + 1) % 3 == 0): ?>
                                                            </div><div class="row">
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                                <?php 
                                $usersGroupedByID = [];
                                $hasRejected = false;

                                // Group users by their userID for the Handed In section
                                foreach ($users as $user) {
                                    if ($user['Status'] == 'Rejected') {
                                        $hasRejected = true;
                                        $usersGroupedByID[$user['UserID']][] = $user; // Grouping by userID
                                    }
                                }

                                if ($hasRejected): ?>
                                    <h6 class="status-title " style="margin-top: 20px;">Rejected</h6>
                                    <?php foreach ($usersGroupedByID as $userID => $documents): ?>
                                        <div class="row" style="margin-bottom: 20px;">
                                            <!-- User List Column -->
                                            <div class="col-md-4 user-list" style="display: flex; align-items: center;">
                                                <?php $user = $documents[0]; // Get the first document's user data to show profile ?>
                                                
                                                <!-- Display User Profile Picture -->
                                                <?php if (!empty($user['profile'])): ?>
                                                    <img src="../img/UserProfile/<?php echo htmlspecialchars($user['profile']); ?>" alt="Profile Image" style="width: 50px; height: 50px; border-radius: 50%; margin-right: 15px;">
                                                <?php else: ?>
                                                    <img src="../img/UserProfile/profile.jpg" alt="Default Profile Image" style="width: 50px; height: 50px; border-radius: 50%; margin-right: 15px;">
                                                <?php endif; ?>
                                
                                                <!-- Display User Information -->
                                                <div style="flex-grow: 1;">
                                                    <strong><?php echo htmlspecialchars($user['fname'] . ' ' . $user['lname']); ?></strong>
                                                    
                                                </div>
                                                
                                
                                                <!-- Comment Icon -->
                                                <span class="document-icon-container">
                                                    <i 
                                                        class="bx bx-message-dots comment-icon" 
                                                        data-toggle="modal" 
                                                        data-target="#commentModal<?php echo $userID; ?>" 
                                                        style="cursor: pointer;">
                                                    </i>
                                                </span>
                                                <i class="bx bx-chevron-down" data-toggle="dropdown" style="cursor: pointer; margin-left: 15px;font-size: 24px;"></i>
                                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton" style="width: auto; padding: 5px 10px; min-width: 100px;">
                                                    <a class="dropdown-item" href="#" 
                                                        data-toggle="modal" 
                                                        data-target="#approveModal<?php echo $userID; ?>" 
                                                        data-task-user-id="<?php echo $user['Task_User_ID']; ?>" 
                                                        data-content-id="<?php echo $user['ContentID']; ?>" 
                                                        data-task-id="<?php echo $user['TaskID']; ?>">Approve</a>
                                                </div>

                                            </div>
                                
                                            <!-- Comment Modal for Each User -->
                                            <div class="modal fade" id="commentModal<?php echo $userID; ?>" tabindex="-1" role="dialog" aria-labelledby="commentModalLabel<?php echo $userID; ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="commentModalLabel<?php echo $userID; ?>">
                                                                Comments from 
                                                                <span style="font-weight: bold; color: #9b2035;">
                                                                    <?php echo htmlspecialchars($user['fname'] . ' ' . $user['lname']); ?>
                                                                </span>
                                                            </h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <h6>Conversation:</h6>
                                                            <!-- Comments Container -->
                                                            <div id="commentsContainer<?php echo $userID; ?>" class="comments-container" style="margin-bottom: 15px;">
                                                                <!-- Comments will be dynamically loaded here -->
                                                            </div>

                                                            <!-- Comment Form -->
                                                            <form id="commentForm<?php echo $userID; ?>">
                                                                <div class="mb-3">
                                                                    <label for="commentText<?php echo $userID; ?>" class="form-label">Add a comment</label>
                                                                    <textarea class="form-control" id="commentText<?php echo $userID; ?>" name="comment" rows="3" placeholder = "Add your comment here." required></textarea>
                                                                </div>
                                                                <!-- Hidden Fields to Pass IDs -->
                                                                <input type="hidden" name="task_id" value="<?php echo htmlspecialchars($task_id); ?>" />
                                                                <input type="hidden" name="content_id" value="<?php echo isset($_GET['content_id']) ? htmlspecialchars($_GET['content_id']) : ''; ?>" />
                                                                <input type="hidden" name="outgoing_id" value="<?php echo $userID; ?>" />
                                                                <button type="submit" class="btn btn-primary">Send</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Approve Modal for Each User -->
                                            <div class="modal fade" id="approveModal<?php echo $userID; ?>" tabindex="-1" role="dialog" aria-labelledby="approveModalLabel<?php echo $userID; ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="approveModalLabel<?php echo $userID; ?>">
                                                                Approve Document 
                                                                <span style="font-weight: bold; color: #9b2035;">
                                                                    <?php echo htmlspecialchars($user['fname'] . ' ' . $user['lname']); ?>
                                                                </span>
                                                            </h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Are you sure you want to approve the submission?</p>

                                                            <!-- Files Display Section -->
                                                            <h6>Files:</h6>
                                                            <div class="row" style="margin-left: 10px; margin-right: 10px;">
                                                                <?php foreach ($documents as $doc): ?>
                                                                    <?php if (!empty($doc['file_name'])): ?>
                                                                        <div class="col-md-12 document-container" style="margin-bottom: 15px;">
                                                                            <span class="document-name" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; display: inline-block;">
                                                                                <?php echo htmlspecialchars($doc['file_name']); ?>
                                                                            </span>
                                                                            <span class="document-icon-container" style="margin-left: 15px;">
                                                                                <i class="bx bxs-file document-icon"></i>
                                                                            </span>
                                                                        </div>
                                                                    <?php else: ?>
                                                                        <div class="col-md-12" style="margin-bottom: 15px;">
                                                                            <span style="color: #888;">No documents submitted</span>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                <?php endforeach; ?>
                                                            </div>

                                                            <!-- Approval Comment Textarea -->
                                                            <form id="approveForm<?php echo $userID; ?>">
                                                                <div class="mb-3">
                                                                    <label for="approveComment<?php echo $userID; ?>" class="form-label">Add a Comment (Optional):</label>
                                                                    <textarea class="form-control" id="approveComment<?php echo $userID; ?>" rows="3" name="approveComment"></textarea>
                                                                </div>

                                                                <!-- Hidden Fields -->
                                                                <input type="hidden" name="taskUserID" value="<?php echo $user['Task_User_ID']; ?>">
                                                                <input type="hidden" name="contentID" value="<?php echo $user['ContentID']; ?>">
                                                                <input type="hidden" name="taskID" value="<?php echo $user['TaskID']; ?>">

                                                                <button type="submit" class="btn btn-success">Approve</button>
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>                               
                                            <!-- Attachments Column -->
                                            <div class="col-md-8 user-attachments">
                                                <div class="row">
                                                    <?php foreach ($documents as $index => $doc): ?>
                                                        <?php if (!empty($doc['file_name'])): ?>
                                                            <?php 
                                                                // Clean up document display name
                                                                $displayName = preg_replace('/^\d+_/', '', $doc['file_name']);
                                                            ?>
                                                            <div class="col-md-4 document-container" style="margin-bottom: 10px;">
                                                                <a href="../Documents/<?php echo htmlspecialchars($doc['file_name']); ?>" target="_blank" class="document-link" style="display: flex; align-items: center;">
                                                                    <span class="document-name" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; display: inline-block;">
                                                                        <?php echo htmlspecialchars($displayName); ?>
                                                                    </span>
                                                                    <span class="document-icon-container" style="margin-left: 15px;">
                                                                        <i class="bx bxs-file document-icon"></i>
                                                                    </span>
                                                                </a>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="col-md-4" style="margin-bottom: 10px;  margin-left:20px;">
                                                                <span style="color: #888;">No documents submitted</span>
                                                            </div>
                                                        <?php endif; ?>
                                
                                                        <?php 
                                                        // Insert a row break after every 3 documents
                                                        if (($index + 1) % 3 == 0): ?>
                                                            </div><div class="row">
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                

                                

                            </div>
                        </div>
                    <?php else: ?>
                        <p>No users associated with this task.</p>
                    <?php endif; ?>
                </div>




            </div>
            
        </main>
        <!-- Comment Modal -->
        


        <!-- MAIN -->
    </section>

    <!-- =========== Scripts =========  -->
    <script src="assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>




<!-- jQuery 
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>-->

<!-- Bootstrap JS -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>


<script>
    document.addEventListener("DOMContentLoaded", function() {
        const generateReportBtn = document.getElementById("generateReportBtn");
        const myTab = document.getElementById("myTab");

        myTab.addEventListener("click", function(event) {
            if (event.target.id === "teacher-tab") {
                generateReportBtn.style.display = "block";  // Show button when "Teacher Output" is active
            } else {
                generateReportBtn.style.display = "none";   // Hide button on other tabs
            }
        });

        generateReportBtn.addEventListener("click", function() {
            const taskID = generateReportBtn.getAttribute("data-taskid");
            const contentID = generateReportBtn.getAttribute("data-contentid");
            console.log("TaskID:", taskID);  // Check if the taskID is fetched correctly
            console.log("ContentID:", contentID);  // Check if the contentID is fetched correctly

            // Fetch task details using taskID and contentID
            fetchTaskDetails(taskID, contentID);
        });

        // New AJAX function to fetch task details
        function fetchTaskDetails(taskID, contentID) {
            $.ajax({
                url: 'getTaskDetails.php',
                method: 'GET',
                data: { taskID: taskID, contentID: contentID },  // Ensure data is passed correctly
                success: function(data) {
                    const taskData = JSON.parse(data);
                    console.log(taskData);  // Check response in the console

                    // Prepare additional report details with task info
                    const taskTitle = taskData.taskTitle || "No Task Title";
                    const feedContentTitleCaption = taskData.feedContentTitleCaption || "No Title + Caption";
                    const deptName = taskData.deptName || "No Department Name";

                    // Call generateReport function with task info
                    generateReport(taskID, contentID, taskTitle, feedContentTitleCaption, deptName);
                },
                error: function(xhr, status, error) {
                    alert("Error fetching task details: " + error);
                }
            });
        }

        // Function to generate the report and open it in a new window
        function generateReport(taskID, contentID, taskTitle, feedContentTitleCaption, deptName) {
            $.ajax({
                url: 'getSchoolDetails.php',
                method: 'GET',
                success: function(data) {
                    console.log(data); // Check the response in the console
                    console.log("../img/Logo/" + data.Logo);

                    // Prepare the logo and school details for the print view
                    var logo = data.Logo ? '<img src="../img/Logo/' + data.Logo + '" style="width: 130px; height: auto; margin-right:20px;" />' : '<p>No Logo Available</p>';
                    var schoolDetails = ` 
                        <div class="header-content">
                            <div class="logo">${logo}</div>
                            <div class="school-details">
                                <p>Republic of the ${data.Country}</p>
                                <p>${data.Organization}</p>
                                <p>${data.Region}</p>
                                <h2 style="font-weight: bold; font-size: 1.5em;">${data.Name}</h2>
                                <p>${data.Address}</p>
                                <p>School ID: ${data.School_ID}</p>
                            </div>
                        </div>
                        <hr/>
                        <div class="additional-titles" style="text-align: center; font-family: 'Times New Roman', serif;">
                            <h3>Submission Report</h3>
                            <h4>${feedContentTitleCaption}</h4>
                            <h4>Department: ${deptName}</h4>
                        </div>
                    `;

                    // Prepare the report HTML content (without opening a new window)
                    const reportContent = `
                        <html>
                        <head>
                            <title>Report for Task ${taskTitle}</title>
                            <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
                            <style>
                                body { font-family: Arial, sans-serif; padding: 20px; }
                                h1 { text-align: center; }
                                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                                th, td { padding: 8px; text-align: center; border: 1px solid #ddd; } /* Centering all cells */
                                th { background-color: #f2f2f2; }
                                .header-content { display: flex; justify-content: center; align-items: center; margin-bottom: 20px; }
                                .logo {}
                                .school-details { text-align: center; font-family: 'Times New Roman', serif; }
                                .school-details h2 { font-size: 1.5em; font-weight: bold; }
                                .school-details p { margin: 0; }
                                hr { border-top: 1px solid black; width: 100%; margin-top: 10px; }
                                .additional-titles { margin-top: 10px; margin-bottom: 50px; }
                                .additional-titles h3, .additional-titles h4 { margin: 5px 0; font-weight: bold; }
                                .signature-section { margin-top: 50px; text-align: center; display: flex; justify-content: space-between; align-items: flex-start; }
                                .signature-section div { flex: 1; text-align: center; }
                                .watermark { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); opacity: 0.1; pointer-events: none; z-index: -1; }
                            </style>
                        </head>
                        <body>
                            ${schoolDetails}
                            <h1>Report for "${taskTitle}"</h1>
                            <table>
                                <thead>
                                    <tr>
                                        <th>#</th> <!-- Change UserID column to # -->
                                        <th>First Name</th>
                                        <th>Last Name</th>
                                        <th>Document(s)</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${generateTableRows()}
                                </tbody>
                            </table>
                        </body>
                        </html>
                    `;

                    // Open a new window and print the content
                    const printWindow = window.open('', '', 'width=900,height=700');
                    printWindow.document.open();
                    printWindow.document.write(reportContent);
                    printWindow.document.close();

                    // Wait for content to load before triggering print
                    setTimeout(function() {
                        printWindow.print();
                        printWindow.close();
                    }, 500); // Delay to ensure content is fully loaded
                },
                error: function(xhr, status, error) {
                    alert("Error fetching school details: " + error);
                }
            });
        }

        // Function to generate table rows dynamically with sequential numbering
        function generateTableRows() {
            const users = <?php echo json_encode($users); ?>; // PHP array from backend

            // Helper function to format the date in YYYY/MM/DD format with AM/PM time
            function formatDate(dateString) {
                const date = new Date(dateString);
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0'); // Months are zero-indexed
                const day = String(date.getDate()).padStart(2, '0');
                const hours = date.getHours();
                const minutes = String(date.getMinutes()).padStart(2, '0');
                const ampm = hours >= 12 ? 'PM' : 'AM';
                const hour12 = hours % 12 || 12; // Convert hours to 12-hour format
                const formattedTime = `${hour12}:${minutes} ${ampm}`;
                return `${year}/${month}/${day} ${formattedTime}`;
            }

            // Generate rows with an index column instead of UserID
            return users.map((user, index) => {
                let statusHTML = '';
                let color = '';
                let dateInfo = '';

                let fileName = user.file_name || "N/A";  // Ensure there's a fallback for missing file name
                fileName = fileName.replace(/^\d+_/, ''); // Removes leading digits and underscore

                if (user.Status === 'Assigned') {
                    color = 'grey';
                    statusHTML = `
                        <td style="color: ${color};">
                            Not yet submitted
                        </td>`;
                    dateInfo = ''; // No date for "Assigned" status
                } else if (user.Status === 'Approved') {
                    color = 'green';
                    statusHTML = `
                        <td style="color: ${color};">
                            Approved
                            <br><span style="color: grey; font-size: 0.8em;">Date Submitted: ${formatDate(user.SubmitDate)}</span>
                            <br><span style="color: grey; font-size: 0.8em;">Date Approved: ${formatDate(user.ApproveDate)}</span>
                        </td>`;
                } else if (user.Status === 'Rejected') {
                    color = 'red';
                    statusHTML = `
                        <td style="color: ${color};">
                            Rejected
                            <br><span style="color: grey; font-size: 0.8em;">Date Submitted: ${formatDate(user.SubmitDate)}</span>
                            <br><span style="color: grey; font-size: 0.8em;">Date Rejected: ${formatDate(user.RejectDate)}</span>
                        </td>`;
                } else if (user.Status === 'Submitted') {
                    color = 'orange';
                    statusHTML = `
                        <td style="color: ${color};">
                            For Approval
                            <br><span style="color: grey; font-size: 0.8em;">Date Submitted: ${formatDate(user.SubmitDate)}</span>
                        </td>`;
                }

                return `
                    <tr>
                        <td>${index + 1}</td> <!-- Sequential numbering starting from 1 -->
                        <td>${user.fname}</td>
                        <td>${user.lname}</td>
                        <td>${fileName}</td>
                        ${statusHTML}
                    </tr>
                `;
            }).join('');
        }
    });
</script>








<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ensure jQuery is loaded and Bootstrap's dropdown and modal are working
    $(document).ready(function() {
        // Initialize Bootstrap dropdown
        $('[data-toggle="dropdown"]').dropdown();

        // Handle the modal trigger (Approve button in dropdown)
        $('.dropdown-item').on('click', function() {
            const taskUserID = $(this).data('task-user-id');
            const contentID = $(this).data('content-id');
            const taskID = $(this).data('task-id');

            // You can access these data attributes to set values in the modal form
            $('#approveModal').find('input[name="taskUserID"]').val(taskUserID);
            $('#approveModal').find('input[name="contentID"]').val(contentID);
            $('#approveModal').find('input[name="taskID"]').val(taskID);
        });

        // Handling the form submission for approval
        const approveForms = document.querySelectorAll('[id^="approveForm"]');

        approveForms.forEach(form => {
            form.addEventListener('submit', function(event) {
                event.preventDefault();

                const comment = this.querySelector('textarea[name="approveComment"]').value;
                const taskUserID = this.querySelector('input[name="taskUserID"]').value;
                const contentID = this.querySelector('input[name="contentID"]').value;
                const taskID = this.querySelector('input[name="taskID"]').value;

                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'approve.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                xhr.onreadystatechange = function() {
                    if (xhr.readyState === XMLHttpRequest.DONE) {
                        if (xhr.status === 200) {
                            const response = JSON.parse(xhr.responseText);

                            if (response.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Approved!',
                                    text: response.message,
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    form.reset();
                                    $(form.closest('.modal')).modal('hide');
                                    $('.modal-backdrop').remove();
                                    document.body.classList.remove('modal-open');
                                    document.body.style.overflow = '';
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: response.message,
                                    confirmButtonText: 'Try Again'
                                });
                            }
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'An error occurred while processing your request.',
                                confirmButtonText: 'Try Again'
                            });
                        }
                    }
                };

                const params = `userID=${taskUserID}&contentID=${contentID}&taskID=${taskID}&comment=${comment}`;
                xhr.send(params);
            });
        });
    });
});
document.addEventListener('DOMContentLoaded', function() {
    const approveForms = document.querySelectorAll('[id^="rejectForm"]');

    approveForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();

            const comment = this.querySelector('textarea[name="rejectComment"]').value;
            const taskUserID = this.querySelector('input[name="taskUserID"]').value;
            const contentID = this.querySelector('input[name="contentID"]').value;
            const taskID = this.querySelector('input[name="taskID"]').value;

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'reject.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function() {
                if (xhr.readyState === XMLHttpRequest.DONE) {
                    if (xhr.status === 200) {
                        const response = JSON.parse(xhr.responseText);

                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Rejected!',
                                text: response.message,
                                confirmButtonText: 'OK'
                            }).then(() => {
                                form.reset();
                                $(form.closest('.modal')).modal('hide');
                                $('.modal-backdrop').remove();
                                document.body.classList.remove('modal-open');
                                document.body.style.overflow = '';
                                
                                // Trigger a refresh of the Teacher Output tab content
                                // Refresh the content inside the "Teacher Output" tab
                                let teacherTab = document.getElementById('teacher-tab');
                                if (teacherTab) {
                                    // Optional: Use AJAX to reload content or refresh the page in the same tab
                                    $(teacherTab).tab('show'); // Show the Teacher Output tab
                                    location.reload(); // Reload the content within the same tab
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: response.message,
                                confirmButtonText: 'Try Again'
                            });
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'An error occurred while processing your request.',
                            confirmButtonText: 'Try Again'
                        });
                    }
                }
            };

            const params = `userID=${taskUserID}&contentID=${contentID}&taskID=${taskID}&comment=${comment}`;
            xhr.send(params);
        });
    });
});

</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get all comment forms by their IDs
    const commentForms = document.querySelectorAll('[id^="commentForm"]');

    commentForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent default form submission

            const commentText = this.querySelector('textarea[name="comment"]').value;
            const taskId = this.querySelector('input[name="task_id"]').value;
            const contentId = this.querySelector('input[name="content_id"]').value;
            const outgoingId = this.querySelector('input[name="outgoing_id"]').value;

            // AJAX request to save comment
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'save_comment.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function() {
                if (xhr.readyState === XMLHttpRequest.DONE) {
                    if (xhr.status === 200) {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            // Show success alert using SweetAlert
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                confirmButtonText: 'OK'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    form.reset(); // Reset the form

                                    // Use jQuery to hide the modal
                                    $(form.closest('.modal')).modal('hide'); // Hide the modal

                                    // Remove the backdrop
                                    $('.modal-backdrop').remove(); // Explicitly remove the backdrop
                                    document.body.classList.remove('modal-open'); // Remove modal-open class from body
                                    document.body.style.overflow = ''; // Reset body overflow style
                                }
                            });
                        } else {
                            // Show error alert using SweetAlert
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: response.message,
                                confirmButtonText: 'OK'
                            });
                        }
                    } else {
                        // Handle error response
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'An error occurred while saving the comment.',
                            confirmButtonText: 'OK'
                        });
                    }
                }
            };

            // Send the data
            const params = `comment=${encodeURIComponent(commentText)}&task_id=${taskId}&content_id=${contentId}&outgoing_id=${outgoingId}`;
            xhr.send(params);
        });
    });

   // Ensure modal can be opened again
   const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function () {
            // Reset modal content if necessary
            const textarea = modal.querySelector('textarea[name="comment"]');
            if (textarea) {
                textarea.value = ''; // Clear comment
            }
        });
    });
});

document.addEventListener('DOMContentLoaded', function() {
    // Event listener to load comments when the modal opens
    document.querySelectorAll('[id^="commentModal"]').forEach(modal => {
        const userID = modal.id.replace('commentModal', '');
        $(modal).on('show.bs.modal', function() {
            loadComments(userID);
        });
    });

    function loadComments(userID) {
        const commentsContainer = document.getElementById(`commentsContainer${userID}`);
        commentsContainer.innerHTML = '<p>Loading comments...</p>';

        const taskId = document.querySelector(`[name="task_id"]`).value;
        const contentId = document.querySelector(`[name="content_id"]`).value;

        // AJAX request to fetch comments
        const xhr = new XMLHttpRequest();
        xhr.open('GET', `fetch_comments.php?task_id=${taskId}&content_id=${contentId}&user_id=${userID}`, true);

        xhr.onreadystatechange = function() {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        commentsContainer.innerHTML = ''; // Clear previous content
                        response.comments.forEach(comment => {
                            const commentHTML = `
                                <div class="comment-item" style="display: flex; align-items: flex-start; margin-bottom: 10px;">
                                    <img src="${comment.profile}" alt="${comment.fname} ${comment.lname}'s profile" style="width: 30px; height: 30px; border-radius: 50%; margin-right: 10px;">
                                    <div style="flex: 1;">
                                        <strong style="color: #9b2035;">${comment.fname} ${comment.lname}</strong>
                                        <span style="color: gray; font-size: 0.8em; margin-left: 5px;">&bull; ${comment.timestamp}</span> <!-- Dot icon and timestamp -->
                                        <p style="margin: 0;">${comment.Comment}</p>
                                    </div>
                                </div>
                            `;
                            commentsContainer.innerHTML += commentHTML;
                        });


                    } else {
                        commentsContainer.innerHTML = '<p>No comments available.</p>';
                    }
                } else {
                    commentsContainer.innerHTML = '<p>Error loading comments.</p>';
                }
            }
        };

        xhr.send();
    }
});


</script>





</body>
</html>
