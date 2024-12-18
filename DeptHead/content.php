<?php
session_start();
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

// Check if content_id is provided in URL
if (isset($_GET['ContentID'])) {
    $content_id = $_GET['ContentID'];

    // Query to fetch tasks based on ContentID
    $sql_tasks = "SELECT * FROM tasks WHERE ContentID = '$content_id' AND Status = 'Assign' AND ApprovalStatus = 'Approved' ORDER BY Timestamp DESC";

    $result_tasks = mysqli_query($conn, $sql_tasks);

    // Query to fetch content details
    $sql_content = "SELECT Title, Captions, ContentCode FROM feedcontent WHERE ContentID = '$content_id'";
    $result_content = mysqli_query($conn, $sql_content);

    // Fetch content details
    if ($row_content = mysqli_fetch_assoc($result_content)) {
        $content_title = $row_content['Title'];
        $content_captions = $row_content['Captions'];
        $content_code = $row_content['ContentCode'];
    }

    // Start HTML output
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Content</title>
            <link rel="icon" type="image/png" href="../img/Logo/docmap-logo-1.png">

        <!-- ======= Styles ====== -->
        <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
        <link href="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.css" rel="stylesheet">
        <!-- Add Bootstrap CSS link -->
        <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="assets/css/styles.css">
        <style>
            body {
  background-color: #F1F0F6; /* Light gray */
}
            /* Custom styles */
            .contentCard {
                background-color: var(--blue);
                border-radius: 12px;
                padding: 20px;
                margin-bottom: 20px;
                color: #fff;
                height: 300px;
            }

            .contentCard h2 {
                font-size: 24px;
                margin-bottom: 10px;
                font-weight:bold;
            }

            .contentCard p {
                font-size: 16px;
                line-height: 1.6;
            }

            .taskContainer {
                background-color: #ffff;
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 15px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                position: relative;
                display: flex;
                flex-direction: column; /* Change the layout to column */
                justify-content: flex-start; /* Align content to the top */
                align-items: flex-start; /* Align elements to the left */
                height: auto; /* Allow height to adjust to content */
            }

            .taskContainer h3 {
                font-size: 20px;
                margin-bottom: 5px;
                color: black;
                font-weight:bold;
            }

            .taskContainer p.timestamp {
                font-size: 14px;
                color: #666;
                
            }

            .taskIcon {
                width: 60px;
                height: 60px;
                background-color: #9B2035;
                border-radius: 50%;
                display: flex;
                justify-content: center;
                align-items: center;
                position: absolute;
                right: 10px; /* Positions the icon 10px from the right */
                top: 50%; /* Vertically centers the icon */
                transform: translateY(-50%); /* Fine-tunes the vertical alignment */
            }

            .taskIcon ion-icon {
                color: #fff;
                font-size: 25px;
            }

            .no-tasks-message {
                text-align: center;
                color: #666;
                font-size: 18px;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 200px;
            }

            .contentCodeContainer {
                background-color: #fff;
                padding: 15px;
                border-radius: 8px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                position: relative; /* Added to allow absolute positioning of the icon */
                margin-bottom: 15px;
            }

            #copyIcon {
                font-size: 24px;
                cursor: pointer;
                position: absolute;
                top: 10px;
                right: 10px;
                margin-top:5px;
            }
            #copyIcon:hover {
                color:#9b2035;
            }

          /* Style for the taskLink hover */
            .taskLink:hover {
                color: #9b2035; /* Change text color to #9b2035 */
                cursor: pointer;
                text-decoration: none;
            }

            /* Change color of h3 and p.timestamp when taskLink is hovered */
            .taskLink:hover ~ h3,
            .taskLink:hover ~ p.timestamp {
                color: #9b2035; /* Change text color to #9b2035 */
            }
            .searchContainer {
                margin-bottom: 15px; /* Space between search bar and tasks */
                text-align: right;
            }

            .searchBar {
                width: 30%;
                padding: 10px;
                font-size: 16px;
                border-radius: 8px;
                border: 1px solid #ccc;
                outline: none;
                box-sizing: border-box;
            }

        </style>
    </head>
    <body>
        <!-- SIDEBAR -->
        <section id="sidebar">
            <?php include 'navbar.php'; ?>
        </section>

        <!-- CONTENT -->
        <section id="content">
            <!-- NAVBAR -->
            <?php include 'topbar.php'; ?>
            <!-- MAIN -->
            <main>
                <h1 class ="title">Content</h1>
                <!-- Content Details -->
                <div class="container">
                    <div class="contentCard">
                        <h2><?php echo htmlspecialchars($content_title); ?></h2>
                        <p><?php echo htmlspecialchars($content_captions); ?></p>
                    </div>

                    <!-- Tasks List -->
                    <div class="row">
                        <div class="col-md-3">
                            <!-- Display Content Code -->
                            <div class="contentCodeContainer">
                                <h5>Content Code</h5>
                                <p style="font-weight:bold;color:#9b2035; font-size:30px;" id="contentCode"><?php echo htmlspecialchars($content_code); ?></p>
                                <i class="bx bx-fullscreen" id="copyIcon" title="Copy code"style="font-size: 24px; cursor: pointer; position: absolute; top: 10px; right: 10px;"></i>
                            </div>

                        </div>
                        <div class="col-md-9">
                            <div class="searchContainer">
                                <input type="text" id="searchBar" placeholder="Search tasks..." class="searchBar" oninput="filterTasks()">
                            </div>
                            <div class="taskList">
                                <?php
                                if (mysqli_num_rows($result_tasks) > 0) {
                                    while ($row_task = mysqli_fetch_assoc($result_tasks)) {
                                        $taskID = $row_task['TaskID'];
                                        echo "<a href='taskdetails.php?task_id=" . htmlspecialchars($taskID) . "&content_id=" . htmlspecialchars($content_id) . "' class='taskLink'>";
                                        echo "<div class='taskContainer'>";
                                        echo "<h3>" . $row_task['Title'] . "</h3>";
                                        echo "<p class='timestamp'>" . $row_task['TimeStamp'] . "</p>"; /* Added timestamp below title */
                                        $taskType = $row_task['Type'];
                                        $iconClass = '';
                                        switch ($taskType) {
                                            case 'Task':
                                                $iconClass = 'document-outline';
                                                break;
                                            case 'Reminder':
                                                $iconClass = 'calendar-clear-outline';
                                                break;
                                            case 'Announcement':
                                                $iconClass = 'notifications-outline';
                                                break;
                                        }
                                        echo "<div class='taskIcon'><ion-icon name='$iconClass'></ion-icon></div>";
                                        echo "</div>";
                                    }
                                } else {
                                    echo "<div class='no-tasks-message'>This is where you will see your tasks, announcements, and reminders for this classroom.</div>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </section>

        <!-- Scripts -->
        <script src="assets/js/script.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
        <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
        <!-- Add Bootstrap JS and dependencies -->
        <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

        <script>
    document.getElementById('copyIcon').addEventListener('click', function() {
        // Get the content code text
        var contentCode = document.getElementById('contentCode').textContent;

        // Create a temporary input to copy the content code
        var tempInput = document.createElement('input');
        document.body.appendChild(tempInput);
        tempInput.value = contentCode;
        tempInput.select();
        document.execCommand('copy'); // Copies the text

        // Remove the temporary input
        document.body.removeChild(tempInput);

        // Optionally, show a confirmation message
        alert('Content Code copied to clipboard!');
    });

    function filterTasks() {
        const searchQuery = document.getElementById("searchBar").value.toLowerCase();
        const tasks = document.querySelectorAll(".taskContainer");

        tasks.forEach(task => {
            // Select the task title directly from the h3 element
            const taskTitle = task.querySelector("h3").textContent.toLowerCase();

            if (taskTitle.includes(searchQuery)) {
                task.style.display = "block"; // Show the task
            } else {
                task.style.display = "none"; // Hide the task if search query doesn't match
            }
        });
    }
</script>


    </body>
    </html>
    <?php
} else {
    echo "Content ID not provided.";
}
?>
