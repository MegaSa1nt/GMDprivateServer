<?php
require __DIR__."/../../config/dashboard.php";
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if(!$person['success']) exit(Dashboard::renderErrorPage(Dashboard::string("reuploadLevelTitle"), Dashboard::string("errorLoginRequired")));

if(!$lrEnabled) exit(Dashboard::renderErrorPage(Dashboard::string("reuploadLevelTitle"), Dashboard::string("errorPageIsDisabled")));

if(isset($_POST['reuploadType']) && isset($_POST['serverURL']) && isset($_POST['levelID']) && isset($_POST['reuploadUserName']) && isset($_POST['reuploadPassword'])) {
	$reuploadType = abs(Escape::number($_POST['reuploadType']) ?: 0);
	$serverURL = isset($_POST['serverURL']) ? filter_var($_POST['serverURL'], FILTER_SANITIZE_URL) : false;
	$levelID = abs(Escape::number($_POST['levelID']) ?: 0);
	$reuploadUserName = Escape::text($_POST['reuploadUserName']) ?: '';
	$reuploadPassword = $_POST['reuploadPassword'] ?: '';
	
	$reuploadLevel = Library::reuploadLevel($person, $reuploadType, $serverURL, $levelID, $reuploadUserName, $reuploadPassword);
	
	if(!$reuploadLevel['success']) {
		switch($reuploadLevel['error']) {
			case LevelUploadError::ReuploadingDisabled:
				exit(Dashboard::renderErrorPage(Dashboard::string("reuploadLevelTitle"), Dashboard::string("errorPageIsDisabled")));
			case LevelUploadError::SameServer:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorReuploadSameServer"), "error"));
			case CommonError::Banned:
				exit(Dashboard::renderToast("gavel", sprintf(Dashboard::string("bannedToast"), htmlspecialchars(Escape::url_base64_decode($reuploadLevel['info']['reason'])), '<text dashboard-date="'.$reuploadLevel['info']['expires'].'"></text>'), "error"));
			case LevelUploadError::TooFast:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorReuploadTooFast"), "error"));
			case CommonError::Automod:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorReuploadAutomod"), "error"));
			case LevelUploadError::UploadingDisabled:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorUploadingLevelsDisabled"), "error"));
			case CommonError::BannedByServer:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorServerBanned"), "error"));
			case LoginError::WrongCredentials:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorIncorrectCredentials"), "error"));
			case CommonError::InvalidRequest:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorServerConnection"), "error"));
			case LevelUploadError::NothingFound:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorLevelNotFound"), "error"));
			case LevelUploadError::NotYourLevel:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorNotYourLevel"), "error"));
			case LevelUploadError::FailedToWriteLevel:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorFailedToWriteLevel"), "error"));
			default:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
		}
	}
	
	switch($reuploadType) {
		case 0:
			$level = Library::getLevelByID($reuploadLevel['levelID']);
			
			$dataArray = [
				'INFO_TITLE' => Dashboard::string("reuploadLevelTitle"),
				'INFO_DESCRIPTION' => Dashboard::string("successReuploadToServer"),
				'INFO_EXTRA' => Dashboard::renderLevelCard($level, $person),
				'INFO_BUTTON_TEXT' => Dashboard::string("reuploadLevelTitle"),
				'INFO_BUTTON_ONCLICK' => "getPage('@')"
			];
			
			exit(Dashboard::renderPage("general/info", Dashboard::string("reuploadLevelTitle"), "../", $dataArray));
		case 1:
			$dataArray = [
				'INFO_TITLE' => Dashboard::string("reuploadLevelTitle"),
				'INFO_DESCRIPTION' => sprintf(Dashboard::string("successReuploadFromServer"), $reuploadLevel['levelID']),
				'INFO_EXTRA' => '',
				'INFO_BUTTON_TEXT' => Dashboard::string("reuploadLevelTitle"),
				'INFO_BUTTON_ONCLICK' => "getPage('@')"
			];
			
			exit(Dashboard::renderPage("general/info", Dashboard::string("reuploadLevelTitle"), "../", $dataArray));
	}
}

$dataArray = [
	'REUPLOAD_LEVEL_TO_TEXT' => htmlspecialchars(sprintf(Dashboard::string("reuploadLevelToServer"), $gdps)),
	'REUPLOAD_LEVEL_FROM_TEXT' => htmlspecialchars(sprintf(Dashboard::string("reuploadLevelFromServer"), $gdps)),
	
	'REUPLOAD_LEVEL_BUTTON_ONCLICK' => "postPage('upload/level', 'reuploadLevelForm')"
];

exit(Dashboard::renderPage("upload/level", Dashboard::string("reuploadLevelTitle"), "../", $dataArray));
?>