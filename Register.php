<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the Composer autoload file
require 'vendor/autoload.php';

// Include your database connection file
include 'connection.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Function to generate a random string for password
function generateRandomString($length = 4) {
    return bin2hex(random_bytes($length)); // Generates a random string of specified length
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = htmlspecialchars($_POST['firstname']);
    $middleinitial = !empty($_POST['middleinitial']) ? htmlspecialchars($_POST['middleinitial']) : 'N/A';
    $lastname = htmlspecialchars($_POST['lastname']);
    $gender = htmlspecialchars($_POST['gender']);
    $birthday = htmlspecialchars($_POST['birthday']);
    $mobile = htmlspecialchars($_POST['mobile']);
    $address = htmlspecialchars($_POST['address']);
    $email = htmlspecialchars($_POST['email']);
    $ranking = htmlspecialchars($_POST['ranking']);

    // Local path to the Excel file
    $localFile = '/var/www/html/LNHS-Teachers.xlsx';

    // Check if the file exists locally
    if (!file_exists($localFile)) {
        die("Error: The file LNHS-Teachers.xlsx does not exist.");
    }

    // Load the Excel file
    try {
        $spreadsheet = IOFactory::load($localFile);
        $worksheet = $spreadsheet->getActiveSheet();
    } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
        die("Error reading the Excel file: " . $e->getMessage());
    }

    $found = false;

    // Iterate through rows
    foreach ($worksheet->getRowIterator() as $row) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);

        $phoneNumber = '';
        $emailAddress = '';

        foreach ($cellIterator as $cell) {
            $columnIndex = $cell->getColumn();
            $cellValue = $cell->getValue();

            if ($columnIndex == 'E') {
                $phoneNumber = $cellValue;
            }
            if ($columnIndex == 'F') {
                $emailAddress = $cellValue;
            }
        }

        if ($mobile === $phoneNumber && $email === $emailAddress) {
            $found = true;
            break;
        }
    }

    // Check email existence in the database
    $emailCheckQuery = "SELECT * FROM useracc WHERE Email = ?";
    $emailCheckStmt = $conn->prepare($emailCheckQuery);
    $emailCheckStmt->bind_param("s", $email);
    $emailCheckStmt->execute();
    $emailCheckResult = $emailCheckStmt->get_result();

    if ($emailCheckResult->num_rows > 0) {
        echo "
            <script>
                alert('Email already exists. Please use a different email.');
                window.history.back();
            </script>
        ";
    } else {
        $username = strtolower($firstname[0] . $lastname);
        $password = generateRandomString(4);
        $hashedPassword = md5($password);
        $status = $found ? 'Approved' : 'Pending';

        $stmt = $conn->prepare("INSERT INTO useracc (username, password, fname, mname, lname, bday, Sex, Address, Email, Rank, mobile, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssssss", $username, $hashedPassword, $firstname, $middleinitial, $lastname, $birthday, $gender, $address, $email, $ranking, $mobile, $status);

        if ($stmt->execute()) {
            echo "
                <script>
                    alert('Registration successful. Status: $status');
                    window.location.href = 'index.php';
                </script>
            ";
        } else {
            echo "
                <script>
                    alert('Error during registration. Please try again.');
                </script>
            ";
        }

        $stmt->close();
    }

    $emailCheckStmt->close();
    $conn->close();
}
?>
