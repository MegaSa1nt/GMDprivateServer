<?php
require_once __DIR__ . '/incl/lib/connection.php';
require_once __DIR__ . '/incl/lib/GJPCheck.php';
require_once __DIR__ . '/incl/lib/exploitPatch.php';

$accountID = GJPCheck::getAccountIDOrDie();
$admin = $db->prepare('SELECT isAdmin FROM accounts WHERE accountID = :accountID');
$admin->execute([':accountID' => $accountID]);
if (!(int)$admin->fetchColumn()) exit('-1');

$items = array_values(array_filter(array_map('intval', explode(',', (string)($_POST['items'] ?? '')))));
$targetAccountID = ExploitPatch::number($_POST['targetAccountID'] ?? 0);
if ($items) {
    $marks = implode(',', array_fill(0, count($items), '?'));
    $query = $db->prepare("DELETE FROM reports WHERE ID IN ($marks)");
    $query->execute($items);
} elseif ($targetAccountID) {
    $query = $db->prepare('DELETE FROM reports WHERE targetID = :targetAccountID AND type = 0');
    $query->execute([':targetAccountID' => $targetAccountID]);
} else exit('-1');
echo '1';

