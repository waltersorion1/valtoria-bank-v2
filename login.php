<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/otp.php';

if (isLoggedIn()) {
    safeRedirect(isAdmin() ? 'admin/dashboard' : 'user/dashboard');
}

$error = '';
$email = '';
$errorCode = (string) ($_GET['error'] ?? '');
$messages = [
    'unauthorized_login' => 'This sign-in attempt was denied. If it was not you, change your password.',
    'verification_failed' => 'Verification failed. Please sign in again.',
    'verification_expired' => 'Your verification code expired. Please sign in again.',
    'session_invalid' => 'Your session ended because your account access changed. Please sign in again.',
];
if (isset($messages[$errorCode])) $error = $messages[$errorCode];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    try {
        foreach (['user_id','is_admin','role','session_version','temp_user_id','temp_is_admin'] as $key) unset($_SESSION[$key]);
        $stmt = $pdo->prepare('SELECT user_id,password_hash,is_admin,status,is_active,login_attempts,blocked_until FROM users WHERE LOWER(email)=?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        $ip = clientIp();
        $agent = mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'), 0, 255);

        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            if ($user) {
                $attempts = (int) $user['login_attempts'] + 1;
                $blockedUntil = null;
                if ($attempts >= 3) { $attempts = 0; $blockedUntil = gmdate('Y-m-d H:i:s', time() + 7200); }
                $pdo->prepare('UPDATE users SET login_attempts=?,blocked_until=? WHERE user_id=?')->execute([$attempts,$blockedUntil,$user['user_id']]);
                $pdo->prepare("INSERT INTO login_records(user_id,ip_address,user_agent,status) VALUES(?,?,?,'failed')")->execute([$user['user_id'],$ip,$agent]);
            }
            $error = 'Invalid email or password.';
        } elseif (!empty($user['blocked_until']) && strtotime((string) $user['blocked_until']) > time()) {
            $minutes = max(1, (int) ceil((strtotime((string) $user['blocked_until']) - time()) / 60));
            $error = "Too many failed attempts. Try again in {$minutes} minute(s).";
        } elseif ($user['status'] !== 'approved') {
            $error = 'Your account application is still awaiting approval.';
        } elseif (!(bool) $user['is_active']) {
            $error = 'This account is currently restricted. Contact support for assistance.';
        } else {
            $pdo->prepare('UPDATE users SET login_attempts=0,blocked_until=NULL WHERE user_id=?')->execute([$user['user_id']]);
            $pdo->prepare("INSERT INTO login_records(user_id,ip_address,user_agent,status) VALUES(?,?,?,'success')")->execute([$user['user_id'],$ip,$agent]);
            if (emailOtpAvailable($pdo)) {
                $_SESSION['temp_user_id'] = (int) $user['user_id'];
                $_SESSION['temp_is_admin'] = (int) $user['is_admin'];
                if (generateOTP($email)) safeRedirect('otp-verification.php?type=login');
                unset($_SESSION['temp_user_id'], $_SESSION['temp_is_admin']);
                $error = 'Verification email could not be delivered. Please try again later.';
            } else {
                establishAuthenticatedSession($pdo, (int) $user['user_id']);
                safeRedirect((int) $user['is_admin'] === 1 ? 'admin/dashboard.php' : 'user/dashboard.php');
            }
        }
    } catch (Throwable $exception) {
        error_log('Login processing failed.');
        $error = 'Sign-in is temporarily unavailable. Please try again.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="description" content="Securely sign in to your Valtoria Bank account.">
  <title>Sign in | Valtoria Bank</title>
  <link rel="icon" href="assets/images/brand/favicon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="assets/css/valtoria.css">
  <link rel="stylesheet" href="assets/css/login.css">
  <link rel="stylesheet" href="assets/css/brand-assets.css">
</head>
<body class="signin-page">
<a class="skip-link" href="#signin-form">Skip to sign in</a>
<main class="signin-shell">
  <aside class="signin-story" aria-label="Valtoria account access">
    <a class="valtoria-wordmark signin-brand" href="index.php">Valtoria Bank</a>
    <div class="signin-story-copy">
      <span class="eyebrow">Secure account access</span>
      <h1>Welcome back.</h1>
      <p>Review balances, manage cards, move money, and follow every account event from one clear workspace.</p>
      <div class="signin-assurance">
        <div><span aria-hidden="true">01</span><p><strong>Protected sessions</strong><small>Account and role changes invalidate older sessions.</small></p></div>
        <div><span aria-hidden="true">02</span><p><strong>Traceable activity</strong><small>Financial and security events remain available for review.</small></p></div>
      </div>
    </div>
    <p class="signin-story-note">Valtoria will never ask for your password or one-time code through support.</p>
  </aside>

  <section class="signin-stage">
    <div class="signin-card" id="signin-form">
      <div class="signin-mobile-brand"><a class="valtoria-wordmark" href="index.php">Valtoria Bank</a></div>
      <header class="signin-heading"><span class="signin-kicker">Customer and operations access</span><h2>Sign in to your account</h2><p>Enter the email and password associated with your profile.</p></header>
      <?php if ($error): ?><div class="signin-alert" role="alert"><span aria-hidden="true">!</span><p><?= e($error) ?></p></div><?php endif; ?>
      <form method="post" class="signin-form">
        <?= csrfField() ?>
        <div class="signin-field"><label for="email">Email address</label><input id="email" type="email" name="email" value="<?= e($email) ?>" maxlength="100" autocomplete="email" inputmode="email" required autofocus></div>
        <div class="signin-field"><div class="field-label-row"><label for="password">Password</label><a href="forgot-password.php">Forgot password?</a></div><div class="password-control"><input id="password" type="password" name="password" autocomplete="current-password" required><button type="button" data-password-toggle aria-controls="password" aria-pressed="false">Show</button></div></div>
        <button type="submit" class="button button-primary signin-submit">Sign in securely</button>
      </form>
      <div class="signin-divider"><span>New to Valtoria?</span></div>
      <a class="button button-secondary create-account" href="register.php">Open an account</a>
      <p class="signin-help">Having trouble accessing your account? <a href="contact.php">Contact support</a></p>
    </div>
  </section>
</main>
<script src="assets/js/login.js"></script>
</body>
</html>
