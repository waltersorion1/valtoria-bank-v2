<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function sanitizeInput($data) {
    return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
}


function validatePassword($password) {
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password);
}

// Add other essential functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && (int) $_SESSION['is_admin'] === 1;
}

function currentRole(): string {
    if (!isLoggedIn()) return 'guest';
    return $_SESSION['role'] ?? (isAdmin() ? 'super_admin' : 'customer');
}

function hasRole(string ...$roles): bool {
    return in_array(currentRole(), $roles, true);
}

function can(string $permission): bool {
    $role = currentRole();
    $permissions = [
        'super_admin' => ['*'],
        'operations' => ['dashboard.view','customers.view','customers.manage','cards.view','cards.manage','transactions.view','transactions.reverse','reconciliation.view','support.view','support.manage'],
        'kyc_reviewer' => ['dashboard.view','customers.view','kyc.manage'],
        'credit_officer' => ['dashboard.view','customers.view','credit.view','credit.manage'],
        'support_agent' => ['dashboard.view','customers.view','support.view','support.manage'],
        'read_only_auditor' => ['dashboard.view','customers.view','cards.view','transactions.view','credit.view','support.view','audit.view','reconciliation.view'],
    ];
    return in_array('*', $permissions[$role] ?? [], true)
        || in_array($permission, $permissions[$role] ?? [], true);
}

function requirePermission(string $permission): void {
    redirectIfNotLoggedIn();
    if (!isAdmin() || !can($permission)) {
        http_response_code(403);
        exit('You do not have permission to perform this operation.');
    }
}

function enforceSessionVersion(PDO $pdo): void {
    if (!isLoggedIn()) return;
    $stmt = $pdo->prepare('SELECT is_active,is_admin,role,session_version FROM users WHERE user_id=?');
    $stmt->execute([(int)$_SESSION['user_id']]);
    $user = $stmt->fetch();
    $known = isset($_SESSION['session_version']) ? (int)$_SESSION['session_version'] : null;
    if (!$user || !(int)$user['is_active'] || ($known !== null && $known !== (int)$user['session_version'])) {
        $_SESSION=[];
        if (session_status()===PHP_SESSION_ACTIVE) session_destroy();
        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $loginPath = str_contains($script, '/admin/') || str_contains($script, '/user/')
            ? '../login.php?error=session_invalid'
            : 'login.php?error=session_invalid';
        safeRedirect($loginPath);
    }
    $_SESSION['session_version']=(int)$user['session_version'];
    $_SESSION['is_admin']=(int)$user['is_admin'];
    $_SESSION['role']=(string)$user['role'];
}

function featureEnabled(PDO $pdo, string $key, bool $default = false): bool {
    $stmt = $pdo->prepare('SELECT value_json FROM product_settings WHERE setting_key=?');
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    if ($value === false) return $default;
    return json_decode((string) $value, true) === true;
}

function requireFeature(PDO $pdo, string $key, string $message): void {
    if (!featureEnabled($pdo, $key, false)) throw new RuntimeException($message);
}

function emailOtpAvailable(PDO $pdo): bool {
    $mail = config('mail');
    return featureEnabled($pdo, 'features.email_otp', false)
        && (bool) ($mail['enabled'] ?? false)
        && !empty($mail['host'])
        && !empty($mail['username'])
        && !empty($mail['password'])
        && !empty($mail['from_address']);
}

function establishAuthenticatedSession(PDO $pdo, int $userId): void {
    $stmt = $pdo->prepare('SELECT user_id,is_admin,role,status,is_active,session_version FROM users WHERE user_id=?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user || $user['status'] !== 'approved' || !(bool) $user['is_active']) throw new RuntimeException('This account is not available.');
    regenerateSession();
    $_SESSION['user_id'] = (int) $user['user_id'];
    $_SESSION['is_admin'] = (int) $user['is_admin'];
    $_SESSION['role'] = $user['role'] ?: ((int) $user['is_admin'] === 1 ? 'super_admin' : 'customer');
    $_SESSION['session_version'] = (int) $user['session_version'];
    $_SESSION['last_activity'] = time();
    $_SESSION['_fingerprint'] = hash('sha256', (string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));
    unset($_SESSION['temp_user_id'], $_SESSION['temp_is_admin']);
}

function audit(PDO $pdo, string $action, ?string $targetType = null, string|int|null $targetId = null, array $metadata = []): void {
    $stmt = $pdo->prepare('INSERT INTO audit_logs(actor_user_id,action,target_type,target_id,metadata_json) VALUES(?,?,?,?,?)');
    $stmt->execute([$_SESSION['user_id'] ?? null, $action, $targetType, $targetId === null ? null : (string)$targetId, $metadata ? json_encode($metadata, JSON_THROW_ON_ERROR) : null]);
}

// Add all other original functions here
function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

function redirectIfNotAdmin() {
    redirectIfNotLoggedIn();
    if (!isAdmin()) {
        header("Location: ../user/dashboard.php");
        exit();
    }
}

function requireRole(string ...$roles): void {
    redirectIfNotLoggedIn();
    if (!hasRole(...$roles)) {
        http_response_code(403);
        exit('You do not have permission to access this area.');
    }
}

function generateAccountNumber() {
    return 'VT' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
}

function generateUniqueAccountNumber($pdo) {
    do {
        $number = generateAccountNumber();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM accounts WHERE account_number = ?");
        $stmt->execute([$number]);
        $exists = $stmt->fetchColumn() > 0;
    } while ($exists);
    return $number;
}


function formatCurrency($amount) {
    return '$' . number_format((float) $amount, 2, '.', ',');
}

function formatDate($dateString) {
    return date('M j, Y H:i', strtotime($dateString));
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function getRecentLoginRecords($pdo, $limit = 10) {
    $stmt = $pdo->prepare("
        SELECT 
            lr.login_time,
            lr.status,
            u.full_name,
            u.email
        FROM login_records lr
        JOIN users u ON u.user_id = lr.user_id
        ORDER BY lr.login_time DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
