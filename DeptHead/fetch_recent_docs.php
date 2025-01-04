<?php
include 'connection.php';

$user_id = $_SESSION['user_id'];  // Ensure session is already active
if (!isset($_SESSION['dept_ID'])) {
    echo "Department ID not found in session.";
    exit;
}
$dept_ID = $_SESSION['dept_ID']; // Get dept_ID from session

// Query to get recent tasks and teacher names for a specific department.  No changes needed here.
$sql = "
    SELECT 
        ua.UserID,
        CONCAT(ua.fname, ' ', ua.lname) AS name,
        t.Title AS taskTitle,
        t.timestamp AS taskTimestamp
    FROM 
        useracc ua
    LEFT JOIN 
        task_user tu ON ua.UserID = tu.UserID
    LEFT JOIN 
        tasks t ON tu.TaskID = t.TaskID
    LEFT JOIN 
        feedcontent fc ON t.ContentID = fc.ContentID
    WHERE 
        ua.role = 'Teacher'
        AND fc.dept_ID = ? AND tu.Status = 'Submitted'  
    ORDER BY 
        t.timestamp DESC
    LIMIT 2;  -- Limit to 2 most recent tasks
";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $dept_ID);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        echo "<div class='scrollable-container'>"; // Single container for scrolling
        echo "<ul style='list-style: none; padding-left: 0;'>";

        while ($row = mysqli_fetch_assoc($result)) {
            echo "<li class='task-item'>
                    <strong style='font-size: 16px;'>Title:</strong> " . htmlspecialchars($row['taskTitle']) . "<br>
                    <small>Submitted by: " . htmlspecialchars($row['name']) . "</small>
                  </li>";
        }

        echo "</ul>";
        echo "</div>"; // Close scrollable container
    } else {
        echo "<p>No recent submissions found.</p>";
    }

    mysqli_stmt_close($stmt);
} else {
    echo "<p>Error with query execution: " . mysqli_error($conn) . "</p>"; // Added error reporting
}
?>

<style>
    .scrollable-container {
        max-height: 200px;
        overflow-y: auto;
        padding: 10px; /* Added padding for better appearance */
        border: 1px solid #ddd; /* Added border for better visual separation */
    }

    .task-item {
        margin-bottom: 10px; /* Adjusted margin */
    }

    .task-item strong {
        font-size: 16px;
        color: #333;
    }

    .task-item small {
        font-size: 14px;
        color: #777;
        display: block; /* Makes small text a new line */
    }

    /* Responsive adjustments (simplified) */
    @media (max-width: 768px) {
        .task-item strong, .task-item small {
            font-size: 14px;
        }
    }
    @media (max-width: 576px) {
        .task-item strong, .task-item small {
            font-size: 12px;
        }
    }
</style>