<?php
chdir(dirname(__FILE__));
require "../lib/connection.php";
require_once "../lib/GJPCheck.php";
require_once "../lib/exploitPatch.php";
require_once "../lib/XORCipher.php";
require_once "../lib/mainLib.php";
$gs = new mainLib();

$accountID = GJPCheck::getAccountIDOrDie();
$threadID = ExploitPatch::number($_POST["threadID"]) ?: 0;
$beforeID = ExploitPatch::number($_POST["beforeID"]) ?: 0;
$afterID = ExploitPatch::number($_POST["afterID"]) ?: 0;
$mv = isset($_POST["mv"]);
$showLoadEarlier = false;

$messagesArray = $userIDsArray = $usersArray = $threadMembersArray = $formerMembersArray = $invitedMembersArray = $threadArray = [];
$userIDsString = "";

$thread = $gs->getThreadByID($threadID);
if(!$thread) exit("##P##t=".ExploitPatch::url_base64_encode("Unknown thread").":b=".ExploitPatch::url_base64_encode("This thread does not exist.")."##P##-1");

$threadMembersIDsArray = explode(",", $thread["threadMembers"]);
if(!in_array($accountID, $threadMembersIDsArray)) exit("##P##t=".ExploitPatch::url_base64_encode("Forbidden").":b=".ExploitPatch::url_base64_encode("You are not member of this thread.")."##P##-1");

if($thread["isDMThread"]) {
	$threadMembers = explode(",", $thread["threadMembers"]);
	$threadReceiver = $threadMembers[0] == $accountID ? $threadMembers[1] : $threadMembers[0];
	
	$messages = $db->prepare("SELECT * FROM messages WHERE ((accID = :accountID AND toAccountID = :receiver) OR (accID = :receiver AND toAccountID = :accountID)) AND isThreadMessage = 0 ".($beforeID > 0 ? "AND messageID < ".$beforeID : "")." ".($afterID > 0 ? "AND messageID > ".$afterID : "")." ORDER BY messageID DESC LIMIT ".($mv ? 10 : 50));
	$messages->execute([':accountID' => $accountID, ':receiver' => $threadReceiver]);
	$messages = $messages->fetchAll();
	
	$messagesCount = $db->prepare("SELECT messageID, count(*) AS messagesCount FROM messages WHERE ((accID = :accountID AND toAccountID = :receiver) OR (accID = :receiver AND toAccountID = :accountID)) AND isThreadMessage = 0 ORDER BY messageID ASC");
	$messagesCount ->execute([':accountID' => $accountID, ':receiver' => $threadReceiver]);
	$messagesCount = $messagesCount->fetch();
} else {
	$messages = $db->prepare("SELECT * FROM messages WHERE toAccountID = :threadID AND isThreadMessage = 1 ".($beforeID > 0 ? "AND messageID < ".$beforeID : "")." ".($afterID > 0 ? "AND messageID > ".$afterID : "")." ORDER BY messageID DESC LIMIT ".($mv ? 10 : 50));
	$messages->execute([':threadID' => $threadID]);
	$messages = $messages->fetchAll();
	
	$messagesCount = $db->prepare("SELECT messageID, count(*) AS messagesCount FROM messages WHERE toAccountID = :threadID AND isThreadMessage = 1 ORDER BY messageID ASC");
	$messagesCount ->execute([':threadID' => $threadID]);
	$messagesCount = $messagesCount->fetch();
}

$messages = array_reverse($messages);

foreach($messages AS &$message) {
	if($message["isDeleted"] && $message["accID"] != $accountID) continue;
		
	$messageArray = [];
	$messageContent = XORCipher::cipher(ExploitPatch::url_base64_decode($message["body"]), 14251);
	
	if($message["accID"] == 0) {
		$messageContentArray = explode(",", $messageContent);
		$usersToGet = [];
		
		if(!isset($usersArray[$messageContentArray[1]])) $usersToGet[] = $messageContentArray[1];
		if(isset($messageContentArray[3]) && !isset($usersArray[$messageContentArray[3]])) $usersToGet[] = $messageContentArray[3];
		
		if(!empty($usersToGet)) {
			$users = $db->prepare("SELECT * FROM users WHERE extID IN (".implode(",", $usersToGet).")");
			$users->execute();
			$users = $users->fetchAll();
			
			foreach($users AS &$user) $usersArray[$user["extID"]] = $user;
		}
		
		$messageContentArray[2] = $usersArray[$messageContentArray[1]]["userName"] ?: "Undefined";
		if(isset($messageContentArray[3])) $messageContentArray[4] = $usersArray[$messageContentArray[3]]["userName"] ?: "Undefined";
		
		$messageContent = implode(",", $messageContentArray);
	}
	
	$messageArray[0] = $message["messageID"];
	$messageArray[1] = $message["accID"];
	$messageArray[2] = ExploitPatch::url_base64_encode($messageContent);
	$messageArray[3] = $message["timestamp"];
	$messageArray[4] = $message["isDeleted"];
	
	$messagesArray[] = implode(":", $messageArray);
}

if(!$mv) {
	if($thread["threadMembers"]) $userIDsArray[] = $thread["threadMembers"];
	if($thread["invitedUsers"]) $userIDsArray[] = $thread["invitedUsers"];
	if($thread["formerMembers"]) $userIDsArray[] = $thread["formerMembers"];
	$userIDsString = trim(implode(",", $userIDsArray), ",");

	if($userIDsString) {
		$users = $db->prepare("SELECT * FROM users WHERE extID IN (".$userIDsString.")");
		$users->execute();
		$users = $users->fetchAll();
		
		foreach($users AS &$user) $usersArray[$user["extID"]] = $user;
	}

	if($thread["threadMembers"]) {
		foreach($threadMembersIDsArray AS &$memberID) {
			$user["userName"] = $gs->makeClanUsername($user);
			$user = $usersArray[$memberID];
			
			$threadMembersArray[] = "1:".$user["userName"].":2:".$user["userID"].":9:".$user["icon"].":10:".$user["color1"].":11:".$user["color2"].":51:".$user["color3"].":14:".$user["iconType"].":15:".$user["special"].":16:".$user["extID"].":3:".$user["stars"].":52:".$user["moons"].":4:".$user["demons"].":13:".$user["coins"].":17:".$user["userCoins"].":46:".$user["diamonds"];
		}
	}

	if($thread["formerMembers"]) {
		$formerMembersIDsArray = explode(",", $thread["formerMembers"]);
		
		foreach($formerMembersIDsArray AS &$user) {
			$formerMemberArray = [];
			$user = $usersArray[$memberID];
			
			$formerMemberArray[0] = $user["extID"];
			$formerMemberArray[1] = $user["userID"];
			$formerMemberArray[2] = ExploitPatch::url_base64_encode($user["userName"]);
			$formerMemberArray[3] = $user["icon"];
			$formerMemberArray[4] = $user["color1"];
			$formerMemberArray[5] = $user["color2"];
			$formerMemberArray[6] = $user["color3"];
			$formerMemberArray[7] = $user["iconType"];
			$formerMemberArray[8] = $user["special"];
			
			$formerMembersArray[] = implode(":", $formerMemberArray);
		}
	}

	if($thread["invitedUsers"]) {
		$friendships = $gs->getFriendships($accountID);
		
		$invitedUsersIDsArray = explode(",", $thread["invitedUsers"]);
		
		foreach($invitedUsersIDsArray AS &$memberID) {
			$invitedMemberArray = [];
			$user = $usersArray[$memberID];
			
			$invitedMemberArray[0] = $user["extID"];
			$invitedMemberArray[1] = $user["userID"];
			$invitedMemberArray[2] = ExploitPatch::url_base64_encode($user["userName"]);
			$invitedMemberArray[3] = $thread["threadOwner"];
			$invitedMemberArray[4] = $user["icon"];
			$invitedMemberArray[5] = $user["color1"];
			$invitedMemberArray[6] = $user["color2"];
			$invitedMemberArray[7] = $user["color3"];
			$invitedMemberArray[8] = $user["iconType"];
			$invitedMemberArray[9] = $user["special"];
			$invitedMemberArray[10] = $user["stars"];
			$invitedMemberArray[11] = $user["moons"];
			$invitedMemberArray[12] = $user["demons"];
			$invitedMemberArray[13] = $user["coins"];
			$invitedMemberArray[14] = $user["userCoins"];
			$invitedMemberArray[15] = $user["diamonds"];
			$invitedMemberArray[16] = $friendships[$user["extID"]] ?: 0;
			
			$invitedMembersArray[] = implode(":", $invitedMemberArray);
		}
	}
	
	if($messages[0]["messageID"] != $messagesCount["messageID"]) $showLoadEarlier = true;

	$threadArray[0] = $thread["threadID"];
	$threadArray[1] = "0";
	$threadArray[2] = count($threadMembersArray);
	$threadArray[3] = $thread["threadDesc"];
	$threadArray[4] = "0"; // Key `mv`, GD sends it when it refreshes messages
	$threadArray[5] = $showLoadEarlier ? "1" : "0";
	$threadArray[6] = "0";
	$threadArray[7] = "0";
	$threadArray[8] = $thread["threadName"];
	$threadArray[9] = $thread["isLocked"];
	$threadArray[10] = "0";
}

$messagesString = implode("|", $messagesArray) ?: "none";
$threadMembersString = implode("|", $threadMembersArray);
$formerMemberString = implode("|", $formerMembersArray);
$invitedMembersString = implode("|", $invitedMembersArray);
$threadString = implode(":", $threadArray);

$messagesRefreshInterval = 3; // How often GD retrieves messages, min value is 3
exit($messagesString."#".$threadMembersString."#".$formerMemberString."#".$invitedMembersString."#".$threadString."#0#".$messagesRefreshInterval.",1,10,50");
?>