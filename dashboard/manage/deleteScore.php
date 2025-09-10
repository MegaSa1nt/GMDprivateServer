<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if(!$person['success']) exit(Dashboard::renderToast("xmark", Dashboard::string("errorLoginRequired"), "error", "account/login", "box"));

if(isset($_POST['scoreID'])) {
	$scoreID = Escape::number($_POST['scoreID']);
	$isPlatformer = abs(Escape::number($_POST['isPlatformer']) ?: 0);
	
	if(empty($scoreID)) exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
	
	$deleteScore = Library::deleteScore($person, $scoreID, $isPlatformer);
	if(!$deleteScore) exit(Dashboard::renderToast("xmark", Dashboard::string("errorCantDeleteScore"), "error"));
	
	exit(Dashboard::renderToast("check", Dashboard::string("successDeletedScore"), "success", '@', "settings"));
}

exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
?>