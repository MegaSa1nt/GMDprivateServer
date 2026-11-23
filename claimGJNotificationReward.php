<?php
require_once __DIR__ . '/incl/lib/GJPCheck.php';
require_once __DIR__ . '/incl/lib/exploitPatch.php';
require_once __DIR__ . '/incl/lib/notification.php';
require_once __DIR__ . '/incl/lib/connection.php';

$accountID = GJPCheck::getAccountIDOrDie();
$notificationID = ExploitPatch::number($_POST['notificationID'] ?? $_POST['id'] ?? 0);
if (!$notificationID) exit('-1');

ensureNotificationsTable($db);
$db->beginTransaction();
$query = $db->prepare('SELECT rewardItem, rewardAmount FROM notifications WHERE notificationID = :notificationID AND targetAccountID = :accountID AND claimed = 0 FOR UPDATE');
$query->execute([':notificationID' => $notificationID, ':accountID' => $accountID]);
$reward = $query->fetch(PDO::FETCH_ASSOC);
if (!$reward) { $db->rollBack(); exit('-1'); }
$db->prepare('UPDATE notifications SET claimed = 1 WHERE notificationID = :notificationID')->execute([':notificationID' => $notificationID]);
if ((int)$reward['rewardItem'] === 6 && (int)$reward['rewardAmount'] > 0) {
    $query = $db->prepare('INSERT INTO notification_rewards (accountID, demonKeys, updatedAt) VALUES (:accountID, :amount, :now) ON DUPLICATE KEY UPDATE demonKeys = demonKeys + VALUES(demonKeys), updatedAt = VALUES(updatedAt)');
    $query->execute([':accountID' => $accountID, ':amount' => (int)$reward['rewardAmount'], ':now' => time()]);
}
$db->commit();
echo '1';

