<?php
include('connection.php'); // Include your database connection here
session_start();

// Ensure that the user is logged in and has a dept_id in the session
if (!isset($_SESSION['user_dept_id'])) {
    echo json_encode(['error' => 'User not logged in or department not assigned.']);
    exit;
}

$user_dept_id = $_SESSION['user_dept_id']; // Get the dept_ID of the logged-in user

// Query to get department details for the logged-in user's department ID
$departmentsQuery = "SELECT dept_ID, dept_name 
                     FROM department 
                     WHERE dept_ID = ?"; // Use the logged-in user's dept_ID to filter departments

$stmt_dept = $conn->prepare($departmentsQuery);
$stmt_dept->bind_param('i', $user_dept_id); // Bind the dept_ID from session to the query
$stmt_dept->execute();
$departmentsResult = $stmt_dept->get_result();

$departments = [];

if ($departmentsResult && $departmentsResult->num_rows > 0) {
    // Fetch department rows
    while ($dept = $departmentsResult->fetch_assoc()) {
        $deptID = $dept['dept_ID'];
        $deptName = $dept['dept_name'];

        // Fetch submitted task count for the department
        $submittedQuery = "SELECT COUNT(UserID) AS totalSubmit 
                   FROM task_user 
                   INNER JOIN feedcontent ON task_user.ContentID = feedcontent.ContentID 
                   WHERE (task_user.Status = 'Submitted' or 'Approved' or 'Rejected') 
                   AND feedcontent.dept_ID = ?";

        $submittedStmt = $conn->prepare($submittedQuery);
        $submittedStmt->bind_param('i', $deptID);
        $submittedStmt->execute();
        $submittedResult = $submittedStmt->get_result();

        // Fetch assigned task count for the department
        $assignedQuery = "SELECT COUNT(UserID) AS totalAssigned 
                          FROM task_user 
                          INNER JOIN feedcontent ON task_user.ContentID = feedcontent.ContentID 
                          WHERE feedcontent.dept_ID = ?";

        $assignedStmt = $conn->prepare($assignedQuery);
        $assignedStmt->bind_param('i', $deptID);
        $assignedStmt->execute();
        $assignedResult = $assignedStmt->get_result();

        // Fetch task counts
        $submittedRow = $submittedResult->fetch_assoc();
        $assignedRow = $assignedResult->fetch_assoc();

        $totalSubmit = $submittedRow['totalSubmit'] ?? 0;
        $totalAssigned = $assignedRow['totalAssigned'] ?? 0;

        // Add data to departments array
        $departments[] = [
            'dept_ID' => $deptID,
            'dept_name' => $deptName,
            'totalSubmit' => $totalSubmit,
            'totalAssigned' => $totalAssigned
        ];
    }

    // Output the data in JSON format
    echo json_encode($departments);
} else {
    // Handle case where no department is found for the logged-in user
    echo json_encode(['error' => 'No department found for the logged-in user.']);
}
// Fetch tasks for display, filtered by user's dept_ID, ordered by timestamp (newest first)
$sql = "SELECT t.TaskID AS TaskID, t.Title AS TaskTitle, t.taskContent, t.DueDate, t.DueTime, d.dept_name, fc.Title AS ContentTitle, fc.Captions, t.Status, t.TimeStamp
        FROM tasks t
        LEFT JOIN feedcontent fc ON t.ContentID = fc.ContentID
        LEFT JOIN department d ON fc.dept_ID = d.dept_ID
        WHERE t.Type = 'Task' AND d.dept_ID = ? AND (t.ApprovalStatus = 'Approved' OR t.ApprovalStatus IS NULL)
        HAVING 
    totalAssigned > 0
        ORDER BY t.TimeStamp DESC  
        LIMIT ? OFFSET ?";
$stmt_tasks = $conn->prepare($sql);
$stmt_tasks->bind_param('iii', $user_dept_id, $rows_per_page, $offset);
$stmt_tasks->execute();
$result_tasks = $stmt_tasks->get_result();

$tasks = array();
if ($result_tasks->num_rows > 0) {
    while ($row = $result_tasks->fetch_assoc()) {
        $tasks[] = $row;
    }
}
// Close the database connection
$conn->close();
?>
