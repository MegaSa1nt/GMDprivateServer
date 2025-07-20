<?php
require __DIR__."/../../config/security.php";
require_once __DIR__."/../incl/lib/mainLib.php";
require_once __DIR__."/../incl/lib/security.php";
require_once __DIR__."/../incl/lib/exploitPatch.php";
require_once __DIR__."/../incl/lib/enums.php";
$sec = new Security();

$saveData = $_POST['saveData'];
if(empty($saveData)) exit(BackupError::SomethingWentWrong);

$person = $sec->loginPlayer();
if(!$person["success"]) {
	$logPerson = [
		'accountID' => "0",
		'userID' => "0",
		'userName' => '',
		'IP' => $person['IP']
	];
	
	Library::logAction($logPerson, Action::FailedAccountBackup, strlen($saveData), 1);
	exit(CommonError::InvalidRequest);
}
$accountID = $person["accountID"];
$userName = $person['userName'];

$checkRateLimit = Library::checkRateLimits($person, RateLimit::AccountBackup);
if(!$checkRateLimit) exit(CommonError::InvalidRequest);

if(strlen($saveData) > $maxBackupFileSize) {
	Library::logAction($person, Action::FailedAccountBackup, strlen($saveData), 2);
	exit(BackupError::TooLarge);
}

$account = Library::getAccountByID($accountID);

$saveDataArray = explode(";", $saveData);
$saveDataDecoded = mb_ereg_replace('(\r\n|\r|\n|( \t)|\t)', '', Security::decodeSaveFile($saveDataArray[0]));

$isSaveDataValid = simplexml_load_string($saveDataDecoded);
if(!$isSaveDataValid) {
	Library::logAction($person, Action::FailedAccountBackup, strlen($saveData), 3);
	exit(CommonError::InvalidRequest);
}

if(strpos($saveDataDecoded, "<key>") !== false) {
	$keyName = 'key';
	$stringName = 'string';
} else {
	$keyName = 'k';
	$stringName = 's';
}

$saveDataDecoded = str_replace('<'.$keyName.'>GJA_002</'.$keyName.'><'.$stringName.'>'.$_POST['password'].'</'.$stringName.'>', '<'.$keyName.'>GJA_002</'.$keyName.'><'.$stringName.'>:3</'.$stringName.'>', $saveDataDecoded);
$saveDataDecoded = str_replace('<'.$keyName.'>GJA_005</'.$keyName.'><'.$stringName.'>'.$_POST['gjp2'].'</'.$stringName.'>', '<'.$keyName.'>GJA_005</'.$keyName.'><'.$stringName.'>:3</'.$stringName.'>', $saveDataDecoded);

$accountOrbs = explode('</'.$stringName.'>', explode('</'.$stringName.'><'.$keyName.'>14</'.$keyName.'><'.$stringName.'>', $saveDataDecoded)[1])[0] ?: 0;
$accountCompletedOfficialLevels = explode('</'.$stringName.'>', explode('</'.$stringName.'><'.$keyName.'>3</'.$keyName.'><'.$stringName.'>', explode('<'.$keyName.'>GS_value</'.$keyName.'>', $saveDataDecoded)[1])[1])[0] ?: 0;
$accountCompletedOnlineLevels = explode('</'.$stringName.'>', explode('</'.$stringName.'><'.$keyName.'>4</'.$keyName.'><'.$stringName.'>', str_replace(['<dict>', '<d>'], '</'.$stringName.'>', explode('<'.$keyName.'>GS_value</'.$keyName.'>', $saveDataDecoded)[1]))[1])[0] ?: 0;
$accountLevels = $accountCompletedOfficialLevels + $accountCompletedOnlineLevels;

$levelsDataDecoded = Security::decodeSaveFile($saveDataArray[1]);

$isLevelsDataValid = simplexml_load_string($levelsDataDecoded);
if(!$isLevelsDataValid) {
	Library::logAction($person, Action::FailedAccountBackup, strlen($saveData), 4);
	exit(CommonError::InvalidRequest);
}

$saveData = Security::encodeSaveFile($saveDataDecoded).';'.$saveDataArray[1];
Library::updateOrbsAndCompletedLevels($person, $accountOrbs, $accountLevels);

if(!empty($account['salt'])) {
	$salt = $account['salt'];
	$fileEncrypted = $sec->encryptData($saveData, $salt);
	file_put_contents(__DIR__."/../data/accounts/".$accountID, $fileEncrypted);
} else {
	file_put_contents(__DIR__."/../data/accounts/".$accountID, $saveData);
}

Library::logAction($person, Action::SuccessfulAccountBackup, $userName, filesize(__DIR__."/../data/accounts/".$accountID), $accountOrbs, $accountLevels);

exit(CommonError::Success);
?>