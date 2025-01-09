<?php
if (!isset($_GET['file'])) {
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>No File Specified</title>
    </head>
    <body>
        <h1>No file specified</h1>
    </body>
    </html>";
    exit;
}

$filename = $_GET['file'];
$url = "https://raw.githubusercontent.com/AbiAb1/DocMaP/extra/Admin/Templates/" . urlencode($filename);

$fileContent = @file_get_contents($url);

if ($fileContent === false) {
    http_response_code(404);
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>File Not Found</title>
    </head>
    <body>
        <h1>File not found</h1>
    </body>
    </html>";
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
    'zip' => 'application/zip',
    'rar' => 'application/x-rar-compressed',
    'csv' => 'text/csv',
    // Add more extensions as needed
];
$mimeType = $mimeTypeMap[$extension] ?? 'application/octet-stream';

header("Content-Type: $mimeType");

// Common HTML structure
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Viewing File: $filename</title>
</head>
<body>
    <h1>Viewing File: $filename</h1>";

// Display content based on MIME type
if ($mimeType === 'text/plain' || $mimeType === 'text/html' || $mimeType === 'text/csv') {
    // Display text-based files
    echo "<pre>" . htmlspecialchars($fileContent) . "</pre>";
} elseif (in_array($mimeType, ['image/jpeg', 'image/png'])) {
    // Display image files
    echo "<img src='data:$mimeType;base64," . base64_encode($fileContent) . "' alt='Image' />";
} elseif ($mimeType === 'application/pdf') {
    // Display PDF files in a viewer
    echo "<embed src='data:$mimeType;base64," . base64_encode($fileContent) . "' width='100%' height='600px' />";
} elseif (in_array($mimeType, ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])) {
    // Display DOCX and XLSX files as a download link
    echo "<p>This file cannot be displayed directly. <a href='$url' target='_blank'>Click here to download the file</a>.</p>";
} else {
    // Provide a download link for unsupported file types
    echo "<p>This file cannot be displayed. <a href='$url' target='_blank'>Click here to download the file</a>.</p>";
}

echo "</body>
</html>";
?>
