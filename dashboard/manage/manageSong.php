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

$songID = Escape::number($_POST['songID']);

$song = Library::getSongByID($songID);
if(!$song) exit(Dashboard::renderToast("xmark", Dashboard::string("errorSongNotFound"), "error"));

if(($song["reuploadID"] == $accountID || Library::checkPermission($person, "dashboardManageSongs")) && isset($_POST['songID']) && isset($_POST['songAuthor']) && isset($_POST['songTitle'])) {
	$songArtist = Escape::text($_POST['songAuthor'], 40) ?: '';
	$songTitle = Escape::text($_POST['songTitle'], 35) ?: '';
	
	$songIsDisabled = isset($_POST['songEnabled']) ? 0 : 1;
	
	Library::changeSong($person, $songID, $songArtist, $songTitle, $songIsDisabled);
	
	exit(Dashboard::renderToast("check", Dashboard::string("successAppliedSettings"), "success", '@', "box"));
}

exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
?>