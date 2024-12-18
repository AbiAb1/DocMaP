<?php
include 'connection.php';

$query = "
SELECT 
    CONCAT(u.fname, ' ', u.mname, '. ', u.lname) AS full_name,
    u.profile,
    u.UserID,
    COUNT(tu.TaskID) AS total_tasks, 
    SUM(CASE WHEN tu.Status = 'Approved' THEN 1 ELSE 0 END) AS approved_tasks,
    TIMESTAMPDIFF(HOUR, tu.SubmitDate, CONCAT(t.DueDate, ' ', t.DueTime)) AS hours_early,
    TIMESTAMPDIFF(DAY, tu.SubmitDate, CONCAT(t.DueDate, ' ', t.DueTime)) AS days_early
FROM 
    useracc u
JOIN 
    task_user tu ON u.UserID = tu.UserID
JOIN 
    tasks t ON tu.TaskID = t.TaskID
WHERE 
    t.Type = 'Task' 
    AND tu.Status IN ('Submitted', 'Approved')
GROUP BY 
    u.UserID
ORDER BY 
    approved_tasks DESC, hours_early DESC
";

$result = mysqli_query($conn, $query);
$data = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $precision = ($row['total_tasks'] > 0) ? round(($row['approved_tasks'] / $row['total_tasks']) * 100, 2) : 0;
        $data[] = [
            'full_name' => $row['full_name'],
            'profile' => $row['profile'],
            'hours_early' => $row['hours_early'],
            'days_early' => $row['days_early'],
            'precision' => $precision,
        ];
    }
} else {
    // Error handling
    error_log(mysqli_error($conn));
}

header('Content-Type: application/json');
echo json_encode($data);
?>
