<?php
session_start();

// Database connection
include 'connection.php';

// Ensure that the user ID is available in the session
if (!isset($_SESSION['user_id'])) {
    die('User not logged in');
}

// Fetch the user ID from the session
$user_id = $_SESSION['user_id'];

// Fetch the dept_ID for the logged-in user from the useracc table
$queryDept = "SELECT dept_ID FROM useracc WHERE UserID = ?";
$stmtDept = mysqli_prepare($conn, $queryDept);
mysqli_stmt_bind_param($stmtDept, "i", $user_id);  // Use $user_id here
mysqli_stmt_execute($stmtDept);
$resultDept = mysqli_stmt_get_result($stmtDept);

$deptID = null;
if ($resultDept && mysqli_num_rows($resultDept) > 0) {
    $rowDept = mysqli_fetch_assoc($resultDept);
    $deptID = $rowDept['dept_ID']; // Get the dept_ID of the user
} else {
    die('Department not found for the user');
}

// If dept_ID is found, fetch the folders based on dept_ID
$folders = [];
if ($deptID) {
    $query = "SELECT DepartmentFolderID, dept_ID, Name, CreationTimestamp FROM departmentfolders WHERE dept_ID = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $deptID);
    mysqli_stmt_execute($stmt);

    // Fetch the folders
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $folders[] = $row;
        }
    } else {
        echo "No folders found for this department.";
    }
} else {
    echo "No department ID found.";
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
            cursor: pointer; /* Change cursor to pointer on hover */
        }
        .folder-item:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.2); /* Optional: shadow on hover */
        }
        .folder-item .icon {
            font-size: 60px; /* Adjust icon size */
            margin-right: 20px; /* Space between icon and text */
            color: #9B2035;
        }
        .name {
            font-size: 1.25rem; /* Adjust font size as needed */
            white-space: nowrap; /* Prevent text from wrapping to the next line */
            overflow: hidden; /* Hide overflow text */
            text-overflow: ellipsis; /* Add ellipsis for overflow text */
            max-width: 100%; /* Set a maximum width for the container */
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
        <h1 class="title">Documents</h1>
    </div>
    <div style = "margin-top:100px"  class="container">
        <div class="row"> 
            <?php if (!empty($folders)): ?>
                <?php foreach ($folders as $folder): ?>
                    <div class="col-md-4 col-sm-6"> <!-- Use Bootstrap col classes for responsive layout -->
                        <div class="folder-item" data-id="<?php echo $folder['DepartmentFolderID']; ?>">
                            <i class="fas fa-folder icon"></i>
                            <h6 class="name"><?php echo htmlspecialchars($folder['Name']); ?></h6>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-folders-message col-12">No folders found.</div>
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
            // Redirect to doc_gfolder.php with the DepartmentFolderID as a query parameter
            window.location.href = `doc_gfolder.php?id=${folderId}`;
        });
    });

    // Search functionality
    const searchInput = document.getElementById('search');
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase(); // Convert search term to lowercase
        folderItems.forEach(item => {
            const folderName = item.querySelector('.name').textContent.toLowerCase(); // Convert folder name to lowercase
            if (folderName.includes(searchTerm)) {
                item.parentElement.style.display = 'block'; // Show the folder's column
            } else {
                item.parentElement.style.display = 'none'; // Hide the folder's column
            }
        });
    });
});

    </script>
    <script src="assets/js/script.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
