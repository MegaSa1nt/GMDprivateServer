<?php
require_once __DIR__ . '/../lib/connection.php';
require_once __DIR__ . '/../lib/GJPCheck.php';
require_once __DIR__ . '/../lib/exploitPatch.php';
require_once __DIR__ . '/../lib/notification.php';

$accountID = GJPCheck::getAccountIDOrDie();
ensureNotificationsTable($db);

$query = $db->prepare('SELECT notificationID, icon, title, description, notificationTime, isNew, achievementID, hasChest, chestType FROM notifications WHERE targetAccountID = :accountID AND claimed = 0 ORDER BY notificationID DESC LIMIT 100');
$query->execute([':accountID' => $accountID]);
$notifications = [];
foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $notification) {
    $notifications[] = implode(':', [
        1, (int)$notification['notificationID'],
        2, 0,
        3, ExploitPatch::url_base64_encode($notification['icon']),
        4, ExploitPatch::url_base64_encode($notification['title']),
        5, ExploitPatch::url_base64_encode($notification['description']),
        6, $notification['notificationTime'],
        7, (int)$notification['isNew'],
        8, 0,
        9, 0,
        10, ExploitPatch::url_base64_encode($notification['achievementID']),
        11, 0,
        12, 0,
        13, (int)$notification['hasChest'],
        14, (int)$notification['chestType'],
        15, $accountID,
    ]);
}
echo implode('|', $notifications) . '#1:0:10';

