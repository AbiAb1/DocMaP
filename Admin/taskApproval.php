<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    // Redirect to login if not authenticated
    header("Location: ../index.php");
    exit();
}

include 'connection.php'; // Include your database connection

// Function to fetch pending tasks along with user names
function getPendingTasks($conn) {
    $query = "
        SELECT tasks.TaskID, tasks.Title, tasks.Type, tasks.taskContent, tasks.DueDate, tasks.DueTime, tasks.Status, useracc.fname, useracc.lname, tasks.ApprovalStatus
        FROM tasks
        JOIN useracc ON tasks.UserID = useracc.UserID
        WHERE tasks.ApprovalStatus = 'Pending'
        ORDER BY 
            CASE 
                WHEN tasks.Status = 'Schedule' THEN 1
                ELSE 2
            END,
            tasks.TaskID ASC
    ";
    $result = $conn->query($query);
    $tasks = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $tasks[] = $row;
        }
    } else {
        error_log("Error fetching pending tasks: " . $conn->error);
    }

    return $tasks;
}

// Function to log messages to logfile.log
function write_log($message) {
    $logfile = 'logfile.log'; // Path to your log file
    $currentDate = date('Y-m-d H:i:s');
    $logMessage = "[{$currentDate}] - {$message}\n";
    file_put_contents($logfile, $logMessage, FILE_APPEND); // Append to logfile
}

// Function to send bulk SMS
function send_bulk_sms($conn, $ContentID, $notificationTitle, $Title, $DueDate, $DueTime) {
    $mobileQuery = $conn->prepare("
        SELECT ua.mobile, UPPER(CONCAT(ua.fname, ' ', ua.lname)) AS FullName 
        FROM usercontent uc
        JOIN useracc ua ON uc.UserID = ua.UserID
        WHERE uc.ContentID = ?
    ");
    if ($mobileQuery) {
        $mobileQuery->bind_param("i", $ContentID);
        $mobileQuery->execute();
        $mobileResult = $mobileQuery->get_result();

        if ($mobileResult->num_rows > 0) {
            $mobileNumbers = [];
            $messages = [];

            while ($row = $mobileResult->fetch_assoc()) {
                $mobileNumbers[] = $row['mobile']; // Add mobile number to the array
                // Pass the DueDate and DueTime to the SMS message
                $messages[] = "NEW TASK ALERT!\n\nHi " . $row['FullName'] . "! " . $notificationTitle . " \"" . $Title . "\" Due on " . $DueDate . " at " . $DueTime . ". Don't miss it! Have a nice day!";
            }

            // Create comma-separated list of mobile numbers
            $mobileNumbersList = implode(",", $mobileNumbers);

            // Log the message and mobile numbers
            write_log("Mobile numbers for ContentID $ContentID: $mobileNumbersList");
            write_log("Messages to be sent: " . implode(" | ", $messages));

            // Send SMS using Semaphore API (example)
            $api_url = "https://api.semaphore.co/api/v4/messages"; // Semaphore API URL
            $api_key = "d796c0e11273934ac9d789536133684a"; // Replace with your Semaphore API key

            foreach ($messages as $index => $message) {
                $number = $mobileNumbers[$index]; // Get the corresponding mobile number

                // Prepare POST data
                $postData = [
                    'apikey' => $api_key,
                    'number' => $number, // Individual number
                    'message' => $message
                ];

                // Initialize cURL session
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                // Execute cURL request
                $response = curl_exec($ch);
                if (curl_errno($ch)) {
                    write_log("Error sending SMS to number ($number): " . curl_error($ch));
                } else {
                    write_log("SMS sent successfully to number: $number");
                }
                curl_close($ch);
            }
        } else {
            write_log("No mobile numbers found for ContentID $ContentID");
        }
        $mobileQuery->close();
    } else {
        write_log("Error preparing mobile query: " . $conn->error);
    }
}

// Function to approve or reject tasks
function updateTaskStatus($conn, $taskIDs, $status) {
    $ids = implode(',', array_map('intval', $taskIDs));

    mysqli_begin_transaction($conn, MYSQLI_TRANS_START_READ_WRITE);

    $query = "UPDATE tasks SET ApprovalStatus = ? WHERE TaskID IN ($ids)";
    $stmt = mysqli_prepare($conn, $query);

    if ($stmt === false) {
        write_log("Error preparing statement: " . mysqli_error($conn));
        mysqli_rollback($conn);
        return false;
    }

    mysqli_stmt_bind_param($stmt, "s", $status);
    if (!mysqli_stmt_execute($stmt)) {
        write_log("Error executing statement: " . mysqli_stmt_error($stmt));
        mysqli_rollback($conn);
        return false;
    }

    if ($status === 'Approved') {
        foreach ($taskIDs as $TaskID) {
            // Fetch the task details
            $taskDetailsQuery = $conn->prepare("
                SELECT t.ContentID, t.UserID, t.Title, t.taskContent, t.DueDate, t.DueTime, ua.fname AS creatorName 
                FROM tasks t 
                JOIN useracc ua ON t.UserID = ua.UserID 
                WHERE t.TaskID = ?
            ");
            if ($taskDetailsQuery) {
                $taskDetailsQuery->bind_param("i", $TaskID);
                $taskDetailsQuery->execute();
                $taskDetails = $taskDetailsQuery->get_result()->fetch_assoc();

                $ContentID = $taskDetails['ContentID'];
                $creatorUserID = $taskDetails['UserID'];
                $creatorName = $taskDetails['creatorName'];
                $taskContent = $taskDetails['taskContent'];
                $taskTitle = $taskDetails['Title'];
                $DueDate = $taskDetails['DueDate'];
                $DueTime = $taskDetails['DueTime'];

                // Fetch associated users
                $userContentQuery = $conn->prepare("SELECT ua.UserID FROM usercontent uc 
                                                    JOIN useracc ua ON uc.UserID = ua.UserID 
                                                    WHERE uc.ContentID = ?");
                if ($userContentQuery) {
                    $userContentQuery->bind_param("i", $ContentID);
                    $userContentQuery->execute();
                    $userResult = $userContentQuery->get_result();
                    if ($userResult) {
                        $timestamp = date('Y-m-d H:i:s');
                        while ($row = $userResult->fetch_assoc()) {
                            $userInContentId = $row['UserID'];

                            // Insert into task_user table
                            $taskUserSql = "INSERT INTO task_user (ContentID, TaskID, UserID, Status, TimeStamp) VALUES (?, ?, ?, 'Assigned', ?)";
                            $taskUserStmt = $conn->prepare($taskUserSql);
                            if ($taskUserStmt) {
                                $taskUserStmt->bind_param("iiis", $ContentID, $TaskID, $userInContentId, $timestamp);
                                if (!$taskUserStmt->execute()) {
                                    write_log("Error inserting into task_user: " . $taskUserStmt->error);
                                    mysqli_rollback($conn);
                                    return false;
                                }
                                $taskUserStmt->close();
                            } else {
                                write_log("Error preparing task_user insert statement: " . $conn->error);
                                mysqli_rollback($conn);
                                return false;
                            }
                        }

                        // Fetch content title
                        $contentQuery = $conn->prepare("SELECT Title FROM feedcontent WHERE ContentID = ?");
                        if ($contentQuery) {
                            $contentQuery->bind_param("i", $ContentID);
                            $contentQuery->execute();
                            $contentResult = $contentQuery->get_result();
                            $contentTitle = $contentResult->num_rows > 0 ? $contentResult->fetch_assoc()['Title'] : "Unknown Content";

                            // Create notification
                            $notificationTitle = "$creatorName Posted a new Task! ($contentTitle)";
                            $notificationContent = "$taskTitle: $taskContent";
                            $status = 1;

                            $notifStmt = $conn->prepare("INSERT INTO notifications (UserID, TaskID, ContentID, Title, Content, Status, TimeStamp) VALUES (?, ?, ?, ?, ?, ?, ?)");
                            if ($notifStmt) {
                                $notifStmt->bind_param("ssssiss", $creatorUserID, $TaskID, $ContentID, $notificationTitle, $notificationContent, $status, $timestamp);
                                if (!$notifStmt->execute()) {
                                    write_log("Error inserting into notifications: " . $notifStmt->error);
                                    mysqli_rollback($conn);
                                    return false;
                                }
                                $notifID = $notifStmt->insert_id;
                            
                                // Additional notification for approval
                                $approvalNotificationTitle = "Your Task has been Approved!";
                                $approvalNotificationContent = "Task: $taskTitle has been approved by the administrator.";
                                $approvalNotifStmt = $conn->prepare("INSERT INTO notifications (UserID, TaskID, ContentID, Title, Content, Status, TimeStamp) VALUES (?, ?, ?, ?, ?, ?, ?)");
                                if ($approvalNotifStmt) {
                                    $approvalNotifStmt->bind_param("ssssiss", $creatorUserID, $TaskID, $ContentID, $approvalNotificationTitle, $approvalNotificationContent, $status, $timestamp);
                                    if ($approvalNotifStmt->execute()) {
                                        $approvalNotifID = $approvalNotifStmt->insert_id;
                                        $notifUserApprovalStmt = $conn->prepare("INSERT INTO notif_user (NotifID, UserID, Status, TimeStamp) VALUES (?, ?, ?, ?)");
                                        if ($notifUserApprovalStmt) {
                                            $notifUserApprovalStmt->bind_param("iiss", $approvalNotifID, $creatorUserID, $status, $timestamp);
                                            if (!$notifUserApprovalStmt->execute()) {
                                                write_log("Error inserting approval notification for task creator: " . $notifUserApprovalStmt->error);
                                                mysqli_rollback($conn);
                                                return false;
                                            }
                                            $notifUserApprovalStmt->close();
                                        } else {
                                            write_log("Error preparing notif_user insert statement: " . $conn->error);
                                            mysqli_rollback($conn);
                                            return false;
                                        }
                                    } else {
                                        write_log("Error inserting approval notification: " . $approvalNotifStmt->error);
                                        mysqli_rollback($conn);
                                        return false;
                                    }
                                    $approvalNotifStmt->close();
                                } else {
                                    write_log("Error preparing approval notification insert statement: " . $conn->error);
                                    mysqli_rollback($conn);
                                    return false;
                                }
                            
                                // Insert into notif_user for each associated user
                                foreach ($userResult as $row) {
                                    $userInContentId = $row['UserID'];
                                    $notifUserStmt = $conn->prepare("INSERT INTO notif_user (NotifID, UserID, Status, TimeStamp) VALUES (?, ?, ?, ?)");
                                    if ($notifUserStmt) {
                                        $notifUserStmt->bind_param("iiss", $notifID, $userInContentId, $status, $timestamp);
                                        if (!$notifUserStmt->execute()) {
                                            write_log("Error inserting into notif_user: " . $notifUserStmt->error);
                                            mysqli_rollback($conn);
                                            return false;
                                        }
                                        $notifUserStmt->close();
                                    } else {
                                        write_log("Error preparing notif_user insert statement: " . $conn->error);
                                        mysqli_rollback($conn);
                                        return false;
                                    }
                                }
                            
                                // Call send_bulk_sms after all notifications are set up
                                if (!send_bulk_sms($conn, $ContentID, $notificationTitle, $taskTitle, $DueDate, $DueTime)) {
                                    write_log("Error sending bulk SMS for TaskID: $TaskID");
                                    mysqli_rollback($conn);
                                    return false;
                                }
                                
                                $notifStmt->close();
                                $contentQuery->close();

                            } else {
                                write_log("Error preparing notification insert statement: " . $conn->error);
                                mysqli_rollback($conn);
                                return false;
                            }
                            
                        } else {
                            write_log("Error fetching content title for ContentID: $ContentID");
                            mysqli_rollback($conn);
                            return false;
                        }
                        $userContentQuery->close();
                    } else {
                        write_log("Error fetching users for ContentID: $ContentID");
                        mysqli_rollback($conn);
                        return false;
                    }
                } else {
                    write_log("Error preparing userContentQuery statement: " . $conn->error);
                    mysqli_rollback($conn);
                    return false;
                }
                $taskDetailsQuery->close();
            } else {
                write_log("Error preparing taskDetailsQuery statement: " . $conn->error);
                mysqli_rollback($conn);
                return false;
            }
        }
    }

    if (mysqli_commit($conn)) {
        write_log("Successfully updated tasks with IDs: " . implode(', ', $taskIDs) . " to status: $status");
        return true;
    } else {
        write_log("Failed to commit transaction for tasks with IDs: " . implode(', ', $taskIDs));
        mysqli_rollback($conn);
        return false;
    }
}

//This is taskApproval.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $taskIDs = json_decode($_POST['taskIDs'], true); // Decode as array
    $action = $_POST['action'];
    $status = ($action === 'approve') ? 'Approved' : 'Rejected';

    $response = ['success' => false, 'message' => ''];

    if (empty($taskIDs)) {
        $response['message'] = 'No tasks selected.';
    } else {
        try {
            if (updateTaskStatus($conn, $taskIDs, $status)) {
                $response['success'] = true;
                $response['message'] = "$status tasks successfully.";
            } else {
                $response['success'] = false;
                $response['message'] = "Failed to update tasks.";
            }
        } catch (mysqli_sql_exception $e) {
            $response['success'] = false;
            $response['message'] = "Database error: " . $e->getMessage();
            error_log("Database error in taskApproval.php: " . $e->getMessage());
        } catch (Exception $e) {
            $response['success'] = false;
            $response['message'] = "An error occurred: " . $e->getMessage();
            error_log("Error in taskApproval.php: " . $e->getMessage());
        }
    }
    echo json_encode($response);
    exit;
} else {
    //Handle GET request (fetch pending tasks)
    $response = ['success' => false, 'message' => '', 'tasks' => []];

    try {
        $tasks = getPendingTasks($conn); //Fetch tasks
        $response['success'] = true;
        $response['tasks'] = $tasks; //Send tasks
    } catch (mysqli_sql_exception $e) {
        $response['success'] = false;
        $response['message'] = "Database error: " . $e->getMessage();
        error_log("Database error in taskApproval.php (GET): " . $e->getMessage());
    } catch (Exception $e) {
        $response['success'] = false;
        $response['message'] = "An error occurred: " . $e->getMessage();
        error_log("Error in taskApproval.php (GET): " . $e->getMessage());
    }
    echo json_encode($response);
    exit;
}

$conn->close();
?>