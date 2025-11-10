<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if(!$person['success']) exit(Dashboard::renderToast("xmark", Dashboard::string("errorLoginRequired"), "error", "account/login", "box"));

if(isset($_POST['listID'])) {
	$listID = Escape::number($_POST['listID']);
	if(empty($listID)) exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
	
	$deleteList = Library::deleteList($listID, $person);
	if(!$deleteList) exit(Dashboard::renderToast("xmark", Dashboard::string("errorCantDeleteList"), "error"));
	
	exit(Dashboard::renderToast("check", Dashboard::string("successDeletedList"), "success", 'browse/lists', "list"));
}

exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
?>