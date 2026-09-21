<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../../includes/db.php';

$options = getopt('', ['email:', 'password:', 'name::']);
$email = strtolower(trim((string) ($options['email'] ?? '')));
$password = (string) ($options['password'] ?? '');
$name = trim((string) ($options['name'] ?? 'Valtoria Super Administrator'));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Provide a valid --email value.\n");
    exit(1);
}

if (
    strlen($password) < 16
    || !preg_match('/[a-z]/', $password)
    || !preg_match('/[A-Z]/', $password)
    || !preg_match('/\d/', $password)
    || !preg_match('/[^A-Za-z0-9]/', $password)
) {
    fwrite(STDERR, "The password must be at least 16 characters and include upper, lower, numeric, and symbol characters.\n");
    exit(1);
}

if ($name === '' || mb_strlen($name) > 100) {
    fwrite(STDERR, "Provide a name no longer than 100 characters.\n");
    exit(1);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$existing = $pdo->prepare('SELECT user_id FROM users WHERE LOWER(email)=? LIMIT 1');
$existing->execute([$email]);
$userId = $existing->fetchColumn();

if ($userId !== false) {
    $update = $pdo->prepare("UPDATE users SET full_name=?,password_hash=?,is_admin=1,role='super_admin',status='approved',kyc_status='verified',is_active=1,login_attempts=0,blocked_until=NULL,reset_token=NULL,reset_expires_at=NULL,session_version=session_version+1 WHERE user_id=?");
    $update->execute([$name, $passwordHash, (int) $userId]);
    fwrite(STDOUT, "Super administrator updated successfully. Existing sessions were invalidated.\n");
    exit(0);
}

$insert = $pdo->prepare("INSERT INTO users(full_name,age,birth_year,email,address,occupation,phone,password_hash,is_admin,role,status,kyc_status,is_active) VALUES(?,18,?,?,'Administrative account','Platform administrator','',?,1,'super_admin','approved','verified',1)");
$insert->execute([$name, (int) date('Y') - 18, $email, $passwordHash]);
fwrite(STDOUT, "Super administrator created successfully.\n");
