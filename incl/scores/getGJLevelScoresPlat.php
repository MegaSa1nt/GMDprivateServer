<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/XOR.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$person = $sec->loginPlayer();
if(!$person["success"]) exit(CommonError::InvalidRequest);

$scoreString = '';
$scores = [];

$levelID = Escape::number($_POST['levelID']);
$type = Escape::number($_POST['type']) ?: 1;
$scores['time'] = Escape::number($_POST["time"]);
$scores['points'] = Escape::number($_POST["points"]);

$attempts = !empty($_POST["s1"]) ? Escape::number($_POST["s1"]) - 8354 : 0;
$clicks = !empty($_POST["s2"]) ? Escape::number($_POST["s2"]) - 3991 : 0;
$progresses = !empty($_POST["s6"]) ? Escape::multiple_ids(XORCipher::cipher(Escape::url_base64_decode($_POST["s6"]), 41274)) : 0;
$coins = !empty($_POST["s9"]) ? Escape::number($_POST["s9"]) - 5819 : 0;
$dailyID = !empty($_POST["s10"]) ? Escape::number($_POST["s10"]) : 0;
$mode = $_POST['mode'] == 1 ? 'points' : 'time';

Library::submitPlatformerLevelScore($levelID, $person, $scores, $attempts, $clicks, $progresses, $coins, $dailyID, $mode);

$levelScores = Library::getPlatformerLevelScores($levelID, $person, $type, $dailyID, $mode);
if(!$levelScores) exit(CommonError::InvalidRequest);

foreach($levelScores AS $key => $user) {
	$time = Library::makeTime($user["timestamp"]);
	$user["userName"] = Library::makeClanUsername($user["extID"]);
	$scoreString .= "1:".$user["userName"].":2:".$user["userID"].":9:".$user["icon"].":10:".$user["color1"].":11:".$user["color2"].":51:".$user["color3"].":14:".$user["iconType"].":15:".$user["special"].":16:".$user["extID"].":3:".$user[$mode].":6:".($key + 1).":13:".$user["scoreCoins"].":42:".$time."|";
}

exit(rtrim($scoreString, "|"));
?>