<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$person = $sec->loginPlayer();
if(!$person["success"]) exit(CommonError::InvalidRequest);

$usersString = '';
$type = Escape::number($_POST["type"]);

switch($type) {
	case 0:
		$isBlocks = false;
		$users = Library::getFriendships($person);
		break;
	case 1:
		$isBlocks = true;
		$users = Library::getBlocks($person);
		break;
	case 2:
		exit(CommonError::InvalidRequest);
}

if(empty($users)) exit(CommonError::NothingFound);

foreach($users AS &$user) $usersString .= Library::returnFriendshipsString($person, $user, $isBlocks)."|";

exit(rtrim($usersString, "|"));
?>