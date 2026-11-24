<?php
require_once __DIR__ . '/incl/lib/GJPCheck.php';
require_once __DIR__ . '/incl/lib/exploitPatch.php';
require_once __DIR__ . '/incl/lib/notification.php';

$accountID = GJPCheck::getAccountIDOrDie();
$notificationID = ExploitPatch::number($_POST['notificationID'] ?? $_POST['id'] ?? 0);
if (!$notificationID) exit('-1');

ensureNotificationsTable($db);
$query = $db->prepare('UPDATE notifications SET claimed = 1 WHERE notificationID = :notificationID AND targetAccountID = :accountID AND claimed = 0');
$query->execute([':notificationID' => $notificationID, ':accountID' => $accountID]);
echo $query->rowCount() ? '1' : '-1';

