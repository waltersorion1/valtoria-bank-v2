<?php
declare(strict_types=1);

$appPage = 'support';
$appTitle = 'Support center';
require __DIR__ . '/../includes/customer_header.php';

$notice = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        requireCsrf();
        requireFeature($pdo, 'features.support_cases', 'Support case updates are temporarily unavailable.');
        $action = $_POST['action'] ?? '';
        if ($action === 'create') {
            $subject = trim((string) ($_POST['subject'] ?? ''));
            $body = trim((string) ($_POST['message'] ?? ''));
            $category = (string) ($_POST['category'] ?? '');
            if (!in_array($category, ['account','card','funding','transfer','credit','security','other'], true)
                || mb_strlen($subject) < 4 || mb_strlen($subject) > 160
                || mb_strlen($body) < 10 || mb_strlen($body) > 5000) {
                throw new InvalidArgumentException('Complete the subject, category, and message.');
            }
            $pdo->beginTransaction();
            $reference = Reference::generate('SUP');
            $pdo->prepare('INSERT INTO support_cases(reference,user_id,category,subject,priority) VALUES(?,?,?,?,?)')
                ->execute([$reference, $customerId, $category, $subject, $category === 'security' ? 'high' : 'normal']);
            $id = (int) $pdo->lastInsertId();
            $pdo->prepare('INSERT INTO support_messages(case_id,author_user_id,body) VALUES(?,?,?)')->execute([$id, $customerId, $body]);
            audit($pdo, 'support.case_created', 'support_case', $id);
            $pdo->commit();
            $notice = 'Support case ' . $reference . ' was created.';
        } elseif ($action === 'reply') {
            $id = (int) ($_POST['case_id'] ?? 0);
            $body = trim((string) ($_POST['message'] ?? ''));
            if (mb_strlen($body) < 2 || mb_strlen($body) > 5000) throw new InvalidArgumentException('Enter a valid reply.');
            $stmt = $pdo->prepare("SELECT 1 FROM support_cases WHERE case_id=? AND user_id=? AND status<>'closed'");
            $stmt->execute([$id, $customerId]);
            if (!$stmt->fetchColumn()) throw new RuntimeException('Case is not available.');
            $pdo->prepare('INSERT INTO support_messages(case_id,author_user_id,body) VALUES(?,?,?)')->execute([$id, $customerId, $body]);
            $pdo->prepare("UPDATE support_cases SET status='open' WHERE case_id=? AND user_id=?")->execute([$id, $customerId]);
            audit($pdo, 'support.customer_reply', 'support_case', $id);
            $notice = 'Reply added.';
        } else {
            throw new InvalidArgumentException('Unsupported support action.');
        }
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = $exception->getMessage();
    }
}

$selected = (int) ($_GET['case'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM support_cases WHERE user_id=? ORDER BY updated_at DESC');
$stmt->execute([$customerId]);
$cases = $stmt->fetchAll();
$messages = [];
$selectedCase = null;
if ($selected) {
    $stmt = $pdo->prepare('SELECT * FROM support_cases WHERE case_id=? AND user_id=?');
    $stmt->execute([$selected, $customerId]);
    $selectedCase = $stmt->fetch();
    if (!$selectedCase) {
        http_response_code(404);
        $error = 'Support case not found.';
        $selected = 0;
    } else {
        $stmt = $pdo->prepare('SELECT sm.body,sm.created_at,u.role FROM support_messages sm JOIN users u ON u.user_id=sm.author_user_id WHERE sm.case_id=? AND sm.is_internal=0 ORDER BY sm.created_at');
        $stmt->execute([$selected]);
        $messages = $stmt->fetchAll();
    }
}
?>
<?php if ($notice): ?><div class="notice success"><?= e($notice) ?></div><?php endif; ?>
<?php if ($error): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?>
<div class="app-grid">
  <section class="panel span-5"><h2>Start a support case</h2><p class="muted">Never send passwords, one-time codes, or complete card details.</p>
    <form method="post" class="form-grid"><?= csrfField() ?><input type="hidden" name="action" value="create">
      <div class="field full"><label for="category">Category</label><select id="category" name="category"><?php foreach (['account','card','funding','transfer','credit','security','other'] as $value): ?><option><?= e($value) ?></option><?php endforeach; ?></select></div>
      <div class="field full"><label for="subject">Subject</label><input id="subject" name="subject" maxlength="160" required></div>
      <div class="field full"><label for="message">How can we help?</label><textarea id="message" name="message" maxlength="5000" required></textarea></div>
      <button class="button button-primary">Create case</button>
    </form>
  </section>
  <section class="panel span-7"><h2>Your cases</h2>
    <?php foreach ($cases as $case): ?><a href="support.php?case=<?= (int) $case['case_id'] ?>" class="note support-case-link"><strong><?= e($case['reference']) ?> · <?= e($case['subject']) ?></strong><br><span class="muted"><?= e(str_replace('_', ' ', $case['status'])) ?> · updated <?= e(formatDate($case['updated_at'])) ?></span></a><?php endforeach; ?>
    <?php if (!$cases): ?><div class="empty">You have no support cases.</div><?php endif; ?>
  </section>
  <?php if ($selectedCase): ?><section class="panel span-12"><h2>Case conversation</h2>
    <?php foreach ($messages as $message): ?><div class="note"><strong><?= e($message['role'] === 'customer' ? 'You' : 'Valtoria support') ?></strong><small> · <?= e(formatDate($message['created_at'])) ?></small><p><?= nl2br(e($message['body'])) ?></p></div><?php endforeach; ?>
    <form method="post" class="compact-form"><?= csrfField() ?><input type="hidden" name="action" value="reply"><input type="hidden" name="case_id" value="<?= $selected ?>"><label class="sr-only" for="reply">Reply</label><input id="reply" name="message" maxlength="5000" required placeholder="Add a reply"><button class="button button-primary">Send reply</button></form>
  </section><?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/customer_footer.php'; ?>
