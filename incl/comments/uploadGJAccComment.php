<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/commands.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$person = $sec->loginPlayer();
if(!$person["success"]) exit(CommonError::InvalidRequest);
$accountID = $person['accountID'];

$gameVersion = abs(Escape::number($_POST['gameVersion']));
$comment = Escape::text($_POST['comment']);

if(empty($comment)) exit(CommonError::InvalidRequest);

if($gameVersion >= 20) $comment = Escape::url_base64_decode($comment);

$account = Library::getAccountByID($accountID);

$command = Commands::processProfileCommand($comment, $account, $person);
if($command) exit(Library::showCommentsBanScreen($command, 0));

$ableToComment = Library::isAbleToAccountComment($person, $comment);
if(!$ableToComment['success']) {
	switch($ableToComment['error']) {
		case CommonError::Banned:
			exit(Library::showCommentsBanScreen(Escape::translit(Escape::url_base64_decode($ableToComment['info']['reason'])), $ableToComment['info']['expires']));
		case CommonError::Filter:
			exit(Library::showCommentsBanScreen("Your post contains a ".Library::textColor("bad", Color::Red)." word.", 0));
		case CommonError::Automod:
			exit(Library::showCommentsBanScreen("Posting account comments is currently ".Library::textColor("disabled", Color::Red).".", 0));
		default:
			exit(Library::showCommentsBanScreen("Unknown error has occured.", 0));
	}
}

Library::uploadAccountComment($person, $comment);
exit(CommonError::Success); 
?>