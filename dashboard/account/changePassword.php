<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if(!$person['success']) exit(Dashboard::renderErrorPage(Dashboard::string("changePasswordTitle"), Dashboard::string("errorLoginRequired")));

if(isset($_POST['userName']) && isset($_POST['password'])) {
	$person = $sec->loginPlayer();
	if(!$person['success']) exit(Dashboard::renderToast("xmark", Dashboard::string("errorWrongLoginOrPassword"), "error"));
	
	$targetPassword = $_POST['targetPassword'];
	
	if($_POST['password'] == $targetPassword) exit(Dashboard::renderToast("xmark", Dashboard::string("errorSamePasswords"), "error"));
	
	$changePassword = Library::changePassword($person, $targetPassword);
	if(!$changePassword) exit(Dashboard::renderToast("xmark", Dashboard::string("errorBadPassword"), "error"));
	
	setcookie('auth', '', 2147483647, '/');
	
	exit(Dashboard::renderToast("check", Dashboard::string("successChangedPassword"), "success", "account/login", "box"));
}

$dataArray = ['CHANGE_PASSWORD_BUTTON_ONCLICK' => "postPage('account/changePassword', 'changePasswordForm', 'box')"];

exit(Dashboard::renderPage("account/changePassword", Dashboard::string("changePasswordTitle"), "../", $dataArray));
?>