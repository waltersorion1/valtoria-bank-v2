<?php
declare(strict_types=1);
require_once __DIR__ . '/mailer.php';
function sendNotification(string $recipientEmail, string $subject, string $messageHtml, string $messagePlain = ''): bool
{
    return sendMailMessage($recipientEmail, $subject, $messageHtml, $messagePlain);
}
