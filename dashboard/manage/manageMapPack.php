<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if(!$person['success']) exit(Dashboard::renderToast("xmark", Dashboard::string("errorLoginRequired"), "error", "account/login", "box"));

if(Library::checkPermission($person, "dashboardManageMapPacks") && isset($_POST['mapPackID']) && isset($_POST['levels'])) {
	$mapPackID = Escape::number($_POST['mapPackID']);
	
	$mapPack = Library::getMapPackByID($mapPackID);
	if(!$mapPack) exit(Dashboard::renderToast("xmark", Dashboard::string("errorMapPackNotFound"), "error"));
	
	$newMapPackName = trim(Escape::text($_POST['mapPackName'], 25));
	if(!$newMapPackName) exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
	
	if(Security::checkFilterViolation($person, $newMapPackName, 3)) exit(Dashboard::renderToast("xmark", Dashboard::string("errorBadName"), "error"));
	
	$newStars = Security::limitValue(0, abs(Escape::number($_POST['stars']) ?: 0), 10);
	$newCoins = Security::limitValue(0, abs(Escape::number($_POST['coins']) ?: 0), 2);
	$newDifficulty = Security::limitValue(0, abs(Escape::number($_POST['difficulty']) ?: 0), 10);
	
	$newTextColor = Library::convertHEXToRBG(Escape::latin_no_spaces($_POST['mapPackTextColor']) ?: '000000') ?: '0,0,0';
	$newBarColor = Library::convertHEXToRBG(Escape::latin_no_spaces($_POST['mapPackBarColor']) ?: '000000') ?: '0,0,0';
	
	$newLevels = Escape::multiple_ids($_POST['levels']) ?: '';
	$newLevelsArray = array_unique(explode(',', $newLevels));
	
	if(count($newLevelsArray) == 0) exit(Dashboard::renderToast("xmark", Dashboard::string("errorMapPackNoLevels"), "error"));
	
	$newLevels = implode(',', $newLevelsArray);
	
	$friendsString = Library::getFriendsQueryString($accountID);
	$filters = ["levelID IN (".$newLevels.") AND (
			unlisted != 1 OR
			(unlisted = 1 AND (levels.extID IN (".$friendsString.")))
		)"];
	
	$levelsArray = Library::getLevels($filters, '', '', '', 0);
	
	if($levelsArray['count'] != count($newLevelsArray)) exit(Dashboard::renderToast("xmark", Dashboard::string("errorMultipleLevelsNotFound"), "error"));
	
	Library::changeMapPack($person, $mapPackID, $newMapPackName, $newStars, $newCoins, $newDifficulty, $newTextColor, $newBarColor, $newLevels);
	
	exit(Dashboard::renderToast("check", Dashboard::string("successAppliedSettings"), "success", '@', "settings"));
}

exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
?>