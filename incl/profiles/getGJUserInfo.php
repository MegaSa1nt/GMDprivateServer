<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$player = $sec->loginPlayer();
if(!$player["success"]) exit(CommonError::InvalidRequest);
$accountID = $player["accountID"];
$userID = $player["userID"];
$userName = $player["userName"];

$targetAccountID = Escape::number($_POST['targetAccountID']);
$targetUserID = Library::getUserID($targetAccountID);
if(!$targetUserID) exit(CommonError::InvalidRequest);

$isBlocked = Library::isPersonBlocked($accountID, $targetAccountID);
if($isBlocked) exit(CommonError::InvalidRequest);

$user = Library::getUserByID($targetUserID);
$account = Library::getAccountByID($targetAccountID);

$queryText = Library::getBannedPeopleQuery(0, true);

$user['rank'] = Library::getUserRank($user['stars'], $user['moons']);
$user['creatorPoints'] = round($user["creatorPoints"], PHP_ROUND_HALF_DOWN);

$user['messagesState'] = $account['mS'];
$user['friendRequestsState'] = $account['frS'];
$user['commentsState'] = $account['cS'];

$user['youtubeurl'] = $account['youtubeurl'];
$user['twitter'] = $account['twitter'];
$user['twitch'] = $account['twitch'];

$person = [
	'accountID' => $user['extID'],
	'userID' => $user['userID'],
	'IP' => $user['IP']
];

$userAppearance = Library::getPersonCommentAppearance($person);
$user['badge'] = $userAppearance['modBadgeLevel'];

if($accountID == $targetAccountID) {
	$requestsCount = $db->prepare("SELECT count(*) FROM friendreqs WHERE toAccountID = :accountID AND isNew = 1");
	$requestsCount->execute([':accountID' => $accountID]);
	$requestsCount = $requestsCount->fetchColumn();
	
	$newMessagesCount = $db->prepare("SELECT count(*) FROM messages WHERE toAccountID = :accountID AND isNew = 0");
	$newMessagesCount->execute([':accountID' => $accountID]);
	$newMessagesCount = $newMessagesCount->fetchColumn();
	
	$newFriendRequestsCount = $db->prepare("SELECT count(*) FROM friendships WHERE (person1 = :accountID AND isNew1 = 1) OR (person2 = :accountID AND isNew2 = 1)");
	$newFriendRequestsCount->execute([':accountID' => $accountID]);
	$newFriendRequestsCount = $newFriendRequestsCount->fetchColumn();
	
	$user['incomingRequestText'] = ":38:".$newMessagesCount.":39:".$requestsCount.":40:".$newFriendRequestsCount;
} else {
	$isFriends = Library::isFriends($accountID, $targetAccountID);
	if($isFriends) $user['friendsState'] = 1;
	else {
		$incomingFriendRequest = Library::getFriendRequest($targetAccountID, $accountID);
		if($incomingFriendRequest) {
			$incomingFriendRequestTime = Library::makeTime($incomingFriendRequest["uploadDate"]);
			$user['incomingRequestText'] = ":32:".$incomingFriendRequest["ID"].":35:".$incomingFriendRequest["comment"].":37:".$incomingFriendRequestTime;
			$user['friendsState'] = 3;
		} else {
			$outcomingFriendRequest = Library::getFriendRequest($accountID, $targetAccountID);
			if($outcomingFriendRequest) $user['friendsState'] = 4;
		}
	}
}
exit(Library::returnUserString($user));
?>