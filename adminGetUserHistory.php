<?php
require_once __DIR__ . '/incl/lib/connection.php';
require_once __DIR__ . '/incl/lib/GJPCheck.php';
require_once __DIR__ . '/incl/lib/exploitPatch.php';

$accountID = GJPCheck::getAccountIDOrDie();
$admin = $db->prepare('SELECT isAdmin FROM accounts WHERE accountID = :accountID');
$admin->execute([':accountID' => $accountID]);
if (!(int)$admin->fetchColumn()) exit('-1');

$targetAccountID = ExploitPatch::number($_POST['targetAccountID'] ?? 0);
$page = max(0, (int)($_POST['page'] ?? 0));
$pageSize = 20;
if (!$targetAccountID) exit('-1');

$count = $db->prepare('SELECT COUNT(*) FROM modactions WHERE value2 = :targetAccountID OR account = :targetAccountID');
$count->execute([':targetAccountID' => $targetAccountID]);
$total = (int)$count->fetchColumn();
$query = $db->prepare('SELECT * FROM modactions WHERE value2 = :targetAccountID OR account = :targetAccountID ORDER BY timestamp DESC, ID DESC LIMIT :limit OFFSET :offset');
$query->bindValue(':targetAccountID', $targetAccountID, PDO::PARAM_INT);
$query->bindValue(':limit', $pageSize, PDO::PARAM_INT);
$query->bindValue(':offset', $page * $pageSize, PDO::PARAM_INT);
$query->execute();

$logs = [];
foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $action) {
    $moderator = $db->prepare('SELECT userName FROM accounts WHERE accountID = :accountID');
    $moderator->execute([':accountID' => (int)$action['account']]);
    $moderatorName = (string)($moderator->fetchColumn() ?: 'Unknown');
    $actionName = match ((int)$action['type']) {
        20, 24 => 'role_change',
        13 => 'delete_comments',
        12 => 'delete_posts',
        default => 'mod_action',
    };
    $reason = (string)($action['value'] ?? '');
    $logs[] = implode(':', [
        $actionName,
        'done',
        0,
        0,
        ExploitPatch::url_base64_encode($reason),
        ExploitPatch::url_base64_encode($moderatorName),
        (int)$action['timestamp'],
        0,
        ExploitPatch::url_base64_encode('action_' . (int)$action['ID']),
    ]);
}
echo implode('|', $logs) . '#' . $total . ':' . ($page * $pageSize) . ':' . $pageSize;

