<?php
chdir(dirname(__FILE__));
require "../lib/connection.php";
require_once "../lib/GJPCheck.php";
require_once "../lib/exploitPatch.php";
require_once "../lib/mainLib.php";
$gs = new mainLib();

$accountID = GJPCheck::getAccountIDOrDie();
$mode = ExploitPatch::number($_POST["mode"]) ?: 0;
$threadID = ExploitPatch::number($_POST["threadID"]) ?: 0;
$value = ExploitPatch::rucharclean(ExploitPatch::url_base64_decode($_POST["value"])) ?: "";

$thread = $gs->getThreadByID($threadID);
if(!$thread && $mode != 3) exit("-1");

switch($mode) {
	case 2:
		$value = ExploitPatch::url_base64_encode(ExploitPatch::translit($value));
		
		$renameThread = $gs->changeThreadName($accountID, $thread, $value);
		if($renameThread) exit("1");
		
		break;
	case 4:
		$value = ExploitPatch::numbercolon($value);
		
		$inviteMembers = $gs->inviteMembersToThread($accountID, $threadID, $value);
		if($inviteMembers) exit("1");
		
		break;
	case 8:
		$acceptInvite = $gs->acceptThreadInvite($accountID, $thread);
		if($acceptInvite) exit("1");
		
		break;
	case 9:
		$ignoreInvite = $gs->ignoreThreadInvite($accountID, $thread);
		if($ignoreInvite) exit("1");
		
		break;
	case 12:
		$value = ExploitPatch::number($value);
		
		$deleteThreadMessage = $gs->deleteThreadMessage($accountID, $thread, $value);
		if($deleteThreadMessage) exit("1");
		
		break;
	case 13:
		$value = $value == 1 ? 1 : 0;
		
		$setThreadInvitePermissions = $gs->setThreadInvitePermissions($accountID, $thread, $value);
		if($setThreadInvitePermissions) exit("1");
		
		break;
	case 14:
		$value = ExploitPatch::url_base64_encode($value, 300);
		
		$changeThreadDescription = $gs->changeThreadDescription($accountID, $thread, $value);
		if($changeThreadDescription) exit("1");
		
		break;
	case 16:
		$ignoreInvite = $gs->changeThreadIcon($accountID, $thread, $value);
		if($ignoreInvite) exit("1");
		
		break;
}

exit("-1");
?>