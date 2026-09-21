<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendMailMessage(string $recipientEmail, string $subject, string $html, string $plain = ''): bool
{
    $cfg = config('mail');
    if (!$cfg['enabled']) {
        error_log('[mail] Delivery disabled; message suppressed.');
        return false;
    }
    foreach (['host', 'username', 'password', 'from_address'] as $field) {
        if (empty($cfg[$field])) { error_log('[mail] Configuration incomplete.'); return false; }
    }
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $cfg['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $cfg['username'];
        $mail->Password = $cfg['password'];
        $mail->Port = $cfg['port'];
        $mail->SMTPSecure = $cfg['encryption'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->setFrom($cfg['from_address'], $cfg['from_name']);
        $mail->addAddress($recipientEmail);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $html;
        $mail->AltBody = $plain !== '' ? $plain : strip_tags($html);
        $mail->send();
        return true;
    } catch (Exception $exception) {
        error_log('[mail] Delivery failed: ' . $mail->ErrorInfo);
        return false;
    }
}
function sendOTP(string $recipientEmail, string $otpCode): bool
{
    return sendMailMessage($recipientEmail, 'Your Valtoria Bank verification code',
        '<p>Your verification code is <strong>' . e($otpCode) . '</strong>.</p><p>It expires in five minutes. Never share this code.</p>',
        'Your Valtoria Bank verification code is ' . $otpCode . '. It expires in five minutes.');
}
