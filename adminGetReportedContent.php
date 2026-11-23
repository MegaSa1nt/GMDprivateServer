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
$query = $db->prepare('SELECT ID, targetID, type, reasons, extraID FROM reports WHERE (targetID = :targetAccountID AND type = 0) ORDER BY ID DESC LIMIT :limit OFFSET :offset');
$query->bindValue(':targetAccountID', $targetAccountID, PDO::PARAM_INT);
$query->bindValue(':limit', $pageSize, PDO::PARAM_INT);
$query->bindValue(':offset', $page * $pageSize, PDO::PARAM_INT);
$query->execute();
$rows = $query->fetchAll(PDO::FETCH_ASSOC);
$count = $db->prepare('SELECT COUNT(*) FROM reports WHERE targetID = :targetAccountID AND type = 0');
$count->execute([':targetAccountID' => $targetAccountID]);
$total = (int)$count->fetchColumn();
$objects = [];
foreach ($rows as $row) {
    $objects[] = implode(':', [
        (int)$row['ID'], (int)$row['type'], (int)$row['targetID'],
        ExploitPatch::url_base64_encode((string)$row['reasons']), (int)$row['extraID'],
    ]);
}
echo implode('|', $objects) . '#' . $total . ':' . ($page * $pageSize) . ':' . $pageSize;

