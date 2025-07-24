<?php
require_once __DIR__."/../incl/dashboardLib.php";
require __DIR__."/../".$dbPath."config/mail.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if($person['success']) exit(Dashboard::renderErrorPage(Dashboard::string("registerAccountTitle"), Dashboard::string("errorAlreadyLoggedIn")));

if(isset($_POST['userName']) && isset($_POST['password']) && isset($_POST['repeatPassword'])) {
	$userName = Escape::latin_no_spaces($_POST['userName']);
	$password = $_POST['password'];
	$repeatPassword = $_POST['repeatPassword'];
	$email = Escape::text($_POST['email']) ?: '';
	$repeatEmail = Escape::text($_POST['repeatEmail']) ?: '';
	
	$createAccount = Library::createAccount($userName, $password, $repeatPassword, $email, $repeatEmail);
	if(!$createAccount['success']) {
		switch($createAccount['error']) {
			case CommonError::Automod:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorAccountsAutomod"), "error"));
			case RegisterError::InvalidUserName:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorBadUsername"), "error"));
			case RegisterError::UserNameIsTooShort:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorUsernameTooShort"), "error"));
			case RegisterError::PasswordIsTooShort:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorPasswordTooShort"), "error"));
			case RegisterError::PasswordsDoNotMatch:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorPasswordsDontMatch"), "error"));
			case RegisterError::AccountExists:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorUsernameIsTaken"), "error"));
			case RegisterError::EmailsDoNotMatch:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorEmailsDontMatch"), "error"));
			case RegisterError::InvalidEmail:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorBadEmail"), "error"));
			case RegisterError::EmailIsInUse:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorEmailInUse"), "error"));
			case "THERE WILL BE ERROR LATER":
				exit(Dashboard::renderErrorPage(Dashboard::string("registerAccountTitle"), Dashboard::string("errorPageIsDisabled")));
			default:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
		}
	}
	
	exit(Dashboard::renderToast("check", Dashboard::string("successCreatedAccount"), "success", 'account/login'));
}

$dataArray = [
	'REGISTER_EMAIL_ENABLED' => $mailEnabled ? 'true' : 'false',
	
	'REGISTER_BUTTON_ONCLICK' => "postPage('account/register', 'registerForm')"
];

exit(Dashboard::renderPage("account/register", Dashboard::string("registerAccountTitle"), "../", $dataArray));
?>