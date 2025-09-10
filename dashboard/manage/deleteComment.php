<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if(!$person['success']) exit(Dashboard::renderToast("xmark", Dashboard::string("errorLoginRequired"), "error", "account/login", "box"));

if(isset($_POST['commentID'])) {
	$commentID = Escape::number($_POST['commentID']);
	if(empty($commentID)) exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
	
	$deleteComment = Library::deleteComment($person, $commentID);
	if(!$deleteComment) exit(Dashboard::renderToast("xmark", Dashboard::string("errorCantDeleteComment"), "error"));
	
	exit(Dashboard::renderToast("check", Dashboard::string("successDeletedComment"), "success", '@', "settings"));
}

exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
?>