<?php
require __DIR__."/../../config/misc.php";
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$person = $sec->loginPlayer();
if(!$person["success"]) exit(CommonError::InvalidRequest);
$accountID = $person["accountID"];
$userID = $person["userID"];

$time = time();
$echoString = $userString = $songsString = $queryJoin = '';
$levelsStatsArray = [];
$order = "uploadDate";
$orderSorting = "DESC";
$orderEnabled = $isIDSearch = $noLimit = false;

$str = Escape::text($_POST["str"]) ?: '';
$gameVersion = abs(Escape::number($_POST["gameVersion"]) ?: 18);

$pageOffset = is_numeric($_POST["page"]) ? abs(Escape::number($_POST["page"]) * 10) : 0;

$getFilters = Library::getLevelSearchFilters($_POST, $gameVersion, false, false);
$filters = $getFilters['filters'];
$type = $getFilters['type'];

// Type detection
switch($type) {
	case 0: // Search
	case 15: // Most liked, changed to 15 in GDW for whatever reason
		$order = "likes";
		if(!empty($str)) {
			if(is_numeric($str)) {
				$friendsArray = Library::getFriends($accountID);
				$friendsArray[] = $accountID;
				$friendsString = "'".implode("','", $friendsArray)."'";
				
				$filters = ["levelID = ".$str." AND (
					unlisted != 1 OR
					(unlisted = 1 AND (extID IN (".$friendsString.")))
				)"];
				
				$isIDSearch = true;
			} else {
				$firstCharacter = $enableUserLevelsSearching ? substr($str, 0, 1) : 'd';
				switch($firstCharacter) {
					case 'u':
						$potentialUserID = substr($str, 1);
						if(is_numeric($potentialUserID)) {
							$filters[] = "userID = ".$potentialUserID;
							break;
						}
					case 'a':
						$potentialAccountID = substr($str, 1);
						if(is_numeric($potentialAccountID)) {
							$filters[] = "extID = ".$potentialAccountID;
							break;
						}
					default:
						$filters[] = "levelName LIKE '%".$str."%'";
						break;
				}
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
		if($userID == $str) $filters = [];
		$filters[] = "levels.userID = '".$str."'";
		break;
	case 6: // Featured
	case 17: // Featured in GDW
		if($gameVersion > 21) $filters[] = "NOT starFeatured = 0 OR NOT starEpic = 0";
		else $filters[] = "NOT starFeatured = 0";
		$order = "starFeatured DESC, rateDate DESC, uploadDate";
		break;
	case 16: // Hall of Fame
		$filters[] = "NOT starEpic = 0";
		$order = "starFeatured DESC, rateDate DESC, uploadDate";
		break;
	case 7: // Magic
        $filters[] = "objects > 9999"; // L
		break;
	case 10: // Map Packs
	case 19: // Unknown, but same as Map Packs (on real GD type 10 has star rated filter and 19 doesn't)
		$levelsArray = explode(',', $str);
		$levelsText = '';
		
		foreach($levelsArray AS $levelKey => $levelID) $levelsText .= 'WHEN levelID = '.$levelID.' THEN '.($levelKey + 1).PHP_EOL;
		
		$order = 'CASE
			'.$levelsText.'
		END';
		$orderSorting = 'ASC';
		
		$friendsArray = Library::getFriends($accountID);
		$friendsArray[] = $accountID;
		$friendsString = "'".implode("','", $friendsArray)."'";
		
		$filters[] = "levelID IN (".$str.") AND (
				unlisted != 1 OR
				(unlisted = 1 AND (extID IN (".$friendsString.")))
			)";
			
		$noLimit = true;
		break;
	case 11: // Awarded
		$filters[] = "NOT starStars = 0";
		$order = "rateDate DESC, uploadDate";
		break;
	case 12: // Followed
		$followed = Escape::multiple_ids($_POST["followed"]);
		$filters[] = $followed ? "extID IN (".$followed.")" : "1 != 1";
		break;
	case 13: // Friends
		$friendsArray = Library::getFriends($accountID);
		$friendsString = "'".implode("','", $friendsArray)."'";
		$filters[] = $friendsString ? "extID IN (".$friendsString.")" : "1 != 1";
		break;
	case 21: // Daily safe
		$queryJoin = "INNER JOIN dailyfeatures ON levels.levelID = dailyfeatures.levelID";
		$filters[] = "dailyfeatures.type = 0 AND timestamp < ".$time;
		$order = "dailyfeatures.feaID";
		break;
	case 22: // Weekly safe
		$queryJoin = "INNER JOIN dailyfeatures ON levels.levelID = dailyfeatures.levelID";
		$filters[] = "dailyfeatures.type = 1 AND timestamp < ".$time;
		$order = "dailyfeatures.feaID";
		break;
	case 23: // Event safe
		$queryJoin = "INNER JOIN events ON levels.levelID = events.levelID";
		$filters[] = "timestamp < ".$time;
		$order = "events.feaID";
		break;
	case 25: // List levels
		$listLevels = Library::getListLevels($str);
		
		$friendsArray = Library::getFriends($accountID);
		$friendsArray[] = $accountID;
		$friendsString = "'".implode("','", $friendsArray)."'";
		
		$filters = ["levelID IN (".$listLevels.") AND (
				unlisted != 1 OR
				(unlisted = 1 AND (extID IN (".$friendsString.")))
			)"];
			
		$noLimit = true;
		break;
	case 27: // Sent levels
		$queryJoin = "JOIN (SELECT suggestLevelId AS levelID, MAX(suggest.timestamp) AS timestamp FROM suggest GROUP BY levelID) suggest ON levels.levelID = suggest.levelID";
		$filters[] = "suggest.levelID > 0";
		
		if(!$ratedLevelsInSent) $filters[] = "starStars = 0";
		
		$order = 'suggest.timestamp';
		break;
}

$levels = Library::getLevels($filters, $order, $orderSorting, $queryJoin, $pageOffset, $noLimit);

foreach($levels['levels'] as &$level) {
	if(empty($level["levelID"])) continue;

	$levelsStatsArray[] = ["levelID" => $level["levelID"], "stars" => $level["starStars"], 'coins' => $level["starCoins"]];
	
	$level['levelDesc'] = Escape::translit(Escape::url_base64_decode($level['levelDesc']));
	if($gameVersion < 20) $level['levelDesc'] = Escape::gd($level['levelDesc']);
	else $level['levelDesc'] = Escape::url_base64_encode($level['levelDesc']);

	if(isset($gauntlet)) $echoString .= "44:1:";
	$echoString .= "1:".$level["levelID"].":2:".Escape::translit($level["levelName"]).":5:".$level["levelVersion"].":6:".$level["userID"].":8:".$level["difficultyDenominator"].":9:".$level["starDifficulty"].":10:".$level["downloads"].":12:".$level["audioTrack"].":13:".$level["gameVersion"].":14:".$level["likes"].":16:".$level["dislikes"].":17:".$level["starDemon"].":43:".$level["starDemonDiff"].":25:".$level["starAuto"].":18:".$level["starStars"].":19:".$level["starFeatured"].":42:".$level["starEpic"].":45:".$level["objects"].":3:".$level["levelDesc"].":15:".$level["levelLength"].":28:".Library::makeTime($level['uploadDate']).($level['updateDate'] ? ":29:".Library::makeTime($level['updateDate']) : "").":30:".$level["original"].":31:".$level['twoPlayer'].":37:".$level["coins"].":38:".$level["starCoins"].":39:".$level["requestedStars"].":46:".$level["wt"].":47:".$level["wt2"].":40:".$level["isLDM"].":35:".$level["songID"]."|";

	if($level["songID"] != 0) {
		$song = Library::getSongString($level["songID"]);
		if($song) $songsString .= $song."~:~";
	}

	$userString .= Library::getUserString($level)."|";
}

if($showUnknownLevel && !$levels['count'] && $isIDSearch) {
	$levelID = abs(Escape::number($str) ?: 0);
	
	$levelsStatsArray[] = ["levelID" => $levelID, "stars" => 0, 'coins' => 0];
	
	$echoString = "1:".$levelID.":2:Unknown level:5:0:6:0:8:0:9:0:10:-1:12:1:13:".$gameVersion.":14:0:16:0:17:0:43:0:25:0:18:0:19:0:42:0:45:0:3:VGhpcyBsZXZlbCB3YXMgZGVsZXRlZCwgbmV2ZXIgZXhpc3RlZCBvciB5b3UgaGF2ZSBubyBhY2Nlc3MgdG8gaXQu:15:0:28:NA:30:0:31:0:37:0:38:0:39:0:46:0:47:0:40:0:35:0|";
	$userString = '0:-:0|';
}

$echoString = rtrim($echoString, "|");
$userString = rtrim($userString, "|");
$songsString = rtrim($songsString, "~:~");
exit($echoString."#".$userString.($gameVersion > 18 ? "#".$songsString : '')."#".$levels['count'].":".$pageOffset.":10#".Security::generateLevelsHash($levelsStatsArray));
?>