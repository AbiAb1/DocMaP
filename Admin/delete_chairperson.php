<?php
session_start();
// Include the database connection
include 'connection.php';

if (isset($_POST['chairpersonID']) && !empty($_POST['chairpersonID'])) {
    // Check if any chairperson IDs are selected
    $selectedChairpersons = $_POST['chairpersonID'];

    // Prepare the SQL DELETE query
    $ids = implode(',', array_map('intval', $selectedChairpersons)); // Ensure the IDs are integers
    $sql = "DELETE FROM chairperson WHERE Chairperson_ID IN ($ids)";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(['status' => 'success', 'message' => 'Chairperson(s) deleted successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $conn->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No chairpersons selected for deletion.']);
}

$conn->close();
?>
