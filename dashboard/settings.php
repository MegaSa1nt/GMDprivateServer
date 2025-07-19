<?php
require_once __DIR__."/incl/dashboardLib.php";
require_once __DIR__."/".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/".$dbPath."incl/lib/security.php";
require_once __DIR__."/".$dbPath."incl/lib/exploitPatch.php";
require_once __DIR__."/".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
$accountID = $person['accountID'];

$personAppearance = Library::getPersonCommentAppearance($person);
$accountClan = Library::getAccountClan($accountID);

$dataArray = [
	'SETTING_LANGUAGE_EN_DEFAULT' => $_COOKIE['lang'] == 'EN' ? 'true' : 'false',
	'SETTING_LANGUAGE_RU_DEFAULT' => $_COOKIE['lang'] == 'RU' ? 'true' : 'false',
	
	'ACCOUNT_COLOR' => "color: rgb(".str_replace(",", " ", $personAppearance['commentColor']).")",
	'CLAN_NAME' => $accountClan ? $accountClan['clanName'] : Dashboard::string('notInClan'),
	'CLAN_COLOR' => $accountClan ? "color: #".$accountClan['clanColor']."; text-shadow: 0px 0px 20px #".$accountClan['clanColor']."61;" : '',
	
	'CLAN_TITLE' => $accountClan ? sprintf(Dashboard::string("clanProfile"), $accountClan['clanName']) : '',
	'PROFILE_TITLE' => $person['accountID'] ? sprintf(Dashboard::string("userProfile"), $person['userName']) : '',
	
	'DASHBOARD_SETTINGS_BUTTON_ONCLICK' => "postPage('settings', 'dashboardSettingsForm')"
];

exit(Dashboard::renderPage("settings", Dashboard::string("dashboardSettingsTitle"), ".", $dataArray));
?>