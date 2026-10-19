<?php
chdir(dirname(__FILE__));
require "../lib/connection.php";
require_once "../lib/GJPCheck.php";
require_once "../lib/exploitPatch.php";
require_once "../lib/mainLib.php";
$gs = new mainLib();

$accountID = GJPCheck::getAccountIDOrDie();
$invitesArray = [];

$invites = $db->prepare("SELECT *, threadinvites.timestamp AS inviteTimestamp FROM threadinvites INNER JOIN threads ON threadinvites.threadID = threads.threadID INNER JOIN users ON users.extID = threadinvites.accountID WHERE toAccountID = :accountID AND threadinvites.timestamp >= :timestamp ORDER BY threadinvites.timestamp DESC");
$invites->execute([":accountID" => $accountID, ":timestamp" => time() - 1209600]);
$invites = $invites->fetchAll();

foreach($invites AS &$invite) {
	$inviteArray = [];
	
	$inviteArray[0] = $invite["threadID"];
	$inviteArray[1] = $invite["accountID"];
	$inviteArray[2] = ExploitPatch::url_base64_encode($invite["userName"]);
	$inviteArray[3] = $invite["threadName"];
	$inviteArray[4] = count(explode(",", $invite["threadMembers"]));
	$inviteArray[5] = $gs->makeTime($thread["inviteTimestamp"]);
	$inviteArray[6] = "";
	$inviteArray[7] = "";
	$inviteArray[8] = $invite["threadDesc"];
	$inviteArray[9] = $invite["isDMThread"];
	$inviteArray[10] = $invite["isIgnored"];
	$inviteArray[11] = $invite["icon"];
	$inviteArray[12] = $invite["color1"];
	$inviteArray[13] = $invite["color2"];
	$inviteArray[14] = $invite["color3"];
	$inviteArray[15] = $invite["iconType"];
	$inviteArray[16] = $invite["special"];
	$inviteArray[17] = $invite["threadIcon"];
	$inviteArray[18] = $thread["inviteTimestamp"];
	
	$invitesArray[] = implode(":", $inviteArray);
}

exit(implode("|", $invitesArray) ?: "-2");
?>