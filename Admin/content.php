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
$content_color = "";

// Check if content_id is provided in URL
if (isset($_GET['ContentID'])) {
    $content_id = $_GET['ContentID'];

    // Query to fetch tasks based on ContentID
    $sql_tasks = "SELECT * FROM tasks WHERE ContentID = '$content_id' AND Status = 'Assign' ORDER BY Timestamp DESC";
    $result_tasks = mysqli_query($conn, $sql_tasks);

    // Query to fetch content details
    $sql_content = "SELECT Title, Captions, ContentCode, ContentColor AS color FROM feedcontent WHERE ContentID = '$content_id'";
    $result_content = mysqli_query($conn, $sql_content);

    // Fetch content details
    if ($row_content = mysqli_fetch_assoc($result_content)) {
        $content_title = $row_content['Title'];
        $content_captions = $row_content['Captions'];
        $content_code = $row_content['ContentCode'];
        $content_color = $row_content['color'];
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
            /* Custom styles */
            .contentCard {
                background-color: var(--blue);
                border-radius: 12px;
                padding: 20px;
                margin-bottom: 20px;
                margin-top: 20px;
                color: #fff;
                height: 300px;
            
            }

            .contentCard h2 {
                font-size: 40px;
                margin-bottom: 2px;
                font-weight:bold;
            }

            .contentCard p {
                font-size: 20px;
                
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

            .searchBar, #taskTypeFilter {
                width: 30%;
                padding: 10px;
                font-size: 16px;
                border-radius: 8px;
                border: 1px solid #ccc;
                outline: none;
                box-sizing: border-box;
            }
            .tasktitle {
                color: black;
                transition: color 0.3s ease; /* Smooth transition */
            }

            .tasktitle:hover {
            color: #9b2035; /* Hover color dynamically set */
                
            }
            .three-dots {
                background: none;
                border: none;
                font-size: 18px;
                cursor: pointer;
                padding: 5px;
                position: relative;
                float: right;
                color: #fff;
            }

            .three-dots:focus {
                outline: none;
            }

            .dropdown {
                float: right;
                position: relative; /* Ensures the dropdown-menu is positioned relative to this container */
            }

            .dropdown-menu {
                display: none;
                position: absolute;
                top: 35px; /* Adjusts the vertical alignment below the icon */
                /* Moves dropdown to the left, adjust as needed */
                transform: translateX(-10px); /* Moves the dropdown slightly left for better alignment */
                background-color: #fff;
                border: 1px solid #ccc;
                border-radius: 5px;
                box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
                z-index: 10;
                transition: opacity 0.2s ease, transform 0.2s ease;
                opacity: 0;
                transform: scale(0.95) translateX(-10px); /* Adds smooth scaling and position adjustment */
            }

            .dropdown-menu a {
                display: block;
                padding: 10px;
                text-decoration: none;
                color: #333;
                font-size: 14px;
                white-space: nowrap; /* Prevents text wrapping for long content */
            }

            .dropdown-menu a:hover {
                background-color: #f5f5f5;
            }


            .dropdown.open .dropdown-menu {
                display: block;
                opacity: 1;
                transform: scale(1) translateX(-10px); /* Ensures the dropdown stays moved left */
            }

            #teacherDetails ul {
                list-style: none;
                padding: 10;
                margin-top:20px;
            }



            #teacherDetails ul li img {
                margin-right: 10px;
                margin-left: 10px;
                border: 0.5px solid gray;
            
            }
</style>



        </style>
    </head>
    <body style="background-color:#f0f0f0;">
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
                    <div class="contentCard" >
                        <div class="dropdown">
                            <button class="three-dots" onclick="toggleDropdown(this)">    
                                <i class="bx bx-dots-horizontal-rounded" style="font-size:30px;"></i>
                            </button>
                            <div class="dropdown-menu" style ="width:max-content;">
                            <a href="#" data-toggle="modal" data-target="#teachersModal" data-contentid="<?php echo $content_id; ?>"><i class="bx bxs-user-detail" style="margin-right:20px;"></i>Teachers</a>
                                <a href="#"><i class='bx bxs-detail' style="margin-right:20px;"></i>Details</a>
                               
                            </div>
                        </div>
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
                                <select id="taskTypeFilter" onchange="filterTasks()">
                                    <option value="">All Types</option>
                                    <option value="Task">Task</option>
                                    <option value="Reminder">Reminder</option>
                                    <option value="Announcement">Announcement</option>
                                </select>
                                <input type="text" id="searchBar" placeholder="Search tasks..." class="searchBar" oninput="filterTasks()">
                            </div>
                            <div class="taskList">
                                <?php
                                if (mysqli_num_rows($result_tasks) > 0) {
                                    while ($row_task = mysqli_fetch_assoc($result_tasks)) {
                                        $taskID = $row_task['TaskID'];
                                        $taskType = $row_task['Type']; // Get task type
                                        echo "<a href='taskdetails.php?task_id=" . htmlspecialchars($taskID) . "&content_id=" . htmlspecialchars($content_id) . "' class='taskLink'>";
                                        echo "<div class='taskContainer' data-type='" . htmlspecialchars($taskType) . "'>";  // Add data-type attribute
                                        echo "<h3 class ='tasktitle'>" . $row_task['Title'] . "</h3>";
                                        echo "<p class='timestamp'>" . $row_task['TimeStamp'] . "</p>"; /* Added timestamp below title */
                                        
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
                                        echo "</a>";
                                    }
                                } else {
                                    echo "<div class='no-tasks-message'>This is where you will see your tasks, announcements, and reminders for this classroom.</div>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="modal fade" id="teachersModal" tabindex="-1" aria-labelledby="teachersModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                            <h5 class="modal-title" id="teachersModalLabel">Teachers</h5>
                                <div id="teacherDetails" class="mt-3 mb-4">
                                    <p>Loading...</p>
                                </div>
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
            // Define loadTeacherDetails globally so it can be called anywhere
            function loadTeacherDetails(contentId) {
                const teacherDetailsDiv = document.getElementById('teacherDetails');
                const modalTitle = document.getElementById('teachersModalLabel');
                teacherDetailsDiv.innerHTML = "<p>Loading...</p>";
                modalTitle.innerHTML = "Teachers"; // Reset the title while loading

                fetch(`fetch_teacher_details.php?ContentID=${contentId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            teacherDetailsDiv.innerHTML = `<p>${data.error}</p>`;
                        } else {
                            // Update the modal title with the count
                            modalTitle.innerHTML = `Teachers (${data.count})`;

                            let html = '<ul style="list-style: none; padding: 0;">';
                            data.teachers.forEach(teacher => {
                                // Include the UserID as a data attribute
                                html += `
                                    <li style="margin-top: 20px; font-weight: bold; display: flex; align-items: center; justify-content: space-between;">
                                        <div style="display: flex; align-items: center;">
                                            <img src="../img/UserProfile/${teacher.Profile}" alt="Profile" style="width: 50px; height: 50px; border-radius: 50%; margin-right: 10px;">
                                            ${teacher.FULLNAME}
                                        </div>
                                        <div class="dropdown">
                                            <button class="three-dots" onclick="toggleDropdown2(this)" data-userid="${teacher.UserID}">
                                                <i class="bx bx-dots-horizontal-rounded" style="font-size:30px; color:gray"></i>
                                            </button>
                                            <div class="dropdown-menu" style="width:max-content;">
                                                <a href="#" onclick="removeTeacher(event)" data-userid="${teacher.UserID}" data-contentid="${contentId}">
                                                    <i class="bx bx-user-x" style="margin-right:20px;"></i>Remove
                                                </a>                                                    
                                                <a href="#" data-userid="${teacher.UserID}"><i class='bx bxs-detail' style="margin-right:20px;"></i>Details</a>
                                            </div>
                                        </div>
                                    </li>
                                `;
                            });
                            html += '</ul>';
                            teacherDetailsDiv.innerHTML = html;
                        }
                    })
                    .catch(err => {
                        console.error('Error fetching teacher details:', err);
                        teacherDetailsDiv.innerHTML = "<p>Failed to load teacher details.</p>";
                    });
            }

            // Attach event listener to show modal
            $('#teachersModal').on('show.bs.modal', function (event) {
                const button = $(event.relatedTarget); // Button that triggered the modal
                const contentId = button.data('contentid'); // Extract info from data-* attributes
                loadTeacherDetails(contentId);
            });

           // Function to remove teacher
window.removeTeacher = function (event) {
    event.preventDefault();

    // Get the data attributes from the clicked <a> tag
    const userId = event.target.closest('a').getAttribute('data-userid');
    const contentId = event.target.closest('a').getAttribute('data-contentid');

    // Log the userId and contentId to check if they are fetched properly
    console.log('User ID:', userId);
    console.log('Content ID:', contentId);

    // Show SweetAlert confirmation dialog
    Swal.fire({
        title: 'Are you sure?',
        text: "This user will be removed from the grade and will no longer accept tasks. If they rejoin, they won't see old tasks created.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, remove.',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Proceed with AJAX to remove the user from the database
            fetch('remove_teacher.php', {
                method: 'POST',
                body: JSON.stringify({
                    contentId: contentId,
                    userId: userId
                }),
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                console.log('Backend response:', JSON.stringify(data, null, 2)); // Pretty print the response for debugging

                if (data.success) {
                    Swal.fire('Removed!', 'The teacher has been removed from the grade.', 'success');
                    loadTeacherDetails(contentId); // Reload teacher details after removal
                } else {
                    Swal.fire('Error!', data.error || 'There was an issue removing the teacher.', 'error');
                }
            })
            .catch(err => {
                console.error('Error:', err);
                Swal.fire('Error!', 'Failed to remove the teacher. Please try again.', 'error');
            });
        }
    });
};


        </script>





        
        <script>
            function toggleDropdown(button) {
                const dropdown = button.closest('.dropdown');
                dropdown.classList.toggle('open');
            }

            // Close the dropdown when clicking outside
            document.addEventListener('click', function (event) {
                const openDropdowns = document.querySelectorAll('.dropdown.open');
                openDropdowns.forEach(dropdown => {
                    if (!dropdown.contains(event.target)) {
                        dropdown.classList.remove('open');
                    }
                });
            });

            
            function toggleDropdown2(button) {
                const dropdown = button.closest('.dropdown');
                dropdown.classList.toggle('open');
            }

            // Close the dropdown when clicking outside
            document.addEventListener('click', function (event) {
                const openDropdowns = document.querySelectorAll('.dropdown.open');
                openDropdowns.forEach(dropdown => {
                    if (!dropdown.contains(event.target)) {
                        dropdown.classList.remove('open');
                    }
                });
            });


        </script>

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
            const searchQuery = document.getElementById("searchBar").value.toLowerCase();  // Get the search query
            const taskTypeFilter = document.getElementById("taskTypeFilter").value;  // Get the selected task type filter
            const tasks = document.querySelectorAll(".taskContainer");  // Get all task containers

            tasks.forEach(task => {
                const taskTitle = task.querySelector("h3").textContent.toLowerCase();  // Get task title
                const taskType = task.getAttribute("data-type");  // Get task type from data-type attribute

                // Check if task matches both search query and task type filter
                const matchesSearch = taskTitle.includes(searchQuery);  
                const matchesType = taskTypeFilter === "" || taskType === taskTypeFilter;  // If no type filter, match all types

                // Show task if it matches both filters
                if (matchesSearch && matchesType) {
                    task.style.display = "block";  
                } else {
                    task.style.display = "none";  
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
