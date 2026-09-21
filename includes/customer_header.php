<?php
require_once __DIR__ . '/../app/bootstrap_financial.php';
redirectIfNotLoggedIn();
enforceSessionVersion($pdo);
$appPage = $appPage ?? '';
$appTitle = $appTitle ?? 'Valtoria Bank';
$customerId = (int) $_SESSION['user_id'];
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= e($appTitle) ?> | Valtoria Bank</title><link rel="stylesheet" href="../assets/css/customer-app.css"></head><body><a class="skip-link" href="#customer-main">Skip to account content</a><div class="app-shell"><aside class="app-sidebar"><a class="valtoria-wordmark" href="dashboard.php">Valtoria</a><nav class="app-nav" aria-label="Customer navigation"><?php foreach ([['dashboard','dashboard.php','Overview'],['cards','card-center.php','Cards'],['funding','funding.php','Card funding'],['transfer','transfer.php','Transfers'],['beneficiaries','beneficiaries.php','Beneficiaries'],['transactions','transactions.php','Transactions'],['credit','credit-center.php','Credit'],['notifications','notifications.php','Notifications'],['support','support.php','Support'],['profile','profile.php','Profile & security']] as $item): ?><a class="<?= $appPage===$item[0]?'active':'' ?>" href="<?= $item[1] ?>"><?= e($item[2]) ?></a><?php endforeach; ?></nav></aside><div class="app-main"><header class="app-topbar"><h1 class="app-title"><?= e($appTitle) ?></h1><div><a class="muted-link" href="../index.php">Public site</a> · <a href="../logout.php">Sign out</a></div></header><main class="app-content" id="customer-main">
