<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$person = $sec->loginPlayer();
if(!$person["success"]) exit(CommonError::InvalidRequest);

$leaderboardsString = '';

$leaderboard = Library::getLeaderboard($person, "creators", 0);
$rank = $leaderboard['rank'];

foreach($leaderboard['leaderboard'] AS &$user) {
	$rank++;
	
	$user["userName"] = Library::makeClanUsername($user['extID']);
	
	if(date("d.m", time()) == "01.04" && $sakujes) $leaderboardsString .= "1:sakujes:2:".$user["userID"].":13:999:17:999:6:".$rank.":9:9:10:9:11:8:14:1:15:3:16:".$user['extID'].":3:999:8:99999:4:999:7:".$user['extID'].":46:99999|";
	else $leaderboardsString .= "1:".$user["userName"].":2:".$user["userID"].":13:".$user["coins"].":17:".$user["userCoins"].":6:".$rank.":9:".$user["icon"].":10:".$user["color1"].":11:".$user["color2"].":12:".($user['iconType'] == 1 ? $user["accShip"] : 0).":51:".$user["color3"].":14:".$user["iconType"].":15:".$user["special"].":16:".$user['extID'].":3:".$user["stars"].":8:".round($user["creatorPoints"], 0, PHP_ROUND_HALF_DOWN).":4:".$user["demons"].":7:".$user['extID'].":46:".$user["diamonds"].":52:".$user["moons"]."|";
}

exit(rtrim($leaderboardsString, '|'));
?>