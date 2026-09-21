<?php

declare(strict_types=1);

return [
    'name' => 'Valtoria Bank',
    'env' => env('APP_ENV', 'production'),
    'debug' => filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
    'url' => rtrim(env('APP_URL', 'http://localhost/valtoria-bank'), '/'),
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    'session_name' => env('SESSION_NAME', 'valtoria_session'),
    'session_idle_timeout' => (int) env('SESSION_IDLE_TIMEOUT', '900'),
];
