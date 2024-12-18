<?php
// Ensure the session is started
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$loginSuccess = isset($_SESSION['login_success']) ? $_SESSION['login_success'] : false;
if ($loginSuccess) {
    unset($_SESSION['login_success']); // Unset the session variable after use
}

include 'connection.php';

$user_id = $_SESSION['user_id'];
// Ensure dept_ID is available in the session
if (!isset($_SESSION['dept_ID'])) {
    echo "Department ID not found in session.";
    exit;
}

$dept_ID = $_SESSION['dept_ID']; // Get dept_ID from session

// Get the year from the GET request (default to current year if not provided)
$year = isset($_GET['year']) ? intval($_GET['year']) : date("Y");

// Query the department name based on dept_ID
$deptNameQuery = "SELECT dept_name FROM department WHERE dept_ID = ?";
$stmtDeptName = $conn->prepare($deptNameQuery);

if (!$stmtDeptName) {
    die("Error in SQL preparation: " . $conn->error);
}

$stmtDeptName->bind_param("i", $dept_ID);
$stmtDeptName->execute();
$resultDeptName = $stmtDeptName->get_result();

// Check if department name was found
if ($resultDeptName->num_rows > 0) {
    $deptRow = $resultDeptName->fetch_assoc();
    $departmentName = $deptRow['dept_name'];
} else {
    echo "Department not found.";
    exit;
}

// Prepare SQL query for teachers' tasks data
$sql = "SELECT 
    ua.UserID,
    CONCAT(ua.fname, ' ', ua.lname) AS name,
    COUNT(tu.Task_User_ID) AS totalTasks,
    SUM(CASE WHEN tu.Status = 'Assigned' THEN 1 ELSE 0 END) AS assignedTasks,
    SUM(CASE WHEN tu.Status = 'Missing' THEN 1 ELSE 0 END) AS missingTasks,
    SUM(CASE WHEN tu.Status IN ('Submitted', 'Approved', 'Rejected') THEN 1 ELSE 0 END) AS submittedTasks
FROM 
    useracc ua
LEFT JOIN 
    task_user tu ON ua.UserID = tu.UserID
LEFT JOIN 
    tasks t ON tu.TaskID = t.TaskID
LEFT JOIN 
    feedcontent fc ON t.ContentID = fc.ContentID
WHERE 
    t.Type = 'Task'
    AND ua.role = 'Teacher'  -- Added AND here
    AND fc.dept_ID = ?  -- dept_ID placeholder
    AND (
        YEAR(t.TimeStamp) = ?  -- Year placeholder for task timestamp
        OR YEAR(tu.SubmitDate) = ?  -- Year placeholder for task dates
        OR YEAR(tu.RejectDate) = ? 
        OR YEAR(tu.ApproveDate) = ? 
    )
GROUP BY 
    ua.UserID
ORDER BY 
    submittedTasks DESC;


";

// Prepare the statement
$stmt = $conn->prepare($sql);

// Check if the statement was prepared successfully
if (!$stmt) {
    die("Error in SQL preparation: " . $conn->error);
}

// Bind the department ID and the year as parameters
$stmt->bind_param("iiiii", $dept_ID, $year, $year, $year, $year);

// Execute the statement
$stmt->execute();

// Get the result
$result = $stmt->get_result();

// Check query result
if (!$result) {
    die(json_encode(['error' => 'Query failed: ' . $conn->error]));
}

// Prepare response data
$teachers = [];
while ($row = $result->fetch_assoc()) {
    $teachers[] = [
        'name' => $row['name'],
        'assigned' => $row['assignedTasks'],
        'missing' => $row['missingTasks'],
        'submitted' => $row['submittedTasks'],
        'total' => $row['totalTasks'],
    ];
}

// Return data as JSON, including department name
header('Content-Type: application/json');
echo json_encode([
    'departmentName' => $departmentName,  // Include the department name
    'teachers' => $teachers
]);

// Close statement and connection
$stmt->close();
$stmtDeptName->close();
$conn->close();
?>