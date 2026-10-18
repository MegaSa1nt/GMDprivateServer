<?php
chdir(dirname(__FILE__));
require "../lib/connection.php";
require_once "../lib/GJPCheck.php";
require_once "../lib/exploitPatch.php";
require_once "../lib/mainLib.php";
$gs = new mainLib();

$accountID = GJPCheck::getAccountIDOrDie();
$search = ExploitPatch::charclean(ExploitPatch::url_base64_decode($_POST["search"]));
$filter = ExploitPatch::number($_POST["filter"]) ?: 0;
$filterArray = ["", "isDMThread = 1", "isDMThread = 0"];

$threadsArray = [];
$page = ExploitPatch::number($_POST["page"]) ?: 0;
$offset = $page * 20;

$gs->syncAccountDMThreads($accountID);

$threads = $db->prepare("SELECT *, messages.timestamp AS lastMessageTimestamp FROM threads INNER JOIN messages ON threads.lastMessageID = messages.messageID ".($filterArray[$filter] ? " WHERE ".$filterArray[$filter] : "")." ORDER BY lastMessageTimestamp DESC");
$threads->execute();
$threads = $threads->fetchAll();

foreach($threads AS &$thread) {
	$threadArray = $usersArray = [];
	$lastMessageSenderID = $thread["accID"];
	for($i = 0; $i < 26; $i++) $threadArray[$i] = "";
	
	$users = $db->prepare("SELECT * FROM users WHERE extID IN (".$thread["threadMembers"].")");
	$users->execute();
	$users = $users->fetchAll();
	foreach($users AS &$user) {
		$usersArray[$user["extID"]] = $user;
	}

	if($thread["isDMThread"]) {
		$threadMembers = explode(",", $thread["threadMembers"]);
		$threadReceiver = $threadMembers[0] == $accountID ? $threadMembers[1] : $threadMembers[0];
		
		$threadArray[0] = $thread["threadID"];
		$threadArray[1] = $threadReceiver;
		$threadArray[2] = ExploitPatch::url_base64_encode($usersArray[$threadReceiver]["userName"]);
		$threadArray[4] = $thread["newMessagesCount"];
		$threadArray[5] = $lastMessageSenderID;
		$threadArray[6] = $gs->makeTime($thread["lastMessageTimestamp"]);
		$threadArray[7] = "2"; // Thread members
		$threadArray[8] = ExploitPatch::url_base64_encode($usersArray[$lastMessageSenderID]["userName"]);
		$threadArray[9] = $accountID;
		$threadArray[10] = "0"; // Is thread muted
		$threadArray[12] = "1"; // Thread type
		$threadArray[14] = "0"; // Is thread pinned
		$threadArray[15] = $usersArray[$threadReceiver]["icon"];
		$threadArray[16] = $usersArray[$threadReceiver]["color1"];
		$threadArray[17] = $usersArray[$threadReceiver]["color2"];
		$threadArray[18] = $usersArray[$threadReceiver]["color3"];
		$threadArray[19] = $usersArray[$threadReceiver]["iconType"];
		$threadArray[20] = $usersArray[$threadReceiver]["special"];
		$threadArray[23] = $thread["lastMessageTimestamp"];
		$threadArray[24] = $gs->getMaxValuePermission($threadReceiver, "modBadgeLevel");
		$threadArray[25] = "0"; // Thread status
	}
	
	/*
	$modBadgeLevel = $gs->getMaxValuePermission($thread["receiverID"], "modBadgeLevel");

	$threadArray[0] = $thread["receiverID"];
	$threadArray[1] = $thread["receiverID"];
	$threadArray[2] = ExploitPatch::url_base64_encode($thread["userName"]);
	$threadArray[3] = "";
	$threadArray[4] = $thread["newMessagesCount"];
	$threadArray[5] = $accountID;
	$threadArray[6] = $gs->makeTime($thread["timestamp"]);
	$threadArray[7] = "2";
	$threadArray[8] = "";
	$threadArray[9] = $accountID;
	$threadArray[10] = "0";
	$threadArray[11] = "";
	$threadArray[12] = "1";
	$threadArray[13] = "";
	$threadArray[14] = "0";
	$threadArray[15] = $thread["icon"];
	$threadArray[16] = $thread["color1"];
	$threadArray[17] = $thread["color2"];
	$threadArray[18] = $thread["color3"];
	$threadArray[19] = $thread["iconType"];
	$threadArray[20] = $thread["special"];
	$threadArray[21] = "";
	$threadArray[22] = "";
	$threadArray[23] = $thread["timestamp"];
	$threadArray[24] = $modBadgeLevel;
	$threadArray[25] = "0";
	*/
	
	$threadsArray[] = implode(":", $threadArray);
}

$threadsString = implode("|", $threadsArray);

$threadsCount = $db->prepare("SELECT count(*) FROM threads ".($filterArray[$filter] ? " WHERE ".$filterArray[$filter] : "")."");
$threadsCount->execute();
$threadsCount = $threadsCount->fetchColumn();

$newInvitesCount = 0;
$refreshThreadsInterval = 10;
exit($threadsString."#".$threadsCount.":".$offset.":20:".$newInvitesCount.":".$refreshThreadsInterval);
?>