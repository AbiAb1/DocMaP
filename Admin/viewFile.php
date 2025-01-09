<?php
// Ensure no output before headers
if (!isset($_GET['file'])) {
    echo "No file specified";
    exit;
}

// Validate the filename
$filename = basename($_GET['file']); // Prevent directory traversal
if (empty($filename)) {
    echo "Invalid file name.";
    exit;
}

$url = "https://raw.githubusercontent.com/AbiAb1/DocMaP/extra/Admin/Templates/" . urlencode($filename);

$fileContent = @file_get_contents($url);

if ($fileContent === false) {
    http_response_code(404);
    echo "File not found";
    exit;
}

// Attempt to detect MIME type
$pathInfo = pathinfo($filename);
$extension = strtolower($pathInfo['extension'] ?? '');
$mimeTypeMap = [
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'txt' => 'text/plain',
    'html' => 'text/html',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    // Add more extensions as needed
];
$mimeType = $mimeTypeMap[$extension] ?? 'application/octet-stream';

// Set the content type header before any output
header("Content-Type: $mimeType");


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View File</title>
</head>
<body>
    <pre><?php echo htmlspecialchars($fileContent); ?></pre>
</body>
</html>
