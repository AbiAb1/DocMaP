<?php
session_start();
include 'connection.php';

function getFileIcon($filename) {
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    switch (strtolower($extension)) {
        case 'pdf':
            return 'fas fa-file-pdf';
        case 'doc':
        case 'docx':
            return 'fas fa-file-word';
        case 'xls':
        case 'xlsx':
            return 'fas fa-file-excel';
        case 'ppt':
        case 'pptx':
            return 'fas fa-file-powerpoint';
        case 'txt':
            return 'fas fa-file-alt';
        case 'jpg':
        case 'jpeg':
        case 'png':
            return 'fas fa-file-image';
        case 'zip':
            return 'fas fa-file-archive';
        default:
            return 'fas fa-file'; // Default icon
    }
}

$query = "SELECT t.TemplateID, t.name, t.filename, t.created_at, 
          CONCAT(u.fname, ' ', u.mname, '.', ' ', u.lname) AS uploaded_by 
          FROM templates t
          JOIN useracc u ON t.UserID = u.UserID";
$result = mysqli_query($conn, $query);
$rowCount = mysqli_num_rows($result); // Get the number of rows returned
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Templates</title>
    <link rel="icon" type="image/png" href="../img/Logo/docmap-logo-1.png">
    <style>
         table {
            width: 80%;
            border-collapse: collapse;
        }
        th, td {
            text-align: center;
            padding: 10px;
        }
        th {
            background-color: #9B2035;
            color: white;
        }
        td {
            background-color: #ffff;
        }
        .btn-circle {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            margin: 0 3px;
            text-decoration: none;
        }
        .btn-view {
            background-color: #007bff; /* Blue color for view icon */
        }
        .btn-delete {
            background-color: #dc3545; /* Red color for delete icon */
        }
        .btn-circle i {
            margin: 0;
        }
        .btnTemplate{
             background-color: #9b2035;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 25px; /* Adjust for desired roundness */
            cursor: pointer;
            transition: background-color 0.3s; /* Add a smooth transition effect */
            float: right;
            font-weight:bold;
        }

        .btnTemplate:hover {
            background-color: #7a182a; /* Darker shade on hover */
        }
        .info-message {
            display: flex;
            align-items: center;
            margin-top: 5px; /* Space between title and message */
            margin-bottom: 5px;
            margin-left: 50px; /* Move message to the right */  
        }

        .info-message p {
            font-size: 16px; /* Font size for the message */
            color: #555; /* Color for the message text */
            margin: 0; /* Remove default margin */
        }

        
    </style>
</head>
<body style ="background-color:#F1F0F6;">
    <section id="sidebar">
        <?php include 'navbar.php'; ?>
    </section>
    <section id="content">
        <?php include 'topbar.php'; ?>
        <main>
            <h2 class="title mb-4">Templates</h2>
            
<!-- Information Icon and Message -->
                <div class="info-message" style="display: flex; align-items: center; margin-top: 10px;">
                    <i class='bx bx-info-circle'style="font-size: 24px; margin-right: 10px; color:#9B2035; "></i>
                    <p style="font-size: 14px; color: #555; margin: 0;"> <!-- Remove margin for better alignment -->
                        Templates will reflect to the user's interface.
                    </p>
                </div>
            
            <div class="container">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <input type="text" id="searchInput" class="form-control" placeholder="Search by name...">
                    </div>
                    <div class="col-md-6 text-right">
                        <button class="btnTemplate"  data-toggle="modal" data-target="#uploadModal">Add Template</button>
                    </div>
                </div>

                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Uploaded By</th>
                            <th>Uploaded At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="templateTableBody">
                        <?php if ($rowCount > 0) { ?>
                            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                                <tr>
                                    <td> 
                                        <i style="margin-right:5px; color:grey;" class="<?php echo getFileIcon($row['filename']); ?>"></i>
                                        <?php echo htmlspecialchars($row['name']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['uploaded_by']); ?></td>
                                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                    <td>
                                        <!-- View Icon -->
                                        <a href="Templates/<?php echo $row['filename']; ?>" target="_blank" class="btn btn-circle btn-view" title="View">
                                            <i class="fas fa-eye"></i> <!-- View Icon (Font Awesome) -->
                                        </a>
                                        <!-- Delete Icon -->
                                        <a href="#" class="btn btn-circle btn-delete" title="Delete" 
                                           onclick="confirmDelete('<?php echo $row['TemplateID']; ?>', '<?php echo $row['filename']; ?>'); return false;">
                                           <i class="fas fa-trash-alt"></i> <!-- Delete Icon (Font Awesome) -->
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="4" class="text-center">No templates available.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <!-- Upload Modal -->
            <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="uploadModalLabel">Upload New Template</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form id="uploadTemplateForm" enctype="multipart/form-data">
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="templateName">Template Name</label>
                                    <input type="text" class="form-control" id="templateName" name="template_name" required>
                                </div>
                                <div class="form-group">
                                    <label for="templateFile">Upload File</label>
                                    <input type="file" class="form-control-file" id="templateFile" name="template_file" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Upload</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php if (isset($_SESSION['message'])): ?>
                <script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: '<?php echo $_SESSION['message']; ?>',
                        timer: 3000, // Alert will close after 3 seconds
                        showCloseButton: true,
                        confirmButtonText: 'Okay'
                    });
                </script>
                <?php unset($_SESSION['message']); // Clear message after displaying ?>
            <?php elseif (isset($_SESSION['error'])): ?>
                <script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: '<?php echo $_SESSION['error']; ?>',
                        timer: 3000,
                        showCloseButton: true,
                        confirmButtonText: 'Okay'
                    });
                </script>
                <?php unset($_SESSION['error']); // Clear error message after displaying ?>
            <?php endif; ?>

        </main>
    </section>
    <script src="assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    $(document).ready(function() {
        $('#uploadTemplateForm').on('submit', function(e) {
            e.preventDefault(); // Prevent form submission

            var formData = new FormData(this);

            $.ajax({
                url: 'upload_template.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    var result = JSON.parse(response);
                    if (result.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Uploaded!',
                            text: result.message,
                        }).then(() => {
                            $('#uploadModal').modal('hide'); // Hide modal
                            location.reload(); // Reload page
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: result.message,
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Something went wrong with the AJAX request.',
                    });
                }
            });
        });
    });
</script>
    <script>
    function confirmDelete(templateId, filename) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirect to delete_template.php with templateId
                window.location.href = 'delete_template.php?id=' + templateId + '&filename=' + filename;
            }
        });
    }

    // Search functionality
    $(document).ready(function() {
        $('#searchInput').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $('#templateTableBody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
        });
    });
    </script>
</body>
</html>
