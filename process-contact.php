<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/mailer.php';
require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') safeRedirect('contact.php');
requireCsrf();

$name = trim((string) ($_POST['name'] ?? ''));
$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$subject = trim((string) ($_POST['subject'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));
$errors = [];
if (mb_strlen($name) < 2 || mb_strlen($name) > 100) $errors[] = 'Enter a valid name.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) $errors[] = 'Enter a valid email address.';
if (mb_strlen($subject) < 3 || mb_strlen($subject) > 160) $errors[] = 'Enter a subject between 3 and 160 characters.';
if (mb_strlen($message) < 10 || mb_strlen($message) > 5000) $errors[] = 'Enter a message between 10 and 5,000 characters.';
if (isset($_SESSION['contact_last_submission']) && time() - (int) $_SESSION['contact_last_submission'] < 30) $errors[] = 'Please wait before sending another message.';

if ($errors) {
    $_SESSION['contact_error'] = implode(' ', $errors);
    safeRedirect('contact.php');
}

try {
    $pdo->prepare('INSERT INTO contact_messages (name,email,subject,message) VALUES (?,?,?,?)')->execute([$name, $email, $subject, $message]);
    $_SESSION['contact_last_submission'] = time();
    $escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $htmlBody = '<h2>New contact request</h2><p><strong>Name:</strong> ' . $escape($name)
        . '</p><p><strong>Email:</strong> ' . $escape($email)
        . '</p><p><strong>Subject:</strong> ' . $escape($subject)
        . '</p><p><strong>Message:</strong></p><p>' . nl2br($escape($message)) . '</p>';
    try {
        sendMailMessage((string) config('mail.support_email'), 'Contact form: ' . $subject, $htmlBody);
    } catch (Throwable $exception) {
        error_log('Contact notification delivery failed.');
    }
    $_SESSION['contact_success'] = 'Thank you. Your message has been received.';
} catch (Throwable $exception) {
    error_log('Contact request storage failed.');
    $_SESSION['contact_error'] = 'Your message could not be submitted. Please try again later.';
}

safeRedirect('contact.php');
