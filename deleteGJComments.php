<?php
require_once __DIR__ . '/incl/lib/connection.php';
require_once __DIR__ . '/incl/lib/GJPCheck.php';

$accountID = GJPCheck::getAccountIDOrDie();
$admin = $db->prepare('SELECT isAdmin FROM accounts WHERE accountID = :accountID');
$admin->execute([':accountID' => $accountID]);
if (!(int)$admin->fetchColumn()) exit('-1');
$items = array_values(array_filter(array_map('intval', explode(',', (string)($_POST['items'] ?? '')))));
if (!$items) exit('-1');
$marks = implode(',', array_fill(0, count($items), '?'));
$query = $db->prepare("DELETE FROM comments WHERE commentID IN ($marks)");
$query->execute($items);
echo '1';

