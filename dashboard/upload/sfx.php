<?php
require __DIR__."/../../config/dashboard.php";
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if(!$person['success']) exit(Dashboard::renderErrorPage(Dashboard::string("uploadSFXTitle"), Dashboard::string("errorLoginRequired")));

if(!$sfxEnabled) exit(Dashboard::renderErrorPage(Dashboard::string("uploadSFXTitle"), Dashboard::string("errorPageIsDisabled")));

if(isset($_POST['sfxTitle']) && $_FILES && $_FILES['sfxFile']['error'] == UPLOAD_ERR_OK) {
	$sfxTitle = Escape::text($_POST['sfxTitle'], 40) ?: '';
	$sfxFile = $_FILES['sfxFile'];
	
	$pathToSFXsFolder = realpath(__DIR__.'/../sfxs/');
	
	$uploadSFX = Library::uploadSFX($person, $sfxTitle, $sfxFile, $pathToSFXsFolder);
	
	if(!$uploadSFX['success']) {
		switch($uploadSFX['error']) {
			case SongError::UnknownError:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorFileIsEmpty"), "error"));
			case SongError::Banned:
				exit(Dashboard::renderToast("gavel", sprintf(Dashboard::string("bannedToast"), htmlspecialchars(Escape::url_base64_decode($uploadSFX['info']['reason'])), '<text dashboard-date="'.$uploadSFX['info']['expires'].'"></text>'), "error"));
			case SongError::Disabled:
				exit(Dashboard::renderErrorPage(Dashboard::string("uploadSFXTitle"), Dashboard::string("errorPageIsDisabled")));
			case SongError::RateLimit:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorSongRateLimit"), "error"));
			case SongError::InvalidFile:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorCouldntReadFile"), "error"));
			case SongError::NotAnAudio:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorIsNotAnAudio"), "error"));
			case SongError::TooBig:
				exit(Dashboard::renderToast("xmark", sprintf(Dashboard::string("errorMaxFileSize"), $sfxSize), "error"));
			default:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
		}
	}
	
	$sfxTime = Library::lastSFXTime();
	Library::updateLibraries('', '', $sfxTime, 0);
	
	$filters = ['ID = '.$uploadSFX['sfxID'], "sfxs.isDisabled = 0"];
	
	$sfxs = Library::getSFXs($filters, '', '', '', 0, 1);
	$sfx = $sfxs['sfxs'][0];
	
	$dataArray = [
		'INFO_TITLE' => Dashboard::string("uploadSFXTitle"),
		'INFO_DESCRIPTION' => Dashboard::string("successUploadedSFX"),
		'INFO_EXTRA' => Dashboard::renderSFXCard($sfx, $person),
		'INFO_BUTTON_TEXT' => Dashboard::string("uploadSFXTitle"),
		'INFO_BUTTON_ONCLICK' => "getPage('@')"
	];
	
	exit(Dashboard::renderPage("general/info", Dashboard::string("uploadSFXTitle"), "../", $dataArray));
}

$dataArray = [
	'UPLOAD_SFX_BUTTON_ONCLICK' => "handleSFXUpload('uploadSFXForm')"
];

exit(Dashboard::renderPage("upload/sfx", Dashboard::string("uploadSFXTitle"), "../", $dataArray));
?>