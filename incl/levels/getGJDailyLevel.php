<?php
require __DIR__."/../../config/misc.php";
require __DIR__."/../lib/connection.php";
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/XOR.php";
require_once __DIR__."/../lib/cron.php";
require_once __DIR__."/../lib/ip.php";
require_once __DIR__."/../lib/enums.php";

$IP = IP::getIP();

$type = !empty($_POST["type"]) ? $_POST["type"] : (!empty($_POST["weekly"]) ? $_POST["weekly"] : 0);
$current = time();
$stringToAdd = '';

switch($type) {
	case 0:
	case 1:
		$dailyTable = 'dailyfeatures';
		$dailyTime = 'timestamp';
		$isEvent = false;
		
		$daily = $db->prepare("SELECT * FROM dailyfeatures WHERE timestamp < :current AND type = :type ORDER BY timestamp DESC LIMIT 1");
		$daily->execute([':current' => $current, ':type' => $type]);
		break;
	case 2:
		$dailyTable = 'events';
		$dailyTime = 'duration';
		$isEvent = true;
		
		$daily = $db->prepare("SELECT * FROM events WHERE timestamp < :current AND duration >= :current ORDER BY duration ASC LIMIT 1");
		$daily->execute([':current' => $current]);
		break;
}

$daily = $daily->fetch();
if(!$daily) exit(CommonError::InvalidRequest);

$dailyID = $daily['feaID'] + ($type * 100000);

if(!$isEvent) {
	$newDailyTime = $db->prepare("SELECT timestamp FROM dailyfeatures WHERE feaID > :feaID AND type = :type ORDER BY feaID ASC LIMIT 1");
	$newDailyTime->execute([':feaID' => $daily['feaID'], ':type' => $type]);
	$newDailyTime = $newDailyTime->fetchColumn();
	
	$timeLeft = ($newDailyTime ?: $current) - $current;
} else {
	$chk = XORCipher::cipher(Escape::url_base64_decode(substr(Escape::latin($_POST["chk"]), 5)), 59182);
	$string = Escape::url_base64_encode(XORCipher::cipher('M336G:'.$chk.':'.($daily['feaID'] + 19).':3:'.$daily['rewards'], 59182));
	$hash = Security::generateFourthHash($string);
	
	$timeLeft = 10;
	
	$stringToAdd = '|PGDPS'.$string.'|'.$hash;
}

if(!$oldDailyWeekly && $timeLeft <= 0) exit("0|0");

if(!$daily['webhookSent']) {
	//$gs->sendDailyWebhook($daily['levelID'], $type);
	$sent = $db->prepare('UPDATE '.$dailyTable.' SET webhookSent = 1 WHERE feaID = :feaID');
	$sent->execute([':feaID' => $daily['feaID']]);
	
	$person = [
		'accountID' => 0,
		'userID' => 0,
		'IP' => $IP
	];
	
	if($automaticCron) Cron::updateCreatorPoints($person, $enableTimeoutForAutomaticCron);
}

exit($dailyID."|".$timeLeft.$stringToAdd);
?>