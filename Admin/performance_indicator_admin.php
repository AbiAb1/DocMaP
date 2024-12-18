<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'connection.php'; // Include your database connection file

$userId = $_SESSION['user_id'];

// Query to fetch fname and lname
$sql = "SELECT fname, lname FROM useracc WHERE UserID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId); // Bind the UserID parameter
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Fetch the user's name
    $row = $result->fetch_assoc();
    $userFullName = $row['fname'] . ' ' . $row['lname'];
} else {
    $userFullName = "Unknown User"; // Fallback if user is not found
}


// Fetch available school years
$schoolYearsQuery = "
    SELECT DISTINCT sy.School_Year_ID, sy.Year_Range
    FROM schoolyear sy
    JOIN ierformance_indicator pi ON sy.School_Year_ID = pi.School_Year_ID
    ORDER BY sy.Year_Range DESC";
$schoolYearsResult = $conn->query($schoolYearsQuery);
$schoolYears = [];
if ($schoolYearsResult && $schoolYearsResult->num_rows > 0) {
    while ($row = $schoolYearsResult->fetch_assoc()) {
        $schoolYears[$row['School_Year_ID']] = $row['Year_Range'];
    }
}

// Fetch records based on the selected school year
$selectedYear = isset($_GET['year']) && ctype_digit($_GET['year']) ? $_GET['year'] : "All";
$records = [];
if ($selectedYear === "All") {
    foreach ($schoolYears as $yearId => $year) {
        $query = "
            SELECT 
                g.Grade_Level,
                e.Enroll_Gross,
                e.Enroll_Net,
                c.Cohort_Figure,
                c.Cohort_Rate,
                d.Dropout_Figure,
                d.Dropout_Rate,
                r.Repeaters_Figure,
                r.Repeaters_Rate,
                p.Promotion_Figure,
                p.Promotion_Rate,
                t.Transition_Figure,
                t.Transition_Rate,
                a.Age_Group_12To15,
                n.NAT_Score
            FROM performance_indicator pi
            JOIN schoolyear sy ON pi.School_Year_ID = sy.School_Year_ID
            JOIN grade g ON pi.Grade_ID = g.Grade_ID
            JOIN enroll e ON pi.Enroll_ID = e.Enroll_ID
            JOIN dropout d ON pi.Dropout_ID = d.Dropout_ID
            JOIN promotion p ON pi.Promotion_ID = p.Promotion_ID
            JOIN cohort_survival c ON pi.Cohort_ID = c.Cohort_ID
            JOIN repetition r ON pi.Repetition_ID = r.Repetition_ID
            JOIN age a ON pi.Age_ID = a.Age_ID
            JOIN nat n ON pi.NAT_ID = n.NAT_ID
            JOIN transition t ON pi.Transition_ID = t.Transition_ID
            WHERE sy.School_Year_ID = $yearId
            GROUP BY g.Grade_Level, e.Enroll_Gross, e.Enroll_Net
            ORDER BY g.Grade_Level";
        $result = $conn->query($query);
        $records[$year] = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
} else {
    $query = "
        SELECT 
            g.Grade_Level,
            e.Enroll_Gross,
            e.Enroll_Net,
            c.Cohort_Figure,
            c.Cohort_Rate,
            d.Dropout_Figure,
            d.Dropout_Rate,
            r.Repeaters_Figure,
            r.Repeaters_Rate,
            p.Promotion_Figure,
            p.Promotion_Rate,
            t.Transition_Figure,
            t.Transition_Rate,
            a.Age_Group_12To15,
            n.NAT_Score
        FROM performance_indicator pi
        JOIN schoolyear sy ON pi.School_Year_ID = sy.School_Year_ID
        JOIN grade g ON pi.Grade_ID = g.Grade_ID
        JOIN enroll e ON pi.Enroll_ID = e.Enroll_ID
        JOIN dropout d ON pi.Dropout_ID = d.Dropout_ID
        JOIN promotion p ON pi.Promotion_ID = p.Promotion_ID
        JOIN cohort_survival c ON pi.Cohort_ID = c.Cohort_ID
        JOIN repetition r ON pi.Repetition_ID = r.Repetition_ID
        JOIN age a ON pi.Age_ID = a.Age_ID
        JOIN nat n ON pi.NAT_ID = n.NAT_ID
        JOIN transition t ON pi.Transition_ID = t.Transition_ID
        WHERE sy.School_Year_ID = $selectedYear
        GROUP BY g.Grade_Level, e.Enroll_Gross, e.Enroll_Net
        ORDER BY g.Grade_Level";
    $result = $conn->query($query);
    $records[$schoolYears[$selectedYear]] = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Indicator</title>
    <link rel="icon" type="image/png" href="../img/Logo/docmap-logo-1.png">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .header-section {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap; /* Allow items to wrap on smaller screens */
            padding-right: 20px;
            margin-bottom: 20px;
        }

        h1.title {
            margin: 0;
            font-size: 1.5rem; /* Adjust font size for better scaling */
        }

        .table-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            overflow-x: auto; /* Allow horizontal scrolling for smaller screens */
        }

        table {
            width: 100%;
            max-width: 100%;
            border-collapse: collapse;
        }

        th, td {
            text-align: center; /* Center text horizontally */
            vertical-align: middle; /* Center text vertically */
            padding: 10px;
            font-size: 1rem;
            word-wrap: break-word; /* Ensure long text wraps */
        }

        th {
            background-color: #9B2035;
            color: white;
        }

        td {
            background-color: #fff;
        }


        .button-group {
            display: flex;
            flex-wrap: wrap; /* Allow buttons to wrap on smaller screens */
            gap: 10px; /* Optional gap between buttons */
        }

        .centered {
            text-align: center;
            vertical-align: middle;
        }

        .text-school-year {
            margin-top: 20px;
        }

        /* Media queries for responsiveness */

        /* For small screens (phones) */
        @media (max-width: 600px) {
            h1.title {
                font-size: 1.2rem;
            }

            .header-section {
                flex-direction: column; /* Stack header items vertically */
                align-items: flex-start; /* Align items to the start */
                padding-right: 10px;
            }

            table {
                font-size: 0.9rem; /* Reduce font size for smaller screens */
            }

            th, td {
                padding: 8px; /* Reduce padding */
            }

            .button-group {
                justify-content: center; /* Center buttons */
            }
        }

        /* For medium screens (tablets) */
        @media (max-width: 900px) {
            h1.title {
                font-size: 1.4rem;
            }

            .header-section {
                flex-direction: row;
                justify-content: center; /* Center header items */
            }

            table {
                width: 90%; /* Adjust table width */
            }

            th, td {
                padding: 10px;
            }
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
                <h1 class="title">Performance Indicator</h1>
                <!-- Buttons side by side -->
                <div class="button-group">
                    <button class="printbtn" id="printButton" onclick="fetchSchoolDetailsAndPrint()">Print</button> <!-- Print button -->
                </div>
            </div>

            <!-- Dropdowns Above the Table -->
            <div class="filter-station">
                <label for="schoolYearFilter">School Year:</label>
                <select id="schoolYearFilter" class="form-control" onchange="filterByYear()">
                    <option value="All" <?= $selectedYear === "All" ? "selected" : "" ?>>All</option>
                    <?php foreach ($schoolYears as $yearId => $year): ?>
                        <option value="<?= $yearId ?>" <?= $selectedYear == $yearId ? "selected" : "" ?>>
                            <?= $year ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <?php if (!empty($records)): ?>
                    <?php foreach ($records as $year => $yearRecords): ?>
                        <h3 class="text-center text-school-year"><?= $year ?></h3>
                        <div class="table-container">
                            <table class="table table-bordered" id="performance_table">
                                <thead>
                                    <tr>
                                        <th colspan="1">GRADE</th>
                                        <th colspan="2">ENROLLMENT</th>
                                        <th colspan="2">COHORT</th>
                                        <th colspan="2">DROP/SCHOOL LEAVERS</th>
                                        <th colspan="2">REPEATERS</th>
                                        <th colspan="2">GRADUATION</th>
                                        <th colspan="2">TRANSITION</th>
                                        <th colspan="2">AGE</th>
                                        <th rowspan="2">NAT</th>
                                    </tr>
                                    <tr>
                                        <th>LEVEL</th>
                                        <th>GROSS</th>
                                        <th>NET</th>
                                        <th>FIGURE</th>
                                        <th>RATE</th>
                                        <th>FIGURE</th>
                                        <th>RATE</th>
                                        <th>FIGURE</th>
                                        <th>RATE</th>
                                        <th>FIGURE</th>
                                        <th>RATE</th>
                                        <th>FIGURE</th>
                                        <th>RATE</th>
                                        <th  colspan='2'>12-15</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($yearRecords)): ?>
                                        <?php 
                                            $totals = [
                                                'Enroll_Gross' => 0,
                                                'Enroll_Net' => 0,
                                                'Cohort_Figure' => 0,
                                                'Cohort_Rate' => 0,
                                                'Dropout_Figure' => 0,
                                                'Dropout_Rate' => 0,
                                                'Repeaters_Figure' => 0,
                                                'Repeaters_Rate' => 0,
                                                'Promotion_Figure' => 0,
                                                'Promotion_Rate' => 0,
                                                'Transition_Figure' => 0,
                                                'Transition_Rate' => 0,
                                                'Age_Group_12To15' => 0,
                                                'NAT_Score' => 0
                                            ];

                                            foreach ($yearRecords as $record):
                                                // Calculate totals
                                                foreach ($totals as $key => $value) {
                                                    $totals[$key] += $record[$key];
                                                }
                                            endforeach;

                                            // Calculate Total Cohort Rate
                                            $totals['Dropout_Rate'] = ($totals['Enroll_Gross'] > 0) 
                                                ? ($totals['Dropout_Figure'] / $totals['Enroll_Gross']) * 100 
                                                : 0;
                                        ?>

                                        <?php foreach ($yearRecords as $record): ?>
                                            <tr>
                                                <td><?= $record['Grade_Level'] ?></td>
                                                <td><?= $record['Enroll_Gross'] ?></td>
                                                <td><?= $record['Enroll_Net'] ?></td>
                                                <td><?= $record['Cohort_Figure'] ?></td>
                                                <td><?= $record['Cohort_Rate'] ?></td>
                                                <td><?= $record['Dropout_Figure'] ?></td>
                                                <td><?= number_format($record['Dropout_Rate'], 2) ?></td>
                                                <td><?= $record['Repeaters_Figure'] ?></td>
                                                <td><?= $record['Repeaters_Rate'] ?></td>
                                                <td><?= $record['Promotion_Figure'] ?></td>
                                                <td><?= $record['Promotion_Rate'] ?></td>
                                                <td><?= $record['Transition_Figure'] ?></td>
                                                <td><?= $record['Transition_Rate'] ?></td>
                                                <td colspan="2"><?= $record['Age_Group_12To15'] ?></td>
                                                <td><?= $record['NAT_Score'] ?></td>
                                            </tr>
                                        <?php endforeach; ?>

                                        <!-- Add Total Row -->
                                        <tr style="font-weight: bold; background-color: #f0f0f0;">
                                            <td>TOTAL</td>
                                            <td><?= $totals['Enroll_Gross'] ?></td>
                                            <td><?= $totals['Enroll_Net'] ?></td>
                                            <td><?= $totals['Cohort_Figure'] ?></td>
                                            <td><?= $totals['Cohort_Rate'] ?></td>
                                            <td><?= $totals['Dropout_Figure'] ?></td>
                                            <td><?= number_format($totals['Dropout_Rate'], 2) ?></td>
                                            <td><?= $totals['Repeaters_Figure'] ?></td>
                                            <td><?= $totals['Repeaters_Rate'] ?></td>
                                            <td><?= $totals['Promotion_Figure'] ?></td>
                                            <td><?= $totals['Promotion_Rate'] ?></td>
                                            <td><?= $totals['Transition_Figure'] ?></td>
                                            <td><?= $totals['Transition_Rate'] ?></td>
                                            <td colspan="2"><?= $totals['Age_Group_12To15'] ?></td>
                                            <td><?= $totals['NAT_Score'] ?></td>
                                        </tr>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="16" class="text-center">No records found for this school year.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>


                            </table>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="table-container">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th colspan="1">Grade</th>
                                    <th colspan="2">Enrollment</th>
                                    <th colspan="2">Cohort</th>
                                    <th colspan="2">Drop/School Leavers</th>
                                    <th colspan="2">Repeaters</th>
                                    <th colspan="2">Graduation</th>
                                    <th colspan="2">Transition</th>
                                    <th colspan="2">Age</th>
                                    <th rowspan="2">NAT</th>
                                </tr>
                                <tr>
                                    <th>Level</th>
                                    <th>Gross</th>
                                    <th>Net</th>
                                    <th>Figure</th>
                                    <th>Rate</th>
                                    <th>Figure</th>
                                    <th>Rate</th>
                                    <th>Figure</th>
                                    <th>Rate</th>
                                    <th>Figure</th>
                                    <th>Rate</th>
                                    <th>Figure</th>
                                    <th>Rate</th>
                                    <th>12-15</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="16" class="text-center">No records available.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

        </main>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="assets/js/script.js"></script>

    <script>
        function filterByYear() {
            const selectedYear = document.getElementById("schoolYearFilter").value;
            window.location.href = "?year=" + selectedYear;
        }

    </script>

   <script>
    function fetchSchoolDetailsAndPrint() {
        const userFullName = "<?= $userFullName ?>"; // Injected PHP variable
        const selectedYearText = "<?= $selectedYear === 'All' ? '' : $schoolYears[$selectedYear] ?>"; // Only add year if it's not "All"

        $.ajax({
            url: '../getSchoolDetails.php',
            method: 'GET',
            success: function(data) {
                const logo = data.Logo
                    ? `<img src="../img/Logo/${data.Logo}" alt="School Logo" style="width: 130px; height: auto;" />`
                    : '<p>No Logo Available</p>';

                const schoolDetails = `
                    <div class="header-content" style="display: flex; align-items: center; margin-bottom: 20px; width: 100%;">
                        <div class="logo" style="flex-shrink: 0;  padding-left:290px;">
                            ${logo}
                        </div>
                        <div class="school-details" style="text-align: center; flex: 1 ;padding-right:400px;">
                            <p style="margin: 0;">Republic of the ${data.Country}</p>
                            <p style="margin: 0;">${data.Organization}</p>
                            <p style="margin: 0;">Region ${data.Region}</p>
                            <p style="margin: 0;">Division of Batangas</p>
                            <h2 style="font-weight: bold; font-size: 1.2em; margin: 5px 0;">${data.Name}</h2>
                            <p style="margin: 0;">${data.Address}</p>
                        </div>
                    </div>
                    <hr/>
                    <br>
                    
                    <div class="additional-titles" style="text-align: center; font-family: 'Times New Roman', serif;">
                        <h3 style="margin: 0;">Performance Indicator</h3>
                        <span style="margin: 0;">S.Y. ${selectedYearText}</span>
                    </div>
                `;

                const signatureSection = `
                    <div class="signature-section" style="margin-top: 50px; text-align: center; display: flex; justify-content: space-between; align-items: flex-start;">
                        <div style="text-align: center; flex: 1;">
                            <span>PREPARED BY:</span><br/><br/>
                            <span style="font-weight:bold;"><?= strtoupper($userFullName) ?></span>
                            <hr style="max-width: 30%; margin: 0 auto;" />
                        </div>
                        <div style="text-align: center; flex: 1;">
                            <span>NOTED:</span><br/><br/>
                            <span style="font-weight:bold;">${data.Principal_FullName.toUpperCase()}</span>
                            <hr style="max-width: 30%; margin: 0 auto;" />
                            <span>PRINCIPAL</span>
                        </div>
                    </div>
                `;

                let tablesToPrint = '';
                document.querySelectorAll('.table-container').forEach((tableContainer, index) => {
                    const yearHeader = tableContainer.previousElementSibling?.outerHTML || '';
                    const tableHTML = tableContainer.outerHTML;

                    tablesToPrint += `
                        <div class="print-section">
                            ${schoolDetails}
                            ${yearHeader}
                            ${tableHTML}
                            ${signatureSection}
                        </div>
                    `;
                });

                const win = window.open('', '', 'height=700,width=900');
                win.document.write(`
                    <!DOCTYPE html>
                    <html lang="en">
                    <head>
                        <meta charset="UTF-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                        <title>Print</title>
                        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
                        <style>
                            body {
                                font-family: 'Times New Roman', serif;
                                margin: 0;
                                padding: 0;
                            }

                            .table-container {
                                width: 100%;
                                margin-top: 20px;
                                border-collapse: collapse;
                            }
                            
                            th, td {
                                text-align: center;
                                vertical-align: middle;
                                padding: 10px;
                                font-size: 1rem;
                                word-wrap: break-word;
                            }

                            th {
                                background-color: #9B2035;
                                color: black;
                                font-weight: bold;
                            }

                            td {
                                background-color: #ffffff;
                            }

                            .header-content {
                                display: flex;
                                align-items: center;
                                margin-bottom: 20px;
                                width: 100%;
                                padding: 0 20px;
                            }

                            .school-details {
                                text-align: center;
                                flex: 1;
                            }

                            .school-details p, .school-details h2 {
                                margin: 0;
                            }

                            .logo img {
                                width: 130px;
                                height: auto;
                            }

                            .additional-titles {
                                text-align: center;
                                font-family: 'Times New Roman', serif;
                            }

                            .signature-section {
                                margin-top: 50px;
                            }
                        </style>
                    </head>
                    <body>
                        ${tablesToPrint}
                    </body>
                    </html>
                `);

                win.document.close();
                win.onload = function() {
                    win.print();
                    win.close();
                };
            }
        });
    }
</script>


</body>
</html>
