<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if(!$person['success']) exit(Dashboard::renderToast("xmark", Dashboard::string("errorLoginRequired"), "error", "account/login", "box"));

if(Library::checkPermission($person, "dashboardGauntletCreate") && isset($_POST['gauntletID']) && isset($_POST['levels'])) {
	$gauntletID = Escape::number($_POST['gauntletID']);
	
	$gauntlet = Library::getGauntletByID($gauntletID);
	if(!$gauntlet) exit(Dashboard::renderToast("xmark", Dashboard::string("errorGauntletNotFound"), "error"));
	
	$newLevels = Escape::multiple_ids($_POST['levels']);
	
	$newLevelsArray = array_unique(explode(',', $newLevels));
	
	if(count($newLevelsArray) != 5) exit(Dashboard::renderToast("xmark", Dashboard::string("errorGauntletWrongLevelsCount"), "error"));
	
	$friendsString = Library::getFriendsQueryString($accountID);
	$filters = ["levelID IN (".$newLevels.") AND (
			unlisted != 1 OR
			(unlisted = 1 AND (levels.extID IN (".$friendsString.")))
		)"];
	
	$levelsArray = Library::getLevels($filters, '', '', '', 0);
	
	if($levelsArray['count'] != 5) exit(Dashboard::renderToast("xmark", Dashboard::string("errorMultipleLevelsNotFound"), "error"));
	
	Library::changeGauntlet($person, $gauntletID, $newLevelsArray[0], $newLevelsArray[1], $newLevelsArray[2], $newLevelsArray[3], $newLevelsArray[4]);
	
	exit(Dashboard::renderToast("check", Dashboard::string("successAppliedSettings"), "success", '@', "settings"));
}

exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
?>