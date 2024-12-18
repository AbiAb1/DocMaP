<?php  if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Include database connection
        include 'connection.php';?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://unpkg.com/ionicons@5.5.2/dist/ionicons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        body {
  background-color: #F1F0F6; /* Light gray */
}
        /* Basic Styles */
        body {
            font-family: Arial, sans-serif;
            overflow: hidden;
            display: flex;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .header {
            margin-top: -10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .header h1 {
            color: #333;
            margin: 0;
            font-size: 1.5rem;
        }

        .search-filter {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
        }

        .search-filter input,
        .search-filter select {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 48%;
        }

        .document-list {
            display: flex;
            flex-wrap: wrap; /* Allows wrapping to the next line */
            gap: 10px;
            flex: 2; /* Allow list to take up more space */
        }

        .document-item {
            display: flex;
            align-items: center;
            padding: 10px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            width: 280px; /* Adjust size as needed */
            justify-content: center;
            cursor: pointer; /* Indicate clickable */
        }

        .document-item .icon {
            font-size: 48px;
            margin-right: 15px;
            color: #9B2035;
        }

        .document-item h3 {
            margin: 0;
            text-align: center;
            font-size: 16px;
            font-weight: normal;
        }

        .document-details {
            flex: none; /* Allow panel to float */
            background: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            display: none; /* Hide by default */
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: fixed; /* Fixed position */
            top: 50%;
            left: 60%;
            transform: translate(-50%, -50%); /* Center it */
            z-index: 10; /* Ensure it appears above other content */
            max-width: 300px;
            max-height: 280px;
            width: 100%;
            height: 100%;
        }

        /* Overlay to darken the background */
        .overlay {
            display: none; /* Hide by default */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9; /* Below the details panel but above other content */
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
                <h1>Document Management</h1>
            </div>
            <div class="container">
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
                    <!-- Document items will be injected here by JavaScript -->
                </div>

                
            </div>
        </main>
    </section>

    <div class="overlay"></div>
    <div class="document-details"></div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="assets/js/script.js"></script>
    <script>
        // Example data - replace with dynamic data from your database
        const documents = [
            { id: 1, name: 'Document 1', mimeType: 'application/pdf', status: 'approved', description: 'Description for Document 1', size: '1.2 MB', dateCreated: '2023-01-10', dateModified: '2023-01-15', owner: 'John Doe' },
            { id: 2, name: 'Document 2', mimeType: 'image/jpeg', status: 'pending', description: 'Description for Document 2', size: '3.5 MB', dateCreated: '2023-02-12', dateModified: '2023-02-13', owner: 'Jane Smith' },
            { id: 3, name: 'Document 3', mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', status: 'approved', description: 'Description for Document 3', size: '450 KB', dateCreated: '2023-03-15', dateModified: '2023-03-16', owner: 'Alice Johnson' },
            { id: 4, name: 'Document 4', mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', status: 'needs-revision', description: 'Description for Document 4', size: '2.1 MB', dateCreated: '2023-04-18', dateModified: '2023-04-20', owner: 'Bob Brown' },
            { id: 5, name: 'Document 5', mimeType: 'text/plain', status: 'approved', description: 'Description for Document 5', size: '150 KB', dateCreated: '2023-05-10', dateModified: '2023-05-12', owner: 'Charlie Davis' },
            { id: 6, name: 'Document 6', mimeType: 'image/png', status: 'pending', description: 'Description for Document 6', size: '2.8 MB', dateCreated: '2023-06-14', dateModified: '2023-06-15', owner: 'Dana Lee' },
            { id: 7, name: 'Document 7', mimeType: 'application/zip', status: 'approved', description: 'Description for Document 7', size: '5.4 MB', dateCreated: '2023-07-10', dateModified: '2023-07-12', owner: 'Eve Martinez' },
            { id: 8, name: 'Document 8', mimeType: 'application/x-rar-compressed', status: 'needs-revision', description: 'Description for Document 8', size: '4.2 MB', dateCreated: '2023-08-14', dateModified: '2023-08-16', owner: 'Frank Wilson' },
            { id: 9, name: 'Document 9', mimeType: 'application/pdf', status: 'approved', description: 'Description for Document 9', size: '1.8 MB', dateCreated: '2023-09-10', dateModified: '2023-09-11', owner: 'Grace Hall' },
            { id: 10, name: 'Document 10', mimeType: 'image/jpeg', status: 'pending', description: 'Description for Document 10', size: '3.2 MB', dateCreated: '2023-10-15', dateModified: '2023-10-16', owner: 'Hank Scott' },
            { id: 11, name: 'Document 11', mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', status: 'approved', description: 'Description for Document 11', size: '480 KB', dateCreated: '2023-11-12', dateModified: '2023-11-14', owner: 'Ivy King' },
            { id: 12, name: 'Document 12', mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', status: 'needs-revision', description: 'Description for Document 12', size: '2.5 MB', dateCreated: '2023-12-10', dateModified: '2023-12-12', owner: 'Jack Wright' },
            { id: 13, name: 'Document 13', mimeType: 'text/plain', status: 'approved', description: 'Description for Document 13', size: '130 KB', dateCreated: '2023-01-05', dateModified: '2023-01-07', owner: 'Karen Thompson' },
            { id: 14, name: 'Document 14', mimeType: 'image/png', status: 'pending', description: 'Description for Document 14', size: '2.9 MB', dateCreated: '2023-02-18', dateModified: '2023-02-19', owner: 'Liam White' },
            { id: 15, name: 'Document 15', mimeType: 'application/zip', status: 'approved', description: 'Description for Document 15', size: '5.0 MB', dateCreated: '2023-03-10', dateModified: '2023-03-12', owner: 'Mia Green' },
            { id: 16, name: 'Document 16', mimeType: 'application/x-rar-compressed', status: 'needs-revision', description: 'Description for Document 16', size: '4.5 MB', dateCreated: '2023-04-12', dateModified: '2023-04-14', owner: 'Noah Harris' },
            { id: 17, name: 'Document 17', mimeType: 'application/pdf', status: 'approved', description: 'Description for Document 17', size: '1.9 MB', dateCreated: '2023-05-11', dateModified: '2023-05-13', owner: 'Olivia Clark' },
            { id: 18, name: 'Document 18', mimeType: 'image/jpeg', status: 'pending', description: 'Description for Document 18', size: '3.0 MB', dateCreated: '2023-06-15', dateModified: '2023-06-17', owner: 'Paul Lewis' },
            { id: 19, name: 'Document 19', mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', status: 'approved', description: 'Description for Document 19', size: '470 KB', dateCreated: '2023-07-18', dateModified: '2023-07-20', owner: 'Quinn Robinson' },
            { id: 20, name: 'Document 20', mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', status: 'needs-revision', description: 'Description for Document 20', size: '2.4 MB', dateCreated: '2023-08-14', dateModified: '2023-08-16', owner: 'Rachel Walker' }
        ];

        function displayDocuments(docs) {
            const documentList = document.querySelector('.document-list');
            documentList.innerHTML = '';
            docs.forEach(doc => {
                const iconClass = getIconForMimeType(doc.mimeType);
                documentList.innerHTML += `
                    <div class="document-item" data-id="${doc.id}">
                        <i class="fas ${iconClass} icon"></i>
                        <h3>${doc.name}</h3>
                    </div>
                `;
            });

            // Add event listeners to document items
            document.querySelectorAll('.document-item').forEach(item => {
                item.addEventListener('click', function() {
                    const docId = this.getAttribute('data-id');
                    showDocumentDetails(docId);
                });
            });
        }

        function getIconForMimeType(mimeType) {
            const mimeTypeIcons = {
                'application/pdf': 'fa-file-pdf',
                'application/msword': 'fa-file-word',
                'application/vnd.ms-excel': 'fa-file-excel',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'fa-file-word',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': 'fa-file-excel',
                'image/jpeg': 'fa-file-image',
                'image/png': 'fa-file-image',
                'text/plain': 'fa-file-alt',
                'application/zip': 'fa-file-archive',
                'application/x-rar-compressed': 'fa-file-archive'
            };
            return mimeTypeIcons[mimeType] || 'fa-file';
        }

        function showDocumentDetails(id) {
            const doc = documents.find(d => d.id === parseInt(id));
            const detailsDiv = document.querySelector('.document-details');
            const overlay = document.querySelector('.overlay');
            if (doc) {
                detailsDiv.innerHTML = `
                    <h2>${doc.name}</h2>
                    <p><strong>Status:</strong> ${doc.status}</p>
                    <p><strong>Description:</strong> ${doc.description}</p>
                    <p><strong>Type:</strong> ${doc.mimeType}</p>
                    <p><strong>Size:</strong> ${doc.size}</p>
                    <p><strong>Date Created:</strong> ${doc.dateCreated}</p>
                    <p><strong>Date Modified:</strong> ${doc.dateModified}</p>
                    <p><strong>Owner:</strong> ${doc.owner}</p>
                `;
                detailsDiv.style.display = 'block'; // Show details
                overlay.style.display = 'block'; // Show overlay
            }
        }

        // Hide document details when the overlay is clicked
        document.querySelector('.overlay').addEventListener('click', function() {
            document.querySelector('.document-details').style.display = 'none'; // Hide details
            this.style.display = 'none'; // Hide overlay
        });

        // Ensure the details panel hides when clicking outside of it
        document.querySelector('.container').addEventListener('click', function(event) {
            if (!event.target.closest('.document-item') && !event.target.closest('.document-details')) {
                document.querySelector('.document-details').style.display = 'none'; // Hide details
                document.querySelector('.overlay').style.display = 'none'; // Hide overlay
            }
        });

        document.getElementById('search').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const filteredDocs = documents.filter(doc => doc.name.toLowerCase().includes(searchTerm));
            displayDocuments(filteredDocs);
        });

        document.getElementById('filter-status').addEventListener('change', function() {
            const status = this.value;
            const filteredDocs = documents.filter(doc => doc.status === status || status === '');
            displayDocuments(filteredDocs);
        });

        // Display documents on page load
        displayDocuments(documents);
    </script>
</body>
</html>
