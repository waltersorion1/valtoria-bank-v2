<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/otp.php';

$type = $_GET['type'] ?? 'login';
if ($type !== 'login' || empty($_SESSION['temp_user_id'])) safeRedirect('login.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        requireCsrf();
        $userId = (int) $_SESSION['temp_user_id'];
        $stmt = $pdo->prepare('SELECT email, is_admin, role, status, is_active FROM users WHERE user_id=?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user || $user['status'] !== 'approved' || !(bool) $user['is_active']) throw new RuntimeException('This account is not available.');
        if (!verifyOTP($user['email'], $_POST['otp'] ?? '')) throw new RuntimeException('The verification code is invalid or expired.');
        regenerateSession();
        $_SESSION['user_id'] = $userId;
        $_SESSION['is_admin'] = (int) $user['is_admin'];
        $_SESSION['role'] = $user['role'] ?: ((int) $user['is_admin'] === 1 ? 'super_admin' : 'customer');
        $_SESSION['last_activity'] = time();
        $_SESSION['_fingerprint'] = hash('sha256', (string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));
        unset($_SESSION['temp_user_id'], $_SESSION['temp_is_admin']);
        safeRedirect((int) $user['is_admin'] === 1 ? 'admin/dashboard.php' : 'user/dashboard.php');
    } catch (Throwable $exception) { $error = $exception->getMessage(); }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Verification | Valtoria Bank</title><link rel="stylesheet" href="assets/css/valtoria.css"><link rel="stylesheet" href="assets/css/otp.css"></head><body><main class="container" style="max-width:520px;padding-top:70px"><section class="content-card"><span class="valtoria-wordmark">Valtoria Bank</span><h1>Verify your sign-in</h1><p class="muted">Enter the six-digit code sent to your email. It expires in five minutes.</p><?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif?><form method="post"><?=csrfField()?><div style="margin:20px 0"><label for="otp">Verification code</label><input id="otp" name="otp" inputmode="numeric" pattern="\d{6}" maxlength="6" autocomplete="one-time-code" required style="width:100%;padding:12px;margin-top:6px"></div><button class="button button-primary">Verify sign-in</button></form><p><a href="resend-otp.php?type=login">Send a new code</a></p></section></main></body></html>
