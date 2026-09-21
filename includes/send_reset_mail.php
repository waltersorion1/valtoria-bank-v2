<?php
declare(strict_types=1);
require_once __DIR__ . '/mailer.php';
function sendResetLink(string $recipientEmail, string $resetLink): bool
{
    return sendMailMessage($recipientEmail, 'Reset your Valtoria Bank password',
        '<p>We received a password reset request.</p><p><a href="' . e($resetLink) . '">Reset your password</a></p><p>This link expires in one hour.</p>',
        'Reset your password using this one-hour link: ' . $resetLink);
}
