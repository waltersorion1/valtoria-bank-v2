<?php
require_once __DIR__ . '/../app/bootstrap_financial.php';
enforceSessionVersion($pdo);
requirePermission($adminPermission ?? 'dashboard.view');
$adminPage = $adminPage ?? '';
$adminTitle = $adminTitle ?? 'Operations';
$adminNav = [
 ['dashboard','dashboard.php','Overview','dashboard.view'],
 ['customers','customers.php','Customers & KYC','customers.view'],
 ['cards','cards.php','Card operations','cards.view'],
 ['transactions','transactions.php','Money movement','transactions.view'],
 ['credit','credit-applications.php','Credit','credit.view'],
 ['support','support.php','Support','support.view'],
 ['settings','settings.php','Products & access','settings.manage'],
 ['audit','audit.php','Audit & reconciliation','audit.view'],
];
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($adminTitle)?> | Valtoria Operations</title><link rel="stylesheet" href="../assets/css/customer-app.css"><link rel="stylesheet" href="../assets/css/admin-operations.css"></head><body><a class="skip-link" href="#admin-main">Skip to operations content</a><div class="app-shell admin-shell"><aside class="app-sidebar admin-sidebar"><a class="valtoria-wordmark" href="dashboard.php">Valtoria <small>Operations</small></a><nav class="app-nav" aria-label="Operations navigation"><?php foreach($adminNav as $item):if(!can($item[3]))continue;?><a class="<?=$adminPage===$item[0]?'active':''?>" href="<?=$item[1]?>"><?=e($item[2])?></a><?php endforeach?></nav><div class="admin-identity"><strong><?=e(currentRole())?></strong><a href="../logout.php">Sign out</a></div></aside><div class="app-main"><header class="app-topbar"><div><span class="admin-kicker">Operations console</span><h1 class="app-title"><?=e($adminTitle)?></h1></div><div class="topbar-links"><a href="../index.php">Public site</a><a href="../user/dashboard.php">Customer view</a></div></header><main class="app-content" id="admin-main">
