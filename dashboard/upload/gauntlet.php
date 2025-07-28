<?php
require __DIR__."/../../config/dashboard.php";
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if(!$person['success']) exit(Dashboard::renderErrorPage(Dashboard::string("addGauntletTitle"), Dashboard::string("errorLoginRequired")));

if(!Library::checkPermission($person, "dashboardGauntletCreate")) exit(Dashboard::renderErrorPage(Dashboard::string("addGauntletTitle"), Dashboard::string("errorNoPermission"), '../'));

$gauntletNames = Library::getGauntletNames();

if(isset($_POST['gauntletID']) && isset($_POST['levels'])) {
	$gauntletID = Security::limitValue(1, abs(Escape::number($_POST['gauntletID']) ?: 0), count($gauntletNames));
	if(!$gauntletID) exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
	
	$gauntlet = Library::getGauntletByID($gauntletID);
	if($gauntlet) exit(Dashboard::renderToast("xmark", Dashboard::string("errorGauntletAlreadyExists"), "error"));

	$levels = Escape::multiple_ids($_POST['levels']) ?: '';
	$levelsArray = array_unique(explode(',', $levels));
	
	if(count($levelsArray) != 5) exit(Dashboard::renderToast("xmark", Dashboard::string("errorGauntletWrongLevelsCount"), "error"));
	
	$levels = implode(',', $levelsArray);
	
	$friendsString = Library::getFriendsQueryString($accountID);
	$filters = ["levelID IN (".$levels.") AND (
			unlisted != 1 OR
			(unlisted = 1 AND (levels.extID IN (".$friendsString.")))
		)"];
	
	$levelsSearchArray = Library::getLevels($filters, '', '', '', 0);
	
	if($levelsSearchArray['count'] != 5) exit(Dashboard::renderToast("xmark", Dashboard::string("errorMultipleLevelsNotFound"), "error"));

	Library::addGauntlet($person, $gauntletID, $levelsArray[0], $levelsArray[1], $levelsArray[2], $levelsArray[3], $levelsArray[4]);
	
	unset($GLOBALS['core_cache']['gauntlet'][$gauntletID]);
	$gauntlet = Library::getGauntletByID($gauntletID);
	
	$dataArray = [
		'INFO_TITLE' => Dashboard::string("addGauntletTitle"),
		'INFO_DESCRIPTION' => Dashboard::string("successAddedGauntlet"),
		'INFO_EXTRA' => Dashboard::renderGauntletCard($gauntlet, $person),
		'INFO_BUTTON_TEXT' => Dashboard::string("addGauntletTitle"),
		'INFO_BUTTON_ONCLICK' => "getPage('@')"
	];
	
	exit(Dashboard::renderPage("general/info", Dashboard::string("addGauntletTitle"), "../", $dataArray));
}

$gauntletIDs = [];
$gauntletElements = '';

$gauntlets = Library::getGauntlets();
foreach($gauntlets['gauntlets'] AS &$gauntlet) $gauntletIDs[] = (int)$gauntlet['ID'];

foreach($gauntletNames AS $index => $gauntletName) {
	if(in_array($index + 1, $gauntletIDs)) continue;
	
	$gauntletElements .= '<div class="option" value="'.$index + 1 .'" dashboard-select-option="'.$gauntletName.' Gauntlet">
			<text>'.$gauntletName.' Gauntlet</text>
		</div>';
}

$dataArray = [
	'ADD_GAUNTLET_TYPES' => $gauntletElements,
	
	'ADD_GAUNTLET_BUTTON_ONCLICK' => "postPage('upload/gauntlet', 'addGauntletForm')"
];

exit(Dashboard::renderPage("upload/gauntlet", Dashboard::string("addGauntletTitle"), "../", $dataArray));
?>