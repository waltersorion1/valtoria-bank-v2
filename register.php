<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/otp.php';

if (isLoggedIn()) {
    safeRedirect(isAdmin() ? 'admin/dashboard' : 'user/dashboard');
}

$errors = [];
$success = '';
$data = ['full_name'=>'','email'=>'','phone'=>'','address'=>'','occupation'=>''];
$requiresApproval = featureEnabled($pdo, 'features.require_account_approval', false);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        requireCsrf();
        $data['full_name'] = trim((string) ($_POST['full_name'] ?? ''));
        $data['email'] = strtolower(trim((string) ($_POST['email'] ?? '')));
        $data['phone'] = trim((string) ($_POST['phone'] ?? ''));
        $data['address'] = trim((string) ($_POST['address'] ?? ''));
        $data['occupation'] = trim((string) ($_POST['occupation'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirmation = (string) ($_POST['confirm_password'] ?? '');

        if (mb_strlen($data['full_name']) < 2 || mb_strlen($data['full_name']) > 100) $errors[] = 'Enter your full legal name.';
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($data['email']) > 100) $errors[] = 'Enter a valid email address.';
        if (!preg_match('/^\+[1-9]\d{7,14}$/', $data['phone'])) $errors[] = 'Enter your phone number in international format, such as +12025550123.';
        if (mb_strlen($data['address']) < 5 || mb_strlen($data['address']) > 1000) $errors[] = 'Enter your current residential address.';
        if (mb_strlen($data['occupation']) > 50) $errors[] = 'Occupation must be 50 characters or fewer.';
        if (!validatePassword($password)) $errors[] = 'Use at least eight characters with uppercase, lowercase, a number, and a symbol.';
        if ($password !== $confirmation) $errors[] = 'Passwords do not match.';
        if (!isset($_POST['legal_consent'])) $errors[] = 'You must accept the Terms and Privacy Policy to continue.';

        if (!$errors) {
            $stmt = $pdo->prepare('SELECT 1 FROM users WHERE LOWER(email)=?');
            $stmt->execute([$data['email']]);
            if ($stmt->fetchColumn()) $errors[] = 'An account already exists for this email address.';
        }

        if (!$errors) {
            $pdo->beginTransaction();
            $status = $requiresApproval ? 'pending' : 'approved';
            $stmt = $pdo->prepare("INSERT INTO users(full_name,email,password_hash,address,occupation,phone,status,role,kyc_status) VALUES(?,?,?,?,?,?,?,'customer','not_started')");
            $stmt->execute([mb_substr($data['full_name'],0,100),$data['email'],password_hash($password,PASSWORD_DEFAULT),mb_substr($data['address'],0,1000),mb_substr($data['occupation'],0,50),$data['phone'],$status]);
            $userId = (int) $pdo->lastInsertId();
            $pdo->prepare('INSERT INTO accounts(user_id,account_number,balance_cents) VALUES(?,?,0)')->execute([$userId, generateUniqueAccountNumber($pdo)]);
            audit($pdo, 'customer.registered', 'user', $userId, ['approval_required'=>$requiresApproval]);
            $pdo->commit();

            if ($requiresApproval) {
                $success = 'Your application has been received. We will notify you after the initial account review.';
                $data = array_fill_keys(array_keys($data), '');
            } elseif (emailOtpAvailable($pdo)) {
                $_SESSION['temp_user_id'] = $userId;
                $_SESSION['temp_is_admin'] = 0;
                if (generateOTP($data['email'])) safeRedirect('otp-verification.php?type=login');
                unset($_SESSION['temp_user_id'], $_SESSION['temp_is_admin']);
                $errors[] = 'Your account was created, but verification email delivery failed. Please sign in and try again.';
            } else {
                establishAuthenticatedSession($pdo, $userId);
                safeRedirect('user/dashboard.php?welcome=1');
            }
        }
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Registration request failed.');
        $errors[] = 'We could not submit your application. Please try again.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="description" content="Apply for a Valtoria Bank account through a clear, secure onboarding process.">
  <title>Open an account | Valtoria Bank</title>
  <link rel="stylesheet" href="assets/css/valtoria.css">
  <link rel="stylesheet" href="assets/css/register.css">
</head>
<body class="onboarding-page">
<a class="skip-link" href="#registration">Skip to application</a>
<main class="onboarding-shell">
  <aside class="onboarding-story" aria-label="Application overview">
    <a class="valtoria-wordmark onboarding-brand" href="index.php">Valtoria Bank</a>
    <div class="story-content">
      <span class="eyebrow">Open your account</span>
      <h1>A clear start to managing your money.</h1>
      <p>Submit your core contact details now. You can securely complete identity information from your protected profile.</p>
      <ol class="onboarding-steps">
        <li><span>1</span><div><strong>Create your profile</strong><small>Contact information and secure credentials.</small></div></li>
        <li><span>2</span><div><strong><?= $requiresApproval ? 'Initial review' : 'Immediate access' ?></strong><small><?= $requiresApproval ? 'We review the application before account access.' : 'Your account opens after successful submission.' ?></small></div></li>
        <li><span>3</span><div><strong>Complete verification</strong><small>Add birth and identity details from your protected profile.</small></div></li>
      </ol>
    </div>
    <p class="story-footnote">Never send a password, one-time code, full card number, or CVV to support.</p>
  </aside>

  <section class="onboarding-form-panel" id="registration">
    <div class="form-progress" aria-label="Application progress"><span class="active" data-progress="1">1 <b>Your details</b></span><i></i><span data-progress="2">2 <b>Security</b></span></div>
    <div class="form-heading"><span class="form-kicker">Account application</span><h2 id="step-title">Tell us about yourself</h2><p id="step-description">Enter the contact details we need to create your profile.</p></div>

    <?php if ($errors): ?><div class="form-alert error" role="alert"><strong>Please review the following:</strong><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <?php if ($success): ?><div class="form-alert success" role="status"><strong>Application submitted</strong><p><?= e($success) ?></p><a href="login.php">Return to sign in</a></div><?php endif; ?>

    <?php if (!$success): ?><form method="post" class="registration-form">
      <?= csrfField() ?>
      <fieldset class="form-step active" data-step="1"><legend class="sr-only">Personal and contact details</legend>
        <div class="registration-grid">
          <div class="field full"><label for="full_name">Full legal name <span aria-hidden="true">*</span></label><input id="full_name" name="full_name" value="<?= e($data['full_name']) ?>" maxlength="100" autocomplete="name" required></div>
          <div class="field"><label for="email">Email address <span aria-hidden="true">*</span></label><input id="email" type="email" name="email" value="<?= e($data['email']) ?>" maxlength="100" autocomplete="email" required></div>
          <div class="field"><label for="phone">Mobile number <span aria-hidden="true">*</span></label><input id="phone" type="tel" name="phone" value="<?= e($data['phone']) ?>" maxlength="16" autocomplete="tel" placeholder="+12025550123" required><small>Include the international country code.</small></div>
          <div class="field"><label for="occupation">Occupation</label><input id="occupation" name="occupation" value="<?= e($data['occupation']) ?>" maxlength="50" autocomplete="organization-title"></div>
          <div class="field full"><label for="address">Residential address <span aria-hidden="true">*</span></label><textarea id="address" name="address" maxlength="1000" autocomplete="street-address" required><?= e($data['address']) ?></textarea></div>
        </div>
        <div class="step-actions"><span></span><button class="button button-primary" type="button" data-next>Continue</button></div>
      </fieldset>
      <fieldset class="form-step" data-step="2" hidden><legend class="sr-only">Secure your account</legend>
        <div class="registration-grid">
          <div class="field"><label for="password">Password <span aria-hidden="true">*</span></label><input id="password" type="password" name="password" autocomplete="new-password" aria-describedby="password-help" required></div>
          <div class="field"><label for="confirm_password">Confirm password <span aria-hidden="true">*</span></label><input id="confirm_password" type="password" name="confirm_password" autocomplete="new-password" required></div>
          <p class="password-help full" id="password-help">Use 8 or more characters with uppercase, lowercase, a number, and a symbol.</p>
        </div>
        <label class="consent-row full"><input type="checkbox" name="legal_consent" value="1" required><span>I agree to the <a href="terms.php" target="_blank">Terms of Service</a> and acknowledge the <a href="privacy-policy.php" target="_blank">Privacy Policy</a>.</span></label>
        <div class="step-actions"><button class="button button-secondary" type="button" data-back>Back</button><button class="button button-primary" type="submit"><?= $requiresApproval ? 'Submit application' : 'Create my account' ?></button></div>
      </fieldset>
      <p class="signin-prompt">Already have an account? <a href="login.php">Sign in</a></p>
    </form><?php endif; ?>
  </section>
</main>
<script src="assets/js/register.js"></script>
</body>
</html>
