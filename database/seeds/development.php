<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap_financial.php';

if (!in_array('--force', $argv ?? [], true)) {
    fwrite(STDERR, "This replaces all data in the configured database. Re-run with --force.\n");
    exit(1);
}

$databaseName = (string) config('database.database');
if ($databaseName === '' || in_array(strtolower($databaseName), ['mysql','information_schema','performance_schema','phpmyadmin'], true)) {
    throw new RuntimeException('Refusing to seed an unsafe database target.');
}

echo "Resetting {$databaseName} with synthetic development fixtures...\n";
$tables = $pdo->query('SELECT table_name FROM information_schema.tables WHERE table_schema=DATABASE()')->fetchAll(PDO::FETCH_COLUMN);
$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
foreach ($tables as $table) {
    if (!preg_match('/^[A-Za-z0-9_]+$/', (string) $table)) throw new RuntimeException('Unexpected table name.');
    $pdo->exec('TRUNCATE TABLE `' . $table . '`');
}
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');

$password = 'ValtoriaDemo!2026';
$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$insertUser = $pdo->prepare("INSERT INTO users(full_name,age,birth_year,email,address,occupation,phone,password_hash,created_at,is_admin,status,is_active,role,kyc_status) VALUES(?,?,?,?,?,?,?,?,DATE_SUB(UTC_TIMESTAMP(),INTERVAL ? DAY),?,'approved',1,?,?)");

$userIds = [];
$users = [
    ['Valtoria Administrator','admin@example.test','+12025550101','Platform administrator',120,1,'super_admin','verified'],
    ['Operations Analyst','operations@example.test','+12025550102','Operations',90,1,'operations','verified'],
    ['KYC Reviewer','kyc@example.test','+12025550103','Identity operations',90,1,'kyc_reviewer','verified'],
    ['Credit Officer','credit@example.test','+12025550104','Credit operations',90,1,'credit_officer','verified'],
    ['Support Agent','support@example.test','+12025550105','Customer support',90,1,'support_agent','verified'],
    ['Read Only Auditor','auditor@example.test','+12025550106','Audit',90,1,'read_only_auditor','verified'],
    ['Amina Carter','amina@example.test','+12025550120','Product designer',120,0,'customer','verified'],
    ['Jon Bell','jon@example.test','+12025550121','Consultant',75,0,'customer','verified'],
    ['Pending Customer','pending@example.test','+12025550122','Student',10,0,'customer','pending'],
];
foreach ($users as [$name,$email,$phone,$occupation,$ageDays,$isAdmin,$role,$kyc]) {
    $insertUser->execute([$name,30,1996,$email,'Synthetic development address',$occupation,$phone,$passwordHash,$ageDays,$isAdmin,$role,$kyc]);
    $userIds[$email] = (int) $pdo->lastInsertId();
}

$insertAccount = $pdo->prepare('INSERT INTO accounts(user_id,account_number,balance_cents) VALUES(?,?,0)');
foreach ($userIds as $email => $userId) {
    if (str_ends_with($email, '@example.test') && in_array($email, ['amina@example.test','jon@example.test','pending@example.test'], true)) {
        $number = match ($email) {
            'amina@example.test' => 'VT10000001',
            'jon@example.test' => 'VT10000002',
            default => 'VT10000003',
        };
        $insertAccount->execute([$userId, $number]);
    }
}

$pdo->prepare("INSERT INTO id_verifications(user_id,id_type,id_file_path,verification_status,verified_at) VALUES(?, 'passport', 'seed://synthetic-verification', 'verified', UTC_TIMESTAMP())")
    ->execute([$userIds['amina@example.test']]);
$pdo->prepare("INSERT INTO id_verifications(user_id,id_type,id_file_path,verification_status) VALUES(?, 'national_id', 'seed://document-not-uploaded', 'pending')")
    ->execute([$userIds['pending@example.test']]);

$settings = [
    'features.card_funding' => true,
    'features.transfers' => true,
    'features.credit_applications' => true,
    'features.support_cases' => true,
    'features.require_account_approval' => false,
    'features.email_otp' => false,
    'system.maintenance_mode' => false,
];
$settingStmt = $pdo->prepare('INSERT INTO product_settings(setting_key,value_json,updated_by) VALUES(?,?,?)');
foreach ($settings as $key => $enabled) $settingStmt->execute([$key, json_encode($enabled), $userIds['admin@example.test']]);

$amina = $userIds['amina@example.test'];
$jon = $userIds['jon@example.test'];
$cards = new CardService($pdo);
$visaId = $cards->submitForReview($amina, 'visa', '4242', 'Amina Carter', 12, (int) gmdate('Y') + 3, 'DEV-VISA-4242');
$mastercardId = $cards->submitForReview($jon, 'mastercard', '4444', 'Jon Bell', 11, (int) gmdate('Y') + 4, 'DEV-MC-4444');
$pdo->prepare("UPDATE linked_cards SET verification_status='verified',verified_at=UTC_TIMESTAMP() WHERE card_id IN (?,?)")->execute([$visaId,$mastercardId]);

$provider = new ManualCardFundingProvider();
$funding = new FundingService($pdo, $provider);
$operations = new FinancialService($pdo);
foreach ([$funding->fund($amina, $visaId, 50000, 'seed-amina-funding-1'),$funding->fund($amina, $visaId, 25000, 'seed-amina-funding-2')] as $request) {
    $operations->review($request['reference'],'processing',$userIds['admin@example.test'],'Development fixture review');
    $operations->review($request['reference'],'completed',$userIds['admin@example.test'],'Development fixture completion');
}

$pdo->prepare("INSERT INTO beneficiaries(user_id,display_name,destination_account_number) VALUES(?, 'Jon Bell', 'VT10000002')")->execute([$amina]);
$beneficiaryId = (int) $pdo->lastInsertId();
$seedTransfer=(new TransferService($pdo))->transfer($amina, $beneficiaryId, 7500, 'Development seed transfer', 'seed-amina-transfer-1');
$operations->review($seedTransfer['reference'],'processing',$userIds['admin@example.test'],'Development fixture review');
$operations->review($seedTransfer['reference'],'completed',$userIds['admin@example.test'],'Development fixture completion');

$credit = new CreditService($pdo);
$applicationId = $credit->apply($amina, 30000, 6, 'Synthetic development purchase');
$credit->decide($applicationId, $userIds['credit@example.test'], 'approved', 30000, 'Approved synthetic development fixture.');

$pdo->beginTransaction();
try {
    $reference = Reference::generate('SUP');
    $pdo->prepare("INSERT INTO support_cases(reference,user_id,category,subject,priority,status,assigned_to) VALUES(?,?,'card','Question about a partner card','normal','in_progress',?)")
        ->execute([$reference, $amina, $userIds['support@example.test']]);
    $caseId = (int) $pdo->lastInsertId();
    $message = $pdo->prepare('INSERT INTO support_messages(case_id,author_user_id,body,is_internal) VALUES(?,?,?,?)');
    $message->execute([$caseId, $amina, 'This is a synthetic customer support request.', 0]);
    $message->execute([$caseId, $userIds['support@example.test'], 'Synthetic internal note for authorization testing.', 1]);
    $message->execute([$caseId, $userIds['support@example.test'], 'We are reviewing your partner card question.', 0]);
    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    throw $exception;
}

echo "Seed complete.\n";
echo "Shared development password: {$password}\n";
foreach (array_keys($userIds) as $email) echo " - {$email}\n";
