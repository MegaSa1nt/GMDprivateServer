<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if(!$person['success']) exit(Dashboard::renderErrorPage(Dashboard::string("changeUsernameTitle"), Dashboard::string("errorLoginRequired")));

if(isset($_POST['userName']) && isset($_POST['password'])) {
	$person = $sec->loginPlayer();
	if(!$person['success']) exit(Dashboard::renderToast("xmark", Dashboard::string("errorWrongLoginOrPassword"), "error"));
	
	$targetUserName = Escape::latin_no_spaces($_POST['targetUserName']);
	
	$accountExists = Library::getAccountByUserName($targetUserName);
	if($accountExists) exit(Dashboard::renderToast("xmark", Dashboard::string("errorUsernameIsTaken"), "error"));
	
	$changeUsername = Library::changeUsername($person, $targetUserName);
	if(!$changeUsername) exit(Dashboard::renderToast("xmark", Dashboard::string("errorBadUsername"), "error"));
	
	setcookie('auth', '', 2147483647, '/');
	
	exit(Dashboard::renderToast("check", Dashboard::string("successChangedUsername"), "success", "account/login", "box"));
}

$dataArray = ['CHANGE_USERNAME_BUTTON_ONCLICK' => "postPage('account/changeUsername', 'changeUsernameForm', 'box')"];

exit(Dashboard::renderPage("account/changeUsername", Dashboard::string("changeUsernameTitle"), "../", $dataArray));
?>