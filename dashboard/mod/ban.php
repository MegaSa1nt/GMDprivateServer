<?php
require __DIR__."/../../config/dashboard.php";
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if(!$person['success']) exit(Dashboard::renderErrorPage(Dashboard::string("banUserTitle"), Dashboard::string("errorLoginRequired")));
$accountID = $person['accountID'];
$userID = $person['userID'];
$IP = $person['IP'];

$getIP = '';

if(!Library::checkPermission($person, "dashboardModeratorTools")) exit(Dashboard::renderErrorPage(Dashboard::string("banUserTitle"), Dashboard::string("errorNoPermission"), $pageBase));

if(isset($_POST["personType"]) && isset($_POST["banType"]) && isset($_POST["person"]) && isset($_POST["banReason"])) {
	$personType = Security::limitValue(0, abs(Escape::number($_POST["personType"]) ?: 0), 2);
	$banType = Security::limitValue(0, abs(Escape::number($_POST["banType"]) ?: 0), 5);
	$banPerson = trim(Escape::text($_POST["person"]));
	$banReason = trim(Escape::text($_POST["banReason"]));
	$modBanReason = trim(Escape::text($_POST["modBanReason"])) ?: '';
	$expiryTime = !empty($_POST['expiryTime']) ? (new DateTime($_POST['expiryTime']))->getTimestamp() : 2147483647;
	$alsoBanIP = isset($_POST["alsoBanIP"]) && $personType != 2;
	
	if(time() >= $expiryTime) exit(Dashboard::renderToast("xmark", Dashboard::string("errorExpirationTimeInPast"), "error"));
	
	if(!is_numeric($banPerson) && $personType == 0) {
		$personType = 1;
		$banPerson = Library::getUserID($banPerson);
	}
	
	switch($personType) {
		case 0:
			if($banPerson == $accountID) exit(Dashboard::renderToast("xmark", Dashboard::string("errorCantBanYourself"), "error"));
			
			$user = Library::getUserByAccountID($banPerson);
			if(!$user) exit(Dashboard::renderToast("xmark", Dashboard::string("errorPlayerNotFound"), "error"));
			
			if($alsoBanIP) $getIP = $user['IP'];
			
			break;
		case 1:
			if($banPerson == $userID) exit(Dashboard::renderToast("xmark", Dashboard::string("errorCantBanYourself"), "error"));
			
			$user = Library::getUserByID($banPerson);
			if(!$user) exit(Dashboard::renderToast("xmark", Dashboard::string("errorPlayerNotFound"), "error"));
			
			if($alsoBanIP) $getIP = $user['IP'];
			
			break;
		case 2:
			if(Library::convertIPForSearching($IP) == Library::convertIPForSearching($banPerson)) exit(Dashboard::renderToast("xmark", Dashboard::string("errorCantBanYourself"), "error"));
			
			break;
	}
	
	$checkBan = Library::getBan($banPerson, $personType, $banType);
	if($checkBan) exit(Dashboard::renderToast("xmark", Dashboard::string("errorUserIsBanned"), "error"));
	
	Library::banPerson($accountID, $banPerson, $banReason, $banType, $personType, $expiryTime, $modBanReason);
	if($alsoBanIP) Library::banPerson($accountID, $getIP, $banReason, $banType, Person::IP, $expiryTime, $modBanReason);
	
	exit(Dashboard::renderToast("check", Dashboard::string("successBannedUser"), "success", '@'));
}

$dataArray = [
	'BAN_MINIMUM_EXPIRATION_TIME' => substr(explode('+', (new DateTime())->format('c'))[0], 0, -3)
];

exit(Dashboard::renderPage("mod/ban", Dashboard::string("banUserTitle"), "../", $dataArray));
?>