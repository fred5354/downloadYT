<?php
header('Content-Type: application/json');

function sanitizePath($path) {
    return rtrim(realpath($path), DIRECTORY_SEPARATOR);
}

function updateYtDlp($yt_dlp) {
    exec("$yt_dlp -U 2>&1", $update_output, $update_status);
    return $update_status === 0;
}

function logError($message, $url = '') {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] URL: $url - $message\n";
    
    // Write to log file
    $logFile = dirname(__FILE__) . '/error_log.txt';
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    // Send email if on remote server
    if (php_uname('s') !== 'Darwin') {
        $subject = 'YouTube Downloader Error';
        $emailBody = "An error occurred in the YouTube Downloader:\n\n";
        $emailBody .= "Time: $timestamp\n";
        $emailBody .= "URL: $url\n";
        $emailBody .= "Error: $message\n";
        
        $headers = 'From: noreply@' . $_SERVER['HTTP_HOST'] . "\r\n" .
                  'X-Mailer: PHP/' . phpversion();
        
        mail('cd@crosspointchurchsv.org', $subject, $emailBody, $headers);
    }
}

function getWritableTemp() {
    // Try standard temp directory first
    $temp = sys_get_temp_dir();
    
    // If on a hosting environment, check for writable directories
    if (!is_writable($temp)) {
        // Common writable directories in hosting environments
        $possible_dirs = [
            '/tmp',
            dirname(__FILE__) . '/tmp',
            dirname(__FILE__) . '/temp',
            dirname(__FILE__) . '/downloads'
        ];
        
        foreach ($possible_dirs as $dir) {
            if (is_dir($dir) && is_writable($dir)) {
                return $dir;
            }
        }
        
        // If no writable directory found, create one in the current directory
        $new_temp = dirname(__FILE__) . '/tmp';
        if (!is_dir($new_temp)) {
            mkdir($new_temp, 0755, true);
        }
        return $new_temp;
    }
    
    return $temp;
}

$url = trim($_POST['youtube_url'] ?? '');
$format = $_POST['format'] ?? 'mp4';

// Input validation
if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
    logError('Invalid YouTube URL', $url);
    echo json_encode(['status' => 'error', 'message' => 'Invalid YouTube URL.']);
    exit;
}

if (!in_array($format, ['mp3', 'mp4'])) {
    logError('Unsupported format: ' . $format, $url);
    echo json_encode(['status' => 'error', 'message' => 'Unsupported format.']);
    exit;
}

// Get writable temp directory and create unique subdirectory
$base_temp = getWritableTemp();
$temp_dir = $base_temp . '/ytdl_' . uniqid();
if (!is_dir($temp_dir)) {
    if (!@mkdir($temp_dir, 0755, true)) {
        $error = error_get_last();
        logError('Failed to create temp directory: ' . $error['message'], $url);
        echo json_encode(['status' => 'error', 'message' => 'Failed to create temporary directory']);
        exit;
    }
}

$safe_url = escapeshellarg($url);
$safe_temp = escapeshellarg($temp_dir);

// Detect if running locally or on remote server
$is_local = (php_uname('s') === 'Darwin'); // Check if running on macOS

if ($is_local) {
    $yt_dlp = '/Library/Frameworks/Python.framework/Versions/3.12/bin/yt-dlp';
    $ffmpeg_path = '/opt/homebrew/bin/ffmpeg';
    $cookies = '';
} else {
    $yt_dlp = '/home/u683-twiak05nhmys/.local/bin/yt-dlp';
    $ffmpeg_path = '/usr/local/bin/ffmpeg';
    $cookies = '--cookies cookies.txt';
}

// Update yt-dlp before proceeding
if (!updateYtDlp($yt_dlp)) {
    logError('Failed to update yt-dlp', $url);
}

if ($format === 'mp3') {
    $cmd = "$yt_dlp --ffmpeg-location $ffmpeg_path -k -x --audio-format mp3 --force-ipv4 --no-check-certificates -o {$safe_temp}/'%(title)s.%(ext)s' $safe_url 2>&1";
} else {
    $cmd = "$yt_dlp --ffmpeg-location $ffmpeg_path -S vcodec:h264,res,acodec:m4a $cookies --merge-output-format mp4 -o {$safe_temp}/'%(title)s.%(ext)s' $safe_url 2>&1";
}

exec($cmd, $output, $status);

if ($status === 0) {
    // Find the downloaded file
    $files = glob($temp_dir . '/*.' . $format);
    if (!empty($files)) {
        $file = $files[0];
        $filename = basename($file);
        
        // Return success with file information
        echo json_encode([
            'status' => 'success',
            'message' => 'Download completed successfully',
            'download_url' => 'download_file.php?file=' . urlencode(basename($temp_dir)) . '&name=' . urlencode($filename),
            'filename' => $filename
        ]);
    } else {
        $error_msg = 'Could not find downloaded file';
        logError($error_msg . "\nCommand output: " . implode("\n", $output), $url);
        echo json_encode(['status' => 'error', 'message' => $error_msg]);
    }
} else {
    $error_msg = implode("\n", $output);
    logError("Download failed:\n" . $error_msg, $url);
    echo json_encode(['status' => 'error', 'message' => "Download failed:\n" . $error_msg]);
}

// Cleanup old temp directories (older than 1 hour)
$old_temps = glob($base_temp . '/ytdl_*');
foreach ($old_temps as $old_temp) {
    if (is_dir($old_temp) && (time() - filemtime($old_temp) > 3600)) {
        array_map('unlink', glob($old_temp . '/*.*'));
        rmdir($old_temp);
    }
}
