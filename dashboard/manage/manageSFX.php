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
	
	$changeSFX = Library::changeSFX($person, $sfxID, $sfxTitle, $sfxIsDisabled);
	if(!$changeSFX['success']) {
		switch($changeSFX['error']) {
			case SongError::NothingFound:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorSFXNotFound"), "error"));
			case SongError::Banned:
				exit(Dashboard::renderToast("gavel", sprintf(Dashboard::string("bannedToast"), htmlspecialchars(Escape::url_base64_decode($uploadSong['info']['reason'])), '<text dashboard-date="'.$uploadSong['info']['expires'].'"></text>'), "error"));
			case SongError::BadSongArtist:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorBadArtist"), "error"));
			case SongError::BadSongTitle:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorBadName"), "error"));
			case SongError::NoPermissions:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorNoUsagePermission"), "error"));
			default:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
		}
	}
	
	exit(Dashboard::renderToast("check", Dashboard::string("successAppliedSettings"), "success", '@', "box"));
}

exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
?>