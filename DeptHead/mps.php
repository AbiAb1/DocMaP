<?php
session_start();
include 'connection.php';

// Fetch the user ID from the session
$user_id = $_SESSION['user_id']; // Ensure you set this session variable upon login

if (!isset($_SESSION['dept_ID'])) {
    echo "Department ID not found in session.";
    exit;
} else {
    $dept_ID = $_SESSION['dept_ID']; // Get dept_ID from session
}

$dept_ID = $_SESSION['dept_ID']; // Get dept_ID from session

// Fetch teacher data
$queryTeachers = "
    SELECT DISTINCT CONCAT(ua.fname, ' ', ua.lname) AS TeacherName, ua.UserID
    FROM feedcontent fc
    LEFT JOIN usercontent uc ON fc.ContentID = uc.ContentID
    LEFT JOIN useracc ua ON uc.UserID = ua.UserID
    WHERE ua.role = 'Teacher' 
      AND ua.Status = 'Approved'
      AND fc.dept_ID = ?
";

$stmtTeachers = $conn->prepare($queryTeachers);
$stmtTeachers->bind_param('i', $dept_ID); // Bind the dept_ID from session
$stmtTeachers->execute();
$resultTeacher = $stmtTeachers->get_result();

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
        $options .= '<option value="' . $row['ContentID'] . '">' . htmlspecialchars($row['Title']) . ' - ' . htmlspecialchars($row['Captions']) . '</option>';
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

// Fetch MPS data
$queryMPS = "
    SELECT m.mpsID, m.UserID, m.ContentID, q.School_Year_ID, q.Quarter_Name, 
           CONCAT(fc.Title, ' - ', fc.Captions) AS GradeSection, 
           m.TotalNumOfStudents, m.TotalNumTested, m.HighestScore, 
           m.LowestScore, m.MPS, sy.Year_Range AS SY,
           CONCAT(ua.fname, ' ', ua.lname) AS SubTeacher
    FROM mps m
    INNER JOIN quarter q ON m.Quarter_ID = q.Quarter_ID
    INNER JOIN feedcontent fc ON m.ContentID = fc.ContentID
    INNER JOIN schoolyear sy ON q.School_Year_ID = sy.School_Year_ID
    INNER JOIN useracc ua ON m.UserID = ua.UserID
    INNER JOIN department d ON fc.dept_ID = d.dept_ID
    WHERE q.Quarter_ID = ?
      AND d.dept_ID = ?;
";

// Prepare the statement
if ($stmtMPS = mysqli_prepare($conn, $queryMPS)) {
    // Bind the parameters (quarterID and dept_ID)
    mysqli_stmt_bind_param($stmtMPS, "ii", $quarterID, $dept_ID); // 'i' for integer type

    // Execute the query
    mysqli_stmt_execute($stmtMPS);

    // Get the result
    $resultMPS = mysqli_stmt_get_result($stmtMPS);
}

// Fetch the School Year data from the database
$schoolyearQuery = "SELECT School_Year_ID, Year_Range FROM schoolyear ORDER BY Year_Range DESC";
$schoolyearResult = mysqli_query($conn, $schoolyearQuery);

// Fetch Grade Level data (content titles associated with the user)
$user_id = $_SESSION['user_id']; // Assuming you're using session to store user ID
$dept_ID = $_SESSION['dept_ID']; // Assuming dept_ID is also stored in the session

// Prepare the query with a placeholder for dept_ID
$gradeLevelQuery = "
    SELECT fs.ContentID, fs.Title, fs.Captions
    FROM feedcontent fs
    INNER JOIN usercontent uc ON fs.ContentID = uc.ContentID
    WHERE fs.dept_ID = ?
    GROUP BY fs.ContentID, fs.Title, fs.Captions
";

// Prepare the statement
if ($stmt = mysqli_prepare($conn, $gradeLevelQuery)) {
    // Bind the parameter (dept_ID) to the prepared statement
    mysqli_stmt_bind_param($stmt, "i", $dept_ID); // 'i' for integer type

    // Execute the query
    mysqli_stmt_execute($stmt);

    // Get the result
    $gradeLevelResult = mysqli_stmt_get_result($stmt);
}

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
        body {
  background-color: #F1F0F6; /* Light gray */
}
        .header-section {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-right: 20px;
            margin-bottom: 20px;
        }

        h1.title {
            margin: 0;
        }

        .btn-upload {
            background-color: #9B2035;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
            margin-left: 10px;
        }

        .btn-upload:hover {
            background-color: #7a1a2b;
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
            


    </style>
</head>
<body>
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
                <button class="btn btn-success" id="printButton" onclick="fetchSchoolDetailsAndPrint()">Print</button> <!-- Print button added -->
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
                        <label for="instructorSelect">Teacher:</label>
                        <select class="form-control" id="instructorSelect" name="instructor" required>
                            <option value="">Select Teacher</option>
                            <?php while ($row = mysqli_fetch_assoc($resultTeacher)): ?>
                                <option value="<?= $row['UserID'] ?>"><?= htmlspecialchars($row['TeacherName']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="col d-flex align-items-end justify-content-between">
                        <button class="btn btn-primary" id="filterButton">Filter</button>
                            
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
                            <th>Teacher</th>
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
                               
                                echo '<button class="btn btn-info btn-rounded">
                                        <i class="bx bx-envelope"></i> <!-- Email Icon -->
                                    </button>';
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
           
        </main>
    </section>
    <!-- jQuery, Bootstrap JS, and other dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Your custom scripts -->
    <script src="assets/js/script.js"></script>






    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script>
function fetchSchoolDetailsAndPrint() {
    // Fetch school details using AJAX
    $.ajax({
        url: '../getSchoolDetails.php',
        method: 'GET',
        success: function(data) {
            // Prepare the logo and school details for the print view
            var logo = data.Logo ? '<img src="../img/Logo/' + data.Logo + '" style="width: 130px; height: auto; margin-right:20px;" />' : '<p>No Logo Available</p>';
            var schoolDetails = `
                <div class="header-content">
                    <div class="logo">${logo}</div>
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

                </div>
            `;

            // Hide action columns in the table
            var actionColumns = document.querySelectorAll('.action-column');
            actionColumns.forEach(function(column) {
                column.style.display = 'none';
            });

            // Create print content
            var printContent = schoolDetails + document.getElementById("mpsTable").outerHTML;

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
                                position: relative; /* Allow positioning of watermark */
                            }
                            .watermark {
                                position: absolute;
                                top: 50%; /* Center vertically */
                                left: 50%; /* Center horizontally */
                                transform: translate(-50%, -50%); /* Centering adjustment */
                                opacity: 0.1; /* Make it faint */
                                pointer-events: none; /* Ensure it doesn't block interactions */
                                z-index: -1; /* Send to back */
                            }
                            .header-content {
                                display: flex;
                                justify-content: center; /* Centers the content horizontally */
                                align-items: center; /* Aligns the content vertically */
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
                                display: flex; /* Use flexbox for horizontal alignment */
                                justify-content: space-between; /* Distribute space evenly */
                                align-items: flex-start; /* Align items to the top */
                            }
                            .signature-section div {
                                flex: 1; /* Allow equal distribution of width */
                                text-align: center; /* Center the text in each section */
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
                                    <button class="btn btn-info btn-rounded">
                                        <i class="bx bx-envelope"></i> <!-- Email Icon -->
                                    </button>
                                </td>
                            </tr>
                        `;
                        tbody.append(html);
                    });
                } else {
                    tbody.append('<tr><td colspan="9">No data found</td></tr>');
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
