<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
// Set the timeout duration (15 minutes in seconds)
$timeoutDuration = (int) config('app.session_idle_timeout', 900);

// Set the logout redirect URL
$logoutRedirectUrl = '../logout.php';

// Function to check session status
function checkSessionStatus() {
    global $timeoutDuration, $logoutRedirectUrl;
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit();
    }

    if (!isset($_SESSION['_fingerprint'])) {
        $_SESSION['_fingerprint'] = hash('sha256', (string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));
    } elseif (!hash_equals($_SESSION['_fingerprint'], hash('sha256', (string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown')))) {
        $_SESSION = [];
        session_destroy();
        header('Location: ../login.php?error=session_invalid');
        exit();
    }

    // Initialize last_activity if not set
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
    }

    // Check if session has expired
    if ((time() - $_SESSION['last_activity']) > $timeoutDuration) {
        // If session expired, log out user
        session_unset();
        session_destroy();
        header("Location: $logoutRedirectUrl?timeout=1");
        exit();
    }

    // Update last activity time
    $_SESSION['last_activity'] = time();
}

// Function to get remaining session time in seconds
function getRemainingSessionTime() {
    global $timeoutDuration;
    
    if (!isset($_SESSION['last_activity'])) {
        return $timeoutDuration;
    }
    
    $elapsed = time() - $_SESSION['last_activity'];
    return max(0, $timeoutDuration - $elapsed);
}
?>
