<?php
require_once 'vendor/autoload.php';
require_once 'config.php';

session_start();

$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);
$client->addScope('email');
$client->addScope('profile');

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token);

    // Get user info
    $oauth2 = new Google_Service_Oauth2($client);
    $userInfo = $oauth2->userinfo->get();
    
    $email = $userInfo->email;
    $domain = substr(strrchr($email, "@"), 1);
    
    if (!in_array($domain, ALLOWED_DOMAINS)) {
        session_destroy();
        header('Location: index.php?error=unauthorized_domain');
        exit;
    }
    
    // Store user info in session
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = $userInfo->name;
    $_SESSION['picture'] = $userInfo->picture;
    $_SESSION['logged_in'] = true;
    
    header('Location: index.php');
    exit;
} else {
    header('Location: index.php?error=auth_failed');
    exit;
} 