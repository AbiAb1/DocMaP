<?php

// Database connection
include 'connection.php';

// Function to map MIME types to FontAwesome icons
function getIconForMimeType($mimeType) {
    $icon = 'file'; // Default icon if MIME type not matched

    // Mapping MIME types to FontAwesome icons
    $mimeTypeIcons = [
        'application/pdf' => 'fa-file-pdf',
        'application/msword' => 'fa-file-word',
        'application/vnd.ms-excel' => 'fa-file-excel',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'fa-file-word',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'fa-file-excel',
        'image/jpeg' => 'fa-file-image',
        'image/png' => 'fa-file-image',
        'text/plain' => 'fa-file-alt',
        // Add more MIME types and corresponding icons as needed
    ];

    if (array_key_exists($mimeType, $mimeTypeIcons)) {
        $icon = $mimeTypeIcons[$mimeType];
    }

    return $icon;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Document Management</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://unpkg.com/ionicons@5.5.2/dist/ionicons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
  background-color: #F1F0F6; /* Light gray */
}
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .main-screen .icon-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 40px 0;
        }

        .main-screen .icon-container .large-icon {
            font-size: 120px;
            color: #4a90e2;
            margin-bottom: 10px;
        }

        .main-screen .icon-container .department-name {
            font-size: 20px;
            font-weight: bold;
        }

        .search-filter {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .search-filter input, .search-filter select {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 200px;
        }

        .document-list, .document-details {
            margin-top: 20px;
        }

        .document-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .document-item .fa {
            font-size: 48px; /* Increased icon size */
            margin-right: 15px; /* Adjust spacing if needed */
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <section id="sidebar">
        <a href="#" class="brand"><i class='bx bxs-smile icon'></i> AdminSite</a>
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
                <h1>Document Management</h1>
            </div>
            <div class="search-filter">
                <input type="text" placeholder="Search by name or title" id="search">
                <select id="filter-status">
                    <option value="">Filter by status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="needs-revision">Needs Revision</option>
                </select>
            </div>
            <div class="document-list">
                <!-- Fetch and display documents here -->
                <?php
                // Fetch documents from the database
                $sql = "SELECT * FROM documents WHERE Status = 1";
                if ($result = $conn->query($sql)) {
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $iconClass = getIconForMimeType($row["mimeType"]);
                            echo '<div class="document-item">';
                            echo '<i class="fas ' . $iconClass . '"></i>';
                            echo '<h3>' . htmlspecialchars($row["name"]) . '</h3>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p>No documents found.</p>';
                    }
                } else {
                    echo '<p>Error: ' . htmlspecialchars($conn->error) . '</p>';
                }
                ?>
            </div>
            <div class="document-details">
                <!-- Details of the selected document -->
            </div>
        </main>
    </section>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>
