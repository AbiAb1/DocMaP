<?php
session_start();

// Redirect to index.php if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Include your database connection file here
include 'connection.php';

// Initialize variables
$content_id = null;
$content_title = "";
$content_captions = "";
$color="";
$tasks_exist = false;
$dept_name="";

// Check if content_id is provided in URL
if (isset($_GET['content_id'])) {
    $content_id = $_GET['content_id'];
    $UserID = $_SESSION['user_id']; // Get UserID from session

    // Query to fetch tasks based on ContentID and UserID
    $sql_tasks = "SELECT t.* 
              FROM tasks t 
              JOIN task_user tu ON t.TaskID = tu.TaskID 
              WHERE tu.ContentID = ? 
              AND t.Status = 'Assign' 
              AND tu.UserID = ? 
              ORDER BY t.Timestamp DESC";

    $stmt_tasks = $conn->prepare($sql_tasks);
    $stmt_tasks->bind_param("ss", $content_id, $UserID); // Assuming both are strings
    $stmt_tasks->execute();
    $result_tasks = $stmt_tasks->get_result();

    // Query to fetch content details
    $sql_content = "
    SELECT 
        fc.Title, 
        fc.Captions, 
        fc.ContentColor AS color, 
        fc.dept_ID, 
        d.dept_name
    FROM 
        feedcontent fc
    LEFT JOIN 
        department d ON fc.dept_ID = d.dept_id
    WHERE 
        fc.ContentID = ?
";

$stmt_content = $conn->prepare($sql_content);
$stmt_content->bind_param("s", $content_id);
$stmt_content->execute();
$result_content = $stmt_content->get_result();

// Fetch content details and department name
if ($row_content = $result_content->fetch_assoc()) {
    $content_title = $row_content['Title'];
    $content_captions = $row_content['Captions'];
    $color = $row_content['color'];
    $dept_id = $row_content['dept_ID'];
    $dept_name = $row_content['dept_name'];
}


    // Check if there are any tasks
    if ($result_tasks->num_rows > 0) {
        $tasks_exist = true;
    }

    // Close statements
    $stmt_tasks->close();
    $stmt_content->close();
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
    <title>Tasks</title>
    <link rel="icon" type="image/png" href="img/Logo/docmap-logo-1.png">
    <!-- ======= Styles ====== -->
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
  <style>
    .taskList {
        list-style-type: none;
        padding: 0;
    }
    .taskList li {
        margin-bottom: 10px;
        padding: 10px;
        background-color: #f0f0f0;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    .contentCard {
       
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        color: #fff;
        margin-left: 50px; /* Margin only on the left */
        margin-right: 50px; /* Margin only on the right */
        height: 300px;
    }

    .contentCard h2 {
        font-size: 40px;
       
    }
    .contentCard p {
        font-size: 16px;
        line-height: 1.6;
    }
    .taskContainer {
        background-color: #FFFF;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 15px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        margin-left: 50px; /* Margin only on the left */
        margin-right: 50px; /* Margin only on the right */
        position: relative;
        height: 100px;
    }
    .taskContainer h3 {
        font-size: 20px;
        margin-bottom: 5px;
    }
    .taskContainer p {
        font-size: 14px;
        color: #666;
    }
    .taskIcon {
        position: absolute;
        top: 50%;
        right: -25px; /* Adjust distance from the right */
        transform: translateY(-50%);
        width: 60px;
        height: 60px;
        
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        margin-right: 50px;
    }
    .taskIcon ion-icon {
        color: #fff;
        font-size: 25px;
        
    }
    .tasktitle {
        color: black;
        transition: color 0.3s ease; /* Smooth transition */
    }

    .tasktitle:hover {
    color: <?php echo htmlspecialchars($color); ?>; /* Hover color dynamically set */
}
</style>
</head>
<body">
    <!-- SIDEBAR -->
    <section id="sidebar"style="background-color: white;">
        <?php include 'navbar.php'; ?>
    </section>
    <!-- SIDEBAR -->
    <section id="content" style="background-color:white;">
        <!-- NAVBAR -->
        <?php include 'topbar.php'; ?>
        <!-- NAVBAR -->

        <!--xxasdasd MAIN -->
        <main style="background-color:#F1F0F6;">
            <h1 class="title">Content</h1>
            
            <!-- Content Details -->
            <div class="contentCard">
                
                <h2><?php echo $content_title; ?>  - <?php echo $content_captions; ?></h2>
                <p title = "Department"><i class='bx bxs-building' style ="margin-right:3px;"></i><?php echo $dept_name; ?></p>
            </div>

            <!-- Tasks List -->
            <div class="taskList">
                
                <?php
                if ($tasks_exist) {
                    // Output tasks
                    while ($row_task = mysqli_fetch_assoc($result_tasks)) {
                        $taskID = $row_task['TaskID'];
                        $taskTitle = $row_task['Title'];
                        $taskTimestamp = $row_task['TimeStamp'];
                        $taskType = $row_task['Type'];
                        $iconClass = '';

                        // Determine the task type and display corresponding icon
                        switch ($taskType) {
                            case 'Task':
                                $iconClass = 'document-outline'; // Adjust ion-icon name as needed
                                break;
                            case 'Reminder':
                                $iconClass = 'calendar-clear-outline'; // Adjust ion-icon name as needed
                                break;
                            case 'Announcement':
                                $iconClass = 'notifications-outline'; // Default icon for unknown type
                                break;
                        }
                    ?>
                        <!-- Task Container -->
                        <a href='update_status_task.php?task_id=<?php echo htmlspecialchars($taskID); ?>&content_id=<?php echo htmlspecialchars($content_id); ?>' class='taskLink'>
                            <div class='taskContainer'>
                                <h3 class='tasktitle'><?php echo htmlspecialchars($taskTitle); ?></h3>
                                <p>Posted: <?php echo htmlspecialchars($taskTimestamp); ?></p>
                                <div class='taskIcon' style="background-color: <?php echo htmlspecialchars($color); ?>;">
                                    <ion-icon name='<?php echo htmlspecialchars($iconClass); ?>'></ion-icon>
                                </div>
                            </div>
                        </a>
                    <?php
                    }
                } else {
                    echo "<p style='text-align: center; margin-top: 50px;'>No content available, admin will upload soon! stay tuned!</p>";
                }
                ?>
            </div>
        </main>
        <!-- MAIN -->
    </section>

    <!-- Scripts -->
    <script src="assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>
