<?php

declare(strict_types=1);

if (!defined('VALTORIA_ROOT')) {
    define('VALTORIA_ROOT', dirname(__DIR__));
}

function loadEnvironment(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if ($key === '' || getenv($key) !== false) {
            continue;
        }
        $value = trim($value, "\"'");
        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
    }
}

function env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

function config(string $key, mixed $default = null): mixed
{
    static $configuration = [];
    [$file, $item] = array_pad(explode('.', $key, 2), 2, null);
    if (!isset($configuration[$file])) {
        $path = VALTORIA_ROOT . '/config/' . $file . '.php';
        $configuration[$file] = is_file($path) ? require $path : [];
    }
    return $item === null ? $configuration[$file] : ($configuration[$file][$item] ?? $default);
}

loadEnvironment(VALTORIA_ROOT . '/.env');
date_default_timezone_set((string) config('app.timezone', 'UTC'));

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

if (session_status() === PHP_SESSION_NONE && PHP_SAPI !== 'cli') {
    session_name((string) config('app.session_name', 'valtoria_session'));
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', config('app.debug', false) ? '1' : '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/security.php';

set_exception_handler(static function (Throwable $exception): void {
    error_log(sprintf('[%s] %s in %s:%d', get_class($exception), $exception->getMessage(), $exception->getFile(), $exception->getLine()));
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Application error. See the application log.\n");
        return;
    }
    http_response_code(500);
    echo config('app.debug', false)
        ? '<h1>Application error</h1><pre>' . e($exception->getMessage()) . '</pre>'
        : '<h1>We could not complete that request.</h1><p>Please try again later.</p>';
});
