<?php
@include 'config.php';

/* 1. Unset all session variables */
$_SESSION = [];

/* 2. Destroy the session */
session_destroy();

/* 3. Delete the session cookie (CRITICAL) */
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

/* 4. Redirect to login */
header('location:login.php');
exit;
