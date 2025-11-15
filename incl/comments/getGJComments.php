<?php
require __DIR__."/../../config/misc.php";
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$person = $sec->loginPlayer();
if(!$person["success"]) exit(CommonError::InvalidRequest);

$commentsString = $usersString = "";
$usersArray = [];

$binaryVersion = isset($_POST['binaryVersion']) ? abs(Escape::number($_POST["binaryVersion"])) : 0;
$gameVersion = isset($_POST['gameVersion']) ? abs(Escape::number($_POST["gameVersion"])) : 0;
$sortMode = $_POST["mode"] ? "comments.likes - comments.dislikes" : "comments.timestamp";
$count = isset($_POST["count"]) ? Security::limitValue(10, abs(Escape::number($_POST["count"])), 20) : 10;
$page = isset($_POST["page"]) ? abs(Escape::number($_POST["page"])) : 0;

$pageOffset = $page * $count;

switch(true) {
	case isset($_POST['levelID']):
		$displayLevelID = false;
		
		$levelID = Escape::number($_POST['levelID']);
		
		if($levelID > 0) {
			$level = Library::getLevelByID($levelID);
			
			$canSeeComments = Library::canAccountPlayLevel($person, $level);
			if(!$canSeeComments) exit(CommonError::NothingFound);
			
			$comments = Library::getCommentsOfLevel($person, $levelID, $sortMode, $pageOffset, $count);
		} else {
			$listID = $levelID * -1;
			$list = Library::getListByID($listID);
			
			$canSeeComments = Library::canAccountSeeList($person, $list);
			if(!$canSeeComments) exit(CommonError::NothingFound);
			
			$comments = Library::getCommentsOfList($person, $listID, $sortMode, $pageOffset, $count);
		}
		break;
	case isset($_POST['userID']):
		$displayLevelID = true;

		$targetUserID = Escape::number($_POST['userID']);
		
		$canSeeCommentHistory = Library::canSeeCommentsHistory($person, $targetUserID);
		if(!$canSeeCommentHistory) exit(CommonError::NothingFound);
		
		$comments = Library::getCommentsOfUser($person, $targetUserID, $sortMode, $pageOffset, $count);
		break;
	default:
		exit(CommonError::InvalidRequest);
}

if(empty($comments['comments'])) exit(CommonError::NothingFound);

foreach($comments['comments'] AS &$comment) {
	$extraTextArray = [];
	$creatorRatingArray = ['1' => 'Liked by creator', '-1' => 'Disliked by creator'];
	
	if(!$comment['extID']) $comment['extID'] = Library::getAccountID($comment['userID']);
	
	if($comment['userID'] == $comment['creatorUserID'] || $comment['extID'] == $comment['creatorAccountID']) $extraTextArray[] = 'Creator';
	elseif($comment['creatorRating'] && $showCreatorRating) $extraTextArray[] = $creatorRatingArray[$comment['creatorRating']];
	
	$comment['comment'] = Escape::translit(Escape::url_base64_decode($comment["comment"]));
	$showLevelID = $displayLevelID ? $comment["levelID"] : Library::getFirstMentionedLevel($comment['comment']);
	$commentText = $gameVersion < 20 ? (trim(Escape::gd($comment["comment"])) ?: '(Empty comment)') : Escape::url_base64_encode(trim($comment["comment"]) ?: '(Empty comment)');
	
	$likes = $comment['likes'] - $comment['dislikes'];
	if($commentAutoLike && isset($specialCommentLikes[$comment["commentID"]])) $likes = $likes * $specialCommentLikes[$comment["commentID"]]; // Multiply by the specified value
	
	$user = Library::getUserByID($comment['userID']);
	
	$user["userName"] = Library::makeClanUsername($user["extID"]);
	
	if($binaryVersion > 31) {
		$playerPerson = [
			'accountID' => $user['extID'],
			'userID' => $user['userID'],
			'IP' => $user['IP'],
		];
		
		$appearance = Library::getPersonCommentAppearance($playerPerson);
		if(!empty($appearance['commentsExtraText'])) $extraTextArray[] = Escape::gd($appearance['commentsExtraText']);
		
		$personString = "~11~".$appearance['modBadgeLevel'].'~12~'.$appearance['commentColor'].":1~".$user["userName"]."~7~1~9~".$user["icon"]."~10~".$user["color1"]."~11~".$user["color2"]."~14~".$user["iconType"]."~15~".$user["special"]."~16~".$user["extID"];
	} elseif(!isset($users[$user["userID"]])) {
		$users[$user["userID"]] = true;
		$usersString .=  $user["userID"].":".$user["userName"].":".$user["extID"]."|";
	}
	$timestamp = Library::makeTime($comment['timestamp'], $extraTextArray);
	$commentsString .= ($showLevelID ? "1~".$showLevelID."~" : "")."2~".$commentText."~3~".$comment["userID"]."~4~".$likes."~5~0~7~".$comment["isSpam"]."~9~".$timestamp."~6~".$comment["commentID"]."~10~".$comment["percent"].$personString;
	$commentsString .= "|";
}

$commentsString = rtrim($commentsString, "|");
exit($commentsString.($binaryVersion < 32 ? "#".rtrim($usersString, "|") : '')."#".$comments["count"].":".$pageOffset.":".count($comments["comments"]));
?>