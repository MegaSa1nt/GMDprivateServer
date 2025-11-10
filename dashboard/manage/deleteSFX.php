<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if(!$person['success']) exit(Dashboard::renderToast("xmark", Dashboard::string("errorLoginRequired"), "error", "account/login", "box"));

if(isset($_POST['sfxID'])) {
	$sfxID = Escape::number($_POST['sfxID']);
	if(empty($sfxID)) exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
	
	$deleteSFX = Library::deleteSFX($person, $sfxID);
	if(!$deleteSFX) exit(Dashboard::renderToast("xmark", Dashboard::string("errorCantDeleteSFX"), "error"));
	
	exit(Dashboard::renderToast("check", Dashboard::string("successDeletedSFX"), "success", 'browse/sfxs', "list"));
}

exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
?>