<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap_financial.php';

function assertTrue(bool $condition, string $message): void { if (!$condition) throw new RuntimeException('FAIL: ' . $message); echo "PASS: {$message}\n"; }

assertTrue(Money::fromDecimal('125.67') === 12567, 'decimal USD converts to cents');
assertTrue(Money::format(12567) === '$125.67', 'cents format as USD');
try { Money::fromDecimal('1.001'); assertTrue(false, 'reject excess decimals'); } catch (InvalidArgumentException) { echo "PASS: reject excess decimals\n"; }

$users = $pdo->query("SELECT u.user_id,a.account_id,a.account_number FROM users u JOIN accounts a ON a.user_id=u.user_id WHERE u.is_admin=0 ORDER BY u.user_id LIMIT 2")->fetchAll();
assertTrue(count($users) === 2, 'two fixture customer accounts available');
[$sender,$recipient] = $users;
$senderId=(int)$sender['user_id'];$recipientId=(int)$recipient['user_id'];
$pdo->prepare("UPDATE users SET kyc_status='verified',created_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 60 DAY) WHERE user_id=?")->execute([$senderId]);

$cards = new CardService($pdo);
$cardId = $cards->addSandboxCard($senderId, 'visa', '4242', 'TEST CUSTOMER', 12, (int)gmdate('Y')+2);
assertTrue($cardId > 0, 'token-only Visa metadata created');
$stored=$pdo->prepare('SELECT provider_payment_method_token,last_four FROM linked_cards WHERE card_id=?');$stored->execute([$cardId]);$stored=$stored->fetch();
assertTrue(str_starts_with($stored['provider_payment_method_token'],'sbox_pm_') && $stored['last_four']==='4242', 'no PAN or CVV stored');
try { $cards->ownedActiveCard($recipientId, $cardId); assertTrue(false, 'reject card ownership mismatch'); } catch (RuntimeException) { echo "PASS: reject card ownership mismatch\n"; }

$funding = new FundingService($pdo,new SandboxCardFundingProvider());
$f1=$funding->fund($senderId,$cardId,20000,'test-fund-1');
$f1Again=$funding->fund($senderId,$cardId,20000,'test-fund-1');
assertTrue((int)$f1['transaction_id']===(int)$f1Again['transaction_id'],'funding idempotency returns original transaction');
$funding->fund($senderId,$cardId,1000,'test-fund-2');
$funding->fund($senderId,$cardId,1000,'test-fund-3');

$pdo->prepare("INSERT INTO beneficiaries(user_id,display_name,destination_account_number) VALUES(?, 'TEST RECIPIENT', ?) ON DUPLICATE KEY UPDATE beneficiary_id=LAST_INSERT_ID(beneficiary_id),status='active'")->execute([$senderId,$recipient['account_number']]);
$beneficiaryId=(int)$pdo->lastInsertId();
if($beneficiaryId===0){$s=$pdo->prepare('SELECT beneficiary_id FROM beneficiaries WHERE user_id=? AND destination_account_number=?');$s->execute([$senderId,$recipient['account_number']]);$beneficiaryId=(int)$s->fetchColumn();}
$transfer = new TransferService($pdo);
$pdo->prepare("INSERT INTO users(full_name,age,birth_year,email,address,occupation,phone,password_hash,status,is_active,role,kyc_status) VALUES('ZERO BALANCE TEST',30,1996,?,'test','test','+10000000000',?,'approved',1,'customer','verified')")->execute(['zero-'.bin2hex(random_bytes(4)).'@example.test',password_hash('Test!Password1',PASSWORD_DEFAULT)]);
$zeroUserId=(int)$pdo->lastInsertId();$pdo->prepare("INSERT INTO accounts(user_id,account_number,balance_cents) VALUES(?, ?, 0)")->execute([$zeroUserId,'VT'.strtoupper(bin2hex(random_bytes(4)))]);$pdo->prepare("INSERT INTO beneficiaries(user_id,display_name,destination_account_number) VALUES(?, 'TEST RECIPIENT', ?)")->execute([$zeroUserId,$recipient['account_number']]);$zeroBeneficiary=(int)$pdo->lastInsertId();
try { $transfer->transfer($zeroUserId,$zeroBeneficiary,100,'Insufficient test','test-insufficient'); assertTrue(false,'reject insufficient funds'); } catch (RuntimeException $e) { assertTrue(str_contains($e->getMessage(),'Insufficient'),'reject insufficient funds'); }
$before=$pdo->prepare('SELECT account_id,balance_cents FROM accounts WHERE account_id IN (?,?) ORDER BY account_id');$before->execute([$sender['account_id'],$recipient['account_id']]);$beforeRows=$before->fetchAll(PDO::FETCH_KEY_PAIR);
$tr=$transfer->transfer($senderId,$beneficiaryId,5000,'Integration test','test-transfer-1');
$trAgain=$transfer->transfer($senderId,$beneficiaryId,5000,'Integration test','test-transfer-1');
assertTrue((int)$tr['transaction_id']===(int)$trAgain['transaction_id'],'transfer idempotency returns original transaction');
$after=$pdo->prepare('SELECT account_id,balance_cents FROM accounts WHERE account_id IN (?,?) ORDER BY account_id');$after->execute([$sender['account_id'],$recipient['account_id']]);$afterRows=$after->fetchAll(PDO::FETCH_KEY_PAIR);
assertTrue((int)$beforeRows[$sender['account_id']]-(int)$afterRows[$sender['account_id']]===5050,'sender debited amount plus server fee');
assertTrue((int)$afterRows[$recipient['account_id']]-(int)$beforeRows[$recipient['account_id']]===5000,'recipient credited exact amount');
$adminId=(int)$pdo->query('SELECT user_id FROM users WHERE is_admin=1 ORDER BY user_id LIMIT 1')->fetchColumn();
$reversal=(new FinancialService($pdo))->reverse($tr['reference'],$adminId,'Integration reversal test');
assertTrue($reversal['status']==='completed','transfer reversal posts an immutable correcting transaction');

$eligibility=(new CreditService($pdo))->eligibility($senderId);
assertTrue($eligibility['eligible']===true,'verified Visa customer meets deterministic eligibility');
$ineligible=(new CreditService($pdo))->eligibility($recipientId);
assertTrue($ineligible['eligible']===false,'credit eligibility rejects customer without verified Visa');
$credit=new CreditService($pdo);$applicationId=$credit->apply($senderId,10000,3,'Integration test');
$credit->decide($applicationId,$adminId,'approved',10000,'Automated integration test');
$disbursement=$credit->acceptAndDisburse($senderId,$applicationId,'test-credit-disburse');
assertTrue(!empty($disbursement['facility_id']),'approved credit disburses to a facility');
$repayment=$credit->repay($senderId,(int)$disbursement['facility_id'],1000,'test-credit-repay');
assertTrue($repayment['status']==='completed','credit repayment completes');

$ledgerSum=(int)$pdo->query('SELECT COALESCE(SUM(amount_cents),0) FROM ledger_entries')->fetchColumn();
assertTrue($ledgerSum===0,'global ledger entries reconcile to zero');
$mismatches=(int)$pdo->query('SELECT COUNT(*) FROM accounts a WHERE a.balance_cents<>COALESCE((SELECT SUM(le.amount_cents) FROM ledger_entries le WHERE le.account_id=a.account_id),0)')->fetchColumn();
assertTrue($mismatches===0,'account balances reconcile to customer ledger entries');
echo "FINANCIAL CORE TESTS COMPLETE\n";
