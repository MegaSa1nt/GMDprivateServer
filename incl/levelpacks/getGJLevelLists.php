<?php
require __DIR__."/../../config/misc.php";
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$person = $sec->loginPlayer();
if(!$person["success"]) exit(CommonError::InvalidRequest);
$accountID = $person['accountID'];

$time = time();
$str = $echoString = $userString = '';
$order = "uploadDate";
$isIDSearch = false;

$getFilters = Library::getListSearchFilters($_POST, false, false);
$filters = $getFilters['filters'];
$type = $getFilters['type'];

// Type detection
$str = Escape::text($_POST["str"]) ?: '';
$pageOffset = is_numeric($_POST["page"]) ? abs(Escape::number($_POST["page"]) ?: 0) * 10 : 0;

switch($type) {
	case 0: // Search
		$order = "likes";
		if(!empty($str)) {
			if(is_numeric($str)) {
				$friendsString = Library::getFriendsQueryString($accountID);

				$filters = ["listID = ".$str." AND (
					unlisted != 1 OR
					(unlisted = 1 AND (accountID IN (".$friendsString.")))
				)"];
			} else {
				$firstCharacter = $enableUserLevelsSearching ? substr($str, 0, 1) : '';
				
				if($firstCharacter == 'a') {
					$potentialAccountID = substr($str, 1);
					if(is_numeric($potentialAccountID)) {
						$filters[] = "accountID = ".$potentialAccountID;
						break;
					}
				}
				
				$filters[] = "listName LIKE '%".$str."%'";
				break;
			}
		}
		break;
	case 1: // Most downloaded
		$order = "downloads";
		break;
	case 2: // Most liked
		$order = "likes";
		break;
	case 3: // Trending
		$uploadDate = $time - (7 * 24 * 60 * 60);
		$filters[] = "uploadDate > ".$uploadDate;
		$order = "likes";
		break;
	case 5: // Levels per user
		if($accountID && $accountID == $str) $filters = [];
		$filters[] = "accountID = '".$str."'";
		break;
	case 6: // Top lists
		$filters[] = "lists.starStars > 0 AND lists.starFeatured > 0";
		$order = "downloads";
		break;
	case 7: // Magic
		$order = "likes";
		break;
	case 11: // Rated
		$filters[] = "lists.starStars > 0";
		$order = "downloads";
		break;
	case 12: // Lists from followed accounts
		$followed = Escape::multiple_ids($_POST["followed"]);
		$filters[] = $followed ? "lists.accountID IN (".$followed.")" : "1 != 1";
		break;
	case 13: // Friends
		$friendsArray = Library::getFriends($accountID);
		$friendsString = "'".implode("','", $friendsArray)."'";
		
		$filters[] = $friendsString ? "lists.accountID IN (".$friendsString.")" : "1 != 1";
		break;
	case 27: // Sent
		$filters[] = "suggest.suggestLevelId < 0";
		$order = "suggest.timestamp";
		$morejoins = "LEFT JOIN suggest ON lists.listID * -1 LIKE suggest.suggestLevelId";
		break;
}

$lists = Library::getLists($person, $filters, $order, $pageOffset);

foreach($lists['lists'] as &$list) {
	$list['listName'] = Escape::gd(Escape::translit($list['listName']));
	$list['listDesc'] = Escape::translit($list['listDesc']);
	
	$list['likes'] = $list['likes'] - $list['dislikes'];
	$list['userName'] = Library::makeClanUsername($list['accountID']);
	
	$echoString .= "1:".$list['listID'].":2:".$list['listName'].":3:".$list['listDesc'].":5:".$list['listVersion'].":49:".$list['accountID'].":50:".$list['userName'].":10:".$list['downloads'].":7:".$list['starDifficulty'].":14:".$list['likes'].":19:".$list['starFeatured'].":51:".$list['listlevels'].":55:".$list['starStars'].":56:".$list['countForReward'].":28:".$list['uploadDate'].":29:".$list['updateDate']."|";
	$userString .= Library::getUserString($list)."|";
}
$echoString = rtrim($echoString, "|");
$userString = rtrim($userString, "|");
exit($echoString."#".$userString."#".$lists['count'].":".$pageOffset.":10"."#Welcome_to_PlusGDPS_from_GreenCatsServer");
?>