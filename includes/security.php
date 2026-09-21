<?php

declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function csrfToken(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrfToken(?string $token = null): bool
{
    $token ??= $_POST['_csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    return is_string($token) && hash_equals(csrfToken(), $token);
}

function requireCsrf(): void
{
    if (!verifyCsrfToken()) {
        http_response_code(419);
        exit('Your session token has expired. Please go back, refresh the page, and try again.');
    }
}

function regenerateSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
        $_SESSION['_regenerated_at'] = time();
    }
}

function safeRedirect(string $path): never
{
    if (preg_match('#^(?:https?:)?//#i', $path) || str_contains($path, "\r") || str_contains($path, "\n")) {
        $path = '/';
    }
    header('Location: ' . $path, true, 303);
    exit;
}

function clientIp(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
}
