<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if(!$person['success']) exit(Dashboard::renderToast("xmark", Dashboard::string("errorLoginRequired"), "error", "account/login", "box"));

if(isset($_POST['comment'])) {
	$comment = Escape::text($_POST['comment'], ($enableCommentLengthLimiter ? $maxAccountCommentLength : 0));
	if(empty($comment)) exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
	
	$ableToComment = Library::isAbleToAccountComment($person, $comment);
	if(!$ableToComment['success']) {
		switch($ableToComment['error']) {
			case CommonError::Banned:
				exit(Dashboard::renderToast("gavel", sprintf(Dashboard::string("bannedToast"), htmlspecialchars(Escape::url_base64_decode($ableToComment['info']['reason'])), '<text dashboard-date="'.$ableToComment['info']['expires'].'"></text>'), "error"));
			case CommonError::Filter:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorBadPost"), "error"));
			case CommonError::Automod:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorPostingIsDisabled"), "error"));
			default:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
		}
	}
	
	Library::uploadAccountComment($person, $comment);
	
	exit(Dashboard::renderToast("check", Dashboard::string("successUploadedPost"), "success", '@mode=REMOVE_QUERY&page=REMOVE_QUERY', "settings"));
}

exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
?>