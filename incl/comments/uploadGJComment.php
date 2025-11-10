<?php
require __DIR__."/../../config/misc.php";
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/commands.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$person = $sec->loginPlayer();
if(!$person["success"]) exit(CommonError::InvalidRequest);

$levelID = Escape::number($_POST['levelID']);
$gameVersion = abs(Escape::number($_POST['gameVersion']));
$comment = Escape::base64($_POST['comment']);
$percent = Escape::number($_POST['percent']) ?: 0;

if(empty($comment)) exit(CommonError::InvalidRequest);

if($gameVersion >= 20) $comment = Escape::url_base64_decode($comment);
$comment = Escape::text($comment, ($enableCommentLengthLimiter ? $maxCommentLength : 0));

if($levelID > 0) {
	$level = Library::getLevelByID($levelID);
	if(!$level) exit(CommonError::InvalidRequest);

	$command = Commands::processLevelCommand($comment, $level, $person);
	if($command) exit(Library::showCommentsBanScreen($command, 0));
} else {
	$listID = $levelID * -1;
	$list = Library::getListByID($listID);
	if(!$list) exit(CommonError::InvalidRequest);
	
	$command = Commands::processListCommand($comment, $list, $person);
	if($command) exit(Library::showCommentsBanScreen($command, 0));
}

$ableToComment = Library::isAbleToComment($levelID, $person, $comment);
if(!$ableToComment['success']) {
	switch($ableToComment['error']) {
		case CommonError::Banned:
			exit(Library::showCommentsBanScreen(Escape::translit(Escape::url_base64_decode($ableToComment['info']['reason'])), $ableToComment['info']['expires']));
		case CommonError::Filter:
			exit(Library::showCommentsBanScreen("Your comment contains a ".Library::textColor("bad", Color::Red)." word.", 0));
		case CommonError::Automod:
			exit(Library::showCommentsBanScreen("Commenting is currently ".Library::textColor("disabled", Color::Red).".", 0));
		default:
			exit(Library::showCommentsBanScreen("Commenting on this ".($levelID > 0 ? 'level' : 'list')." is currently ".Library::textColor("disabled", Color::Red)."!", 0));
	}
}

Library::uploadComment($person, $levelID, $comment, $percent);

exit(CommonError::Success); 
?>