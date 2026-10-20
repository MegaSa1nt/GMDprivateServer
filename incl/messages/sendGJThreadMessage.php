<?php
chdir(dirname(__FILE__));
require "../lib/connection.php";
require_once "../lib/GJPCheck.php";
require_once "../lib/exploitPatch.php";
require_once "../lib/XORCipher.php";
require_once "../lib/mainLib.php";
require_once "../lib/automod.php";
$gs = new mainLib();

if(Automod::isAccountsDisabled(3)) exit("##P##t=".ExploitPatch::url_base64_encode("Forbidden").":b=".ExploitPatch::url_base64_encode("You cannot send messages for now.")."##P##-1");

$accountID = GJPCheck::getAccountIDOrDie();
$threadID = ExploitPatch::number($_POST["threadID"]) ?: 0;
$body = trim(ExploitPatch::rucharclean(ExploitPatch::url_base64_decode($_POST["body"]), 300));
$subject = trim(ExploitPatch::rucharclean(ExploitPatch::url_base64_decode($_POST["subject"]), 64));
$description = trim(ExploitPatch::rucharclean(ExploitPatch::url_base64_decode($_POST["description"]), 300));
$emote = trim(ExploitPatch::charclean(ExploitPatch::url_base64_decode($_POST["emote"]), 100));
$targets = ExploitPatch::numbercolon(ExploitPatch::url_base64_decode($_POST["targets"]));
$toAccountID = ExploitPatch::number($_POST["toAccountID"]) ?: 0;
$isDMThread = $_POST["isDM"] == 1;
$canAddWithoutInvite = $gs->checkPermission($accountID, "gameAddMembersWithoutInvite");

$thread = $gs->getThreadByID($threadID);
if(!$thread) {
	if($threadID) exit("##P##t=".ExploitPatch::url_base64_encode("Unknown thread").":b=".ExploitPatch::url_base64_encode("This thread does not exist.")."##P##-1");
	
	$accountsArray = [];
	$friends = $gs->getFriends($accountID);
	
	if($isDMThread) {
		if($account["accountID"] == $accountID) exit("##P##t=".ExploitPatch::url_base64_encode("Forbidden").":b=".ExploitPatch::url_base64_encode("You cannot create a conversation with yourself.")."##P##-1");
		
		$account = $db->prepare("SELECT * FROM accounts WHERE accountID = :accountID");
		$account->execute([":accountID" => $accountID]);
		$account = $account->fetch();
		
		if($account["mS"] == 2) exit("##P##t=".ExploitPatch::url_base64_encode("Forbidden").":b=".ExploitPatch::url_base64_encode("You cannot create a conversation with this player.")."##P##-1");
		
		$accountsArray[] = $account["accountID"];
		if(in_array($account["accountID"], $friends)) $canAddWithoutInvite = true;
	} else {
		$accountsCount = 0;
		
		$accounts = $db->prepare("SELECT * FROM accounts WHERE accountID IN (".$targets.")");
		$accounts->execute();
		$accounts = $accounts->fetchAll();
		
		foreach($accounts AS &$account) {
			if($accountsCount >= 100) break;
				
			switch(true) {
				case $account["gF"] == 1 && $account["gO"] == 2:
					$privacySettings = 1;
					break;
				case $account["gF"] == 2 && $account["gO"] == 2:
					$privacySettings = 2;
					break;
				default:
					$privacySettings = 0;
					break;
			}
			
			if($account["accountID"] == $accountID || ($privacySettings == 1 && !in_array($account["accountID"], $friends)) || $privacySettings == 2) continue;
			
			$accountsCount++;
			$accountsArray[] = $account["accountID"];
		}
	}
	
	$accountsString = trim(implode(",", $accountsArray), ",");
	if(empty($accountsString)) exit("##P##t=".ExploitPatch::url_base64_encode("Select members").":b=".ExploitPatch::url_base64_encode("You haven't selected any members or you can't invite them.")."##P##-1");
	
	if($isDMThread) {
		$body = ExploitPatch::url_base64_encode(XORCipher::cipher($body, 14251));
		
		$threadID = $gs->createDMThread($accountID, $toAccountID, $body, $canAddWithoutInvite);
	}
	else $threadID = $gs->createGroupThread($accountID, ExploitPatch::url_base64_encode($subject), ExploitPatch::url_base64_encode($description), $emote, $accountsString, $canAddWithoutInvite);
	
	if(!$threadID) exit("##P##t=".ExploitPatch::url_base64_encode("Forbidden").":b=".ExploitPatch::url_base64_encode("You cannot send messages here.")."##P##-1");
	
	exit($threadID.":0");
}

$threadMembers = explode(",", $thread["threadMembers"]);
if(!in_array($accountID, $threadMembers) || $thread["isLocked"] || ($thread["isDMThread"] && $accountID == $thread["threadMembers"])) exit("##P##t=".ExploitPatch::url_base64_encode("Forbidden").":b=".ExploitPatch::url_base64_encode("You cannot send messages here.")."##P##-1");

if(!$thread["isDMThread"]) {
	$isThreadMessage = true;
	$targetAccountID = $threadID;
} else {
	$isThreadMessage = false;
	$targetAccountID = $threadMembers[0] == $accountID ? $threadMembers[1] : $threadMembers[0];
}

$body = ExploitPatch::url_base64_encode(XORCipher::cipher($body, 14251));

$sendMessage = $gs->sendMessage($accountID, $targetAccountID, "", $body, $isThreadMessage);

exit($sendMessage["threadID"].":".$sendMessage["messageID"]);
?>