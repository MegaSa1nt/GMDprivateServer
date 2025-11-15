<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if(!$person['success']) exit(Dashboard::renderToast("xmark", Dashboard::string("errorLoginRequired"), "error", "account/login", "box"));

if(!Library::checkPermission($person, "dashboardManageRoles")) exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));

if(isset($_POST['roleID'])) {
	$roleID = Escape::number($_POST['roleID']);
	if(empty($roleID)) exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
	
	$deleteRole = Library::deleteRole($person, $roleID);
	if(!$deleteRole) exit(Dashboard::renderToast("xmark", Dashboard::string("errorCantDeleteRole"), "error"));
	
	exit(Dashboard::renderToast("check", Dashboard::string("successDeletedRole"), "success", 'mod/roles', "list"));
}

exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
?>