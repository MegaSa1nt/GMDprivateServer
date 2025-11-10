<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if(!$person['success']) exit(Dashboard::renderToast("xmark", Dashboard::string("errorLoginRequired"), "error", "account/login", "box"));

if(isset($_POST['songID'])) {
	$songID = Escape::number($_POST['songID']);
	if(empty($songID)) exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
	
	$deleteSong = Library::deleteSong($person, $songID);
	if(!$deleteSong) exit(Dashboard::renderToast("xmark", Dashboard::string("errorCantDeleteSong"), "error"));
	
	exit(Dashboard::renderToast("check", Dashboard::string("successDeletedSong"), "success", 'browse/songs', "list"));
}

exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
?>