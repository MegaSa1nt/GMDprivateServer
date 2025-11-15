<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if(!$person['success']) exit(Dashboard::renderToast("xmark", Dashboard::string("errorLoginRequired"), "error", "account/login", "box"));

if(!Library::checkPermission($person, "dashboardManageGauntlets")) exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));

if(isset($_POST['gauntletID'])) {
	$gauntletID = Escape::number($_POST['gauntletID']);
	if(empty($gauntletID)) exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
	
	$deleteGauntlet = Library::deleteGauntlet($person, $gauntletID);
	if(!$deleteGauntlet) exit(Dashboard::renderToast("xmark", Dashboard::string("errorCantDeleteGauntlet"), "error"));
	
	exit(Dashboard::renderToast("check", Dashboard::string("successDeletedGauntlet"), "success", 'browse/gauntlets', "list"));
}

exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
?>