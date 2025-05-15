<?php
session_start();
$userName = isset($_SESSION['user_name']) ? explode(' ', $_SESSION['user_name'])[0] : 'user';
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex, nofollow">
    <meta name="googlebot" content="noindex, nofollow">
    <title>Logged Out - Crosspoint YouTube Downloader</title>
    <link rel="stylesheet" href="style.css?v=<?php echo rand(1000,9999); ?>">
</head>
<body class="logout-page">
    <div class="logout-container">
        <img src="images/logo.png" alt="Crosspoint Church Logo" class="logo">
        <h1>Goodbye, <?php echo htmlspecialchars($userName); ?>!</h1>
        <p>Thank you for using the Crosspoint YouTube Downloader.</p>
        <p class="close-text">You have been successfully logged out.<br>You can safely close this window now.</p>
    </div>
</body>
</html> 
