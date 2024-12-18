<?php
include('connection.php'); // Your database connection file
session_start();

//Improved Error Handling
function handleError($message, $code = 500){
    http_response_code($code);
    header('Content-Type: application/json'); //Ensure correct content type
    echo json_encode(['error' => $message]);
    exit;
}


if (!isset($_SESSION['user_dept_id'])) {
    handleError('User not logged in or department not assigned.');
}

$user_dept_id = $_SESSION['user_dept_id'];

try {
    $conn->begin_transaction(); //Start transaction for atomicity

    $departmentsQuery = "SELECT dept_ID, dept_name FROM department WHERE dept_ID = ?";
    $stmt_dept = $conn->prepare($departmentsQuery);
    if (!$stmt_dept) {
        $conn->rollback(); //Rollback transaction on error
        handleError("Error preparing department query: " . $conn->error);
    }
    $stmt_dept->bind_param('i', $user_dept_id);
    $stmt_dept->execute();
    $departmentsResult = $stmt_dept->get_result();
    $stmt_dept->close();


    $departments = [];
    if ($departmentsResult && $departmentsResult->num_rows > 0) {
        while ($dept = $departmentsResult->fetch_assoc()) {
            $deptID = $dept['dept_ID'];
            $deptName = $dept['dept_name'];

            //Use parameterized queries to prevent SQL injection
            $submittedQuery = "SELECT COUNT(UserID) AS totalSubmit 
                               FROM task_user 
                               INNER JOIN feedcontent ON task_user.ContentID = feedcontent.ContentID 
                               WHERE task_user.Status IN ('Submitted', 'Approved', 'Rejected') 
                               AND feedcontent.dept_ID = ?";
            $submittedStmt = $conn->prepare($submittedQuery);
            if (!$submittedStmt) {
                $conn->rollback(); //Rollback transaction on error
                handleError("Error preparing submitted query: " . $conn->error);
            }
            $submittedStmt->bind_param('i', $deptID);
            $submittedStmt->execute();
            $submittedResult = $submittedStmt->get_result();
            $submittedStmt->close();

            $assignedQuery = "SELECT COUNT(UserID) AS totalAssigned 
                              FROM task_user 
                              INNER JOIN feedcontent ON task_user.ContentID = feedcontent.ContentID 
                              WHERE feedcontent.dept_ID = ?";
            $assignedStmt = $conn->prepare($assignedQuery);
            if (!$assignedStmt) {
                $conn->rollback(); //Rollback transaction on error
                handleError("Error preparing assigned query: " . $conn->error);
            }
            $assignedStmt->bind_param('i', $deptID);
            $assignedStmt->execute();
            $assignedResult = $assignedStmt->get_result();
            $assignedStmt->close();

            $submittedRow = $submittedResult->fetch_assoc();
            $assignedRow = $assignedResult->fetch_assoc();

            $totalSubmit = $submittedRow['totalSubmit'] ?? 0;
            $totalAssigned = $assignedRow['totalAssigned'] ?? 0;

            $departments[] = [
                'dept_ID' => $deptID,
                'dept_name' => $deptName,
                'totalSubmit' => $totalSubmit,
                'totalAssigned' => $totalAssigned
            ];
        }
        $conn->commit(); //Commit transaction if all is successful
        echo json_encode(['departments' => $departments]);
    } else {
        $conn->rollback(); //Rollback transaction on error
        handleError('No department found for the logged-in user.');
    }
} catch (Exception $e) {
    $conn->rollback(); //Rollback transaction on error
    handleError("An unexpected error occurred: " . $e->getMessage());
}

?>