<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if(!$person['success']) exit(Dashboard::renderToast("xmark", Dashboard::string("errorLoginRequired"), "error", "account/login", "box"));

if(isset($_POST['levelID'])) {
	$levelID = Escape::number($_POST['levelID']);
	if(empty($levelID)) exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
	
	$deleteLevel = Library::deleteLevel($levelID, $person);
	if(!$deleteLevel) exit(Dashboard::renderToast("xmark", Dashboard::string("errorCantDeleteLevel"), "error"));
	
	exit(Dashboard::renderToast("check", Dashboard::string("successDeletedLevel"), "success", 'browse/levels', "list"));
}

exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
?>