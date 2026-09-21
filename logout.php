<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Check if timeout parameter is set
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    // Output HTML + JS for alert before redirect
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Session Timeout</title>
        <link rel="icon" href="assets/images/brand/favicon.svg" type="image/svg+xml">
        <script>
            alert("Your session has timed out due to inactivity. You will be redirected to the login page.");
            window.location.href = "login.php";
        </script>
    </head>
    <body></body>
    </html>';
    exit();
} else {
    // Normal logout, no alert
    header("Location: login.php");
    exit();
}
