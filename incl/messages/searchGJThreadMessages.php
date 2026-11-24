<?php
chdir(dirname(__FILE__));
require '../lib/connection.php';
require_once '../lib/GJPCheck.php';
require_once '../lib/exploitPatch.php';
require_once '../lib/XORCipher.php';
require_once '../lib/mainLib.php';
$gs = new mainLib();

$accountID = GJPCheck::getAccountIDOrDie();
$threadID = ExploitPatch::number($_POST['threadID'] ?? 0) ?: 0;
$queryText = trim(ExploitPatch::url_base64_decode($_POST['query'] ?? ''));
$beforeID = ExploitPatch::number($_POST['beforeID'] ?? 0) ?: 0;
$thread = $gs->getThreadByID($threadID);
if(!$thread) exit('-1');

$members = array_filter(explode(',', $thread['threadMembers'] ?? ''));
$invited = array_filter(explode(',', $thread['invitedUsers'] ?? ''));
if(!in_array((string)$accountID, array_map('strval', array_merge($members, $invited)), true)) exit('-1');

if($thread['isDMThread']) {
    $other = count($members) > 1 ? ($members[0] == $accountID ? $members[1] : $members[0]) : ($invited[0] ?? 0);
    $sql = 'SELECT * FROM messages WHERE ((accID = :accountID AND toAccountID = :other) OR (accID = :other AND toAccountID = :accountID)) AND isThreadMessage = 0';
    $params = [':accountID' => $accountID, ':other' => $other];
} else {
    $sql = 'SELECT * FROM messages WHERE toAccountID = :threadID AND isThreadMessage = 1';
    $params = [':threadID' => $threadID];
}
if($beforeID) $sql .= ' AND messageID < ' . $beforeID;
$sql .= ' ORDER BY messageID DESC';
$query = $db->prepare($sql);
$query->execute($params);
$rows = $query->fetchAll(PDO::FETCH_ASSOC);

$matches = [];
foreach($rows as $row) {
    $body = XORCipher::cipher(ExploitPatch::url_base64_decode($row['body']), 14251);
    if($queryText !== '' && stripos($body, $queryText) === false) continue;
    $matches[] = $row['messageID'] . ':' . $row['accID'] . ':' . $row['body'] . ':' . $row['timestamp'] . ':' . $row['isDeleted'];
    if(count($matches) >= 20) break;
}

exit((empty($matches) ? 'none' : implode('|', $matches)) . '#' . max(0, (int)ceil(max(0, count($rows) - count($matches)) / 20)));
?>
