<?php
include('connection.php'); // Include your database connection here
session_start();

// Ensure that the dept_id is sent in the GET request
if (isset($_GET['dept_id'])) {
    $dept_id = $_GET['dept_id'];

    // Fetch department-level statistics for total submit and assigned counts
    // Total submitted or approved tasks
     $submittedQuery = "SELECT COUNT(UserID) AS totalSubmit 
                   FROM task_user 
                   INNER JOIN feedcontent ON task_user.ContentID = feedcontent.ContentID 
                   WHERE (task_user.Status = 'Submitted' or 'Approved' or 'Rejected') 
                   AND feedcontent.dept_ID = ?";

    $submittedStmt = $conn->prepare($submittedQuery);
    $submittedStmt->bind_param('i', $dept_id);
    $submittedStmt->execute();
    $submittedResult = $submittedStmt->get_result();
    $totalSubmit = $submittedResult->fetch_assoc()['totalSubmit'] ?? 0;

    // Total assigned tasks with Status = 'Assign'
    $assignedQuery = "SELECT COUNT(UserID) AS totalAssigned 
    FROM task_user 
    INNER JOIN feedcontent ON task_user.ContentID = feedcontent.ContentID 
    WHERE feedcontent.dept_ID = ?";
    $assignedStmt = $conn->prepare($assignedQuery);
    $assignedStmt->bind_param('i', $dept_id);
    $assignedStmt->execute();
    $assignedResult = $assignedStmt->get_result();
    $totalAssigned = $assignedResult->fetch_assoc()['totalAssigned'] ?? 0;

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
    t.Type = 'Task' 
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
