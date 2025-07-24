<?php
require __DIR__."/../../config/misc.php";
require_once __DIR__."/../lib/connection.php";
require_once __DIR__."/../lib/cron.php";
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/automod.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

if(!isset($_POST["stars"]) || !isset($_POST["demons"]) || !isset($_POST["icon"]) || !isset($_POST["color1"]) || !isset($_POST["color2"])) exit(CommonError::InvalidRequest);

$person = $sec->loginPlayer();
if(!$person["success"]) exit(CommonError::InvalidRequest);
$accountID = $person["accountID"];
$userID = $person["userID"];
$userName = $person["userName"];
$IP = $person["IP"];

if(Automod::isAccountsDisabled(2) || (!$unregisteredSubmissions && !$accountID)) exit($userID);

$stars = abs(Escape::number($_POST["stars"]));
$demons = abs(Escape::number($_POST["demons"]));
$icon = abs(Escape::number($_POST["icon"]));
$color1 = abs(Escape::number($_POST["color1"]));
$color2 = abs(Escape::number($_POST["color2"]));

$ship = abs(Escape::number($_POST["ship"]) ?: 0); // 1.4 - 1.5 compatibility

$gameVersion = abs(Escape::number($_POST["gameVersion"]) ?: 1);
$binaryVersion = abs(Escape::number($_POST["binaryVersion"]) ?: 1);
$coins = abs(Escape::number($_POST["coins"]) ?: 0);
$iconType = abs(Escape::number($_POST["iconType"]) ?: 0) ?: ($ship ? 1 : 0);
$userCoins = abs(Escape::number($_POST["userCoins"]) ?: 0);
$special = abs(Escape::number($_POST["special"]) ?: 0);
$accIcon = abs(Escape::number($_POST["accIcon"]) ?: 0);
$accShip = abs(Escape::number($_POST["accShip"]) ?: 0) ?: abs($ship ?: 0);
$accBall = abs(Escape::number($_POST["accBall"]) ?: 0);
$accBird = abs(Escape::number($_POST["accBird"]) ?: 0);
$accDart = abs(Escape::number($_POST["accDart"]) ?: 0);
$accRobot = abs(Escape::number($_POST["accRobot"]) ?: 0);
$accGlow = abs(Escape::number($_POST["accGlow"]) ?: 0);
$accSpider = abs(Escape::number($_POST["accSpider"]) ?: 0);
$accExplosion = abs(Escape::number($_POST["accExplosion"]) ?: 0);
$diamonds = abs(Escape::number($_POST["diamonds"]) ?: 0);
$moons = abs(Escape::number($_POST["moons"]) ?: 0);
$color3 = abs(Escape::number($_POST["color3"]) ?: 0);
$accSwing = abs(Escape::number($_POST["accSwing"]) ?: 0);
$accJetpack = abs(Escape::number($_POST["accJetpack"]) ?: 0);
$dinfo = Escape::multiple_ids($_POST["dinfo"]) ?: '';
$dinfow = abs(Escape::number($_POST["dinfow"]) ?: 0);
$dinfog = abs(Escape::number($_POST["dinfog"]) ?: 0);
$sinfo = Escape::multiple_ids($_POST["sinfo"]) ?: '';
$sinfod = abs(Escape::number($_POST["sinfod"]) ?: 0);
$sinfog = abs(Escape::number($_POST["sinfog"]) ?: 0);

$user = Library::getUserByID($userID);

if(!empty($dinfo)) {
	$demonsCount = $db->prepare("SELECT IFNULL(easyNormal, 0) as easyNormal,
	IFNULL(mediumNormal, 0) as mediumNormal,
	IFNULL(hardNormal, 0) as hardNormal,
	IFNULL(insaneNormal, 0) as insaneNormal,
	IFNULL(extremeNormal, 0) as extremeNormal,
	IFNULL(easyPlatformer, 0) as easyPlatformer,
	IFNULL(mediumPlatformer, 0) as mediumPlatformer,
	IFNULL(hardPlatformer, 0) as hardPlatformer,
	IFNULL(insanePlatformer, 0) as insanePlatformer,
	IFNULL(extremePlatformer, 0) as extremePlatformer
	FROM (
		(SELECT count(*) AS easyNormal FROM levels WHERE starDemonDiff = 3 AND levelLength != 5 AND levelID IN (".$dinfo.") AND starDemon != 0) easyNormal
		JOIN (SELECT count(*) AS mediumNormal FROM levels WHERE starDemonDiff = 4 AND levelLength != 5 AND levelID IN (".$dinfo.") AND starDemon != 0) mediumNormal
		JOIN (SELECT count(*) AS hardNormal FROM levels WHERE starDemonDiff = 0 AND levelLength != 5 AND levelID IN (".$dinfo.") AND starDemon != 0) hardNormal
		JOIN (SELECT count(*) AS insaneNormal FROM levels WHERE starDemonDiff = 5 AND levelLength != 5 AND levelID IN (".$dinfo.") AND starDemon != 0) insaneNormal
		JOIN (SELECT count(*) AS extremeNormal FROM  levels WHERE starDemonDiff = 6 AND levelLength != 5 AND levelID IN (".$dinfo.") AND starDemon != 0) extremeNormal
		
		JOIN (SELECT count(*) AS easyPlatformer FROM levels WHERE starDemonDiff = 3 AND levelLength = 5 AND levelID IN (".$dinfo.") AND starDemon != 0) easyPlatformer
		JOIN (SELECT count(*) AS mediumPlatformer FROM levels WHERE starDemonDiff = 4 AND levelLength = 5 AND levelID IN (".$dinfo.") AND starDemon != 0) mediumPlatformer
		JOIN (SELECT count(*) AS hardPlatformer FROM levels WHERE starDemonDiff = 0 AND levelLength = 5 AND levelID IN (".$dinfo.") AND starDemon != 0) hardPlatformer
		JOIN (SELECT count(*) AS insanePlatformer FROM levels WHERE starDemonDiff = 5 AND levelLength = 5 AND levelID IN (".$dinfo.") AND starDemon != 0) insanePlatformer
		JOIN (SELECT count(*) AS extremePlatformer FROM levels WHERE starDemonDiff = 6 AND levelLength = 5 AND levelID IN (".$dinfo.") AND starDemon != 0) extremePlatformer
	)");
	$demonsCount->execute();
	$demonsCount = $demonsCount->fetch();
	
	$allDemons = $demonsCount["easyNormal"] + $demonsCount["mediumNormal"] + $demonsCount["hardNormal"] + $demonsCount["insaneNormal"] + $demonsCount["extremeNormal"] + $demonsCount["easyPlatformer"] + $demonsCount["mediumPlatformer"] + $demonsCount["hardPlatformer"] + $demonsCount["insanePlatformer"] + $demonsCount["extremePlatformer"] + $dinfow + $dinfog;
	$demonsCountDiff = min($demons - $allDemons, 3);
	
	$dinfo = ($demonsCount["easyNormal"] + $demonsCountDiff).','.$demonsCount["mediumNormal"].','.$demonsCount["hardNormal"].','.$demonsCount["insaneNormal"].','.$demonsCount["extremeNormal"].','.$demonsCount["easyPlatformer"].','.$demonsCount["mediumPlatformer"].','.$demonsCount["hardPlatformer"].','.$demonsCount["insanePlatformer"].','.$demonsCount["extremePlatformer"].','.$dinfow.','.$dinfog;
}
if(!empty($sinfo)) {
	$sinfo = explode(",", $sinfo);
	
	$starsCount = $sinfo[0].",".$sinfo[1].",".$sinfo[2].",".$sinfo[3].",".$sinfo[4].",".$sinfo[5].",".$sinfod.",".$sinfog;
	$platformerCount = $sinfo[6].",".$sinfo[7].",".$sinfo[8].",".$sinfo[9].",".$sinfo[10].",".$sinfo[11].",0"; // Last is for Map levels, unused until 2.21
}

$updateUserStats = $db->prepare("UPDATE users SET gameVersion = :gameVersion, userName = :userName, coins = :coins, stars = :stars, demons = :demons, icon = :icon, color1 = :color1, color2 = :color2, iconType = :iconType, userCoins = :userCoins, special = :special, accIcon = :accIcon, accShip = :accShip, accBall = :accBall, accBird = :accBird, accDart = :accDart, accRobot = :accRobot, accGlow = :accGlow, IP = :IP, accSpider = :accSpider, accExplosion = :accExplosion, diamonds = :diamonds, moons = :moons, color3 = :color3, accSwing = :accSwing, accJetpack = :accJetpack, dinfo = :dinfo, sinfo = :sinfo, pinfo = :pinfo WHERE userID = :userID");
$updateUserStats->execute([':gameVersion' => $gameVersion, ':userName' => $userName, ':coins' => $coins, ':stars' => $stars, ':demons' => $demons, ':icon' => $icon, ':color1' => $color1, ':color2' => $color2, ':iconType' => $iconType, ':userCoins' => $userCoins, ':special' => $special, ':accIcon' => $accIcon, ':accShip' => $accShip, ':accBall' => $accBall, ':accBird' => $accBird, ':accDart' => $accDart, ':accRobot' => $accRobot, ':accGlow' => $accGlow, ':IP' => $IP, ':userID' => $userID, ':accSpider' => $accSpider, ':accExplosion' => $accExplosion, ':diamonds' => $diamonds, ':moons' => $moons, ':color3' => $color3, ':accSwing' => $accSwing, ':accJetpack' => $accJetpack, ':dinfo' => $dinfo, ':sinfo' => $starsCount, ':pinfo' => $platformerCount]);

$starsDifference = $stars - $user['stars'];
$coinsDifference = $coins - $user["coins"];
$demonsDifference = $demons - $user["demons"];
$userCoinsDifference = $userCoins - $user["userCoins"];
$diamondsDifference = $diamonds - $user["diamonds"];
$moonsDifference = $moons - $user["moons"];

Library::logAction($person, Action::ProfileStatsChange, $starsDifference, $coinsDifference, $demonsDifference, $userCoinsDifference, $diamondsDifference, $moonsDifference);
if($automaticCron) Cron::autoban($person, $enableTimeoutForAutomaticCron);
Automod::checkStatsSpeed($person);

if($gameVersion < 20 && !is_numeric($accountID) && $starsDifference + $coinsDifference + $demonsDifference + $userCoinsDifference + $diamondsDifference + $moonsDifference != 0) exit(CommonError::SubmitRestoreInfo);

exit($userID);
?>