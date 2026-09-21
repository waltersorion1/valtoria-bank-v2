<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/bootstrap_financial.php';
requirePermission('kyc.manage');

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT id_file_path FROM id_verifications WHERE user_id=?');
$stmt->execute([$id]);
$relative = $stmt->fetchColumn();
if (!$relative || str_starts_with((string) $relative, 'seed://')) { http_response_code(404); exit('Document not found.'); }

$candidate = realpath(VALTORIA_ROOT . '/' . ltrim((string) $relative, '/\\'));
$allowedRoots = array_filter([realpath(VALTORIA_ROOT . '/storage/uploads/identity'), realpath(VALTORIA_ROOT . '/uploads/id_verifications')]);
$contained = false;
foreach ($allowedRoots as $root) {
    if ($candidate && str_starts_with($candidate, $root . DIRECTORY_SEPARATOR)) { $contained = true; break; }
}
if (!$candidate || !$contained || !is_file($candidate)) { http_response_code(404); exit('Document not found.'); }

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($candidate);
$extensions = ['image/jpeg'=>'jpg','image/png'=>'png','application/pdf'=>'pdf'];
if (!isset($extensions[$mime])) { http_response_code(415); exit('Unsupported document type.'); }

audit($pdo, 'kyc.document_viewed', 'user', $id);
header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="verification-document.' . $extensions[$mime] . '"');
header('Content-Length: ' . (string) filesize($candidate));
header('Cache-Control: private, no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
readfile($candidate);
