<?php
$adminPage='cards';$adminTitle='Card operations';$adminPermission='cards.view';require __DIR__.'/../includes/admin_header.php';
$notice='';$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        requireCsrf();requirePermission('cards.manage');$id=(int)($_POST['card_id']??0);$decision=$_POST['decision']??'';$reason=trim($_POST['reason']??'');
        if(mb_strlen($reason)<5)throw new InvalidArgumentException('Provide a specific review reason.');
        $owner=$pdo->prepare('SELECT user_id,network,last_four FROM linked_cards WHERE card_id=?');$owner->execute([$id]);$cardOwner=$owner->fetch();if(!$cardOwner)throw new RuntimeException('Card was not found.');
        if($decision==='approve'){$stmt=$pdo->prepare("UPDATE linked_cards SET verification_status='verified',status='active',verified_at=UTC_TIMESTAMP() WHERE card_id=? AND verification_status='pending'");$stmt->execute([$id]);}
        elseif($decision==='reject'){$stmt=$pdo->prepare("UPDATE linked_cards SET verification_status='rejected',status='disabled',is_default=0 WHERE card_id=? AND verification_status='pending'");$stmt->execute([$id]);}
        elseif(in_array($decision,['active','disabled'],true)){$stmt=$pdo->prepare("UPDATE linked_cards SET status=?,is_default=IF(?='active',is_default,0) WHERE card_id=? AND status<>'removed'");$stmt->execute([$decision,$decision,$id]);}
        else throw new InvalidArgumentException('Invalid card action.');
        if(!$stmt->rowCount())throw new RuntimeException('Card is unavailable for that action.');
        audit($pdo,'card.reviewed','linked_card',$id,['decision'=>$decision,'reason'=>mb_substr($reason,0,500)]);
        (new FinancialService($pdo))->notify((int)$cardOwner['user_id'],'card_'.$decision,'Card review updated',ucfirst($cardOwner['network']).' ending '.$cardOwner['last_four'].' was marked '.$decision.'.');
        $notice='Card review recorded.';
    }catch(Throwable $e){$error=$e->getMessage();}
}
$network=$_GET['network']??'';$status=$_GET['status']??'';$params=[];$where=['1=1'];
if(in_array($network,['visa','mastercard'],true)){$where[]='c.network=?';$params[]=$network;}
if(in_array($status,['active','disabled','removed'],true)){$where[]='c.status=?';$params[]=$status;}
$stmt=$pdo->prepare('SELECT c.*,u.full_name,u.email,(SELECT COUNT(*) FROM card_fundings f WHERE f.card_id=c.card_id) funding_count FROM linked_cards c JOIN users u ON u.user_id=c.user_id WHERE '.implode(' AND ',$where).' ORDER BY FIELD(c.verification_status,\'pending\',\'verified\',\'rejected\'),c.created_at DESC LIMIT 150');$stmt->execute($params);$cards=$stmt->fetchAll();
?>
<?php if($notice):?><div class="notice success"><?=e($notice)?></div><?php endif?><?php if($error):?><div class="notice error"><?=e($error)?></div><?php endif?>
<div class="notice">Review masked metadata and the non-sensitive partner reference below. Confirm compatibility through the approved partner process. Full card numbers and security codes are never displayed here.</div>
<form class="toolbar" method="get"><div class="field"><label>Network</label><select name="network"><option value="">All networks</option><option value="visa" <?=$network==='visa'?'selected':''?>>Visa</option><option value="mastercard" <?=$network==='mastercard'?'selected':''?>>Mastercard</option></select></div><div class="field"><label>Status</label><select name="status"><option value="">All statuses</option><?php foreach(['active','disabled','removed'] as $s):?><option value="<?=$s?>" <?=$status===$s?'selected':''?>><?=$s?></option><?php endforeach?></select></div><button class="button button-primary">Filter</button></form>
<section class="panel"><div class="table-wrap"><table class="data-table"><thead><tr><th>Customer</th><th>Card</th><th>Partner reference</th><th>Verification</th><th>Funding</th><th>Action</th></tr></thead><tbody><?php foreach($cards as $card):?><tr><td><?=e($card['full_name'])?><br><small><?=e($card['email'])?></small></td><td><strong><?=e(strtoupper($card['network']))?> •••• <?=e($card['last_four'])?></strong><br><small><?=e($card['cardholder_name'])?> · <?=str_pad((string)$card['expiry_month'],2,'0',STR_PAD_LEFT)?>/<?=e($card['expiry_year'])?></small></td><td class="mono"><?=e(str_replace('manual_ref_','',$card['provider_payment_method_token']))?></td><td><span class="badge <?=e($card['verification_status'])?>"><?=e($card['verification_status'])?></span></td><td><?=$card['funding_count']?></td><td><?php if(can('cards.manage')&&$card['status']!=='removed'):?><form method="post" class="review-form"><?=csrfField()?><input type="hidden" name="card_id" value="<?=$card['card_id']?>"><select name="decision"><?php if($card['verification_status']==='pending'):?><option value="approve">Compatible — approve</option><option value="reject">Incompatible — reject</option><?php else:?><option value="<?=$card['status']==='active'?'disabled':'active'?>"><?=$card['status']==='active'?'Disable':'Enable'?></option><?php endif?></select><input name="reason" minlength="5" required maxlength="500" placeholder="Review reason"><button class="button button-secondary">Apply</button></form><?php else:?>—<?php endif?></td></tr><?php endforeach?></tbody></table></div><?php if(!$cards):?><div class="empty">No cards match these filters.</div><?php endif?></section>
<?php require __DIR__.'/../includes/admin_footer.php';?>
