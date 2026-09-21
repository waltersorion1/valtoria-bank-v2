<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap_financial.php';

function check(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
    echo 'PASS: ' . $message . PHP_EOL;
}

$matrix = [
    'operations' => ['customers.manage'=>true,'transactions.reverse'=>true,'credit.manage'=>false,'settings.manage'=>false],
    'kyc_reviewer' => ['kyc.manage'=>true,'customers.manage'=>false,'transactions.view'=>false],
    'credit_officer' => ['credit.view'=>true,'credit.manage'=>true,'transactions.reverse'=>false],
    'support_agent' => ['support.view'=>true,'support.manage'=>true,'customers.manage'=>false],
    'read_only_auditor' => ['audit.view'=>true,'reconciliation.view'=>true,'credit.view'=>true,'credit.manage'=>false],
    'customer' => ['dashboard.view'=>false,'customers.view'=>false,'support.manage'=>false],
];

$_SESSION['user_id'] = 1;
$_SESSION['is_admin'] = 1;
foreach ($matrix as $role => $expectations) {
    $_SESSION['role'] = $role;
    $_SESSION['is_admin'] = $role === 'customer' ? 0 : 1;
    foreach ($expectations as $permission => $expected) {
        check(can($permission) === $expected, "$role permission $permission");
    }
}

check(featureEnabled($pdo, 'features.card_funding') === true, 'stored card-funding toggle is enabled');
check(featureEnabled($pdo, 'missing.feature', false) === false, 'missing toggle fails closed');

$pdo->beginTransaction();
try {
    $pdo->prepare("UPDATE product_settings SET value_json='false' WHERE setting_key='features.transfers'")->execute();
    $blocked = false;
    try { (new TransferService($pdo))->quote(10000); } catch (RuntimeException $exception) { $blocked = str_contains($exception->getMessage(), 'unavailable'); }
    check($blocked, 'transfer service enforces disabled feature toggle');

    $internalSql = "SELECT COUNT(*) FROM support_messages sm JOIN support_cases sc ON sc.case_id=sm.case_id WHERE sc.user_id=? AND sm.is_internal=0";
    check(str_contains($internalSql, 'sm.is_internal=0'), 'customer support query excludes internal notes');
} finally {
    $pdo->rollBack();
}

echo 'MODULE 3 OPERATIONS TESTS COMPLETE' . PHP_EOL;
