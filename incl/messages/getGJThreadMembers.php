<?php
chdir(dirname(__FILE__));
require "../lib/connection.php";
require_once "../lib/exploitPatch.php";
require_once "../lib/mainLib.php";
require_once "../lib/GJPCheck.php";

$gs = new mainLib();

$accountID = GJPCheck::getAccountIDOrDie();
$threadID = ExploitPatch::number($_POST["threadID"]);

$query = $db->prepare("SELECT threadOwner, threadMembers, formerMembers, invitedUsers FROM threads WHERE threadID = :threadID");
$query->execute([":threadID" => $threadID]);
$thread = $query->fetch();

if (!$thread)
    exit("-1");

$members = array_filter(explode(",", $thread["threadMembers"]), "strlen");
$formerMembers = array_filter(explode(",", $thread["formerMembers"]), "strlen");
$invitedUsers = array_filter(explode(",", $thread["invitedUsers"]), "strlen");

if (!$thread["threadOwner"] == $accountID && !in_array($accountID, $members) && !in_array($accountID, $invitedUsers))
    exit("-1");

$allIDs = array_values(array_unique(array_merge($members, $formerMembers, $invitedUsers)));
$userMap = [];

if ($allIDs) {
    $placeholder = implode(",", array_fill(0, count($allIDs), "?"));

    $query = $db->prepare("SELECT userName, userID, extID AS accountID, stars, demons, icon, color1, color2,
        color3, iconType, special, coins, userCoins, diamonds, moons
        FROM users WHERE extID IN (" . $placeholder . ")");
    $query->execute($allIDs);

    foreach ($query->fetchAll() as $row)
        $userMap[(int) $row["accountID"]] = $row;

    $query = $db->prepare("SELECT person2, timestamp FROM follows WHERE person1 = ? AND person2 IN (" . $placeholder . ")");
    $query->execute(array_merge([$accountID], $allIDs));
    $friendsSince = $query->fetchAll(PDO::FETCH_KEY_PAIR);

    foreach ($userMap as $id => &$u)
        $u["friendsSince"] = $friendsSince[$id] ?? 0;
    unset($u);
}

$membersString = "";
foreach ($members as $id) {
    if (!isset($userMap[$id]))
        continue;

    $m = $userMap[$id];

    $membersString .= "1:" . $m["userName"]
        . ":2:" . $m["userID"]
        . ":3:" . $m["stars"]
        . ":4:" . $m["demons"]
        . ":9:" . $m["icon"]
        . ":10:" . $m["color1"]
        . ":11:" . $m["color2"]
        . ":13:" . $m["coins"]
        . ":14:" . $m["iconType"]
        . ":15:" . $m["special"]
        . ":16:" . $m["accountID"]
        . ":17:" . $m["userCoins"]
        . ":46:" . $m["diamonds"]
        . ":51:" . $m["color3"]
        . ":52:" . $m["moons"]
        . ":64:" . $m["friendsSince"] . "|";
}
$membersString = rtrim($membersString, "|");

$formerString = "";
foreach ($formerMembers as $id) {
    if (!isset($userMap[$id]))
        continue;

    $m = $userMap[$id];

    $formerString .= $m["accountID"] . ":" . $m["userID"] . ":" . ExploitPatch::url_base64_encode($m["userName"])
        . ":" . $m["icon"] . ":" . $m["color1"] . ":" . $m["color2"] . ":" . $m["color3"]
        . ":" . $m["iconType"] . ":" . $m["special"] . "|";
}
$formerString = rtrim($formerString, "|");

$invitesString = "";
foreach ($invitedUsers as $id) {
    if (!isset($userMap[$id]))
        continue;

    $m = $userMap[$id];

    $query = $db->prepare("SELECT accountID FROM threadinvites WHERE threadID = :threadID AND toAccountID = :toAccountID LIMIT 1");
    $query->execute([":threadID" => $threadID, ":toAccountID" => $id]);
    $invitedBy = $query->fetchColumn();

    $invitesString .= $m["accountID"] . ":" . $m["userID"] . ":" . ExploitPatch::url_base64_encode($m["userName"])
        . ":" . $invitedBy
        . ":" . $m["icon"] . ":" . $m["color1"] . ":" . $m["color2"] . ":" . $m["color3"]
        . ":" . $m["iconType"] . ":" . $m["special"]
        . ":" . $m["stars"] . ":" . $m["moons"] . ":" . $m["demons"]
        . ":" . $m["coins"] . ":" . $m["userCoins"] . ":" . $m["diamonds"]
        . ":" . $m["friendsSince"] . "|";
}
$invitesString = rtrim($invitesString, "|");

echo $membersString . "#" . $formerString . "#" . $invitesString;
?>