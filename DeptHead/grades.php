<?php session_start(); ?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grades</title>
    <link rel="icon" type="image/png" href="../img/Logo/docmap-logo-1.png">

    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
body {
  background-color: #F1F0F6; /* Light gray */
}
   .container {
            width: 100%; /* Ensure full width for responsiveness */
            border-radius: 20px;
            margin: 0 auto;
            padding: 0 15px; /* Added horizontal padding for spacing */
            
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: -10px;
            margin-bottom: 20px;
        }


        .icon-button {
            background-color: #9b2035;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 90px;
            transition: background-color 0.3s;
            font-size: 24px;
            padding: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            height:60px;
            width: 60px;

        }

        .icon-button:hover {
            background-color: #861c2e;
        }

        .arrow-link {
            font-size: 24px;
            color: black;
            text-decoration: none;
            border: none; /* Ensure no border is applied */
            margin-right:10px;

        }

        .arrow-link:hover {
            border: 2px solid gray;
            border-radius: 50%;
            border: none; /* Remove any border on hover */
            outline: none; /* Remove any outline on hover (browsers may apply it) */
        }




        .title h3 {
            margin: 0 0 10px;
        }


        button {
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
            font-size: 14px;
        }

        button:hover {
            background-color: #218838;
        }

        .btn-small {
            font-size: 12px;
            padding: 5px 10px;
            background-color: #dc3545;
            transition: background-color 0.3s;
        }

        .btn-small:hover {
            background-color: #c82333;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            position: relative;
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        .close {
            position: absolute;
            top: 10px;
            right: 10px;
            color: #aaa;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close:hover,
        .close:focus {
            color: #333;
        }

        input[type="text"], textarea {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        textarea {
            resize: vertical;
            height: 100px;
        }

        form button {
            background-color: #28a745;
            border-radius: 5px;
        }

        form button:hover {
            background-color: #218838;
        }

        /* Style for arrow icon link */
        .arrow-link {
        position: absolute;
        bottom: 10px; /* Adjust as needed */
        right: 10px; /* Adjust as needed */
        color: black; /* Change the arrow color to red */
        font-size: 24px; /* Size of the arrow icon */
        text-decoration: none;
        transition: color 0.3s, border 0.3s; /* Transition for both color and border */
        margin-left:50px;

    }
        .arrow-link:hover i {
        color: #9b2035; /* Change the icon color when hovered */
        }

        .title {
            position: relative; /* Ensures the arrow icon is positioned relative to the grade container */
        }


        .rounded-container {
            background-color: #fff; /* Example background color */
            border-radius: 10px; /* Rounded corners */
            padding: 15px; /* Padding for content inside */
            margin-bottom: 10px; /* Spacing between containers */
            height:150px;
        }

        .title h3 {
            margin-bottom: 5px; /* Adjust spacing as needed */
            font-size:24px;color:#9b2035;
        }

        .title p {
           
            font-size:13px;
            color:grey;
        }




    .dropdown {
        position: absolute;
        top: 10px;   /* Adjust as needed */
        right: 10px; /* Adjust as needed */
        /* Add spacing between the heading and dropdown */

    }

    .dropdown-toggle {
        background: none;
        border: none;
        cursor: pointer;
        background-color: transparent;
        color:black;
        margin-left: 10px;
    }

    .dropdown-menu {
        display: none; /* Hide the dropdown menu by default */
        position: absolute; /* Position dropdown menu absolutely */
        background-color: white; /* Background color for dropdown */
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); /* Optional: Add shadow for depth */
        border-radius: 4px; /* Optional: Rounded corners */
        min-width: 120px; /* Set a minimum width for the dropdown menu */
        max-width: 200px; /* Set a maximum width for the dropdown menu */
        width: auto; /* Allows the width to adjust based on content */
    }

    .dropdown.active .dropdown-menu {
        display: block;
    }

    .dropdown-menu button {
        display: block;
        width: 100%;
        text-align: center;
        padding: 10px 15px;
        border: none;
        background: none;
        cursor: pointer;
        color: black;
        font-size: 14px;
        
    }

    .dropdown-menu button:hover {
        background-color: #f0f0f0;
    }
    .dropdown button:hover {
        background-color: #f0f0f0;
        
    }
    .user-info{
        color: grey;
   
    }

    /* Style for the color selection circles */
.color-selection {
    display: flex;
    gap: 10px;
    margin: 10px 0;
}

input[type="radio"] {
    display: none; /* Hide the actual radio button */
}

label.color-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: inline-block;
    border: 2px solid #ccc;
    cursor: pointer;
    transition: transform 0.3s ease-in-out;
}

/* Add hover effect to color circles */
label.color-circle:hover {
    transform: scale(1.1);
}

/* Style the selected circle */
input[type="radio"]:checked + label.color-circle {
    border: 3px solid #000;
    box-shadow: 0 0 5px rgba(0, 0, 0, 0.5);
}

/* Button Styling */
button {
    background-color: #9B2035;
    color: white;
    padding: 10px 15px;
    border-radius: 5px;
    cursor: pointer;
}

button:hover {
    background-color: #B52A46;
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
            <div class="header">
                <h1 class ="title">Grades</h1>
                <button id="openModalBtn" class="icon-button">
                    <i class='bx bx-plus'></i>
                </button>
            </div>
            <div class="container">
                <div class="grades-container" id="gradesContainer" style="">
                    <div class="row">
                        <!-- JavaScript will insert grade items here -->
                    </div>
                    
                </div>
            </div>
        </main>
    
        <!-- Create Grade Modal -->
        <!-- Create Grade Modal -->
        <div id="createModal" class="modal">
            <div class="modal-content">
                <span class="close" id="createModalClose">&times;</span>
                <h2>Create Grade</h2>
                <form id="createGradeForm">
                    <input type="hidden" id="deptID" name="deptID" value="<?php echo htmlspecialchars($_GET['deptID']); ?>">
                    
                    <label for="title">Grade:</label><br>
                    <input type="text" id="title" name="title" required><br>
                    
                    <label for="caption">Section:</label><br>
                    <input type="text" id="caption" name="caption" required><br>
                    
                    <!-- Color Selection (Radio Buttons as Circles) -->
                    <label for="color">Select Color:</label><br>
                    
                    <div class="color-selection">
                        <input type="radio" id="blue" name="color" value="#0000FF" required>
                        <label for="blue" class="color-circle" style="background-color: #0000FF;"></label>
                        
                        <input type="radio" id="green" name="color" value="#008000">
                        <label for="green" class="color-circle" style="background-color: #008000;"></label>
                        
                        <input type="radio" id="orange" name="color" value="#FFA500">
                        <label for="orange" class="color-circle" style="background-color: #FFA500;"></label>
                        
                        <input type="radio" id="greyish-black" name="color" value="#2e2e2e">
                        <label for="greyish-black" class="color-circle" style="background-color: #2e2e2e;"></label>
                        
                        <input type="radio" id="peach" name="color" value="#FFDAB9">
                        <label for="peach" class="color-circle" style="background-color: #FFDAB9;"></label>
                    </div>
                    
                    <button type="submit">Create</button>
                </form>
            </div>
        </div>


        <!-- Edit Grade Modal -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <span class="close" id="editModalClose">&times;</span>
                <h2>Edit Grade</h2>
                <form id="editGradeForm">
                    <input type="hidden" id="editContentID" name="contentID">
                    <input type="hidden" id="deptID" name="deptID" value="<?php echo htmlspecialchars($_GET['deptID']); ?>">
                    <label for="editTitle">Grade:</label><br>
                    <input type="text" id="editTitle" name="title" required><br>
                    <label for="editCaption">Section:</label><br>
                    <input type="text" id="editCaption" name="caption" required><br>
                    <button type="submit">Update</button>
                </form>
            </div>
        </div>
    </section>
    <script>


 document.addEventListener('click', function (event) {
    const dropdowns = document.querySelectorAll('.dropdown');
    dropdowns.forEach(dropdown => {
        const toggle = dropdown.querySelector('.dropdown-toggle');
        const menu = dropdown.querySelector('.dropdown-menu');
        
        // Check if the clicked element is the dropdown toggle
        if (event.target === toggle) {
            dropdown.classList.toggle('active'); // Toggle the active class to show/hide the menu
        } else {
            dropdown.classList.remove('active'); // Close the dropdown if clicking outside
        }
    });
});
</script>


    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const deptID = new URLSearchParams(window.location.search).get('deptID');
            if (deptID) {
                loadGrades(deptID);
                loadDepartmentName(deptID); // Load department name
            }

            document.getElementById('createGradeForm').addEventListener('submit', function (event) {
                event.preventDefault();
                createGrade();
            });

            document.getElementById('editGradeForm').addEventListener('submit', function (event) {
                event.preventDefault();
                updateGrade();
            });

            document.getElementById('openModalBtn').addEventListener('click', function () {
                document.getElementById('createModal').style.display = 'flex';
            });

            document.getElementById('createModalClose').addEventListener('click', function () {
                document.getElementById('createModal').style.display = 'none';
            });

            document.getElementById('editModalClose').addEventListener('click', function () {
                document.getElementById('editModal').style.display = 'none';
            });
        });

        function loadGrades(deptID) {
    fetch(`grades_management.php?action=read&deptID=${deptID}`)
        .then(response => response.json())
        .then(data => {
            const container = document.querySelector('#gradesContainer .row'); // Select the row
            container.innerHTML = '';
            if (data.feedcontent && data.feedcontent.length) {
                data.feedcontent.forEach(title => {
                    const colDiv = document.createElement('div');
                    colDiv.classList.add('col-lg-4', 'col-md-6', 'col-12', 'mb-4');

                    const gradeDiv = document.createElement('div');
                    gradeDiv.classList.add('title', 'rounded-container', 'p-3', 'border'); // Add classes for styling

                    gradeDiv.innerHTML = `
                        <div class="top" style="margin-top:5px;">
                            <h3>${title.Title}</h3>
                            <p>Section: ${title.Captions}</p> 
                            
                        </div>
                         <!-- User Icon and Count Section -->
                        <div class="user-info mt-2 d-flex align-items-center">
                            <i class="bx bxs-user-circle" style="font-size: 1.2rem; margin-right: 5px;"></i>
                            <span style="font-size: 13px;">${title.user_count} Users</span>
                        </div>
                        <div class="dropdown">
                            <button class=" dropdown-toggle" data-bs-toggle="dropdown"></button>
                            <ul class="dropdown-menu">
                                <li><button class="dropdown-item" onclick="editGrade(${title.ContentID}, '${title.Title}', '${title.Captions}')"><i class='bx bx-edit-alt'></i>Edit</button></li>
                            </ul>
                        </div>
                        <a href="content.php?ContentID=${title.ContentID}&Title=${encodeURIComponent(title.Title)}&Captions=${encodeURIComponent(title.Captions)}" class="arrow-link text-decoration-none">
                            <i class='bx bx-right-arrow-alt'></i>
                        </a>
                    `;
                    
                    colDiv.appendChild(gradeDiv);
                    container.appendChild(colDiv);

                    // Load department name for this grade
                    loadDepartmentName(title.deptID, title.ContentID);
                });
            } else {
                container.innerHTML = '<p>No grades found.</p>';
            }
        });
}



        function loadDepartmentName(deptID, ContentID) {
            fetch(`department_management.php?action=getName&deptID=${deptID}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        document.getElementById(`deptName-${ContentID}`).textContent = data.departmentName; // Update department name
                    } else {
                        console.error('Failed to fetch department name');
                    }
                })
                .catch(error => console.error('Error fetching department name:', error));
        }

        function createGrade() {
            const deptID = document.getElementById('deptID').value;
            const title = document.getElementById('title').value;
            const caption = document.getElementById('caption').value;
            const color = document.querySelector('input[name="color"]:checked').value;

            fetch('grades_management.php?action=create', {
                method: 'POST',
                body: JSON.stringify({ title, caption, deptID }),
                headers: { 'Content-Type': 'application/json' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    loadGrades(deptID);
                    document.getElementById('createGradeForm').reset();
                    document.getElementById('createModal').style.display = 'none';
                } else {
                    alert('Error creating grade: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function editGrade(ContentID, titleValue, captionValue) {
            const editModal = document.getElementById('editModal');
            const editTitleInput = document.getElementById('editTitle');
            const editCaptionInput = document.getElementById('editCaption');
            const editContentIDInput = document.getElementById('editContentID');

            editTitleInput.value = titleValue;
            editCaptionInput.value = captionValue;
            editContentIDInput.value = ContentID;

            editModal.style.display = 'flex';

            document.getElementById('editGradeForm').addEventListener('submit', function (event) {
                event.preventDefault();
                updateGrade();
            });
        }

        function updateGrade() {
            const deptID = document.getElementById('deptID').value;
            const ContentID = document.getElementById('editContentID').value;
            const title = document.getElementById('editTitle').value;
            const caption = document.getElementById('editCaption').value;

            fetch(`update_grade.php?id=${ContentID}`, {
                method: 'POST',
                body: JSON.stringify({ title, caption, deptID }),
                headers: { 'Content-Type': 'application/json' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    loadGrades(deptID);
                    document.getElementById('editModal').style.display = 'none';
                } else {
                    alert('Error updating grade: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function deleteGrade(id) {
            const deptID = new URLSearchParams(window.location.search).get('deptID');

            fetch(`grades_management.php?action=delete&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        loadGrades(deptID);
                    } else {
                        alert('Error deleting grade');
                    }
                })
                .catch(error => console.error('Error:', error));
        }

    </script>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
