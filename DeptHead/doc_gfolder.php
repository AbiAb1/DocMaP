<?php
session_start();
// Database connection
include 'connection.php';

// Initialize $departmentName with a default value
$departmentName = '';
$folders = []; // Initialize the $folders array

// Check if user is logged in
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    // Fetch the dept_ID for the logged-in user from the useracc table
    $queryDept = "SELECT dept_ID FROM useracc WHERE UserID = ?";
    $stmtDept = mysqli_prepare($conn, $queryDept);
    mysqli_stmt_bind_param($stmtDept, "i", $userId);
    mysqli_stmt_execute($stmtDept);
    $resultDept = mysqli_stmt_get_result($stmtDept);

    if ($resultDept && mysqli_num_rows($resultDept) > 0) {
        $rowDept = mysqli_fetch_assoc($resultDept);
        $deptID = $rowDept['dept_ID']; // Get the dept_ID of the user

        // Fetch department folder details based on the dept_ID
        $departmentQuery = "SELECT Name FROM departmentfolders WHERE dept_ID = ?";
        $stmtDeptFolder = mysqli_prepare($conn, $departmentQuery);
        mysqli_stmt_bind_param($stmtDeptFolder, "i", $deptID);
        mysqli_stmt_execute($stmtDeptFolder);
        $departmentResult = mysqli_stmt_get_result($stmtDeptFolder);

        if ($departmentResult && mysqli_num_rows($departmentResult) > 0) {
            $departmentRow = mysqli_fetch_assoc($departmentResult);
            $departmentName = $departmentRow['Name']; // Get the department name
        }

        // Fetch folders related to this dept_ID from the gradelevelfolders table
        $query = "SELECT GradeLevelFolderID, DepartmentFolderID, ContentID, CreationTimestamp 
                  FROM gradelevelfolders WHERE DepartmentFolderID IN (SELECT DepartmentFolderID FROM departmentfolders WHERE dept_ID = ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $deptID);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                // For each folder, fetch the corresponding name from the feedcontent table
                $contentId = $row['ContentID'];
                $contentQuery = "SELECT Title, Captions FROM feedcontent WHERE ContentID = ?";
                $stmtContent = mysqli_prepare($conn, $contentQuery);
                mysqli_stmt_bind_param($stmtContent, "i", $contentId);
                mysqli_stmt_execute($stmtContent);
                $contentResult = mysqli_stmt_get_result($stmtContent);

                if ($contentResult && mysqli_num_rows($contentResult) > 0) {
                    $contentRow = mysqli_fetch_assoc($contentResult);
                    $row['DisplayName'] = $contentRow['Title'] . ' - ' . $contentRow['Captions']; // Combine Title and Caption
                }
                $folders[] = $row;
            }
        }
    } else {
        // Handle case where dept_ID is not found for the user
        $folders = []; // No folders to display
    }
} else {
    // Handle case where user is not logged in
    $folders = []; // No folders to display
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <title>Document Management</title>
        <link rel="icon" type="image/png" href="../img/Logo/docmap-logo-1.png">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
  background-color: #F1F0F6; /* Light gray */
}
        .no-folders-message {
            text-align: center;
            font-size: 1.5rem;
            color: #555;
        }
        .search-filter {
            display: flex;
            justify-content: flex-end;
            margin: 20px 0;
        }
        .search-filter input {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 100%;
            max-width: 300px;
        }
        .folder-item {
            background-color: white;
            border-radius: 10px; /* Rounded corners */
            padding: 15px; /* Inner padding */
            margin-bottom: 10px; /* Space between containers */
            display: flex;
            align-items: center; /* Align items in the center */
            height: 100px; /* Set height for each container */
            transition: box-shadow 0.3s ease; /* Optional: add transition for hover effect */
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); /* Add subtle shadow */
        }
        .folder-item:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.2); /* Shadow on hover */
        }
        .folder-item .icon {
            font-size: 60px; /* Adjust icon size */
            margin-right: 20px; /* Space between icon and text */
            color: #9B2035;
        }
        .name{
            font-size: 22px;
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <section id="sidebar">
        <?php include 'navbar.php'; ?>
    </section>
    <!-- SIDEBAR -->

    <!-- CONTENT -->
    <section id="content">
        <!-- NAVBAR -->
        <?php include 'topbar.php'; ?>
        <!-- NAVBAR -->

        <!-- MAIN -->
        <main>
        <div class="header">
            <h1 class="title">
                <a href="doc_folder.php" style="color: inherit; text-decoration: none;" 
   onmouseover="this.style.color='#9B2035'" 
   onmouseout="this.style.color='inherit'">Documents</a>
                <i class="fas fa-angle-right" style="margin: 0 8px; color: #9B2035;"></i> <!-- Right angle icon -->
                <i class="fas fa-folder" style="margin-left: 8px; color: gray;"></i> <!-- Gray folder icon -->
                <?php echo htmlspecialchars($departmentName); ?>
            
            </h1>
        </div>


            <div class="container">
                <div class="search-filter">
                    <input type="text" placeholder="Search by name or title" id="search">
                </div>

                <div class="row"> 
                    <?php if (!empty($folders)): ?>
                        <?php foreach ($folders as $folder): ?>
                            <div class="col-md-4"> <!-- 4 items per row -->
                            <div class="folder-item" data-id="<?php echo $folder['GradeLevelFolderID']; ?>" data-content-id="<?php echo $folder['ContentID']; ?>"> <!-- Add ContentID as a data attribute -->
                                <i class="fas fa-folder icon"></i>
                                    <h6 class = "name"><?php echo $folder['DisplayName']; ?></h6>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-folders-message">No folders found.</div>
                    <?php endif; ?>
                </div> 
            </div>
        </main>
    </section>

    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Add event listener for clicking folder items
    const folderItems = document.querySelectorAll('.folder-item');
    folderItems.forEach(item => {
        item.addEventListener('click', function() {
            const folderId = this.getAttribute('data-id');
            const contentId = this.getAttribute('data-content-id'); // Get ContentID from data attribute
            console.log(`folderId: ${folderId}, contentId: ${contentId}`);
            window.location.href = `doc_grade_content.php?id=${folderId}&content_id=${contentId}`; // Pass ContentID as query parameter
        });
    });

    // Search functionality
    const searchInput = document.getElementById('search');
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const folderItems = document.querySelectorAll('.folder-item');
        let visibleCount = 0; // To track the number of visible items

        folderItems.forEach(item => {
            const folderName = item.querySelector('.name').textContent.toLowerCase(); // Use .name class to find the name
            if (folderName.includes(searchTerm)) {
                item.style.display = 'flex'; // Maintain flex for visible items
                visibleCount++; // Increment count of visible items
            } else {
                item.style.display = 'none'; // Hide non-matching items
            }
        });

        // Show or hide the "No folders found" message
        const noFoldersMessage = document.querySelector('.no-folders-message');
        if (visibleCount === 0) {
            noFoldersMessage.style.display = 'block';
        } else {
            noFoldersMessage.style.display = 'none';
        }
    });
});


    </script>
    <script src="assets/js/script.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
