<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if(!$person['success']) exit(Dashboard::renderToast("xmark", Dashboard::string("errorLoginRequired"), "error", "account/login", "box"));

if(Library::checkPermission($person, "dashboardManageLevels") && isset($_POST['listID']) && isset($_POST['listName']) && isset($_POST['listAuthor'])) {
	$listID = Escape::number($_POST['listID']);
	
	$list = Library::getListByID($listID);
	if(!$list) exit(Dashboard::renderToast("xmark", Dashboard::string("errorListNotFound"), "error"));
	
	$newListName = trim(Escape::latin($_POST['listName'], 25));
	$newListAuthor = Escape::number($_POST['listAuthor']);
	if(!$newListName || !$newListAuthor) exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
	
	if(Security::checkFilterViolation($person, $newListName, 3)) exit(Dashboard::renderToast("xmark", Dashboard::string("errorBadName"), "error"));
	
	$newListAuthorArray = Library::getUserByAccountID($newListAuthor);
	if(!$newListAuthorArray) exit(Dashboard::renderToast("xmark", Dashboard::string("errorPlayerNotFound"), "error"));
	
	$newListDesc = Library::escapeDescriptionCrash(Escape::text(trim($_POST['listDesc']), 300));
	if(Security::checkFilterViolation($person, $newListDesc, 3)) exit(Dashboard::renderToast("xmark", Dashboard::string("errorBadDesc"), "error"));
	
	$newDiamonds = Security::limitValue(0, abs(Escape::number($_POST['diamonds']) ?: 0), 10);
	$newDifficulty = Escape::number($_POST['difficulty']) ?: 0;
	$newListRateType = !empty($_POST['listRateType']) ? 1 : 0;
	
	$newListPrivacy = Security::limitValue(0, abs(Escape::number($_POST['listPrivacy']) ?: 0), 2);
	
	$newLevels = Escape::multiple_ids($_POST['levels']) ?: '';
	$newLevelsArray = array_unique(explode(',', $newLevels));
	
	if(count($newLevelsArray) == 0) exit(Dashboard::renderToast("xmark", Dashboard::string("errorMapPackNoLevels"), "error"));
	
	$newLevels = implode(',', $newLevelsArray);
	
	$friendsString = Library::getFriendsQueryString($accountID);
	$filters = ["levelID IN (".$newLevels.") AND (
			unlisted != 1 OR
			(unlisted = 1 AND (levels.extID IN (".$friendsString.")))
		)"];
	
	$levelsArray = Library::getLevels($filters, '', '', '', 0);
	
	if($levelsArray['count'] != count($newLevelsArray)) exit(Dashboard::renderToast("xmark", Dashboard::string("errorMultipleLevelsNotFound"), "error"));
	
	$newLevelsCount = Security::limitValue(0, abs(Escape::number($_POST['countForReward']) ?: 0), count($newLevelsArray));
	if(!$newLevelsCount || !$newDiamonds) $newLevelsCount = $newDiamonds ? count($newLevelsArray) : 0;
	
	$newUpdatesLock = isset($_POST['updatesLock']) ? 1 : 0;
	$newCommentingLock = isset($_POST['commentingLock']) ? 1 : 0;
	
	if($list['listName'] != $newListName) Library::renameList($listID, $person, $newListName);
	if($list['accountID'] != $newListAuthorArray['extID']) Library::moveList($listID, $person, $newListAuthorArray);
	if($list['listDesc'] != Escape::url_base64_encode($newListDesc)) Library::changeListDescription($listID, $person, $newListDesc);
	
	if($list['starStars'] != $newDiamonds ||
		$list['starDifficulty'] != $newDifficulty ||
		$list['starFeatured'] != $newListRateType ||
		$list['countForReward'] != $newLevelsCount
	) Library::rateList($listID, $person, $newDiamonds, $newDifficulty, $newListRateType, $newLevelsCount);
	
	if($list['unlisted'] != $newListPrivacy) Library::changeListPrivacy($listID, $person, $newListPrivacy);
	
	if($list['listlevels'] != $newLevels) Library::changeListLevels($listID, $person, $newLevels);
	
	if($list['updateLocked'] != $newUpdatesLock) Library::lockUpdatingList($listID, $person, $newUpdatesLock);
	if($list['commentLocked'] != $newCommentingLock) Library::lockCommentingOnList($listID, $person, $newCommentingLock);
	
	exit(Dashboard::renderToast("check", Dashboard::string("successAppliedSettings"), "success", '@', "settings"));
}

exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
?>