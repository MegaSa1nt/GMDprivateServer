<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if(!$person['success']) exit(Dashboard::renderToast("xmark", Dashboard::string("errorLoginRequired"), "error", "account/login"));

if(isset($_POST['levelID']) && isset($_POST['comment'])) {
	$levelID = Escape::number($_POST['levelID']);
	$comment = Escape::text($_POST['comment']);
	if(empty($levelID) || empty($comment)) exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
	
	$ableToComment = Library::isAbleToComment($levelID, $person, $comment);
	if(!$ableToComment['success']) {
		switch($ableToComment['error']) {
			case CommonError::Banned:
				exit(Dashboard::renderToast("gavel", sprintf(Dashboard::string("bannedToast"), Escape::url_base64_decode($ableToComment['info']['reason']), '<text dashboard-date="'.$ableToComment['info']['expires'].'"></text>'), "error"));
			case CommonError::Filter:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorBadComment"), "error"));
			case CommonError::Automod:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorCommentingIsDisabled"), "error"));
			default:
				exit(Dashboard::renderToast("xmark", Dashboard::string(($levelID > 0 ? 'errorLevelCommentingIsDisabled' : 'errorListCommentingIsDisabled')), "error"));
		}
	}
	
	Library::uploadComment($person, $levelID, $comment, 0);
	
	exit(Dashboard::renderToast("check", Dashboard::string("successUploadedComment"), "success", '@mode=0&page=REMOVE_QUERY'));
}

exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
?>