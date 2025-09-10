<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if(!$person['success']) exit(Dashboard::renderToast("xmark", Dashboard::string("errorLoginRequired"), "error", "account/login", "box"));

if(Library::checkPermission($person, "dashboardManageLevels") && isset($_POST['levelID']) && isset($_POST['levelName']) && isset($_POST['levelAuthor'])) {
	$levelID = Escape::number($_POST['levelID']);
	
	$level = Library::getLevelByID($levelID);
	if(!$level) exit(Dashboard::renderToast("xmark", Dashboard::string("errorLevelNotFound"), "error"));
	
	$newLevelName = trim(Escape::latin($_POST['levelName'], 25));
	$newLevelAuthor = Escape::latin($_POST['levelAuthor']); // In case of unregistered user, which will have non-numeric ID
	if(!$newLevelName || !$newLevelAuthor) exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
	
	if(Security::checkFilterViolation($person, $newLevelName, 3)) exit(Dashboard::renderToast("xmark", Dashboard::string("errorBadName"), "error"));
	
	$newLevelAuthorArray = Library::getUserByAccountID($newLevelAuthor);
	if(!$newLevelAuthorArray) exit(Dashboard::renderToast("xmark", Dashboard::string("errorPlayerNotFound"), "error"));
	
	$newLevelDesc = Library::escapeDescriptionCrash(Escape::text(trim($_POST['levelDesc']), 300));
	if(Security::checkFilterViolation($person, $newLevelDesc, 3)) exit(Dashboard::renderToast("xmark", Dashboard::string("errorBadDesc"), "error"));
	
	$newStars = Security::limitValue(0, abs(Escape::number($_POST['stars']) ?: 0), 10);
	$newDifficultyName = Escape::latin($_POST['difficulty']) ?: 'na';
	$newDifficulty = Library::getLevelDifficulty($newDifficultyName);
	$newLevelRateType = Security::limitValue(0, abs(Escape::number($_POST['levelRateType']) ?: 0), 4);
	
	$songType = $_POST['songType'] ? 1 : 0;
	$newSongID = $songType ? abs(Escape::number($_POST['songID']) ?: 0) : Security::limitValue(0, abs(Escape::number($_POST['audioTrack']) ?: 0), 21);
	if($songType) {
		$song = Library::getSongByID($newSongID);
		if(!$song) exit(Dashboard::renderToast("xmark", Dashboard::string("errorSongNotFound"), "error"));
	}
	
	$newPassword = !empty($_POST["levelPass"]) ? '1'.sprintf("%06d", abs(Escape::number($_POST["levelPass"], 6) ?: 0)) : ($level['gameVersion'] > 21 ? 1 : 0);
	$newLevelPrivacy = Security::limitValue(0, abs(Escape::number($_POST['levelPrivacy']) ?: 0), 2);
	
	$newSilverCoins = isset($_POST['silverCoins']) ? 1 : 0;
	$newUpdatesLock = isset($_POST['updatesLock']) ? 1 : 0;
	$newCommentingLock = isset($_POST['commentingLock']) ? 1 : 0;
	
	if($level['levelName'] != $newLevelName) Library::renameLevel($levelID, $person, $newLevelName);
	if($level['extID'] != $newLevelAuthorArray['extID']) Library::moveLevel($levelID, $person, $newLevelAuthorArray);
	if($level['levelDesc'] != Escape::url_base64_encode($newLevelDesc)) Library::changeLevelDescription($levelID, $person, $newLevelDesc);
	
	if($level['starStars'] != $newStars ||
		$level['starDifficulty'] != $newDifficulty['difficulty'] ||
		$level['starAuto'] != $newDifficulty['auto'] ||
		$level['starDemon'] != ($newDifficulty['demon'] ? 1 : 0) ||
		$level['starDemonDiff'] != $newDifficulty['demon'] ||
		($level['starEpic'] + ($level['starFeatured'] ? 1 : 0)) != $newLevelRateType ||
		$level['starCoins'] != $newSilverCoins
	) Library::rateLevel($levelID, $person, $newDifficultyName, $newStars, $newSilverCoins, $newLevelRateType);
	
	if(($songType && $level['songID'] != $newSongID) || (!$songType && $level['audioTrack'] != $newSongID)) Library::changeLevelSong($levelID, $person, $newSongID, $songType);
	
	if($level['password'] != $newPassword) Library::changeLevelPassword($levelID, $person, $newPassword);
	if($level['unlisted'] != $newLevelPrivacy) Library::changeLevelPrivacy($levelID, $person, $newLevelPrivacy);
	
	if($level['updateLocked'] != $newUpdatesLock) Library::lockUpdatingLevel($levelID, $person, $newUpdatesLock);
	if($level['commentLocked'] != $newCommentingLock) Library::lockCommentingOnLevel($levelID, $person, $newCommentingLock);
	
	exit(Dashboard::renderToast("check", Dashboard::string("successAppliedSettings"), "success", '@', "settings"));
}

exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
?>