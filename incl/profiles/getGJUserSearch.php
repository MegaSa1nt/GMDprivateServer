<?php
chdir(dirname(__FILE__));
require "../lib/connection.php";
require_once "../lib/GJPCheck.php";
require_once "../lib/exploitPatch.php";
require_once "../lib/mainLib.php";
$gs = new mainLib();

$accountID = GJPCheck::getAccountIDOrDie();
$str = ExploitPatch::charclean(ExploitPatch::url_base64_decode($_POST["str"]));

$usersArray = [];

$users = $db->prepare("SELECT * FROM accounts INNER JOIN users ON users.extID = accounts.accountID WHERE accounts.userName LIKE '%".$str."%' LIMIT 10");
$users->execute();
$users = $users->fetchAll();

foreach($users AS &$user) {
	$user["userName"] = $gs->makeClanUsername($user);
	
	$usersArray[] = "1:".$user["userName"].":2:".$user["userID"].":9:".$user["icon"].":10:".$user["color1"].":11:".$user["color2"].":51:".$user["color3"].":14:".$user["iconType"].":15:".$user["special"].":16:".$user["extID"].":3:".$user["stars"].":52:".$user["moons"].":4:".$user["demons"].":13:".$user["coins"].":17:".$user["userCoins"].":46:".$user["diamonds"].":62:".$user["gF"].":63:".$user["gO"];
}

exit(implode("|", $usersArray));
?>