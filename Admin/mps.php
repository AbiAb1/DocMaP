<?php
session_start();
include 'connection.php';

// Fetch the user ID from the session
$user_id = $_SESSION['user_id']; // Ensure you set this session variable upon login

// Fetch instructors
$queryTeachers = "SELECT CONCAT(fname, ' ',lname) AS TeacherName, UserID
                  FROM useracc 
                  WHERE role = 'Teacher' OR role = 'Department Head' AND Status= 'Approved'";
$resultTeacher = mysqli_query($conn, $queryTeachers);


// Fetch content data
$queryContent = "SELECT fs.ContentID, fs.Title, fs.Captions
                 FROM feedcontent fs
                 INNER JOIN usercontent uc ON fs.ContentID = uc.ContentID
                 ";
$resultContent = mysqli_query($conn, $queryContent);

// Store options in an array
$options = '';
if ($resultContent) {
    while ($row = mysqli_fetch_assoc($resultContent)) {
        $options .= '<option value="' . $row['ContentID'] . '">' . htmlspecialchars($row['Title']) . ' - '. htmlspecialchars($row['Captions']) . '</option>';
    }
} else {
    $options .= '<option value="">No content available</option>';
}

// Fetch the current quarter
$currentDate = date('Y-m-d'); // Get today's date
$queryQuarter = "SELECT Quarter_ID, Quarter_Name 
                 FROM quarter 
                 WHERE '$currentDate' BETWEEN Start_Date AND End_Date";
$resultQuarter = mysqli_query($conn, $queryQuarter);
$quarterName = '';
$quarterID = '';

if ($resultQuarter) {
    if ($row = mysqli_fetch_assoc($resultQuarter)) {
        $quarterName = htmlspecialchars($row['Quarter_Name']);
        $quarterID = $row['Quarter_ID']; // Store Quarter_ID for submission
    } else {
        $quarterName = 'No current quarter';
    }
}
$queryMPS = "SELECT m.mpsID, m.UserID, m.ContentID, q.School_Year_ID, q.Quarter_Name, 
                    CONCAT(fc.Title, ' - ', fc.Captions) AS GradeSection, 
                    m.TotalNumOfStudents, m.TotalNumTested, m.HighestScore, 
                    m.LowestScore, m.MPS,sy.Year_Range AS SY,
                    CONCAT(ua.fname, ' ', ua.lname) AS SubTeacher
             FROM mps m
             INNER JOIN quarter q ON m.Quarter_ID = q.Quarter_ID
             INNER JOIN feedcontent fc ON m.ContentID = fc.ContentID
             INNER JOIN schoolyear sy ON q.School_Year_ID = sy.School_Year_ID
             INNER JOIN useracc ua ON m.UserID = ua.UserID
             WHERE  q.Quarter_ID = '$quarterID'";
$resultMPS = mysqli_query($conn, $queryMPS);

// Fetch the School Year data from the database
$schoolyearQuery = "SELECT School_Year_ID, Year_Range FROM schoolyear";
$schoolyearResult = mysqli_query($conn, $schoolyearQuery);

// Fetch Grade Level data (content titles associated with the user)
$user_id = $_SESSION['user_id']; // Assuming you're using session to store user ID
$gradeLevelQuery = "
    SELECT fs.ContentID, fs.Title, fs.Captions
    FROM feedcontent fs
    INNER JOIN usercontent uc ON fs.ContentID = uc.ContentID
     ";
$gradeLevelResult = mysqli_query($conn, $gradeLevelQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MPS</title>
<link rel="icon" type="image/png" href="../img/Logo/docmap-logo-1.png">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    <!-- ======= Styles ====== -->
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">

    <style>
        .header-section {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-right: 20px;
            margin-bottom: 20px;
        }


        

        .readonly-input {
            background-color: #f0f0f0; /* Light gray background */
        }

        /* Style to center the table */
        .table-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        table {
            width: 80%;
            border-collapse: collapse;
        }

        th, td {
            text-align: center;
            padding: 10px;
        }

        th {
            background-color: #9B2035;
            color: white;
        }

        td {
            background-color: #ffff;
        }
        .btn-rounded {
            border-radius: 20px; /* Adjust the value for more or less rounding */
            margin: 0 5px; /* Space between buttons */
        }
        .text-danger {
            color: red; /* For MPS below 75 */
            font-weight:bold;
        }

        .text-success {
            color: green; /* For MPS 75 and above */
            font-weight:bold;
        }
        .printbtn{
            background-color: green;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 25px; /* Adjust for desired roundness */
            cursor: pointer;
            transition: background-color 0.3s; /* Add a smooth transition effect */
            font-weight:bold;
        }
        .printbtn:hover{
             background-color: darkgreen;
        }
        .filterbtn{
            background-color: blue;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 25px; /* Adjust for desired roundness */
            cursor: pointer;
            transition: background-color 0.3s; /* Add a smooth transition effect */
            font-weight:bold;
        }
        .filterbtn:hover{
             background-color: darkblue;
        }


            


    </style>
</head>
<body style="background-color:#F1F0F6;">
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
            <div class="header-section">
                <h1 class="title">MPS</h1>
                <button class="printbtn" id="printButton" onclick="fetchSchoolDetailsAndPrint()">Print</button> <!-- Print button added -->
            </div>

        
            <!-- Dropdowns Above the Table -->
            <div class ="filter-station">
                <h5>Filter Options</h5>
                <div class="row mb-3">
                    <div class="col">
                        <label for="schoolYearSelect">School Year:</label>
                        <select class="form-control" id="schoolYearSelect" name="school_year" required>
                            <option value="">Select School Year</option>
                            <?php while($row = mysqli_fetch_assoc($schoolyearResult)): ?>
                                <option value="<?= $row['School_Year_ID'] ?>"><?= $row['Year_Range'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col">
                        <label for="quarterSelect">Quarter:</label>
                        <select class="form-control" id="quarterSelect" name="quarter" required>
                            <option value="">Select Quarter</option>
                            <option value="1">First Quarter</option>
                            <option value="2">Second Quarter</option>
                            <option value="3">Third Quarter</option>
                            <option value="4">Fourth Quarter</option>
                        </select>
                    </div>
                    <div class="col">
                        <label for="gradeLevelSelect">Grade Level:</label>
                        <select class="form-control" id="gradeLevelSelect" name="grade_level" required>
                            <option value="">Select Grade Level</option>
                            <?php while($row = mysqli_fetch_assoc($gradeLevelResult)): ?>
                                <option value="<?= $row['ContentID'] ?>"><?= $row['Title'] ?> - <?= $row['Captions'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col">
                        <label for="instructorSelect">Instructor:</label>
                        <select class="form-control" id="instructorSelect" name="instructor" required>
                            <option value="">Select Instructor</option>
                            <?php while ($row = mysqli_fetch_assoc($resultTeacher)): ?>
                                <option value="<?= $row['UserID'] ?>"><?= htmlspecialchars($row['TeacherName']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="col d-flex align-items-end justify-content-between">
                        <button class="filterbtn" id="filterButton">Filter</button>
                            
                    </div>
                </div>
            </div>
           
            <div class="table-container">
                <table class="table table-bordered" id="mpsTable">
                    <thead>
                        <tr>
                            <th>School Year</th>
                            <th>Quarter</th>
                            <th>Grade Section</th>
                            <th>Instructor</th>
                            <th>Total No. Students</th>
                            <th>Total No. Tested</th>
                            <th>Highest Score</th>
                            <th>Lowest Score</th>
                            <th>MPS</th>
                            <th class="action-column">Action</th>
                        </tr>
                    </thead>
                  <tbody>
                        <?php
                        if ($resultMPS) {
                            while ($row = mysqli_fetch_assoc($resultMPS)) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($row['SY']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['Quarter_Name']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['GradeSection']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['SubTeacher']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['TotalNumOfStudents']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['TotalNumTested']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['HighestScore']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['LowestScore']) . '</td>';

                                // Check MPS value and apply class
                                $mpsClass = htmlspecialchars($row['MPS']) < 75 ? 'text-danger' : 'text-success';
                                echo '<td style="font-weight:bold;" class="' . $mpsClass . '">' . htmlspecialchars($row['MPS']) . '</td>';

                                echo '<td class="action-column">';
                                echo '<button class="btn btn-info btn-rounded" data-toggle="modal" data-target="#messageModal" onclick="openMessageModal(' . $row['UserID'] . ', ' . $row['mpsID'] . ')">'; // Pass MPSID to the function
                                echo '<i class="bx bx-message-dots"></i>'; // Email Icon
                                echo '</button>';
                                echo '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="9">No MPS data available</td></tr>';
                        }
                        ?>
                    </tbody>



                </table>
            </div>
           <div id="messageModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="messageModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="messageModalLabel">Send a message to > <span id="subTeacherName" style="font-weight:bold;"></span></h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body" id="messageModalContent">
                            <div class="form-group">
                                <label for="messageInput">Message:</label>
                                <textarea class="form-control" id="messageInput" name="message" required></textarea>
                            </div>
                            <input type="hidden" id="subTeacherMobile" name="mobile" value="">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" onclick="sendMessage()">Send Message</button>
                        </div>
                    </div>
                </div>
            </div>





           
        </main>
    </section>
    <!-- jQuery, Bootstrap JS, and other dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Your custom scripts -->
    <script src="assets/js/script.js"></script>

<script>
function openMessageModal(userID, mpsID) {
    console.log('User ID:', userID, 'MPS ID:', mpsID);

    // Fetch the details using both the userID and mpsID from the backend
    fetch(`mps_details.php?userID=${userID}&mpsID=${mpsID}`)
        .then(response => response.json())
        .then(data => {
            if (data && data.fullName && data.mobile) {
                // Display the full name and mobile in the modal
                document.getElementById('subTeacherName').textContent = data.fullName;
                document.getElementById('subTeacherMobile').value = data.mobile; // Correctly set the hidden input value
                document.getElementById('subTeacherMPS').textContent = data.MPS;
            } else {
                // Fallback if no data is found
                document.getElementById('subTeacherName').textContent = 'No data available';
                document.getElementById('subTeacherMobile').value = ''; // Clear the hidden input value
                document.getElementById('subTeacherMPS').textContent = 'No data available';
            }
        })
        .catch(error => console.error('Error fetching SubTeacher details:', error));

    // Show the modal
    $('#messageModal').modal('show');
}

function sendMessage() {
    const mobile = document.getElementById('subTeacherMobile').value;
    const message = document.getElementById('messageInput').value;

    if (!mobile || !message) {
        console.error('Mobile number or message is missing');
        return;
    }

    const apiUrl = "https://api.semaphore.co/api/v4/messages";
    const apiKey = "d796c0e11273934ac9d789536133684a";
    const senderName = 'DocMaP';

    const data = {
        api_key: apiKey,
        sender_name: senderName,
        to: mobile,
        message: message
    };

    // Using Fetch API with a proxy to handle CORS
    const proxyUrl = 'http://yourserver.com/proxy'; // Replace with your server URL
    fetch(proxyUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${apiKey}`
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        console.log('SMS sent successfully:', data);
        Swal.fire({
            icon: 'success',
            title: 'Message Sent!',
            text: 'Your message has been sent successfully.',
            showConfirmButton: false,
            timer: 1500
        });
    })
    .catch(error => {
        console.error('Error sending SMS:', error);
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: 'Something went wrong! Please try again later.',
        });
    });

    // Close the modal
    $('#messageModal').modal('hide');
}

</script>



    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script>
        function fetchSchoolDetailsAndPrint() {
            // Fetch school details using AJAX
            $.ajax({
                url: '../getSchoolDetails.php',
                method: 'GET',
                success: function(data) {
                    var mpsTable = document.getElementById("mpsTable");
                    var actionColumns = document.querySelectorAll('.action-column');

                    // Check if the table has rows
                    if (mpsTable.rows.length > 1) {
                        // Prepare the logo and school details for the print view
                        var logo = data.Logo ? '<img src="../img/Logo/' + data.Logo + '" style="width: 130px; height: auto; margin-right:20px;" />' : '<p>No Logo Available</p>';
                        var schoolDetails = `
                            <div class="header-content" style="padding-right:150px;">
                                <div class="logo" style="flex-shrink: 0; ">
                                ${logo}
                                </div>
                                <div class="school-details">
                                    <p>Republic of the ${data.Country}</p>
                                    <p>${data.Organization}</p>
                                    <p>${data.Region}</p>
                                    <h2 style="font-weight: bold; font-size: 1.5em;">${data.Name}</h2>
                                    <p>${data.Address}</p>
                                    <p>School ID: ${data.School_ID}</p>
                                </div>
                            </div>
                            <hr/>
                            <div class="additional-titles" style="text-align: center; font-family: 'Times New Roman', serif;">
                                <h3>Mean Percentage Score</h3>
                                <h4>Philippine Politics and Governance</h4>
                            </div>
                        `;

                        // Hide action columns in the table
                        actionColumns.forEach(function(column) {
                            column.style.display = 'none';
                        });

                        // Create print content
                        var printContent = schoolDetails + mpsTable.outerHTML;

                        // Add signature lines at the bottom
                        printContent += `
                            <div class="signature-section" style="margin-top: 50px; text-align: center; display: flex; justify-content: space-between; align-items: flex-start;">
                                <div style="text-align: center; flex: 1;">
                                    <span>Approved by:</span><br/><br/>
                                    <span style="font-weight:bold;">${data.Principal_FullName}</span><br/>
                                    <hr style="max-width: 30%; margin: 0 auto;" />
                                    <span>Principal</span>
                                </div>
                            </div>
                        `;

                        // Open the print window
                        var win = window.open('', '', 'height=700,width=900');

                        // Write the content to the new window
                        win.document.write(`
                            <html>
                                <head>
                                    <title>Print Table</title>
                                    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
                                    <style>
                                        body {
                                            position: relative;
                                        }
                                        .watermark {
                                            position: absolute;
                                            top: 50%;
                                            left: 50%;
                                            transform: translate(-50%, -50%);
                                            opacity: 0.1;
                                            pointer-events: none;
                                            z-index: -1;
                                        }
                                        .header-content {
                                            display: flex;
                                            justify-content: center;
                                            align-items: center;
                                            margin-bottom: 20px;
                                        }
                                        .logo {}
                                        .school-details {
                                            text-align: center;
                                            font-family: 'Times New Roman', serif;
                                        }
                                        .school-details h2 {
                                            font-size: 1.5em;
                                            font-weight: bold;
                                        }
                                        .school-details p {
                                            margin: 0;
                                        }
                                        hr {
                                            border-top: 1px solid black;
                                            width: 100%;
                                            margin-top: 10px;
                                        }
                                        .additional-titles {
                                            margin-top: 10px;
                                            margin-bottom: 50px;
                                        }
                                        .additional-titles h3, .additional-titles h4 {
                                            margin: 5px 0;
                                            font-weight: bold;
                                        }
                                        .signature-section {
                                            margin-top: 50px;
                                            text-align: center;
                                            display: flex;
                                            justify-content: space-between;
                                            align-items: flex-start;
                                        }
                                        .signature-section div {
                                            flex: 1;
                                            text-align: center;
                                        }
                                    </style>
                                </head>
                                <body>
                                    ${printContent}
                                    <img src="../img/Logo/${data.Logo}" class="watermark" style="width: 700px;" />
                                </body>
                            </html>
                        `);

                        win.document.close();

                        // Wait for the content to load before printing
                        win.onload = function() {
                            win.print();
                            win.close();
                        };

                        // Restore action column visibility after printing
                        actionColumns.forEach(function(column) {
                            column.style.display = '';
                        });
                    } else {
                        // Disable the print button if there are no rows in the table
                        document.getElementById("printButton").disabled = true;
                        document.getElementById("printButton").style.display = 'none'; // Hide the button
                    }
                },
                error: function() {
                    alert("Error fetching school details.");
                }
            });
        }
    </script>







<script>
$(document).ready(function() {
    // When the filter button is clicked
    $('#filterButton').click(function() {
        // Fetch selected values from the dropdowns
        const schoolYear = $('#schoolYearSelect').val();
        const quarter = $('#quarterSelect').val();
        const gradeLevel = $('#gradeLevelSelect').val();
        const instructor = $('#instructorSelect').val(); // Fetch instructor selection
        
        console.log('Filter button clicked');
        console.log('Selected School Year:', schoolYear);
        console.log('Selected Quarter:', quarter);
        console.log('Selected Grade Level:', gradeLevel);
        console.log('Selected Instructor:', instructor); // Log selected instructor

        // AJAX request to fetch filtered data
        $.ajax({
            url: 'filter-mps.php',
            method: 'POST',
            data: {
                school_year: schoolYear,
                quarter: quarter,
                grade_level: gradeLevel,
                instructor: instructor // Send instructor selection in the request
            },
            dataType: 'json',
            success: function(data) {
                console.log('Data received:', data); // Check the response data
                const tbody = $('#mpsTable tbody');
                tbody.empty(); // Clear current table rows
                
                if (data.length > 0) {
                    data.forEach(row => {
                        const mpsClass = row.MPS < 75 ? 'text-danger' : 'text-success';
                        const html = `
                            <tr>
                                <td>${row.SY}</td>
                                <td>${row.Quarter_Name}</td>
                                <td>${row.GradeSection}</td>
                                <td>${row.SubTeacher}</td>
                                <td>${row.TotalNumOfStudents}</td>
                                <td>${row.TotalNumTested}</td>
                                <td>${row.HighestScore}</td>
                                <td>${row.LowestScore}</td>
                                <td class="${mpsClass}">${row.MPS}</td>
                                <td class="action-column">
                                    <button id="printBtn" class="btn btn-info btn-rounded">
                                        <i class="bx bx-envelope"></i> <!-- Email Icon -->
                                    </button>
                                </td>
                            </tr>
                        `;
                        tbody.append(html);
                    });

                    // Enable the print button if data exists
                    $('#printBtn').prop('disabled', false);
                    $('#printBtn').show();
                } else {
                    tbody.append('<tr><td colspan="9">No data found</td></tr>');
                    
                    // Disable the print button and hide it
                    $('#printButton').prop('disabled', true);
                    $('#printButton').hide();
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error); // Log any AJAX errors
                console.log(xhr.responseText); // Log the response
            }
        });
    });
});
</script>

    
    




</body>
</html>
