<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if(!$person['success']) exit(Dashboard::renderToast("xmark", Dashboard::string("errorLoginRequired"), "error", "account/login", "box"));
$accountID = $person["accountID"];

$clanID = Escape::number($_POST['clanID']);

$clan = Library::getClanByID($clanID);
if(!$clan) exit(Dashboard::renderToast("xmark", Dashboard::string("errorClanNotFound"), "error"));

if(($clan["clanOwner"] == $accountID || Library::checkPermission($person, "dashboardManageClans")) && isset($_POST['clanName']) && isset($_POST['clanTag'])) {
	$clanName = Escape::text($_POST['clanName'], 25);
	$clanTag = strtoupper(Escape::latin_no_spaces($_POST['clanTag'], 5));
	$clanDesc = Escape::text($_POST['clanDesc'], 300) ?: '';
	$clanColor = Escape::latin_no_spaces($_POST['clanColor'], 6) ?: '000000';
	$clanClosed = isset($_POST['clanClosed']) ? 1 : 0;
	
	$changeClan = Library::changeClan($person, $clanID, $clanName, $clanTag, $clanDesc, $clanColor, $clanClosed);
	if(!$changeClan['success']) {
		switch($changeClan['error']) {
			case ClanError::NothingFound:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorClanNotFound"), "error"));
			case ClanError::BadClanName:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorBadName"), "error"));
			case ClanError::BadClanTag:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorBadTag"), "error"));
			case ClanError::BadClanDescription:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorBadDescription"), "error"));
			case ClanError::ClanNameExists:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorClanNameTaken"), "error"));
			case ClanError::ClanTagExists:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorClanTagTaken"), "error"));
			default:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
		}
	}
	
	exit(Dashboard::renderToast("check", Dashboard::string("successAppliedSettings"), "success", 'clan/'.$clanName.'/settings', "settings"));
}

exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
?>