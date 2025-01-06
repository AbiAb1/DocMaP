<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Database connection
include 'connection.php';

// Handle AJAX request for updating a task
if (isset($_POST['update_task_id'])) {

    $taskID = $_POST['update_task_id'];

    // Check if grades were submitted
    if (isset($_POST['grade']) && !empty($_POST['grade'])) {
        $contentIDs = implode(',', $_POST['grade']); // Combine selected grades into a comma-separated string
    } else {
        exit;
    }

    $title = $_POST['update_title'];
    $taskContent = $_POST['update_instructions'];
    $actionType = $_POST['actionType']; // Added actionType from the form

    // Set as null since no field exists
    $dueDate = NULL;
    $dueTime = NULL;

    // Variables for schedule-specific data
    $scheduleDate = isset($_POST['update_schedule_date']) ? $_POST['update_schedule_date'] : null;
    $scheduleTime = isset($_POST['update_schedule_time']) ? $_POST['update_schedule_time'] : null;


    // Prepare base SQL
    $sql = "UPDATE tasks SET ContentID = ?, Title = ?, taskContent = ?, DueDate = ?, DueTime = ?, Status = ?";

    // Append SQL for scheduled tasks
    if ($actionType == 'Schedule' && $scheduleDate && $scheduleTime) {
        $sql .= ", Schedule_Date = ?, Schedule_Time = ?";
    }

    $sql .= " WHERE TaskID = ?";

    // Prepare statement
    $stmt = $conn->prepare($sql);

    // Bind parameters based on action type
    if ($actionType == 'Schedule' && $scheduleDate && $scheduleTime) {
        $status = 'Schedule';
        $stmt->bind_param('ssssssssi', $contentIDs, $title, $taskContent, $dueDate, $dueTime, $status, $scheduleDate, $scheduleTime, $taskID);
    } else {
        $status = ($actionType == 'Assign') ? 'Assign' : 'Draft';
        $stmt->bind_param('ssssssi', $contentIDs, $title, $taskContent, $dueDate, $dueTime, $status, $taskID);
    }

    // Execute and handle the response
    $response = array();
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Reminder updated successfully!';
    } else {
        $response['success'] = false;
        $response['message'] = 'Failed to update reminder.';
    }

    // Close statement and connection
    $stmt->close();
    $conn->close();

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>
