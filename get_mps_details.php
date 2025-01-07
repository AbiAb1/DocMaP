<?php
// get_mps_details.php

include('connection.php'); // Include your database connection file

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mpsID'])) {
    $mpsID = mysqli_real_escape_string($conn, $_POST['mpsID']);

    // Fetch the details of the MPS record based on mpsID
    $query = "SELECT TotalScores, TotalNumOfItems, TotalNumTested FROM mps WHERE mpsID = '$mpsID'";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $response = [
            'success' => true,
            'totalScoresValue' => $row['TotalScores'],
            'totalItemsValue' => $row['TotalNumOfItems'],
            'totalTestedValue' => $row['TotalNumTested']
        ];
    } else {
        $response = ['success' => false];
    }

    echo json_encode($response);
}
?>
