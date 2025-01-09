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

// For text and HTML files, display the content in a browser-friendly format
if ($mimeType === 'text/plain' || $mimeType === 'text/html') {
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Viewing File: " . htmlspecialchars($filename) . "</title>
    </head>
    <body>
        <h1>Viewing File: " . htmlspecialchars($filename) . "</h1>
        <pre>" . htmlspecialchars($fileContent) . "</pre>
    </body>
    </html>";
} elseif ($mimeType === 'application/pdf') {
    // For PDF files, display the PDF in an embedded viewer
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Viewing PDF: " . htmlspecialchars($filename) . "</title>
    </head>
    <body>
        <h1>Viewing PDF: " . htmlspecialchars($filename) . "</h1>
        <embed src='" . $url . "' width='100%' height='800px' type='application/pdf'>
    </body>
    </html>";
} elseif ($mimeType === 'image/jpeg' || $mimeType === 'image/png') {
    // For image files, display the image
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Viewing Image: " . htmlspecialchars($filename) . "</title>
    </head>
    <body>
        <h1>Viewing Image: " . htmlspecialchars($filename) . "</h1>
        <img src='" . $url . "' alt='" . htmlspecialchars($filename) . "' width='100%'>
    </body>
    </html>";
} else {
    // For other file types, force a download or display a message
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Viewing File: " . htmlspecialchars($filename) . "</title>
    </head>
    <body>
        <h1>File type not directly viewable</h1>
        <p>You can <a href='" . $url . "' download>download the file here</a>.</p>
    </body>
    </html>";
}
?>
