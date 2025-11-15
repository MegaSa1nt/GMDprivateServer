<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$person = $sec->loginPlayer();
if(!$person["success"]) exit(CommonError::InvalidRequest);

$friendRequestsString = '';
$page = abs(Escape::number($_POST['page']) ?: 0);
$getSent = abs(Escape::number($_POST['getSent']) ?: 0);
$pageOffset = $page * 10;

$friendRequests = Library::getFriendRequests($person, $getSent, $pageOffset);

if(empty($friendRequests['requests'])) exit(CommonError::NothingFound);

foreach($friendRequests['requests'] AS &$request) {
	$uploadTime = Library::makeTime($request["uploadDate"]);
	
	$request["userName"] = Library::makeClanUsername($request['extID']);
	$request["comment"] = Escape::url_base64_encode(Escape::translit(Escape::url_base64_decode($request["comment"])));
	
	$friendRequestsString .= Library::returnFriendRequestsString($person, $request)."|";
}

exit(rtrim($friendRequestsString, "|")."#".$friendRequests['count'].":".$pageOffset.":10");
?>