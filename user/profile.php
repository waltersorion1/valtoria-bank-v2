<?php
declare(strict_types=1);

$appPage = 'profile';
$appTitle = 'Profile & security';
require __DIR__ . '/../includes/customer_header.php';

$notice = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        requireCsrf();
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'profile') {
            $name = trim((string) ($_POST['full_name'] ?? ''));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $phone = trim((string) ($_POST['phone'] ?? ''));
            $address = trim((string) ($_POST['address'] ?? ''));
            $occupation = trim((string) ($_POST['occupation'] ?? ''));
            if (mb_strlen($name) < 2 || mb_strlen($name) > 100) throw new InvalidArgumentException('Enter your full legal name.');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) throw new InvalidArgumentException('Enter a valid email address.');
            if (!preg_match('/^\+[1-9]\d{7,14}$/', $phone)) throw new InvalidArgumentException('Enter a phone number with its international country code.');
            if (mb_strlen($address) < 5 || mb_strlen($address) > 1000) throw new InvalidArgumentException('Enter your current residential address.');
            if (mb_strlen($occupation) > 50) throw new InvalidArgumentException('Occupation must be 50 characters or fewer.');
            $duplicate = $pdo->prepare('SELECT 1 FROM users WHERE LOWER(email)=? AND user_id<>?');
            $duplicate->execute([$email, $customerId]);
            if ($duplicate->fetchColumn()) throw new InvalidArgumentException('That email address is already registered.');
            $pdo->prepare('UPDATE users SET full_name=?,email=?,phone=?,address=?,occupation=? WHERE user_id=?')
                ->execute([$name, $email, $phone, $address, $occupation, $customerId]);
            audit($pdo, 'profile.updated', 'user', $customerId);
            $notice = 'Profile information updated.';
        } elseif ($action === 'identity') {
            $dateText = trim((string) ($_POST['date_of_birth'] ?? ''));
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $dateText, new DateTimeZone('UTC'));
            $dateErrors = DateTimeImmutable::getLastErrors();
            if (!$date || ($dateErrors !== false && ($dateErrors['warning_count'] || $dateErrors['error_count'])) || $date->format('Y-m-d') !== $dateText) {
                throw new InvalidArgumentException('Enter a valid date of birth.');
            }
            $today = new DateTimeImmutable('today', new DateTimeZone('UTC'));
            if ($date > $today || $date->diff($today)->y < 18 || $date->diff($today)->y > 120) throw new InvalidArgumentException('Customers must be at least 18 years old.');
            $idType = (string) ($_POST['id_type'] ?? '');
            $allowedIdTypes = ['passport','drivers_license','national_id','residence_permit','other'];
            if (!in_array($idType, $allowedIdTypes, true)) throw new InvalidArgumentException('Choose a supported identity document type.');
            if (!isset($_FILES['id_file']) || $_FILES['id_file']['error'] !== UPLOAD_ERR_OK) throw new InvalidArgumentException('Choose an identity document to upload.');
            $file = $_FILES['id_file'];
            if ((int) $file['size'] <= 0 || (int) $file['size'] > 5 * 1024 * 1024) throw new InvalidArgumentException('Identity documents must be 5 MB or smaller.');
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            $extensions = ['image/jpeg'=>'jpg','image/png'=>'png','application/pdf'=>'pdf'];
            if (!isset($extensions[$mime])) throw new InvalidArgumentException('Upload a JPEG, PNG, or PDF document.');
            $storage = VALTORIA_ROOT . '/storage/uploads/identity';
            if (!is_dir($storage) && !mkdir($storage, 0750, true)) throw new RuntimeException('Secure document storage is unavailable.');
            $filename = bin2hex(random_bytes(24)) . '.' . $extensions[$mime];
            $destination = $storage . '/' . $filename;
            if (!move_uploaded_file($file['tmp_name'], $destination)) throw new RuntimeException('The identity document could not be stored.');

            $pdo->beginTransaction();
            try {
                $existing = $pdo->prepare('SELECT id_file_path FROM id_verifications WHERE user_id=? FOR UPDATE');
                $existing->execute([$customerId]);
                $oldPath = $existing->fetchColumn();
                $relative = 'storage/uploads/identity/' . $filename;
                $pdo->prepare("INSERT INTO id_verifications(user_id,id_type,id_file_path,verification_status,verified_at) VALUES(?,?,?,'pending',NULL) ON DUPLICATE KEY UPDATE id_type=VALUES(id_type),id_file_path=VALUES(id_file_path),verification_status='pending',verified_at=NULL")
                    ->execute([$customerId, $idType, $relative]);
                $pdo->prepare("UPDATE users SET date_of_birth=?,age=?,birth_year=?,kyc_status='pending' WHERE user_id=?")
                    ->execute([$dateText, $date->diff($today)->y, (int) $date->format('Y'), $customerId]);
                audit($pdo, 'kyc.submitted', 'user', $customerId, ['id_type'=>$idType]);
                $pdo->commit();
                if (is_string($oldPath) && str_starts_with($oldPath, 'storage/uploads/identity/')) {
                    $old = realpath(VALTORIA_ROOT . '/' . $oldPath);
                    $root = realpath($storage);
                    if ($old && $root && str_starts_with($old, $root . DIRECTORY_SEPARATOR) && $old !== realpath($destination)) unlink($old);
                }
                $notice = 'Identity information submitted for review.';
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                if (is_file($destination)) unlink($destination);
                throw $exception;
            }
        } elseif ($action === 'password') {
            $current = (string) ($_POST['current_password'] ?? '');
            $password = (string) ($_POST['new_password'] ?? '');
            $confirm = (string) ($_POST['confirm_password'] ?? '');
            $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE user_id=?');
            $stmt->execute([$customerId]);
            if (!password_verify($current, (string) $stmt->fetchColumn())) throw new RuntimeException('Current password is incorrect.');
            if ($password !== $confirm || !validatePassword($password)) throw new InvalidArgumentException('Use at least eight characters with uppercase, lowercase, a number, and a symbol.');
            $pdo->prepare('UPDATE users SET password_hash=?,session_version=session_version+1 WHERE user_id=?')->execute([password_hash($password, PASSWORD_DEFAULT), $customerId]);
            $pdo->prepare("INSERT INTO security_events(user_id,event_type,ip_address,user_agent) VALUES(?,'password_changed',?,?)")->execute([$customerId, clientIp(), mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255)]);
            regenerateSession();
            $_SESSION['session_version'] = (int) ($_SESSION['session_version'] ?? 1) + 1;
            $notice = 'Password changed and other sessions invalidated.';
        } elseif ($action === 'avatar') {
            if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Choose an image to upload.');
            $file = $_FILES['avatar'];
            if ((int) $file['size'] > 2 * 1024 * 1024) throw new InvalidArgumentException('Image must be 2 MB or smaller.');
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            $extensions = ['image/jpeg'=>'jpg','image/png'=>'png'];
            if (!isset($extensions[$mime])) throw new InvalidArgumentException('Only JPEG and PNG images are accepted.');
            $directory = VALTORIA_ROOT . '/uploads/avatars';
            if (!is_dir($directory) && !mkdir($directory, 0750, true)) throw new RuntimeException('Upload storage is unavailable.');
            $name = bin2hex(random_bytes(20)) . '.' . $extensions[$mime];
            if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $name)) throw new RuntimeException('Upload could not be stored.');
            $stmt = $pdo->prepare('SELECT profile_picture FROM users WHERE user_id=?');
            $stmt->execute([$customerId]);
            $old = $stmt->fetchColumn();
            $pdo->prepare('UPDATE users SET profile_picture=? WHERE user_id=?')->execute(['avatars/' . $name, $customerId]);
            if (is_string($old) && str_starts_with($old, 'avatars/')) {
                $oldPath = realpath(VALTORIA_ROOT . '/uploads/' . $old);
                $root = realpath($directory);
                if ($oldPath && $root && str_starts_with($oldPath, $root . DIRECTORY_SEPARATOR)) unlink($oldPath);
            }
            audit($pdo, 'profile.avatar_updated', 'user', $customerId);
            $notice = 'Profile image updated.';
        } else {
            throw new InvalidArgumentException('Unsupported profile action.');
        }
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = $exception->getMessage();
    }
}

$stmt = $pdo->prepare('SELECT u.*,a.account_number,iv.id_type,iv.verification_status FROM users u LEFT JOIN accounts a ON a.user_id=u.user_id LEFT JOIN id_verifications iv ON iv.user_id=u.user_id WHERE u.user_id=?');
$stmt->execute([$customerId]);
$user = $stmt->fetch();
$logins = $pdo->prepare('SELECT ip_address,user_agent,status,login_time FROM login_records WHERE user_id=? ORDER BY login_time DESC LIMIT 8');
$logins->execute([$customerId]);
?>
<?php if ($notice): ?><div class="notice success" role="status"><?= e($notice) ?></div><?php endif; ?>
<?php if ($error): ?><div class="notice error" role="alert"><?= e($error) ?></div><?php endif; ?>
<div class="app-grid">
  <section class="panel span-7"><h2>Personal details</h2><p class="muted">Keep your contact information accurate. Identity changes may require another review.</p>
    <form method="post" class="form-grid"><?= csrfField() ?><input type="hidden" name="action" value="profile">
      <div class="field"><label for="full_name">Full legal name</label><input id="full_name" name="full_name" value="<?= e($user['full_name']) ?>" maxlength="100" autocomplete="name" required></div>
      <div class="field"><label for="email">Email</label><input id="email" type="email" name="email" value="<?= e($user['email']) ?>" maxlength="100" autocomplete="email" required></div>
      <div class="field"><label for="phone">Phone</label><input id="phone" name="phone" value="<?= e($user['phone']) ?>" maxlength="16" autocomplete="tel" required></div>
      <div class="field"><label for="occupation">Occupation</label><input id="occupation" name="occupation" value="<?= e($user['occupation']) ?>" maxlength="50" autocomplete="organization-title"></div>
      <div class="field full"><label for="address">Residential address</label><textarea id="address" name="address" maxlength="1000" autocomplete="street-address" required><?= e($user['address']) ?></textarea></div>
      <button class="button button-primary">Save details</button>
    </form>
  </section>
  <section class="panel span-5"><h2>Account identity</h2><div class="detail-grid"><div><span>Account number</span><strong><?= e($user['account_number'] ?? '—') ?></strong></div><div><span>KYC status</span><strong><?= e(str_replace('_', ' ', $user['kyc_status'])) ?></strong></div><div><span>Date of birth</span><strong><?= $user['date_of_birth'] ? e(date('M j, Y', strtotime($user['date_of_birth']))) : 'Not provided' ?></strong></div><div><span>Document</span><strong><?= e($user['id_type'] ? str_replace('_', ' ', $user['id_type']) : 'Not provided') ?></strong></div></div>
    <h3>Profile image</h3><form method="post" enctype="multipart/form-data" class="form-grid"><?= csrfField() ?><input type="hidden" name="action" value="avatar"><div class="field full"><label for="avatar">JPEG or PNG, up to 2 MB</label><input id="avatar" type="file" name="avatar" accept="image/jpeg,image/png" required></div><button class="button button-secondary">Upload image</button></form>
  </section>
  <section class="panel span-7"><h2>Identity verification</h2><p class="muted">Submit these details from your protected account. Direct web access to uploaded documents is denied; authorized reviewers use controlled delivery.</p>
    <form method="post" enctype="multipart/form-data" class="form-grid"><?= csrfField() ?><input type="hidden" name="action" value="identity">
      <div class="field"><label for="date_of_birth">Date of birth</label><input id="date_of_birth" type="date" name="date_of_birth" value="<?= e($user['date_of_birth'] ?? '') ?>" max="<?= e(gmdate('Y-m-d', strtotime('-18 years'))) ?>" required></div>
      <div class="field"><label for="id_type">Identity document</label><select id="id_type" name="id_type" required><option value="">Choose a document</option><?php foreach (['passport'=>'Passport','drivers_license'=>'Driver’s license','national_id'=>'National ID','residence_permit'=>'Residence permit','other'=>'Other government ID'] as $value=>$label): ?><option value="<?= e($value) ?>" <?= ($user['id_type'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
      <div class="field full"><label for="id_file">Document file</label><input id="id_file" type="file" name="id_file" accept="image/jpeg,image/png,application/pdf" required><small>JPEG, PNG, or PDF; maximum 5 MB. Do not upload card details.</small></div>
      <button class="button button-primary">Submit for review</button>
    </form>
  </section>
  <section class="panel span-5"><h2>Change password</h2><form method="post" class="form-grid"><?= csrfField() ?><input type="hidden" name="action" value="password"><div class="field full"><label for="current_password">Current password</label><input id="current_password" type="password" name="current_password" autocomplete="current-password" required></div><div class="field full"><label for="new_password">New password</label><input id="new_password" type="password" name="new_password" autocomplete="new-password" required></div><div class="field full"><label for="confirm_password">Confirm new password</label><input id="confirm_password" type="password" name="confirm_password" autocomplete="new-password" required></div><button class="button button-primary">Change password</button></form></section>
  <section class="panel span-12"><h2>Recent sign-ins</h2><div class="table-wrap"><table class="data-table"><thead><tr><th>Time</th><th>IP address</th><th>Result</th><th>Device</th></tr></thead><tbody><?php foreach ($logins as $login): ?><tr><td><?= e(formatDate($login['login_time'])) ?></td><td><?= e($login['ip_address']) ?></td><td><span class="badge <?= e($login['status']) ?>"><?= e($login['status']) ?></span></td><td><?= e(mb_strimwidth($login['user_agent'], 0, 70, '…')) ?></td></tr><?php endforeach; ?><?php if (!$logins): ?><tr><td colspan="4"><div class="empty">No sign-in history is available yet.</div></td></tr><?php endif; ?></tbody></table></div></section>
</div>
<?php require __DIR__ . '/../includes/customer_footer.php'; ?>
