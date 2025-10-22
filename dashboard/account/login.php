<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if($person['success']) exit(Dashboard::renderErrorPage(Dashboard::string("loginToAccountTitle"), Dashboard::string("errorAlreadyLoggedIn")));

if(isset($_POST['userName']) && isset($_POST['password'])) {
	$person = $sec->loginPlayer();
	if(!$person['success']) exit(Dashboard::renderToast("xmark", Dashboard::string("errorWrongLoginOrPassword"), "error"));
	
	$lastLocation = htmlspecialchars(Escape::text($_POST['lastLocation']));
	if(mb_strpos($lastLocation, 'account/register') !== false || mb_strpos($lastLocation, 'account/login') !== false) $lastLocation = './';
	
	setcookie('auth', $person['auth'], 2147483647, '/', '', true, true);

	Library::logAction($person, Action::SuccessfulLogin);
	
	exit(Dashboard::renderToast("check", Dashboard::string("successLoggedIn"), "success", $lastLocation));
}

$dataArray = [
	'LOGIN_BUTTON_ONCLICK' => "postPage('account/login', 'loginForm', 'box')",
	'LOGIN_HTTP_REFERER' => htmlspecialchars(Escape::text($_SERVER['HTTP_REFERER'])) ?: "./"
];

exit(Dashboard::renderPage("account/login", Dashboard::string("loginToAccountTitle"), "../", $dataArray));
?>