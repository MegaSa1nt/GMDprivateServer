<?php
require __DIR__."/../../config/dashboard.php";
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if(!$person['success']) exit(Dashboard::renderErrorPage(Dashboard::string("addMapPackTitle"), Dashboard::string("errorLoginRequired")));

if(!Library::checkPermission($person, "dashboardLevelPackCreate")) exit(Dashboard::renderErrorPage(Dashboard::string("addMapPackTitle"), Dashboard::string("errorNoPermission"), '../'));

if(isset($_POST['mapPackName']) && isset($_POST['levels'])) {
	$mapPackName = trim(Escape::text($_POST['mapPackName'], 25));
	if(!$mapPackName) exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));

	if(Security::checkFilterViolation($person, $mapPackName, 3)) exit(Dashboard::renderToast("xmark", Dashboard::string("errorBadName"), "error"));
	
	$stars = Security::limitValue(0, abs(Escape::number($_POST['stars']) ?: 0), 10);
	$coins = Security::limitValue(0, abs(Escape::number($_POST['coins']) ?: 0), 2);
	$difficulty = Security::limitValue(0, abs(Escape::number($_POST['difficulty']) ?: 0), 10);
	
	$textColor = Library::convertHEXToRBG(Escape::latin_no_spaces($_POST['mapPackTextColor']) ?: '000000') ?: '0,0,0';
	$barColor = Library::convertHEXToRBG(Escape::latin_no_spaces($_POST['mapPackBarColor']) ?: '000000') ?: '0,0,0';
	
	$levels = Escape::multiple_ids($_POST['levels']) ?: '';
	$levelsArray = array_unique(explode(',', $levels));
	
	if(count($levelsArray) == 0) exit(Dashboard::renderToast("xmark", Dashboard::string("errorMapPackNoLevels"), "error"));
	
	$levels = implode(',', $levelsArray);
	
	$friendsString = Library::getFriendsQueryString($accountID);
	$filters = ["levelID IN (".$levels.") AND (
			unlisted != 1 OR
			(unlisted = 1 AND (levels.extID IN (".$friendsString.")))
		)"];
	
	$levelsSearchArray = Library::getLevels($filters, '', '', '', 0);
	
	if($levelsSearchArray['count'] != count($levelsArray)) exit(Dashboard::renderToast("xmark", Dashboard::string("errorMultipleLevelsNotFound"), "error"));

	$mapPackID = Library::addMapPack($person, $mapPackName, $stars, $coins, $difficulty, $textColor, $barColor, $levels);
	
	$mapPack = Library::getMapPackByID($mapPackID);
	
	$dataArray = [
		'INFO_TITLE' => Dashboard::string("addMapPackTitle"),
		'INFO_DESCRIPTION' => Dashboard::string("successAddedMapPack"),
		'INFO_EXTRA' => Dashboard::renderMapPackCard($mapPack, $person),
		'INFO_BUTTON_TEXT' => Dashboard::string("addMapPackTitle"),
		'INFO_BUTTON_ONCLICK' => "getPage('@')"
	];
	
	exit(Dashboard::renderPage("general/info", Dashboard::string("addMapPackTitle"), "../", $dataArray));
}

$dataArray = [
	'ADD_MAPPACK_BUTTON_ONCLICK' => "postPage('upload/mappack', 'addMapPackForm')"
];

exit(Dashboard::renderPage("upload/mappack", Dashboard::string("addMapPackTitle"), "../", $dataArray));
?>