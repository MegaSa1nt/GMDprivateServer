<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if(!$person['success']) exit(Dashboard::renderToast("xmark", Dashboard::string("errorLoginRequired"), "error", "account/login", "box"));
$accountID = $person["accountID"];

$sfxID = Escape::number($_POST['sfxID']);

$sfx = Library::getSFXByID($sfxID);
if(!$sfx || !$sfx['reuploadID']) exit(Dashboard::renderToast("xmark", Dashboard::string("errorSFXNotFound"), "error"));

if(($sfx["reuploadID"] == $accountID || Library::checkPermission($person, "dashboardManageSongs")) && isset($_POST['sfxID']) && isset($_POST['sfxTitle'])) {
	$sfxTitle = Escape::text($_POST['sfxTitle'], 35) ?: '';
	
	$sfxIsDisabled = isset($_POST['sfxEnabled']) ? 0 : 1;
	
	Library::changeSFX($person, $sfxID, $sfxTitle, $sfxIsDisabled);
	
	exit(Dashboard::renderToast("check", Dashboard::string("successAppliedSettings"), "success", '@', "box"));
}

exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
?>