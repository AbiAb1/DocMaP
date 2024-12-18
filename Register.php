<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the Composer autoload file
require 'vendor/autoload.php'; // Ensure this path is correct

// Include your database connection file
include 'connection.php'; // Assuming this file contains the database connection setup

use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Function to generate a random string for password
function generateRandomString($length = 4) {
    return bin2hex(random_bytes($length)); // Generates a random string of specified length
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capture form data
    $firstname = htmlspecialchars($_POST['firstname']);
    $middleinitial = !empty($_POST['middleinitial']) ? htmlspecialchars($_POST['middleinitial']) : 'N/A';
    $lastname = htmlspecialchars($_POST['lastname']);
    $gender = htmlspecialchars($_POST['gender']);
    $birthday = htmlspecialchars($_POST['birthday']);
    $mobile = htmlspecialchars($_POST['mobile']);
    $address = htmlspecialchars($_POST['address']);
    $email = htmlspecialchars($_POST['email']);
    $ranking = htmlspecialchars($_POST['ranking']);

    // GitHub URL for the Excel file
    $githubUrl = 'https://raw.githubusercontent.com/AbiAb1/DocMaP/extra/Admin/TeacherData/LNHS-Teachers.xlsx'; // Replace with your GitHub raw file URL
    $localFile = 'LNHS-Teachers.xlsx';

    // Download the file from GitHub
    file_put_contents($localFile, file_get_contents($githubUrl));

    // Load the Excel file
    $spreadsheet = IOFactory::load($localFile);
    $worksheet = $spreadsheet->getActiveSheet();
    
    $found = false; // Flag to check if data is found

    // Loop through rows in the Excel file
    foreach ($worksheet->getRowIterator() as $row) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false); // Iterate all cells
        
        $phoneNumber = ''; // Placeholder for phone number
        $emailAddress = ''; // Placeholder for email

        foreach ($cellIterator as $cell) {
            $columnIndex = $cell->getColumn(); // Get the column index
            $cellValue = $cell->getValue(); // Get the cell value

            // Assuming phone number is in column E and email is in column F
            if ($columnIndex == 'E') { 
                $phoneNumber = $cellValue;
            }
            if ($columnIndex == 'F') { 
                $emailAddress = $cellValue;
            }
        }

        // Check if there is a match
        if ($mobile === $phoneNumber && $email === $emailAddress) {
            $found = true;
            break; // Exit loop if a match is found
        }
    }

    // Check if the email already exists in the useracc table
    $emailCheckQuery = "SELECT * FROM useracc WHERE Email = ?";
    $emailCheckStmt = $conn->prepare($emailCheckQuery);
    $emailCheckStmt->bind_param("s", $email);
    $emailCheckStmt->execute();
    $emailCheckResult = $emailCheckStmt->get_result();

    if ($emailCheckResult->num_rows > 0) {
        // Email already exists
        echo "
            <script>
                Swal.fire({
                    title: 'Email Exists',
                    text: 'The email $email is already registered. Please use a different email.',
                    icon: 'error'
                });
            </script>
        ";
    } else {
        // Generate a unique username and password
        $username = strtolower($firstname[0] . $lastname);
        $password = generateRandomString(4); 
        $hashedPassword = md5($password); 

        $status = $found ? 'Approved' : 'Pending';

        // Insert into database
        $stmt = $conn->prepare("INSERT INTO useracc (username, password, fname, mname, lname, bday, Sex, Address, Email, Rank, mobile, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssssss", $username, $hashedPassword, $firstname, $middleinitial, $lastname, $birthday, $gender, $address, $email, $ranking, $mobile, $status);
        if ($stmt->execute()) {
            if ($status === "Approved") {
                // Send email for approval
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com'; 
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'proftal2024@gmail.com'; // Replace with your email
                    $mail->Password   = 'ytkj saab gnkb cxwa'; // Replace with your email password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->setFrom('proftal2024@gmail.com', 'DocMaP');
                    $mail->addAddress($email, "$firstname $lastname"); 

                    $mail->isHTML(true);
                    $mail->Subject = 'Account Approved';
                    $mail->Body    = "Dear $firstname $lastname,<br>Your account is approved. Username: $username, Password: $password.<br>Best regards.";

                    $mail->send();
                    echo "
                        <script>
                            Swal.fire({
                                title: 'Congratulations!',
                                text: 'Your account has been approved. An email has been sent.',
                                icon: 'success'
                            }).then(() => window.location.href = 'index.php');
                        </script>
                    ";
                } catch (Exception $e) {
                    echo "
                        <script>
                            Swal.fire({
                                title: 'Email Error',
                                text: 'Email could not be sent.',
                                icon: 'error'
                            });
                        </script>
                    ";
                }
            } else {
                echo "
                    <script>
                        Swal.fire({
                            title: 'Thank You for Registering!',
                            text: 'Your account is pending admin approval.',
                            icon: 'info'
                        }).then(() => window.location.href = 'index.php');
                    </script>
                ";
            }
        } else {
            echo "
                <script>
                    Swal.fire({
                        title: 'Registration Failed',
                        text: 'An error occurred during registration.',
                        icon: 'error'
                    });
                </script>
            ";
        }
        $stmt->close();
    }
    $emailCheckStmt->close();
    $conn->close();
}
?>
