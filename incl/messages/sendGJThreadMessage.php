<?php
chdir(dirname(__FILE__));
require "../lib/connection.php";
require_once "../lib/GJPCheck.php";
require_once "../lib/exploitPatch.php";
require_once "../lib/XORCipher.php";
require_once "../lib/mainLib.php";
require_once "../lib/automod.php";
$gs = new mainLib();

if(Automod::isAccountsDisabled(3)) exit('-1');

$accountID = GJPCheck::getAccountIDOrDie();
$threadID = ExploitPatch::number($_POST["threadID"]) ?: 0;
$body = trim(ExploitPatch::rucharclean(ExploitPatch::url_base64_decode($_POST["body"])));

$thread = $gs->getThreadByID($threadID);
if(!$thread) exit("##P##t=".ExploitPatch::url_base64_encode("Unknown thread").":b=".ExploitPatch::url_base64_encode("This thread does not exist.")."##P##-1");

$threadMembers = explode(",", $thread["threadMembers"]);
if(!in_array($accountID, $threadMembers) || $thread["isLocked"]) exit("##P##t=".ExploitPatch::url_base64_encode("Forbidden").":b=".ExploitPatch::url_base64_encode("You cannot send messages here.")."##P##-1");

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