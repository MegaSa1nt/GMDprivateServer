<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if(!$person['success']) exit(Dashboard::renderToast("xmark", Dashboard::string("errorLoginRequired"), "error", "account/login", "box"));

if(!Library::checkPermission($person, "dashboardManageMapPacks")) exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));

if(isset($_POST['mapPackID'])) {
	$mapPackID = Escape::number($_POST['mapPackID']);
	if(empty($mapPackID)) exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
	
	$deleteMapPack = Library::deleteMapPack($person, $mapPackID);
	if(!$deleteMapPack) exit(Dashboard::renderToast("xmark", Dashboard::string("errorCantDeleteMapPack"), "error"));
	
	exit(Dashboard::renderToast("check", Dashboard::string("successDeletedMapPack"), "success", 'browse/mappacks', "list"));
}

exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
?>