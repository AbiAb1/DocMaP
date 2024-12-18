<?php
session_start();
// Database connection
include 'connection.php';

// Initialize $departmentName with a default value
$departmentName = '';

// Check if DepartmentFolderID is set in the URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    // Sanitize the input to prevent SQL injection
    $departmentFolderId = intval($_GET['id']); // Use intval to ensure it's an integer

    // Fetch the selected department name based on DepartmentFolderID
    $departmentQuery = "SELECT Name FROM departmentfolders WHERE DepartmentFolderID = $departmentFolderId";
    $departmentResult = mysqli_query($conn, $departmentQuery);

    if ($departmentResult && mysqli_num_rows($departmentResult) > 0) {
        $departmentRow = mysqli_fetch_assoc($departmentResult);
        $departmentName = $departmentRow['Name']; // Get the department name
    }

    // Fetch folders from the database based on the DepartmentFolderID
    $query = "SELECT GradeLevelFolderID, DepartmentFolderID, ContentID, CreationTimestamp 
              FROM gradelevelfolders 
              WHERE DepartmentFolderID = $departmentFolderId";
    $result = mysqli_query($conn, $query);
    $folders = [];

    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            // For each folder, fetch the corresponding name from the feedcontent table
            $contentId = $row['ContentID'];
            $contentQuery = "SELECT Title, Captions FROM feedcontent WHERE ContentID = $contentId";
            $contentResult = mysqli_query($conn, $contentQuery);

            if ($contentResult && mysqli_num_rows($contentResult) > 0) {
                $contentRow = mysqli_fetch_assoc($contentResult);
                $row['DisplayName'] = $contentRow['Title'] . ' - ' . $contentRow['Captions']; // Combine Title and Caption
            }
            $folders[] = $row;
        }
    }
} else {
    // Handle case where ID is not provided
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
    <title> Documents</title>
    <link rel="icon" type="image/png" href="../img/Logo/docmap-logo-1.png">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
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
<body style="background-color:#f0f0f0;">
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
                <a href="doc_dfolder.php" style="text-decoration: none; color: inherit;">Documents</a>
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
