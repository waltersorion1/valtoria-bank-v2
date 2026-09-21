<?php
$appPage = 'transfer';
$appTitle = 'Card-to-card transfer';
require __DIR__ . '/../includes/customer_header.php';
$service = new TransferService($pdo);
$error = '';
$result = null;
$quote = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        requireCsrf();
        $action = $_POST['action'] ?? '';
        if ($action === 'quote') {
            $amount = Money::fromDecimal($_POST['amount'] ?? '');
            $quote = $service->quote($amount);
            $beneficiaryId = (int) ($_POST['beneficiary_id'] ?? 0);
            $owned = $pdo->prepare("SELECT 1 FROM beneficiaries WHERE beneficiary_id=? AND user_id=? AND status='active'");
            $owned->execute([$beneficiaryId, $customerId]);
            if (!$owned->fetchColumn()) throw new RuntimeException('Choose an available beneficiary.');
            $_SESSION['transfer_quote'] = $quote + ['beneficiary_id' => $beneficiaryId, 'memo' => mb_substr(trim($_POST['memo'] ?? ''), 0, 140), 'idempotency_key' => bin2hex(random_bytes(24))];
        } elseif ($action === 'confirm') {
            if (empty($_SESSION['transfer_quote'])) throw new RuntimeException('Transfer confirmation expired.');
            $pending = $_SESSION['transfer_quote'];
            $result = $service->transfer($customerId, $pending['beneficiary_id'], $pending['amount_cents'], $pending['memo'], $pending['idempotency_key']);
            unset($_SESSION['transfer_quote']);
        } elseif ($action === 'cancel') unset($_SESSION['transfer_quote']);
    } catch (Throwable $exception) { $error = $exception->getMessage(); }
}
if (!$quote && !empty($_SESSION['transfer_quote'])) $quote = $_SESSION['transfer_quote'];
$account = (new FinancialService($pdo))->accountForUser($customerId);
$beneficiaryStmt = $pdo->prepare("SELECT * FROM beneficiaries WHERE user_id=? AND status='active' ORDER BY display_name");
$beneficiaryStmt->execute([$customerId]);
$beneficiaries = $beneficiaryStmt->fetchAll();
$history = $pdo->prepare('SELECT tr.*,t.reference,b.display_name FROM transfers tr JOIN financial_transactions t ON t.transaction_id=tr.financial_transaction_id JOIN beneficiaries b ON b.beneficiary_id=tr.beneficiary_id WHERE tr.user_id=? ORDER BY tr.initiated_at DESC LIMIT 10');
$history->execute([$customerId]);
?>
<?php if ($error): ?><div class="notice error"><?= e($error) ?></div><?php endif ?>
<?php if ($result): ?><div class="notice success">Transfer submitted for review. Reference <strong><?= e($result['reference']) ?></strong>.</div><?php endif ?>
<div class="app-grid">
<section class="panel span-5"><h2><?= $quote ? 'Confirm transfer' : 'New transfer' ?></h2><p>Available: <strong class="money"><?= Money::format((int) $account['balance_cents'], true) ?></strong></p>
<?php if ($quote): ?><dl><dt>Recipient receives</dt><dd class="money"><?= Money::format($quote['amount_cents']) ?></dd><dt>Transfer fee</dt><dd class="money"><?= Money::format($quote['fee_cents']) ?></dd><dt>Total debit</dt><dd class="money"><?= Money::format($quote['total_cents']) ?></dd></dl>
<div class="actions"><form method="post"><?= csrfField() ?><input type="hidden" name="action" value="confirm"><button class="button button-primary">Submit transfer request</button></form><form method="post"><?= csrfField() ?><input type="hidden" name="action" value="cancel"><button class="button button-secondary">Cancel</button></form></div>
<?php else: ?><form method="post" class="form-grid"><?= csrfField() ?><input type="hidden" name="action" value="quote"><div class="field full"><label for="beneficiary_id">Beneficiary</label><select id="beneficiary_id" name="beneficiary_id" required><option value="">Choose beneficiary</option><?php foreach ($beneficiaries as $row): ?><option value="<?= e($row['beneficiary_id']) ?>"><?= e($row['display_name'] . ' · ' . $row['destination_account_number']) ?></option><?php endforeach ?></select><small><a href="beneficiaries.php">Manage beneficiaries</a></small></div><div class="field full"><label for="amount">Amount (USD)</label><input id="amount" name="amount" inputmode="decimal" placeholder="25.00" required></div><div class="field full"><label for="memo">Memo (optional)</label><input id="memo" name="memo" maxlength="140"></div><div class="field full"><button class="button button-primary">Review transfer</button></div></form><?php endif ?></section>
<section class="panel span-7"><h2>Recent transfers</h2><div class="table-wrap"><table class="data-table"><thead><tr><th>Reference</th><th>Beneficiary</th><th>Amount</th><th>Fee</th><th>Status</th></tr></thead><tbody><?php foreach ($history->fetchAll() as $row): ?><tr><td><a href="transaction.php?reference=<?= urlencode($row['reference']) ?>"><?= e($row['reference']) ?></a></td><td><?= e($row['display_name']) ?></td><td class="money"><?= Money::format((int) $row['amount_cents']) ?></td><td><?= Money::format((int) $row['fee_cents']) ?></td><td><span class="badge <?= e($row['status']) ?>"><?= e($row['status']) ?></span></td></tr><?php endforeach ?></tbody></table></div></section></div>
<?php require __DIR__ . '/../includes/customer_footer.php'; ?>
