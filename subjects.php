<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Include your database connection file here
include 'connection.php';

// Get user_id from session
$user_id = $_SESSION['user_id'];

// Query to fetch contents from feedcontent table. Added a check for NULL ContentColor
$sql = "SELECT fs.ContentID, fs.Title, fs.Captions, IFNULL(fs.ContentColor, '#9B2035') as ContentColor  -- Added IFNULL
        FROM feedcontent fs
        INNER JOIN usercontent uc ON fs.ContentID = uc.ContentID
        WHERE uc.UserID = $user_id AND Status=1";
$result = mysqli_query($conn, $sql);

// Check for errors in the query execution
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

// Fetch all records from the result set
$contents = mysqli_fetch_all($result, MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subjects</title>
    <link rel="icon" type="image/png" href="img/Logo/docmap-logo-1.png">
    <!-- ======= Styles ====== -->
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
   <style>
        .cardBox {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            height: 100%;
            margin-top:30px;
        }

        .card {
            width: calc(33.33% - 20px); /* 3 cards per row */
            padding: 20px;
            box-sizing: border-box;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            color: #fff;
        }

        .card h2 {
            font-size: 20px;
            margin-bottom: -1px;
            color: #fff;
        }

        .card p {
            font-size: 14px;
            color: #fff;
            
        }

        .search-container {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-bottom: 20px;
            margin-right: 20px;
        }

        .search-bar {
            border-radius: 20px;
            padding: 10px 20px;
            border: 1px solid #ccc;
            width: 250px;
        }

        .fab {
           

            right: 20px;
            background-color: #9B2035;
            color: #fff;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            cursor: pointer;
        }

        .fab i {
            font-size: 30px;
        }

        .plus-icon {
            font-size: 30px;
            color: black;
            cursor: pointer;
            margin-left: 20px;
        }

        .search-container {
            position: relative;
        }

        .input {
            width: 150px;
            padding: 10px 0px 10px 40px;
            border-radius: 9999px;
            border: solid 1px #333;
            transition: all .2s ease-in-out;
            outline: none;
            opacity: 0.8;
        }

        .search-container svg {
            position: absolute;
            top: 50%;
            left: 10px;
            transform: translate(0, -50%);
        }

        .input:focus {
            opacity: 1;
            width: 250px;
        }

        .modal-backdrop {
            z-index: 1040 !important;
        }

        .modal {
            z-index: 1050 !important;
        }
    </style>
</head>

<body style ="background-color:#f1fof6;">
    <!-- SIDEBAR -->
    <section id="sidebar">
        <?php include 'navbar.php'; ?>
    </section>
    <!-- SIDEBAR -->

    <section id="content" >
        <!-- NAVBAR -->
        <?php include 'topbar.php'; ?>
        <!-- NAVBAR -->

        <!-- MAIN -->
        <main>
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="title" style="margin-bottom: 20px;">Grades</h2>
                <div class="fab" data-toggle="modal" data-target="#exampleModal">
                    <i class='bx bx-plus'></i>
                </div>
            </div>
            <div class="cardBox">
                <?php
                if (!empty($contents)) {
                    foreach ($contents as $row) {
                        // Escape the ContentColor to ensure safe output in the style attribute
                        $contentColor = htmlspecialchars($row['ContentColor'], ENT_QUOTES, 'UTF-8');
                        
                        // Now applying ContentColor safely to the inline style
                        echo "<div class='card' style='background-color: $contentColor;'>"; // Use background-color instead of color for the card's background
                        
                        echo "<h2><a style='color:#ffff; font-size: 25px;' href='tasks.php?content_id=" . htmlspecialchars($row['ContentID'], ENT_QUOTES, 'UTF-8') . "'>" . htmlspecialchars($row['Title'], ENT_QUOTES, 'UTF-8') . "</a></h2>";
                        echo "<p>" . htmlspecialchars($row['Captions'], ENT_QUOTES, 'UTF-8') . "</p>";
                        // Add more elements as needed (e.g., images, links)
                        echo "</div>";
                    }
                } else {
                    echo "No content available.";
                }
                ?>
            </div>
        </main>
        <!-- MAIN -->
    </section>

    <!-- Floating Action Button -->
   

    <!-- Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Add New Content</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Add your form or content here -->
                    <form>
                        <div class="form-group">
                            <label for="contentCode">Content Code</label>
                            <input type="text" class="form-control" id="contentCode" placeholder="Enter Code">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="saveButton" disabled>Save changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- =========== Scripts =========  -->
    <script src="assets/js/script.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <script>
    function console.logContentID(contentID) {
        console.log("ContentID passed to tasks.php:", contentID);
    }
</script>

    <script>
$(document).ready(function() {
    // Check if the content code exists
    $('#contentCode').on('input', function() {
        var code = $(this).val();

        // Only perform the AJAX request if code is not empty
        if (code) {
            $.ajax({
                url: 'check_code.php',
                type: 'POST',
                data: { code: code },
                success: function(response) {
                    if (response == 'exists') {
                        $('#saveButton').prop('disabled', false); // Enable button if code exists
                    } else {
                        $('#saveButton').prop('disabled', true); // Disable button if code doesn't exist
                    }
                },
                error: function() {
                    console.log('Error occurred while checking the code.');
                }
            });
        } else {
            $('#saveButton').prop('disabled', true); // Disable button if code input is empty
        }
    });

    // Handle content save on button click
    $('#saveButton').on('click', function() {
        var code = $('#contentCode').val();

        if (code) {  // Ensure there's a code to submit
            $.ajax({
                url: 'insert_usercontent.php',
                type: 'POST',
                data: { code: code },
                success: function(response) {
                    if (response == 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Content Added',
                            text: 'Content added successfully.',
                        }).then(() => {
                            $('#exampleModal').modal('hide');
                            location.reload(); // Reload the page to reflect changes
                        });
                    } else if (response == 'exists') {
                        Swal.fire({
                            icon: 'info',
                            title: 'Already Active',
                            text: 'You are already part of this grade.',
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error adding content.',
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while saving content.',
                    });
                }
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Content code is missing.',
            });
        }
    });

    // Search functionality for cards
    document.getElementById('searchInput').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const cards = document.querySelectorAll('.card');
        
        cards.forEach(card => {
            const title = card.querySelector('h2').innerText.toLowerCase();
            if (title.includes(searchTerm)) {
                card.style.display = 'block';  // Show card if it matches search
            } else {
                card.style.display = 'none';  // Hide card if it doesn't match search
            }
        });
    });
});

    </script>
</body>

</html>
