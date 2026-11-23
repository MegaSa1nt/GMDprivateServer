<?php
chdir(dirname(__FILE__));
require "../lib/connection.php";
require_once "../lib/GJPCheck.php";
require_once "../lib/exploitPatch.php";
require_once "../lib/mainLib.php";
$gs = new mainLib();

$accountID = GJPCheck::getAccountIDOrDie();
$threadID = ExploitPatch::number($_POST['threadID'] ?? 0) ?: 0;
$thread = $gs->getThreadByID($threadID);
if(!$thread) exit('-1');

$members = array_filter(explode(',', $thread['threadMembers'] ?? ''));
$invited = array_filter(explode(',', $thread['invitedUsers'] ?? ''));
if(!in_array((string)$accountID, array_map('strval', array_merge($members, $invited)), true)) exit('-1');

$ids = array_values(array_unique(array_merge($members, $invited)));
$users = [];
if($ids) {
    $query = $db->prepare('SELECT * FROM users WHERE extID IN (' . implode(',', array_map('intval', $ids)) . ')');
    $query->execute();
    foreach($query->fetchAll() as $user) {
        $user['userName'] = $gs->makeClanUsername($user);
        $users[$user['extID']] = $user;
    }
}

$memberObjects = [];
foreach($members as $id) {
    if(!isset($users[$id])) continue;
    $user = $users[$id];
    $memberObjects[] = '1:' . $user['userName'] . ':2:' . $user['userID'] . ':9:' . $user['icon']
        . ':10:' . $user['color1'] . ':11:' . $user['color2'] . ':51:' . $user['color3']
        . ':14:' . $user['iconType'] . ':15:' . $user['special'] . ':16:' . $user['extID']
        . ':3:' . $user['stars'] . ':52:' . $user['moons'] . ':4:' . $user['demons']
        . ':13:' . $user['coins'] . ':17:' . $user['userCoins'] . ':46:' . $user['diamonds'];
}

exit(implode('|', $memberObjects) . '##' . '##');
?>
