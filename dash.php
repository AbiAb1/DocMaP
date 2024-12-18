<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$loginSuccess = isset($_SESSION['login_success']) ? $_SESSION['login_success'] : false;
if ($loginSuccess) {
    unset($_SESSION['login_success']); // Unset the session variable after use
}

include 'connection.php';

$user_id = $_SESSION['user_id'];

// Fetch user information
$sql_user = "SELECT * FROM useracc WHERE UserID = ?";
if ($stmt_user = $conn->prepare($sql_user)) {
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();

    $ufname = '';
    if ($result_user->num_rows > 0) {
        $row = $result_user->fetch_assoc();
        $ufname = $row['fname'];
    }
    $stmt_user->close();
} else {
    echo "Error preparing user query";
}

// Fetch recent documents (limit 10, sorted by timestamp)
$sql_documents = "SELECT * FROM documents WHERE UserID = ? ORDER BY Timestamp DESC LIMIT 10";
if ($stmt_documents = $conn->prepare($sql_documents)) {
    $stmt_documents->bind_param("i", $user_id);
    $stmt_documents->execute();
    $result_documents = $stmt_documents->get_result();

    $documents = [];
    while ($row = $result_documents->fetch_assoc()) {
        $documents[] = [
            'name' => $row['name'],
            'timestamp' => $row['TimeStamp'],
            'size' => $row['size'],
            'mimetype' => $row['mimeType'],
            'uri' => $row['uri'],
        ];
    }
    $stmt_documents->close();
} else {
    echo "Error preparing documents query";
}

// Fetch tasks
$sql_tasks = "SELECT ts.Title, ts.DueDate, ts.DueTime, ts.ContentID, ts.TaskContent, ts.Type, 
                     fc.Title as feedContentTitle,fc.ContentColor, tu.UserID, tu.TaskID, tu.ContentID
              FROM tasks ts
              INNER JOIN usercontent uc ON ts.ContentID = uc.ContentID
              INNER JOIN feedcontent fc ON ts.ContentID = fc.ContentID
              INNER JOIN task_user tu ON ts.TaskID = tu.TaskID
              WHERE uc.UserID = ? 
              AND uc.Status = 1
              AND tu.UserID = ? 
              AND (tu.Status = 'Assigned' OR tu.Status IS NULL)";
              
if ($stmt_tasks = $conn->prepare($sql_tasks)) {
    $stmt_tasks->bind_param("ii", $user_id, $user_id);  // Bind both user_id for usercontent and task_user
    $stmt_tasks->execute();
    $result_tasks = $stmt_tasks->get_result();

    $events = [];
        while ($row = $result_tasks->fetch_assoc()) {
            $events[] = [
                'type' => $row['Type'],
                'title' => $row['Title'], 
                'start' => "{$row['DueDate']}T{$row['DueTime']}",  // Combine DueDate and DueTime
                'color' => $row['ContentColor'], 
                'content' => $row['TaskContent'],  
                'feedContentTitle' => "{$row['Type']}: {$row['feedContentTitle']}",
                'task_id' => $row['TaskID'],  // Add task_id to events
                'content_id' => $row['ContentID'],  // Add content_id to events
            ];
        }

    $stmt_tasks->close();
} else {
    echo "Error preparing tasks query";
}

// Fetch content count
$sql_feedcontent_count = "SELECT COUNT(fs.ContentID) AS contentCount
                          FROM feedcontent fs
                          INNER JOIN usercontent uc ON fs.ContentID = uc.ContentID
                          WHERE uc.UserID = ? AND uc.Status = 1";
if ($stmt_feedcontent_count = $conn->prepare($sql_feedcontent_count)) {
    $stmt_feedcontent_count->bind_param("i", $user_id);
    $stmt_feedcontent_count->execute();
    $result_feedcontent_count = $stmt_feedcontent_count->get_result();

    $contentCount = 0;
    if ($row = $result_feedcontent_count->fetch_assoc()) {
        $contentCount = $row['contentCount'];
    }
    $stmt_feedcontent_count->close();
} else {
    echo "Error preparing feedcontent count query";
}

// Fetch assigned tasks count
$sql_assigned_count = "SELECT COUNT(ut.TaskID) AS assignedCount
FROM task_user ut
INNER JOIN tasks ts ON ut.TaskID = ts.TaskID
WHERE ut.UserID = ? 
  AND ut.Status = 'Assigned' 
  AND ts.Type = 'Task'";
if ($stmt_assigned_count = $conn->prepare($sql_assigned_count)) {
    $stmt_assigned_count->bind_param("i", $user_id);
    $stmt_assigned_count->execute();
    $result_assigned_count = $stmt_assigned_count->get_result();

    $assignedCount = 0;
    if ($row = $result_assigned_count->fetch_assoc()) {
        $assignedCount = $row['assignedCount'];
    }
    $stmt_assigned_count->close();
} else {
    echo "Error preparing assigned tasks count query";
}

// Fetch missing tasks count
$sql_missing_count = "SELECT COUNT(ut.TaskID) AS missingCount
FROM task_user ut
INNER JOIN tasks ts ON ut.TaskID = ts.TaskID
WHERE ut.UserID = ? 
  AND ut.Status = 'Missing' 
  AND ts.Type = 'Task'";
if ($stmt_missing_count = $conn->prepare($sql_missing_count)) {
    $stmt_missing_count->bind_param("i", $user_id);
    $stmt_missing_count->execute();
    $result_missing_count = $stmt_missing_count->get_result();

    $missingCount = 0;
    if ($row = $result_missing_count->fetch_assoc()) {
        $missingCount = $row['missingCount'];
    }
    $stmt_missing_count->close();
} else {
    echo "Error preparing missing tasks count query";
}
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" type="image/png" href="img/Logo/docmap-logo-1.png">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 5 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css">
    
    <title>Dashboard | Home</title>
    <style>
        .fc-button {
            background-color: #9b2035;
            border: none;
            border-radius: 5px;
            color: white;
            font-size: 14px;
        }
        .fc-button:hover {
            background-color: #a8243b;
        }
        .fc-icon {
            color: white;
        }
        .fc-day-number {
            color: black; /* Make the dates black */
        }
        .fc-unthemed .fc-today {
            background-color: #9b2035 !important; /* Color the today date */
            color: white !important; /* Make the text color white */
        }
        .fc-unthemed td.fc-today {
            background-color: transparent !important;
            color: white !important;
        }
        .fc-content-skeleton table,
        .fc-basic-view .fc-body .fc-row,
        .fc-row .fc-content-skeleton,
        .fc-row .fc-bg,
        .fc-day,
        .fc-day-top,
        .fc-bg table,
        .fc-bgevent-skeleton,
        .fc-content-skeleton td,
        .fc-axis {
            border: none !important; /* Remove the grid lines */
        }
        .fc-event-dot {
            border-radius: 50%;
            background-color: #9b2035; /* Dot color */
            height: 10px;
            width: 10px;
        }


        .modal-backdrop.show {
            background-color: rgba(0, 0, 0, 0.5); /* Slightly darker background */
        }

        /* Modal Container */
        .modal-dialog {
            max-width: 600px; /* Adjust as needed */
            margin: 1.75rem auto;
        }

        /* Modal Content */
        .modal-content {
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            background-color: #fff;
        }

        /* Modal Header */
        .modal-header {
            border-bottom: 1px solid #dee2e6;
            background-color: #f7f7f7; /* Light grey background */
            padding: 1rem 1.5rem;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }

        /* Modal Title */
        .modal-title {
            font-size: 1.25rem;
            font-weight: 500;
            color: #333;
        }

        /* Close Button */
        .modal-header .close {
            font-size: 1.5rem;
            color: #333;
        }

        /* Modal Body */
        .modal-body {
            padding: 1.5rem;
            color: #555; /* Darker grey text */
        }

        /* Modal Footer */
        .modal-footer {
            border-top: 1px solid #dee2e6;
            padding: 1rem;
            background-color: #f7f7f7; /* Light grey background */
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
        }

        /* Button Styling */
        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
            color: #fff;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #545b62;
        }

        /* Adjust Close Button Alignment */
        .modal-footer .btn-secondary {
            margin-left: auto;
        }

        /* Optional: Adjust modal responsiveness */
        @media (max-width: 576px) {
            .modal-dialog {
                margin: 1rem;
                max-width: 100%;
            }
        }

        /* Icon styles */
        .modal-body i {
            color: #9b2035;
            margin-right: 10px;
        }
        /* Icon styles */
        .modal-body i {
            color: #9b2035;
            margin-right: 10px;
            font-size: 24px; /* Increase font size */
        }
        .count {
            position: absolute;
            top: 40px;
            right: 40px;
            background-color: #fff;
            color: #9b2035;
            padding: 2px 5px;
            border-radius: 3px;
            font-weight: bold;
            font-size:60px;
        }
        .todo-count {
            position: absolute;
            top: 40px;
            right: 40px;
           
            color: #fff;
            padding: 2px 5px;
            border-radius: 3px;
            font-weight: bold;
            font-size:60px;
        }

        .todo-text {
            position: absolute;
            bottom: 0;
            right: 0;
            margin: 10px; /* Adjust as needed */
            color: white; /* Adjust text color as needed */
        }
        .card{
            height:180px;
        }
        .new-container {
            position: relative;
            height: 319px;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            background-color:#ffff;
        }

        .new-container::before {
            
            
        }

        canvas {
            width: 100% !important;
            height: auto !important;
        }

        .task-item {
            position: relative;
            background: #fff;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            height:110px;
            padding:10px;

            
            
        }

        .task-details {
            flex-grow: 1;
        }

        .complete-button {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            width: 24px;
            height: 24px;
            background-color: #28a745;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .complete-button i {
            color: #fff;
            font-size: 16px;
            font-weight:bold;
        }

        .dropbtn {
        background-color: transparent;
        color: black;
        padding: 16px;
        font-size: 16px;
        border: none;
        cursor: pointer;
        text-decoration: none;
        /* Optional: Space from edges */
        }

        .dropbtn:hover, .dropbtn:focus {
        background-color: transparent;
        text-decoration: none;
        }

        .dropdown {
        position: absolute; /* Set to absolute for positioning */
        top: 1px; /* Distance from the top */
        right: 10px; /* Distance from the right */
        }

        .dropdown-content {
        display: none;
        position: absolute;
        background-color: #ffff;
        min-width: 160px;
        left: -120px; /* Move this value to adjust how far left the dropdown goes */
        overflow: auto;
        box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
        z-index: 1;
        border-radius: 10px;
        }

        .dropdown-content a {
        color: black;
        padding: 12px 16px;
        text-decoration: none;
        display: block;
        }

        .dropdown a:hover {
        background-color: #ddd;
        }

        .show {
        display: block;
        }
        .modal-backdrop {
            z-index: 1040 !important; /* Ensure the backdrop appears above content */
        }

        .modal {
            z-index: 1050 !important; /* Ensure modal appears above backdrop */
        }
        .task-details-link {
            display: flex;
            justify-content: flex-end; /* Align the content to the right */
            color: black;
        }

        #taskDetailsLink i {
            font-size: 24px; /* Default icon size */
            transition: all 0.3s ease; /* Smooth transition for hover effect */
            color: black;
        }

        #taskDetailsLink:hover i {
            color: #9b2035; /* Change the color when hovered */
            font-size: 30px; /* Increase the icon size when hovered */
        }
        .icon{
            font-size: 20px;
            
        }
/* Style for document container */
.document-container {
    display: flex;
    align-items: center;
    padding: 10px;
    border-radius: 10px;
    margin-bottom: 10px;
    background-color: #fff;
    border: 1px solid #ddd;
    overflow: hidden;
    transition: box-shadow 0.3s ease, transform 0.3s ease; /* Smooth transition */
}

/* Shadow effect on hover */
.document-container:hover {
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Shadow effect */
    transform: translateY(-3px); /* Slight lift */
}


/* Document content container for proper alignment */
.document-content {
    display: flex;
    justify-content: space-between; /* Space between the document name and the icon */
    align-items: center;
    width: 100%; /* Ensure it takes up full width */
}

/* Document name styling */
.document-name {
    flex: 1; /* Allow the name to take available space */
    white-space: nowrap; /* Prevent the name from wrapping */
    overflow: hidden; /* Hide overflowing text */
    text-overflow: ellipsis; /* Add ellipsis for overflow */
    padding-right: 10px; /* Space between the name and the icon */
    max-width: 320px; /* Set a maximum width for the document name */
}

/* Styling for the file icon container */
.file-icon {
    width: 40px; /* Icon container width */
    height: 40px; /* Icon container height */
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: #9b2035;
    border-radius: 50%; /* Circular icon container */
    border: 1px solid #ccc; /* Border for icon */
    margin-left: auto; /* Push the icon to the far right */
}


/* Additional styling for icons */
.fas {
    font-size: 20px; /* Set the size of the icons */
    color:#fff;
}
 .responsive-image {
        position: absolute;
        right: 0;
        top: -63%;
        height: 320px;
        max-width: none;
        display: block; /* Show image by default */
    }

    @media (max-width: 768px) {
        .responsive-image {
            display: none; /* Hide image on mobile screens */
        }
    }
    body {
  background-color: #F1F0F6; /* Light gray */
}



    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <section id="sidebar">
        <?php include 'navbar.php'; ?>
    </section>
    <!-- SIDEBAR -->

    <!-- NAVBAR -->
    <section id="content" style ="background-color:#f0f0f0;">
        <!-- NAVBAR -->
        <?php include 'topbar.php'; ?>
        <!-- NAVBAR -->

        <!-- MAIN -->
        <main>
            <h1 class="title">Dashboard</h1>
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="info-data">
                                    <div class="card" style="background-color: ; height: 150px; position: relative; padding: 10px; border-radius: 10px; box-shadow: 4px 4px 16px rgba(0, 0, 0, .05);">
                                        <div style="padding: 20px;">
                                            <h1 style="font-weight: bold; color: #9b2035; margin-bottom: -5px;">
                                                Hello, <?php echo htmlspecialchars($ufname); ?>!
                                                <img src="img/icons/EYYY.gif" alt="Animated GIF" style="height: 40px; vertical-align: middle;">
                                            </h1>
                                            <?php
                                                if ($assignedCount == 0 && $contentCount == 0) {
                                                    // Message when there are no tasks and no content
                                                    echo '<p style="color: grey; font-size:16px; margin-top:20px;">
                                                            <em style="font-weight:bold; color:#9b2035">Hello there!</em> Start adding grades to start your journey!
                                                        </p>';
                                                } elseif ($assignedCount == 0) {
                                                    // Message when there are no tasks but content exists
                                                    echo '<p style="color: grey; font-size:16px; margin-top:20px;">
                                                            You have <em style="font-weight:bold; font-size:20px;">no</em> tasks to accomplish! 
                                                            <em style="font-weight:bold; color:#9b2035">Good Job!</em>
                                                        </p>';
                                                } else {
                                                    // Message when there are tasks
                                                    echo '<p style="color: grey; font-size:16px;">
                                                            You currently have <em style="font-weight:bold; font-size:20px;">' . htmlspecialchars($assignedCount) . '</em> tasks to accomplish. 
                                                            Finish it before the <br>assigned due! 
                                                            <em style="font-weight:bold; color:#9b2035">Have a nice day!</em>
                                                        </p>';
                                                }
                                            ?>

                                        </div>
                                        <img src="img/card.png" alt="description of image" class="responsive-image">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="info-data">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="card" style="background-color:#9b2035;">
                                                <div class="head">
                                                    <div>
                                                        <img src="img/icons/todo.png" alt="To-Do Icon">
                                                    </div>
                                                </div>
                                                <p class="todo-count"><?php echo htmlspecialchars($assignedCount); ?></p>
                                                <h6 class="todo-text">Assigned</h6>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card" style="background-color:#9b2035;">
                                                <div class="head">
                                                    <div>
                                                        <img src="img/icons/missing.png">
                                                    </div>
                                                </div>
                                                <p class="todo-count"><?php echo htmlspecialchars($missingCount); ?></p>
                                                <h6 class="todo-text">Missing</h6>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card">
                                                <div class="head">
                                                    <div>
                                                        <img src="img/icons/list.png">
                                                    </div>
                                                </div>
                                                <span class="count" style="background-color:transparent;"><?php echo htmlspecialchars($contentCount); ?></span>
                                                <h6 class="todo-text" style="color:#9b2035;">Subjects</h6>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card" style="margin-top: 20px; height: 455px; background-color: transparent; padding: 20px; border: transparent; overflow-y: auto;">
                                    <div class="head mb-3" style="position: sticky;  background-color: transparent; z-index: 1;">
                                        <h5 style="font-weight: 600;">Progress</h5>
                                        <a href="subjects.php"><span style ="color:#9b2035;">View All</span></a>
                                    </div>
                                    <div id="subjects-container" style="height: 400px;">
                                        <!-- Subjects will be dynamically loaded here -->
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="data">
                                    <div class="content-data" style="height: 455px;">
                                        <div class="d-flex flex-column" style="height: 400px;">
                                            <div class="head mb-3">
                                                <h3>Reminders</h3>
                                                <button type="button" class="btn btn-custom" data-toggle="modal" data-target="#addTaskModal" style="background-color:#9b2035; color:#fff;">
                                                    <i class='bx bx-list-plus icon'></i> Add Reminder
                                                </button>
                                            </div>
                                            <div id="tasksList" style="height: calc(100% - 60px); overflow-y: auto; padding:4px;">
                                                <!-- Tasks will be dynamically inserted here -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-data">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card" style="margin-bottom:20px;height:490px;padding:20px;border-radius:10px;box-shadow:4px 4px 16px rgba(0, 0, 0, .05);">
                                        <div id="calendar" style="height:190px;"></div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="card new-container">
                                        <div class="head mb-3">
                                            <h5 style="font-weight:600;">Recent Files</h5>
                                        </div>
                                        <ul style="max-height:auto;overflow-y:auto;padding-left:0;list-style-type:none;">
                                            <?php if (empty($documents)): ?>
                                                <li>
                                                    <p style="color:grey;font-size:16px;margin-top:20px;">No documents found.</p>
                                                </li>
                                            <?php else: ?>
                                                <?php foreach ($documents as $document): ?>
                                                    <li class="document-container">
                                                        <div class="document-content">
                                                            <span class="document-name">
                                                                <?php
                                                                    // Get the file name without the part before the first underscore
                                                                    $fileName = htmlspecialchars($document['name']);
                                                                    $displayName = strstr($fileName, '_') ? substr(strstr($fileName, '_'), 1) : $fileName;
                                                                    
                                                                    // Create the URL to the document (assuming documents are stored in the "Documents" directory)
                                                                    $documentPath = 'Documents/' . $fileName;

                                                                    // Make the document name clickable, linking to the file's URL
                                                                    echo '<a href="' . $documentPath . '" target="_blank" style="text-decoration:none;color:black;">' . $displayName . '</a>';
                                                                ?>
                                                            </span>
                                                            <div class="file-icon">
                                                                <?php
                                                                    // Extract the file extension from the document's name
                                                                    $fileExtension = pathinfo($document['name'], PATHINFO_EXTENSION);

                                                                    // Display the appropriate icon based on the file extension
                                                                    switch (strtolower($fileExtension)) {
                                                                        case 'pdf':
                                                                            echo '<i class="fas fa-file-pdf"></i>';
                                                                            break;
                                                                        case 'jpg':
                                                                        case 'jpeg':
                                                                        case 'png':
                                                                            echo '<i class="fas fa-image"></i>';
                                                                            break;
                                                                        case 'doc':
                                                                        case 'docx':
                                                                            echo '<i class="fas fa-file-word"></i>';
                                                                            break;
                                                                        case 'xls':
                                                                        case 'xlsx':
                                                                            echo '<i class="fas fa-file-excel"></i>';
                                                                            break;
                                                                        case 'txt':
                                                                            echo '<i class="fas fa-file-alt"></i>';
                                                                            break;
                                                                        default:
                                                                            echo '<i class="fas fa-file"></i>';
                                                                            break;
                                                                    }
                                                                ?>
                                                            </div>
                                                        </div>
                                                    </li>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>        
                </div>
            </div>

            <!-- Event Details Modal -->
            <div class="modal fade" id="eventModal" tabindex="-1" role="dialog" aria-labelledby="eventModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header" style="background-color:transparent;">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p><i class='bx bx-news'></i> <strong><span id="eventFeedContentTitle" style="font-weight:bold;font-size:50px;"></span></strong></p>
                            <p><i class='bx bx-bookmark'></i> <strong><span id="eventTitle"></span></strong></p>
                            <p><i class='bx bx-calendar'></i> <span id="eventDueDate"></span></p>
                            
                            
                        </div>
                        <div class="task-details-link">
                            <a href="#" id="taskDetailsLink" class="btn btn-link">
                                <i class='bx bx-right-arrow-alt'></i>
                            </a>
                        </div>

                        
                    </div>
                </div>
            </div>

            <!-- Add New Task Modal -->
            <div class="modal fade" id="addTaskModal" tabindex="-1" role="dialog" aria-labelledby="addTaskModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header" style="background-color:transparent;">
                            <h5 class="modal-title" id="addTaskModalLabel">Add New Task</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="addTaskForm">
                                <div class="form-group">
                                    <label for="taskName">Task Name</label>
                                    <input type="text" class="form-control" id="taskName" name="taskName" required>
                                </div>
                                <div class="form-group">
                                    <label for="taskDate">Due Date</label>
                                    <input type="date" class="form-control" id="taskDate" name="taskDate" required>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="saveTaskBtn">Create Task</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Task Modal -->
            <div class="modal fade" id="editTaskModal" tabindex="-1" role="dialog" aria-labelledby="editTaskModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editTaskModalLabel">Edit Task</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="editTaskName">Task Name</label>
                                <input type="text" class="form-control" id="editTaskName" placeholder="Enter task name">
                            </div>
                            <div class="form-group">
                                <label for="editTaskDate">Due Date</label>
                                <input type="date" class="form-control" id="editTaskDate">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="button" id="updateTaskBtn" class="btn btn-primary">Update Task</button>
                        </div>
                    </div>
                </div>
            </div>



        </main>
        <!-- MAIN -->
    </section>
    <!-- NAVBAR -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment-timezone/0.5.41/moment-timezone-with-data.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar-timegrid.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Function to fetch subjects
            function fetchSubjects() {
                const container = document.getElementById("subjects-container");
                container.innerHTML = "<p>Loading...</p>"; // Show loading message

                fetch("getSubjects.php") // Replace with the path to your PHP script
                    .then((response) => response.json())
                    .then((subjects) => {
                        container.innerHTML = ""; // Clear loading message

                        if (subjects.length === 0) {
                            container.innerHTML = "<p>No subjects found.</p>";
                            return;
                        }

                        subjects.forEach((subject) => {
                            // Create subject container
                            const subjectDiv = document.createElement("div");
                            subjectDiv.style.cssText = `
                                display: flex;
                                justify-content: space-between;
                                align-items: center;
                                margin-bottom: 10px;
                                padding: 10px;
                                border-radius: 8px;
                                background-color: #fff;
                                box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.1);
                            `;

                            // Leftmost part: Initials and Caption
                            const leftPart = document.createElement("div");
                            leftPart.style.cssText = `
                                display: flex;
                                align-items: center;
                                flex: 1; /* Allow it to take available space */
                            `;

                            // Create circle for initials
                            const circle = document.createElement("div");
                            circle.textContent = subject.initials;
                            circle.style.cssText = `
                                height: 40px;
                                width: 40px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                border-radius: 50%;
                                margin-right: 15px;
                                background-color: ${subject.color};
                                color: white;
                                font-weight: bold;
                                text-align: center;
                            `;
                            leftPart.appendChild(circle);

                            // Display captions (with ellipsis if too long)
                            const captionsDiv = document.createElement("div");
                            captionsDiv.textContent = subject.captions;
                            captionsDiv.style.cssText = `
                                color: #333;
                                font-size: 14px;
                                font-weight: bold;
                                white-space: nowrap;
                                overflow: hidden;
                                text-overflow: ellipsis;
                                max-width: 200px; /* Limit width for truncation */
                            `;
                            leftPart.appendChild(captionsDiv);
                            subjectDiv.appendChild(leftPart);

                            // Middle part: Total approved and submitted over total tasks
                            const middlePart = document.createElement("div");
                            middlePart.style.cssText = `
                                display: flex;
                                justify-content: center;
                                align-items: center;
                                font-size: 16px;
                                color: #333;
                                flex: 1;
                                text-align: center;
                                font-weight:bold;
                            `;
                            middlePart.textContent = `${subject.total_approved_submitted} / ${subject.total_tasks}`;
                            subjectDiv.appendChild(middlePart);

                            // Rightmost part: Progress doughnut
                            const rightPart = document.createElement("div");
                            rightPart.style.cssText = `
                                width: 50px;
                                height: 50px;
                                background-color: transparent;
                                position: relative;
                            `;

                            // Create canvas for doughnut chart
                            const canvas = document.createElement("canvas");
                            canvas.width = 50;
                            canvas.height = 50;
                            rightPart.appendChild(canvas);

                            // Draw animated doughnut chart
                            const ctx = canvas.getContext("2d");
                            const progress = subject.total_approved_submitted / subject.total_tasks;

                            let startAngle = -Math.PI / 2;
                            const endAngle = (2 * Math.PI * progress) - Math.PI / 2;
                            const animationDuration = 1000; // Animation duration in ms
                            const frameRate = 60; // Frames per second
                            const totalFrames = (animationDuration / 1000) * frameRate;
                            let currentFrame = 0;

                            function drawAnimation() {
                                const currentProgress = currentFrame / totalFrames;
                                const currentAngle = startAngle + (endAngle - startAngle) * currentProgress;

                                // Clear canvas
                                ctx.clearRect(0, 0, canvas.width, canvas.height);

                                // Draw background circle
                                ctx.beginPath();
                                ctx.arc(25, 25, 20, 0, 2 * Math.PI);
                                ctx.lineWidth = 8;
                                ctx.strokeStyle = "#e6e6e6"; // Light gray background circle
                                ctx.stroke();

                                // Draw progress circle (doughnut)
                                ctx.beginPath();
                                ctx.arc(25, 25, 20, startAngle, currentAngle);
                                ctx.lineWidth = 8;
                                ctx.strokeStyle = "#4caf50"; // Green for progress
                                ctx.stroke();

                                // Add the percentage in the center of the doughnut
                                const percentage = Math.round(progress * 100); // Convert progress to percentage
                                ctx.font = "12px Poppins";
                                ctx.fillStyle = "#333";
                                ctx.textAlign = "center";
                                ctx.textBaseline = "middle";
                                ctx.fillText(`${percentage}%`, 25, 25); // Draw the percentage at the center

                                // Increment frame and call next frame
                                currentFrame++;
                                if (currentFrame <= totalFrames) {
                                    requestAnimationFrame(drawAnimation);
                                }
                            }

                            drawAnimation();

                            // Append subjectDiv to the container
                            subjectDiv.appendChild(rightPart);
                            container.appendChild(subjectDiv);
                        });
                    })
                    .catch((error) => {
                        console.error("Error fetching subjects:", error);
                        container.innerHTML = "<p>Error loading subjects.</p>";
                    });
            }

            // Fetch subjects on page load
            fetchSubjects();
        });

    </script>





    <script>
        window.onload = function() {
            updateTaskStatus();
            updateUserReminders(); // Call this function on page load
        };

        function updateTaskStatus() {
            console.log("Calling AJAX to update task status");
            $.ajax({
                url: 'updateMissingTask.php',
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        console.log('Task statuses updated successfully.');
                    } else {
                        console.log('Error updating task statuses:', response.error);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log('AJAX request failed:', textStatus, errorThrown);
                    console.log(jqXHR.responseText); // Log the server's response if the request fails
                }
            });
        }

        function updateUserReminders() {
    console.log("Calling AJAX to update user reminders");
    $.ajax({
        url: 'updateUserReminders.php', // PHP script for updating reminders
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                console.log('User reminders updated successfully for User ID:', response.user_id);
            } else {
                console.log('Error updating user reminders:', response.error);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error('AJAX request failed:', textStatus, errorThrown);
            console.error('Response:', jqXHR.responseText); // Log server response
        }
    });
}

    </script>


    <script>
$(document).ready(function() {
    $('#saveTaskBtn').on('click', function() {
        const taskName = $('#taskName').val();
        const taskDate = $('#taskDate').val();

        if (taskName && taskDate) {
            $.ajax({
                url: 'addTask.php',
                type: 'POST',
                data: {
                    taskName: taskName,
                    taskDate: taskDate
                },
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Task Added',
                        text: 'Your task has been added successfully!',
                    }).then(() => {
                        $('#addTaskModal').modal('hide');
                        location.reload();
                    });
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'An error occurred while adding the task.',
                    });
                }
            });
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Incomplete Data',
                text: 'Please fill out both fields.',
            });
        }
    });

    

// Fetch tasks when the page loads
$.ajax({
    url: 'fetch_todo.php',
    type: 'GET',
    dataType: 'json',
    success: function(response) {
        if (Array.isArray(response)) {
            let tasksHtml = '';

            // Sort tasks by Due date (nearest date first)
            response.sort((a, b) => new Date(a.Due) - new Date(b.Due));

            // Get the current date
            const currentDate = new Date();

            // Loop through each task
            response.forEach(task => {
                // Check if the task is missing or pending
                const dueDate = new Date(task.Due);
                let statusText, statusColor;

                if (dueDate < currentDate) {
                    statusText = 'Missing';
                    statusColor = 'red';
                } else {
                    statusText = 'Pending';
                    statusColor = 'blue';
                }

                tasksHtml += `
                    <div class="task-item">
                        <div class="task-details">
                            <h4 style="margin-top:15px;">${task.Title}</h4>
                            <p style="font-size:13px;color:grey;">
                                Due: ${dueDate.toLocaleDateString('en-PH', {
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric'
                                })}
                            </p>
                            <p style="color: ${statusColor};">${statusText}</p>
                        </div>
                        <div class="dropdown">
                            <button onclick="toggleDropdown(event)" class="dropbtn" style="text-decoration: none;">
                                <i class='bx bx-dots-horizontal-rounded'></i>
                            </button>
                            <div id="dropdown${task.id}" class="dropdown-content">
                                <a class="dropdown-item edit-task" href="#" data-id="${task.TodoID}">
                                    <i class='bx bx-edit-alt'></i>Edit
                                </a>
                                <a class="dropdown-item delete-task" href="#" data-id="${task.TodoID}">
                                    <i class='bx bx-trash'></i>Delete
                                </a>
                            </div>
                        </div>
                        <div class="complete-button">
                            <i class="bx bx-check"></i>
                        </div>
                    </div>
                `;
            });

            $('#tasksList').html(tasksHtml);
        } else {
            $('#tasksList').html('<p>No tasks found.</p>');
        }
    },
    error: function(xhr, status, error) {
        $('#tasksList').html('<p>An error occurred while fetching tasks.</p>');
    }
});



    // Handle task completion when the complete button is clicked
    $(document).on('click', '.complete-button', function(e) {
        e.preventDefault();
        
        // Get the TodoID from the task item
        const todoId = $(this).closest('.task-item').find('.edit-task').data('id'); // Ensure the right TodoID is fetched
        
        // SweetAlert confirmation
        Swal.fire({
            title: 'Task Complete?',
            text: 'Mark this task as complete?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            cancelButtonText: 'No',
            confirmButtonText: 'Yes!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Proceed to update the task status
                $.ajax({
                    url: 'complete_todo.php', // Your PHP script to update the task status
                    type: 'POST',
                    data: { TodoID: todoId, Status: 'Completed' },
                    success: function(response) {
                        const result = JSON.parse(response);
                        if (result.success) {
                            Swal.fire('Completed!', result.message, 'success');
                            location.reload(); // Reload the page to reflect changes
                        } else {
                            Swal.fire('Error!', result.message, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire('Error!', 'An error occurred while updating the task status.', 'error');
                    }
                });
            }
        });
    });

    $(document).on('click', '.edit-task', function(e) {
    e.preventDefault();
    
    // Get the TodoID from the data attribute
    const todoId = $(this).data('id'); 
    console.log("Editing task ID:", todoId); // Log the ID being edited

    // Fetch the current task data directly without confirmation alert
    $.ajax({
        url: 'fetch_task.php',
        type: 'GET',
        data: { id: todoId }, // Ensure todoId is correctly set
        dataType: 'json',
        success: function(task) {
            console.log("Fetched task data:", task); // Log the fetched task data
            
            if (task.error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: task.error,
                });
                return;
            }

            // Populate the modal fields with the current task details
            $('#editTaskName').val(task.Title);
            $('#editTaskDate').val(task.Due);
            $('#updateTaskBtn').data('id', todoId);
            $('#editTaskModal').modal('show');
        },
        error: function(xhr, status, error) {
            console.error("Error fetching task details:", xhr.responseText); // Log the response
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'An error occurred while fetching the task details.',
            });
        }
    });
});



    // Handle task update when the update button is clicked
    $('#updateTaskBtn').on('click', function() {
        const todoId = $(this).data('id'); // Use TodoID
        const updatedTaskName = $('#editTaskName').val();
        const updatedTaskDate = $('#editTaskDate').val();

        if (updatedTaskName && updatedTaskDate) {
            $.ajax({
                url: 'updateTask.php', // Your PHP script to update the task
                type: 'POST',
                data: {
                    TodoID: todoId, // Use TodoID in the data object
                    taskName: updatedTaskName,
                    taskDate: updatedTaskDate
                },
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Task Updated',
                        text: 'Your task has been updated successfully!',
                    }).then(() => {
                        $('#editTaskModal').modal('hide');
                        location.reload(); // Reload to reflect changes
                    });
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'An error occurred while updating the task.',
                    });
                }
            });
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Incomplete Data',
                text: 'Please fill out both fields.',
            });
        }
    });

    $(document).on('click', '.delete-task', function(e) {
    e.preventDefault();
    const taskId = $(this).data('id');
    
    // SweetAlert confirmation
    Swal.fire({
        title: 'Are you sure?',
        text: 'You won\'t be able to revert this!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Proceed to delete the task
            $.ajax({
                url: 'deleteTask.php',
                type: 'POST',
                data: { id: taskId },
                success: function(response) {
                    const result = JSON.parse(response);
                    if (result.success) {
                        Swal.fire('Deleted!', result.message, 'success');
                        location.reload(); // Reload the page to reflect changes
                    } else {
                        Swal.fire('Error!', result.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire('Error!', 'An error occurred while deleting the task.', 'error');
                }
            });
        }
    });
});


    // Dropdown functionality
    window.toggleDropdown = function(event) {
        event.stopPropagation(); // Prevent event from bubbling up
        const dropdown = event.currentTarget.nextElementSibling;
        dropdown.classList.toggle("show");
    };

    // Close the dropdown if the user clicks outside of it
    $(document).on('click', function(event) {
        if (!$(event.target).closest('.dropdown').length) {
            $('.dropdown-content').removeClass('show');
        }
    });
});
</script>



<script src="progress.js">
document.addEventListener('DOMContentLoaded', function() {
    const startDate = new Date('2024-07-15');
    const endDate = new Date('2025-03-09');
    const today = new Date();

    const totalDays = (endDate - startDate) / (1000 * 60 * 60 * 24);
    const completedDays = (today - startDate) / (1000 * 60 * 60 * 24);
    const percentageCompleted = (completedDays / totalDays) * 100;

    const ctx = document.getElementById('progressChart').getContext('2d');
    const data = {
        datasets: [{
            data: [percentageCompleted, 100 - percentageCompleted],
            backgroundColor: ['#9b2035', '#d0495e'],
            borderWidth: 0
        }]
    };

    const options = {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '70%',
        plugins: {
            tooltip: {
                enabled: false
            },
            legend: {
                display: false
            },
            beforeDraw: (chart) => {
                const { width, height, ctx } = chart;
                ctx.restore();
                const fontSize = (height / 160).toFixed(2);
                ctx.font = `${fontSize}em sans-serif`;
                ctx.textBaseline = "middle";
                const text = `${Math.round(percentageCompleted)}%`;
                const textX = Math.round((width - ctx.measureText(text).width) / 2);
                const textY = height / 2;
                ctx.fillText(text, textX, textY);
                ctx.save();
            }
        }
    };

    new Chart(ctx, {
        type: 'doughnut',
        data: data,
        options: options
    });
});


</script>

<script>
$(document).ready(function() {
    $('#calendar').fullCalendar({
        header: {
            left: 'title',
            center: '',
            right: 'prev,next',
        },
        footer: {
            right: 'today month agendaWeek agendaDay'
        },
        defaultDate: moment().format('YYYY-MM-DD'),
        navLinks: true,
        editable: true,
        eventLimit: true,
        height: 450,
        timeZone: 'Asia/Manila',
        events: <?php echo json_encode($events); ?>,
        dayRender: function(date, cell) {
            if (date.isSame(moment().tz('Asia/Manila'), 'day')) {
                cell.css("background-color", "rgba(155, 32, 53, 0.2)");
                cell.css("color", "#9b2035");
            }
        },
        eventClick: function(event, jsEvent, view) {
            $('#eventTitle').text(event.title);

            // Format date and time for the event
            const dueDateTime = moment(event.start).tz('Asia/Manila').format('MMMM Do YYYY, h:mm A');
            $('#eventDueDate').text(dueDateTime);

            $('#eventFeedContentTitle').text(event.feedContentTitle || 'No feed content title available.');

            // Set HTML content for eventContent
            $('#eventContent').html(event.content || '<em>No details available.</em>');

            // Construct the URL for task details
            const taskId = event.task_id;  // Assuming task_id is set in your events array
            const contentId = event.content_id;  // Assuming content_id is set in your events array
            $('#taskDetailsLink').attr('href', `taskdetails.php?task_id=${encodeURIComponent(taskId)}&content_id=${encodeURIComponent(contentId)}`);

            $('#eventModal').modal('show');
        }
    });
});
</script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.getElementById('logout').addEventListener('click', function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Are you sure?',
                text: 'You will be logged out of your account!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, log me out!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = this.getAttribute('href');
                }
            });
        });
    </script>
   <script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($loginSuccess === true): ?>
        Swal.fire({
            title: 'Login Successful!',
            text: 'Welcome back, <?php echo htmlspecialchars($ufname); ?>!',
            icon: 'success',
            confirmButtonText: 'OK'
        });
        <?php endif; ?>
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>
