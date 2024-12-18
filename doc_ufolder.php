<?php
session_start();
// Database connection
include 'connection.php';

// Initialize department name and folders array
$departmentName = '';
$folders = [];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Retrieve user_id from session
$user_id = $_SESSION['user_id'];

// Fetch folders based on user_id from session
$query = "SELECT uf.UserFolderID, uf.UserContentID, uf.Timestamp 
          FROM userfolders uf
          INNER JOIN usercontent uc ON uf.UserContentID = uc.UserContentID
          WHERE uc.UserID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $contentId = $row['UserContentID']; // UserContentID to retrieve ContentID

        // Get ContentID and Title, Captions from feedcontent
        $contentQuery = "SELECT fc.Title, fc.Captions 
                         FROM feedcontent fc
                         INNER JOIN usercontent uc ON uc.ContentID = fc.ContentID
                         WHERE uc.UserContentID = ?";
        $contentStmt = $conn->prepare($contentQuery);
        $contentStmt->bind_param("i", $contentId);
        $contentStmt->execute();
        $contentResult = $contentStmt->get_result();

        if ($contentResult && $contentResult->num_rows > 0) {
            $contentRow = $contentResult->fetch_assoc();
            $row['DisplayName'] = $contentRow['Title'] . ' - ' . $contentRow['Captions']; // Combine Title and Caption
        } else {
            $row['DisplayName'] = 'No title found';
        }

        $folders[] = $row; // Add folder to list
    }
} else {
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
    <link rel="icon" type="image/png" href="img/Logo/docmap-logo-1.png">
    <title>Teacher Document Management</title>
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
<body style="background-color#f0f0f0;">
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
                <a href="doc_dfolder.php" style="text-decoration: none; color: inherit;"> My Documents</a>
                
                
            
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
                                <div class="folder-item" data-id="<?php echo $folder['UserFolderID']; ?>"> <!-- Use UserFolderID here -->
                                    <i class="fas fa-folder icon"></i>
                                    <h6 class="name"><?php echo $folder['DisplayName']; ?></h6>
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
                    window.location.href = `doc_ugrade_content.php?id=${folderId}`; // Change to the appropriate next page
                });
            });

            // Search functionality (optional)
            const searchInput = document.getElementById('search');
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const folderItems = document.querySelectorAll('.folder-item');
                let visibleCount = 0; // To track the number of visible items

                folderItems.forEach(item => {
                    const folderName = item.querySelector('h3').textContent.toLowerCase();
                    if (folderName.includes(searchTerm)) {
                        item.style.display = 'flex'; // Maintain flex for visible items
                        visibleCount++; // Increment count of visible items
                    } else {
                        item.style.display = 'none'; // Hide non-matching items
                    }
                });

                // Optional: Show a message if no items match
                const noFoldersMessage = document.querySelector('.no-folders-message');
                if (visibleCount === 0) {
                    noFoldersMessage.style.display = 'block'; // Show message if no items found
                } else {
                    noFoldersMessage.style.display = 'none'; // Hide message if items are found
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
