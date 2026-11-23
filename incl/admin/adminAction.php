<?php
require_once __DIR__ . '/../lib/connection.php';
require_once __DIR__ . '/../lib/GJPCheck.php';
require_once __DIR__ . '/../lib/exploitPatch.php';
require_once __DIR__ . '/../lib/mainLib.php';
require_once __DIR__ . '/../lib/moderationFlags.php';

$moderatorID = GJPCheck::getAccountIDOrDie();
$admin = $db->prepare('SELECT isAdmin FROM accounts WHERE accountID = :accountID');
$admin->execute([':accountID' => $moderatorID]);
if (!(int)$admin->fetchColumn()) exit('-1');

$operation = pathinfo($_SERVER['SCRIPT_NAME'] ?? '', PATHINFO_FILENAME);
$target = ExploitPatch::number($_POST['targetAccountID'] ?? $_POST['accountID'] ?? 0);
$reason = (string)($_POST['reason'] ?? '');
$mode = (string)($_POST['mode'] ?? 'perm');
$duration = max(0, (int)($_POST['durationMinutes'] ?? 0));
$expires = $mode === 'perm' || $duration === 0 ? 2147483647 : time() + ($duration * 60);
$gs = new mainLib();

if (in_array($operation, ['adminCommentBan', 'adminUploadBan', 'adminMessageBan', 'adminFreezeAccount'], true)) {
    if (!$target) exit('-1');
    $banType = match ($operation) {
        'adminCommentBan' => 3,
        'adminUploadBan' => 2,
        'adminMessageBan' => 5,
        'adminFreezeAccount' => 4,
        default => 4,
    };
    $gs->banPerson($moderatorID, $target, $reason, $banType, 0, $expires);
    echo '1';
    exit;
}

if (in_array($operation, ['adminCommentUnban', 'adminUploadUnban', 'adminMessageUnban', 'adminUnfreezeAccount'], true)) {
    if (!$target) exit('-1');
    $banType = match ($operation) {
        'adminCommentUnban' => 3,
        'adminUploadUnban' => 2,
        'adminMessageUnban' => 5,
        'adminUnfreezeAccount' => 4,
        default => 4,
    };
    $query = $db->prepare('SELECT banID FROM bans WHERE person = :person AND personType = 0 AND banType = :banType AND isActive = 1');
    $query->execute([':person' => $target, ':banType' => $banType]);
    $banID = $query->fetchColumn();
    if ($banID !== false) $gs->unbanPerson((int)$banID, $moderatorID);
    echo '1';
    exit;
}

if ($operation === 'adminDeleteUserComments') {
    if (!$target) exit('-1');
    $user = $db->prepare('SELECT userID FROM users WHERE extID = :accountID');
    $user->execute([':accountID' => $target]);
    $userID = $user->fetchColumn();
    $removed = 0;
    if ($userID !== false) {
        $query = $db->prepare('DELETE FROM comments WHERE userID = :userID');
        $query->execute([':userID' => $userID]);
        $removed += $query->rowCount();
    }
    $query = $db->prepare('DELETE FROM acccomments WHERE userID = :userID');
    $query->execute([':userID' => (int)$userID]);
    $removed += $query->rowCount();
    echo (string)$removed;
    exit;
}

if ($operation === 'adminThreadAction') {
    $messageID = (int)($_POST['messageID'] ?? 0);
    $action = strtolower((string)($_POST['action'] ?? ''));
    if (!$messageID) exit('-1');
    if (in_array($action, ['delete', 'remove', 'close'], true)) {
        $query = $db->prepare('UPDATE messages SET isDeleted = 1 WHERE messageID = :messageID');
        $query->execute([':messageID' => $messageID]);
    }
    echo '1';
    exit;
}

if ($operation === 'adminCloseCase') {
    $items = array_values(array_filter(array_map('intval', explode(',', (string)($_POST['items'] ?? '')))));
    if ($items) {
        $marks = implode(',', array_fill(0, count($items), '?'));
        $query = $db->prepare("DELETE FROM reports WHERE ID IN ($marks)");
        $query->execute($items);
    } elseif ($target) {
        $query = $db->prepare('DELETE FROM reports WHERE targetID = :target AND type = 0');
        $query->execute([':target' => $target]);
    }
    echo '1';
    exit;
}

if (in_array($operation, ['adminNameLock', 'adminNameUnlock', 'adminTrendingBan', 'adminTrendingUnban'], true)) {
    if (!$target) exit('-1');
    ensureModerationFlagsTable($db);
    $flag = str_starts_with($operation, 'adminName') ? 'name_lock' : 'trending_ban';
    $active = str_ends_with($operation, 'Unban') || str_ends_with($operation, 'Unlock') ? 0 : 1;
    $query = $db->prepare('INSERT INTO moderation_flags (accountID, flag, active, moderatorID, updatedAt) VALUES (:accountID, :flag, :active, :moderatorID, :updatedAt) ON DUPLICATE KEY UPDATE active = VALUES(active), moderatorID = VALUES(moderatorID), updatedAt = VALUES(updatedAt)');
    $query->execute([':accountID' => $target, ':flag' => $flag, ':active' => $active, ':moderatorID' => $moderatorID, ':updatedAt' => time()]);
    echo '1';
    exit;
}

if ($operation === 'adminGetAbuseInfo') {
    $threadID = (int)($_POST['threadID'] ?? 0);
    $query = $db->prepare('SELECT COUNT(*) FROM reports WHERE type = 8 AND targetID = :threadID');
    $query->execute([':threadID' => $threadID]);
    echo '1:' . (int)$query->fetchColumn();
    exit;
}

if ($operation === 'adminGetAccountReview') {
    if (!$target) exit('-1');
    $query = $db->prepare('SELECT accountID, userName, isAdmin, isActive FROM accounts WHERE accountID = :accountID');
    $query->execute([':accountID' => $target]);
    $account = $query->fetch(PDO::FETCH_ASSOC);
    if (!$account) exit('-1');
    echo '1:' . (int)$account['accountID'] . ':2:' . ExploitPatch::url_base64_encode($account['userName']) . ':3:' . (int)$account['isAdmin'] . ':4:' . (int)$account['isActive'];
    exit;
}

echo '-1';

