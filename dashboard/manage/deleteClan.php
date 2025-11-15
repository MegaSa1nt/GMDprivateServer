<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if(!$person['success']) exit(Dashboard::renderToast("xmark", Dashboard::string("errorLoginRequired"), "error", "account/login", "box"));

if(isset($_POST['clanID'])) {
	$clanID = Escape::number($_POST['clanID']);
	if(empty($clanID)) exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
	
	$deleteClan = Library::deleteClan($person, $clanID);
	if(!$deleteClan) exit(Dashboard::renderToast("xmark", Dashboard::string("errorCantDeleteClan"), "error"));
	
	exit(Dashboard::renderToast("check", Dashboard::string("successDeletedClan"), "success", 'browse/clans', "list"));
}

exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
?>