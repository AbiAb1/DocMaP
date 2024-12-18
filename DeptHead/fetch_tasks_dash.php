<?php
include('connection.php'); // Include your database connection here
session_start();

// Ensure that the dept_id is sent in the GET request
if (isset($_GET['dept_id'])) {
    $dept_id = $_GET['dept_id'];
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
    // Query to fetch tasks grouped by TimeStamp with a representative Title
    $tasksQuery = "SELECT 
    t.TimeStamp, 
    MAX(t.Title) AS TaskTitle,  
    SUM(CASE WHEN tu.Status = 'Submitted' THEN 1 ELSE 0 END) AS totalSubmit,
    COUNT(tu.UserID) AS totalAssigned
FROM 
    tasks t
LEFT JOIN 
    feedcontent fc ON t.ContentID = fc.ContentID
LEFT JOIN 
    task_user tu ON t.TaskID = tu.TaskID
WHERE 
    t.Type IN ('Task', 'Announcement')
    AND fc.dept_ID = ? 
    AND (t.ApprovalStatus = 'Approved' OR t.ApprovalStatus IS NULL)
GROUP BY 
    t.TimeStamp
HAVING 
    totalAssigned > 0
ORDER BY 
    t.TimeStamp DESC
  "; // Limit to 5 groups

    $stmt = $conn->prepare($tasksQuery);
    $stmt->bind_param('i', $dept_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $timestampData = [];
    while ($row = $result->fetch_assoc()) {
        $timestampData[] = $row;
    }

    // Combine the data into a response
    $response = [
        'timestamps' => $timestampData,
        'department' => [
            'dept_ID' => $dept_id,
            'totalSubmit' => $totalSubmit,
            'totalAssigned' => $totalAssigned
        ]
    ];

    // Return the response as JSON
    echo json_encode($response);
} else {
    // If dept_id is not set, return an error
    echo json_encode(['error' => 'Login Again.']);
}
?>
