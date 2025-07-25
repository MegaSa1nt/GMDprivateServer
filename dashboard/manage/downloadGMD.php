<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if(!$person['success']) exit(Dashboard::renderToast("xmark", Dashboard::string("errorLoginRequired"), "error", "account/login"));

if(isset($_GET['levelID'])) {
	$levelID = abs(Escape::number($_GET['levelID']) ?: 0);
	if(empty($levelID)) exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
	
	$level = Library::getLevelByID($levelID);
	if(!$level || !Library::canAccountPlayLevel($person, $level)) exit(Dashboard::renderToast("xmark", Dashboard::string("errorLevelNotFound"), "error"));
	
	$GMDFile = Library::getGMDFile($levelID);
	if(!$GMDFile) exit(Dashboard::renderToast("xmark", Dashboard::string("errorFailedToGetGMD"), "error"));
	
	exit(json_encode(['success' => true, 'level' => ['id' => $levelID, 'name' => $level['levelName'], 'gmd' => base64_encode($GMDFile)]]));
}

exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
?>