<div href="dashdhead.php" class="brand" style="text-align:center; font-size:38px; margin-top: 15px;">
    <i class="icon">
        <img src="img/Logo/docmap-logo-1.png" alt="Icon" style="max-width: 40px; height: auto;">
    </i>
    <img src="img/Logo/docmap.png" alt="Logo" style="max-width: 150px; height: auto;">
</div>

<ul class="side-menu">
			<li><a href="dash_dhead.php" class=""><i class='bx bxs-dashboard icon' ></i> Dashboard</a></li>
			<li class="divider" data-text="main">Main</li>
			<li><a href="departments.php" ><i class='bx bxs-building icon' ></i>Departments</a></li>
			<li class="divider" data-text="productivity">Main</li>
			<li><a href="task.php"><i class='bx bx-task icon' ></i> Tasks</a></li>
			<li><a href="announcement.php"><i class='bx bx-chat icon' ></i> Announcement</a></li>
			<li><a href="mps.php"><i class='bx bx-spreadsheet icon'></i>MPS</a></li>
			<li class="divider" data-text="Documents">Main</li>
			<li><a href="doc_folder.php"><i class='bx bx-chat icon' ></i> Documents</a></li>
			 <li class="divider" data-text="others">Others</li>
 <li>
        <a href="#"><i class='bx bxs-download icon'></i> Forms <i class='bx bx-chevron-right icon-right'></i></a>
        <ul class="side-dropdown">
            <?php
            include 'connection.php'; // Ensure this file establishes the connection

            // Query to fetch form templates
            $sql_templates = "SELECT TemplateID, name, filename FROM templates";
            $result_templates = mysqli_query($conn, $sql_templates);

            // Check if templates are found
            if (mysqli_num_rows($result_templates) > 0) {
                // Loop through each template and create a list item
                while ($row_template = mysqli_fetch_assoc($result_templates)) {
                    $template_id = $row_template['TemplateID'];
                    $template_name = $row_template['name'];
                    $filename = $row_template['filename']; // Get the filename

                    // Generate the path to the file in the Admin/Templates folder
                    $file_path = "Admin/Templates/" . $filename;

                    // Determine the file extension
                    $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                    // Select an icon based on the file type
                    $icon = '';
                    switch ($file_extension) {
                        case 'pdf':
                            $icon = '<i class="bx bxs-file-pdf"></i>'; // PDF icon
                            break;
                        case 'doc':
                        case 'docx':
                            $icon = '<i class="bx bxs-file-doc"></i>'; // Word document icon
                            break;
                        case 'xls':
                        case 'xlsx':
                            $icon = '<i class="bx bxs-file-earmark-excel"></i>'; // Excel file icon
                            break;
                        case 'ppt':
                        case 'pptx':
                            $icon = '<i class="bx bxs-file-presentation"></i>'; // PowerPoint file icon
                            break;
                        case 'txt':
                            $icon = '<i class="bx bxs-file-text"></i>'; // Text file icon
                            break;
                        case 'jpg':
                        case 'jpeg':
                        case 'png':
                        case 'gif':
                            $icon = '<i class="bx bxs-file-image"></i>'; // Image file icon
                            break;
                        default:
                            $icon = '<i class="bx bxs-file"></i>'; // Default file icon
                            break;
                    }

                    // Link to the file when clicked with added spacing
                    echo "<li><a href='$file_path' target='_blank'>$icon <span style='margin-left: 8px;'>$template_name</span></a></li>";
                }
            } else {
                echo "<li><a href='#'>No forms available</a></li>";
            }

            // Close the database connection
            mysqli_close($conn);
            ?>
        </ul>
    </li>
		</ul>