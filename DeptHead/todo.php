<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'connection.php'; // Assuming this file contains your database connection code

// Fetch tasks from the database
$user_id = $_SESSION['user_id'];

// Query to fetch tasks and their statuses from task_user
$sql = "SELECT 
            DISTINCT ts.TaskID, 
            ts.ContentID, 
            ts.Type, 
            ts.Title, 
            ts.Duedate, 
            ts.DueTime, 
            ts.taskContent, 
            ts.TimeStamp, 
            tu.Status, 
            CONCAT(fc.Title, '-', fc.Captions) AS GS
        FROM 
            task_user tu
        INNER JOIN 
            tasks ts ON tu.TaskID = ts.TaskID
        INNER JOIN 
            usercontent uc ON ts.ContentID = uc.ContentID
        INNER JOIN 
            feedcontent fc ON fc.ContentID = ts.ContentID
        WHERE 
            tu.UserID = $user_id AND 
            tu.Status IN ('Assigned', 'Missing')";


$result = $conn->query($sql);

// Initialize arrays to categorize tasks
$todayTasks = [];
$comingUpTasks = [];
$laterTasks = [];
$noDueTasks = [];
$missingTasks = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $taskDueDateTime = strtotime($row['Duedate'] . ' ' . $row['DueTime']); // Combine due date and due time
        $today = strtotime('today');
        $oneWeekFromToday = strtotime('+1 week');
        $threeWeeksFromToday = strtotime('+3 weeks');

        // Check for status and categorize accordingly
        if ($row['Status'] === 'Assigned') {
            if ($taskDueDateTime === false) {
                $noDueTasks[] = $row; // No due date
            } elseif ($taskDueDateTime < $today) {
                $missingTasks[] = $row; // Task is overdue
            } elseif ($taskDueDateTime === $today) {
                $todayTasks[] = $row; // Task is due today
            } elseif ($taskDueDateTime <= $oneWeekFromToday) {
                $comingUpTasks[] = $row; // Task is due within the next week
            } else {
                $laterTasks[] = $row; // Task is due later
            }
        } elseif ($row['Status'] === 'Missing') {
            $missingTasks[] = $row; // Tasks that are marked as missing
        }
    }

    $assignedCount = count($todayTasks) + count($comingUpTasks) + count($laterTasks) + count($noDueTasks);
    $missingCount = count($missingTasks);
} else {
    echo "No tasks found.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        /* Optional styles for button active state */
        .button-container {
            display: flex;
            justify-content: center;
            margin-bottom: 20px; /* Adjust as needed */
            font-weight:bold;
        }

        .section-button {
            display: inline-block;
            margin: 0 10px; /* Adjust spacing */
            text-decoration: none; /* Remove default underline */
            color: #333; /* Text color */
            cursor: pointer;
            position: relative; /* Ensure position for pseudo-element */
            padding: 5px 10px; /* Adjust padding for button size */
        }

        .section-button.active::after {
            content: ''; /* Required for pseudo-element */
            position: absolute; /* Position relative to the button */
            left: 0;
            right: 0;
            bottom: -2px; /* Adjust as needed */
            height: 1px; /* Underline height */
            background-color: #9B2035; /* Underline color */
        }

        .task-container {
            background-color: #ffff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-left: 100px; /* Margin only on the left */
            margin-right: 100px; /* Margin only on the right */
            position: relative;
            height: 100px;
            margin-top: 20px;
        }

        .category-title {
            margin-top: 20px;
            font-size: 1.2em;
            margin-left: 100px;
            margin-right: 100px;
            margin-bottom: 20px;
        }

        .task-title {
            color: black;
            transition: color 0.3s ease; /* Smooth transition */
        }

        .task-title:hover {
            color: #9B2035; /* Hover color */
        }

        .task-icon {
            position: absolute;
            top: 50%;
            right: -25px; /* Adjust distance from the right */
            transform: translateY(-50%);
            width: 60px;
            height: 60px;
            background-color: #9B2035; /* Adjust color as needed */
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 50px;
        }

        .task-icon ion-icon {
            color: #fff;
            font-size: 25px;
        }
        .due-label{
            color:grey;
        }
        .gs{
            font-size: 14px;
            color:grey;
        }
        .count-circle {
    display: inline-block;
    width: 24px; /* Adjust size as needed */
    height: 24px; /* Adjust size as needed */
    border-radius: 50%; /* Make it circular */
    background-color: #9B2035; /* Circle color */
    color: white; /* Font color */
    text-align: center; /* Center text */
    line-height: 24px; /* Center text vertically */
    margin-left: 5px; /* Spacing from the button text */
    font-size: 14px; /* Font size */
}
.info-message {
    display: flex;
    align-items: center;
    margin-top: 5px; /* Space between title and message */
    margin-left: 100px; /* Move message to the right */  
}

.info-message p {
    font-size: 16px; /* Font size for the message */
    color: #555; /* Color for the message text */
    margin: 0; /* Remove default margin */
}

body {
  background-color: #F1F0F6; /* Light gray */
}

    </style>
    <title>To-Do's</title>
</head>
<body>
    <!-- SIDEBAR -->
    <section id="sidebar">
        <?php include 'navbar.php'; ?>
    </section>
    <!-- SIDEBAR -->

    <!-- NAVBAR -->
    <section id="content">
        <!-- NAVBAR -->
        <?php include 'topbar.php'; ?>
        <!-- NAVBAR -->

        <!-- MAIN -->
        <main>
            <h1 class="title">To-Do's</h1>

            <!-- Clickable buttons within the main content area -->
            <div class="button-container">
                <a href="#" class="section-button <?= empty($_GET['section']) || $_GET['section'] === 'assigned' ? 'active' : ''; ?>" data-section="assigned">
                    Assigned 
                    <span class="count-circle"><?= $assignedCount; ?></span>
                </a>
                <a href="#" class="section-button <?= empty($_GET['section']) || $_GET['section'] === 'missing' ? 'active' : ''; ?>" data-section="missing">
                    Missing 
                    <span class="count-circle"><?= $missingCount; ?></span>
                </a>
            </div>

            <!-- Assigned section content -->
            <div id="assigned-content" style="display: block;">
                <h2 class="category-title">Today</h2>
                <?php foreach ($todayTasks as $task) : ?>
                    <div class="task-container">
                    <a href='taskdetails.php?task_id=<?= $task['TaskID']; ?>&content_id=<?= $task['ContentID']; ?>&user_id=<?= $user_id; ?>' class='taskLink'>
                            <h3 class='task-title'><?= $task['Title']; ?></h3>
                            <p class ="due-label">Due: <?= $task['Duedate']; ?> @<?= $task['DueTime']; ?> </p>
                            <p class ="gs"><?= $task['GS']; ?></p>
                            <div class='task-icon'>
                                <ion-icon name='document-outline'></ion-icon>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>

                <h2 class="category-title">Coming Up</h2>
                <?php foreach ($comingUpTasks as $task) : ?>
                    <div class="task-container">
                    <a href='taskdetails.php?task_id=<?= $task['TaskID']; ?>&content_id=<?= $task['ContentID']; ?>&user_id=<?= $user_id; ?>' class='taskLink'>
                            <h3 class='task-title'><?= $task['Title']; ?></h3>
                            <p class ="due-label" >Due: <?= $task['Duedate']; ?> @<?= $task['DueTime']; ?> </p>
                            <p class ="gs"><?= $task['GS']; ?></p>
                            <div class='task-icon'>
                                <ion-icon name='document-outline'></ion-icon>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>

                <h2 class="category-title">Later</h2>
                <?php foreach ($laterTasks as $task) : ?>
                    <div class="task-container">
                    <a href='taskdetails.php?task_id=<?= $task['TaskID']; ?>&content_id=<?= $task['ContentID']; ?>&user_id=<?= $user_id; ?>' class='taskLink'>                            
                            <h3 class='task-title'><?= $task['Title']; ?></h3>
                            <p class ="due-label">Due: <?= $task['Duedate']; ?> @<?= $task['DueTime']; ?> </p>
                            <p class ="gs"><?= $task['GS']; ?></p>
                            <div class='task-icon'>
                                <ion-icon name='document-outline'></ion-icon>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>

                <h2 class="category-title">No Due Date</h2>
                <?php foreach ($noDueTasks as $task) : ?>
                    <div class="task-container">
                    <a href='taskdetails.php?task_id=<?= $task['TaskID']; ?>&content_id=<?= $task['ContentID']; ?>&user_id=<?= $user_id; ?>' class='taskLink'>                            
                            <h3 class='task-title'><?= $task['Title']; ?></h3>
                            <p class ="due-label">No Due Date</p>
                            <div class='task-icon'>
                                <ion-icon name='document-outline'></ion-icon>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

        <!-- Missing section content -->
            <div id="missing-content" style="display: none;">
                <h2 class="category-title">Missing Tasks</h2>

                <!-- Information Icon and Message -->
                <div class="info-message" style="display: flex; align-items: center; margin-top: 10px;">
                    <ion-icon name='information-circle-outline' style="font-size: 24px; margin-right: 10px; color:#9B2035; "></ion-icon>
                    <p style="font-size: 14px; color: #555; margin: 0;"> <!-- Remove margin for better alignment -->
                        Missing tasks do not accept outputs anymore. For late submissions, inquire with the admin for more information.
                    </p>
                </div>

                <?php foreach ($missingTasks as $task) : ?>
                    <div class="task-container">
                        <a href='taskdetails.php?task_id=<?= $task['TaskID']; ?>&content_id=<?= $task['ContentID']; ?>&user_id=<?= $user_id; ?>' class='taskLink'>                            
                            <h3 class='task-title'><?= $task['Title']; ?></h3>
                            <p class ="due-label">Due: <?= $task['Duedate']; ?> @<?= $task['DueTime']; ?></p>
                            <p class ="gs"><?= $task['GS']; ?></p>
                            <div class='task-icon'>
                                <ion-icon name='document-outline'></ion-icon>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>              
            </div>


        </main>
        <!-- MAIN -->
    </section>

    <script>
        document.querySelectorAll('.section-button').forEach(button => {
            button.addEventListener('click', function() {
                const section = this.getAttribute('data-section');
                
                document.getElementById('assigned-content').style.display = section === 'assigned' ? 'block' : 'none';
                document.getElementById('missing-content').style.display = section === 'missing' ? 'block' : 'none';
                
                // Remove active class from all buttons
                document.querySelectorAll('.section-button').forEach(btn => btn.classList.remove('active'));
                
                // Add active class to the clicked button
                this.classList.add('active');
            });
        });
    </script>
       <script src="https://unpkg.com/ionicons@5.5.2/dist/ionicons.js"></script>
       <script src="assets/js/script.js"></script>  
</body>
</html>
