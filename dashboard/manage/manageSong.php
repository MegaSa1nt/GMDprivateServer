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
	
	$changeSong = Library::changeSong($person, $songID, $songArtist, $songTitle, $songIsDisabled);
	if(!$changeSong['success']) {
		switch($changeSong['error']) {
			case SongError::NothingFound:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorSongNotFound"), "error"));
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