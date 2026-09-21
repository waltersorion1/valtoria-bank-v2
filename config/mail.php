<?php

declare(strict_types=1);

return [
    'enabled' => filter_var(env('MAIL_ENABLED', 'false'), FILTER_VALIDATE_BOOL),
    'host' => env('MAIL_HOST', ''),
    'port' => (int) env('MAIL_PORT', '587'),
    'encryption' => env('MAIL_ENCRYPTION', 'tls'),
    'username' => env('MAIL_USERNAME', ''),
    'password' => env('MAIL_PASSWORD', ''),
    'from_address' => env('MAIL_FROM_ADDRESS', 'no-reply@example.com'),
    'from_name' => env('MAIL_FROM_NAME', 'Valtoria Bank'),
    'support_email' => env('SUPPORT_EMAIL', 'support@example.com'),
];
