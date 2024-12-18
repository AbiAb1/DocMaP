<?php
include 'connection.php';
session_start();

// Handle user approval or rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $action = $_POST['action'];

    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE useracc SET Status = 'Approved' WHERE UserID = ?");
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE useracc SET Status = 'Rejected' WHERE UserID = ?");
    }

    if (isset($stmt)) {
        $stmt->bind_param("i", $id);
        $response = array('status' => 'error');
        if ($stmt->execute()) {
            $response['status'] = 'success';
        }
        $stmt->close();
        echo json_encode($response);
        $conn->close();
        exit;
    }
}

// Fetch users based on status
$statuses = ['Pending', 'Approved', 'Rejected'];
$usersByStatus = [];

foreach ($statuses as $status) {
    $stmt = $conn->prepare("SELECT UserID, fname, mname, lname, role, date_registered FROM useracc WHERE Status = ?");
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $usersByStatus[$status] = $users;
    $stmt->close();
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Approval</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        h1 {
            margin-bottom: 15px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .tabs {
            display: flex;
            gap: 10px;
        }
        .tabs button {
            padding: 10px;
            border: none;
            cursor: pointer;
            background-color: transparent;
            transition: background-color 0.3s;
            border-bottom: 2px solid transparent;

        }
        .tablink{
            font-weight:bold;
            font-size:16px;
        }
        .tabs button.active {
            border-color: #9b2035;
        }
        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #fff;
        }
        .user-table th, .user-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
            background-color: #fff;
        }
        .user-table th {
            background-color: #9b2035;
            color:#fff;
            
        }
        .user-table .actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        .user-table .actions i {
            font-size: 1.2em;
            cursor: pointer;
            transition: color 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
        }
        .user-table .actions .fa-check {
            color: green;
        }
        .user-table .actions .fa-times {
            color: red;
        }
        .search-container {
            display: flex;
            align-items: center;
            position: relative;
        }

        .search-container .search-bar {
            display: none;
            width: 100%;
            max-width: 300px;
        }

        .search-bar input {
            width: 200px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .search-container .search-icon {
            cursor: pointer;
            font-size: 1.5em;
            margin-right: 10px;
        }
        body {
  background-color: #F1F0F6; /* Light gray */
}
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <section id="sidebar">
        <?php include 'navbar.php'; ?>
    </section>
    <!-- SIDEBAR -->
    <section id="content">
        <!-- NAVBAR -->
        <?php include 'topbar.php'; ?>
        <!-- NAVBAR -->

        <!-- MAIN -->
        <main>
            <h1>User Management</h1>
            <div class="header">
                <div class="tabs">
                    <button class="tablink" onclick="openTab('pending')" id="defaultOpen">Pending</button>
                    <button class="tablink" onclick="openTab('approved')">Approved</button>
                    <button class="tablink" onclick="openTab('rejected')">Rejected</button>
                </div>
                <div class="search-container">
                    <i class="fas fa-search search-icon" onclick="toggleSearchBar()"></i>
                    <div class="search-bar">
                        <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Search for names..">
                    </div>
                </div>
            </div>
            <div id="pending" class="tabcontent">
                <table class="user-table" id="pendingTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Date Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usersByStatus['Pending'] as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['fname']) . ' ' . htmlspecialchars($user['mname']) . ' ' . htmlspecialchars($user['lname']); ?></td>
                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                            <td><?php echo htmlspecialchars(date('F j, Y', strtotime($user['date_registered']))); ?></td>
                            <td class="actions">
                                <i class="fas fa-check" onclick="confirmAction(<?php echo $user['UserID']; ?>, 'approve')" title="Approve"></i>
                                <i class="fas fa-times" onclick="confirmAction(<?php echo $user['UserID']; ?>, 'reject')" title="Reject"></i>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div id="approved" class="tabcontent">
                <table class="user-table" id="approvedTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Date Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usersByStatus['Approved'] as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['fname']) . ' ' . htmlspecialchars($user['mname']) . ' ' . htmlspecialchars($user['lname']); ?></td>
                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                            <td><?php echo htmlspecialchars(date('F j, Y', strtotime($user['date_registered']))); ?></td>
                            <td class="actions">
                                <i class="fas fa-times" onclick="confirmAction(<?php echo $user['UserID']; ?>, 'reject')" title="Reject"></i>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div id="rejected" class="tabcontent">
                <table class="user-table" id="rejectedTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Date Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usersByStatus['Rejected'] as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['fname']) . ' ' . htmlspecialchars($user['mname']) . ' ' . htmlspecialchars($user['lname']); ?></td>
                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                            <td><?php echo htmlspecialchars(date('F j, Y', strtotime($user['date_registered']))); ?></td>
                            <td class="actions">
                                <i class="fas fa-check" onclick="confirmAction(<?php echo $user['UserID']; ?>, 'approve')" title="Approve"></i>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </section>

    <script>
        function toggleSearchBar() {
            var searchBar = document.querySelector('.search-bar');
            searchBar.style.display = searchBar.style.display === 'none' || searchBar.style.display === '' ? 'block' : 'none';
        }

        function filterTable() {
            var input, filter, tables, tr, td, i, j, txtValue;
            input = document.getElementById("searchInput");
            filter = input.value.toUpperCase();
            tables = document.querySelectorAll(".tabcontent table");
            tables.forEach(function (table) {
                tr = table.getElementsByTagName("tr");
                for (i = 1; i < tr.length; i++) { // Start from 1 to skip the header row
                    tr[i].style.display = "none"; // Hide the row initially
                    td = tr[i].getElementsByTagName("td");
                    for (j = 0; j < td.length; j++) {
                        if (td[j]) {
                            txtValue = td[j].textContent || td[j].innerText;
                            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                                tr[i].style.display = ""; // Show the row if a match is found
                                break; // Stop checking other cells in the same row
                            }
                        }
                    }
                }
            });
        }

        function confirmAction(id, action) {
            var actionText = action === 'approve' ? 'approve' : 'reject';
            Swal.fire({
                title: `Are you sure you want to ${actionText} this user?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: `Yes, ${actionText} it!`
            }).then((result) => {
                if (result.isConfirmed) {
                    modifyUserStatus(id, action);
                }
            });
        }

        function modifyUserStatus(id, action) {
            fetch('approve_user.php', { // Ensure this URL is correct
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${id}&action=${action}`
            })
            .then(response => response.json())
            .then(data => {
                console.log('Server response:', data); // Debugging output
                if (data.status === 'success') {
                    if (data.email_status === 'sent') {
                        Swal.fire(
                            'Success!',
                            action === 'approve' ? 'User approved and notified via email.' : 'User rejected and notified via email.',
                            'success'
                        );
                    } else if (data.email_status === 'failed') {
                        Swal.fire(
                            'Warning!',
                            'User status updated, but email notification failed. Error: ' + (data.email_error || 'Unknown error'),
                            'warning'
                        );
                    } else {
                        Swal.fire(
                            'Success!',
                            'User status updated.',
                            'success'
                        );
                    }
                    setTimeout(() => { location.reload(); }, 2000); // Reload to reflect changes after 2 seconds
                } else {
                    Swal.fire(
                        'Error!',
                        'An error occurred: ' + (data.error || 'Please try again.'),
                        'error'
                    );
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire(
                    'Error!',
                    'An error occurred. Please try again.',
                    'error'
                );
            });
        }


        function openTab(tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tabcontent");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }
            tablinks = document.getElementsByClassName("tablink");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
            document.getElementById(tabName).style.display = "block";
            event.currentTarget.className += " active";
        }

        document.getElementById("defaultOpen").click();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>
