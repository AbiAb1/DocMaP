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
    // Add more extensions as needed
];
$mimeType = $mimeTypeMap[$extension] ?? 'application/octet-stream';

header("Content-Type: $mimeType");

// Display HTML content within a webpage structure for viewable files
if ($mimeType === 'text/plain' || $mimeType === 'text/html') {
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Viewing File: $filename</title>
    </head>
    <body>
        <h1>Viewing File: $filename</h1>
        <pre>" . htmlspecialchars($fileContent) . "</pre>
    </body>
    </html>";
} else {
    // Directly output non-viewable files like PDFs or images
    echo $fileContent;
}
?>
