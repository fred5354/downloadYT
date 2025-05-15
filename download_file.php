<?php
// Validate input parameters
$temp_dir = isset($_GET['file']) ? $_GET['file'] : '';
$filename = isset($_GET['name']) ? $_GET['name'] : '';

if (empty($temp_dir) || empty($filename) || !preg_match('/^ytdl_[a-f0-9]+$/', $temp_dir)) {
    header('HTTP/1.1 400 Bad Request');
    exit('Invalid request');
}

// Construct the full file path
$file_path = sys_get_temp_dir() . '/' . $temp_dir . '/' . $filename;

// Validate file exists and is within the temp directory
if (!file_exists($file_path) || !is_file($file_path)) {
    header('HTTP/1.1 404 Not Found');
    exit('File not found');
}

// Get file information
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file_path);
finfo_close($finfo);

// Set headers for download
header('Content-Type: ' . $mime_type);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($file_path));
header('Pragma: public');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

// Output file content
readfile($file_path);

// Delete the file and its directory after download
unlink($file_path);
rmdir(dirname($file_path)); 