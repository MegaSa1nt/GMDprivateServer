<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
$userID = $person['userID'];

// Level page
if($_GET['id']) {
	$contextMenuData = [];
	
	$parameters = explode("/", Escape::text($_GET['id']));
	
	$levelID = Escape::number($parameters[0]);
	
	$level = Library::getLevelByID($levelID);
	if(!$level || !Library::canAccountPlayLevel($person, $level)) exit(Dashboard::renderErrorPage(Dashboard::string("levelsTitle"), Dashboard::string("errorLevelNotFound"), '../../'));

	$user = Library::getUserByID($level['userID']);
	$userName = $user ? $user['userName'] : 'Undefined';
	
	$userAttributes = [];
	
	$userMetadata = Dashboard::getUserMetadata($user);
	
	$levelLengths = ['Tiny', 'Short', 'Medium', 'Long', 'XL', 'Platformer'];
	
	$level['LEVEL_TITLE'] = sprintf(Dashboard::string('levelTitle'), $level['levelName'], Dashboard::getUsernameString($person, $user, $userName, $userMetadata['mainIcon'], $userMetadata['userAppearance'], $userMetadata['userAttributes']));
	$level['LEVEL_DESCRIPTION'] = Dashboard::parseMentions($person, htmlspecialchars(Escape::url_base64_decode($level['levelDesc']))) ?: "<i>".Dashboard::string('noDescription')."</i>";
	$level['LEVEL_DIFFICULTY_IMAGE'] = Library::getLevelDifficultyImage($level);
	
	$level['LEVEL_LENGTH'] = $levelLengths[$level['levelLength']];
		
	$contextMenuData['MENU_SHOW_NAME'] = 'false';
		
	$contextMenuData['MENU_ID'] = $level['levelID'];
	
	$contextMenuData['MENU_CAN_MANAGE'] = ($person['accountID'] == $level['extID'] || Library::checkPermission($person, "dashboardManageLevels")) ? 'true' : 'false';
	$contextMenuData['MENU_CAN_DELETE'] = ($person['accountID'] == $level['extID'] || Library::checkPermission($person, "gameDeleteLevel")) ? 'true' : 'false';
	
	$contextMenuData['MENU_SHOW_MANAGE_HR'] = ($contextMenuData['MENU_CAN_MANAGE'] == 'true' || $contextMenuData['MENU_CAN_DELETE'] == 'true') ? 'true' : 'false';
	
	$level['LEVEL_CONTEXT_MENU'] = Dashboard::renderTemplate('components/menus/level', $contextMenuData);
	
	$song = $level['songID'] ? Library::getSongByID($level['songID']) : Library::getAudioTrack($level['audioTrack']);
	
	if($song) $level['LEVEL_SONG'] = $song['authorName']." - ".$song['name'];
	else $level['LEVEL_SONG'] = Dashboard::string("unknownSong");
	$level['LEVEL_SONG_ID'] = $song['ID'] ?: '';
	$level['LEVEL_SONG_AUTHOR'] = $song['authorName'] ?: '';
	$level['LEVEL_SONG_TITLE'] = $song['name'] ?: '';
	$level['LEVEL_SONG_URL'] = urlencode(urldecode($song['download'])) ?: '';
	$level['LEVEL_IS_CUSTOM_SONG'] = isset($song['ID']) ? 'true' : 'false';
	
	$songIDs = $level['songIDs'] ? explode(',', $level['songIDs']) : [];
	if($song['ID']) array_unshift($songIDs, $level['songID']);
	$sfxIDs = $level['sfxIDs'] ? explode(',', $level['sfxIDs']) : [];
	
	$songIDs = array_unique($songIDs);
	$sfxIDs = array_unique($sfxIDs);
	
	$level['LEVEL_SONGS'] = count($songIDs);
	$level['LEVEL_SFXS'] = count($sfxIDs);
	
	$level['LEVEL_SONG_IDS'] = implode(',', $songIDs);
	$level['LEVEL_SFX_IDS'] = implode(',', $sfxIDs);
	
	$level['LEVEL_HAS_REQUESTED_STARS'] = $level['requestedStars'] ? 'true' : 'false';
	
	switch($level['unlisted']) {
		case 0:
			$level['LEVEL_PRIVACY_ICON'] = 'eye';
			$level['LEVEL_PRIVACY_TEXT'] = Dashboard::string("public");
			break;
		case 1:
			$level['LEVEL_PRIVACY_ICON'] = 'lock';
			$level['LEVEL_PRIVACY_TEXT'] = Dashboard::string("onlyForFriends");
			break;
		default:
			$level['LEVEL_PRIVACY_ICON'] = 'eye-slash';
			$level['LEVEL_PRIVACY_TEXT'] = Dashboard::string("unlisted");
			break;
	}
	
	$levelStatsCount = Library::getLevelStatsCount($levelID);

	$level['LEVEL_COMMENTS'] = $levelStatsCount['comments'];
	$level['LEVEL_SCORES'] = $levelStatsCount['scores'];
	
	$level['LEVEL_CAN_SEE_PASSWORD'] = (Library::checkPermission($person, "dashboardModeratorTools") && strlen($level['password']) > 1) ? 'true' : 'false';
	$level['LEVEL_PASSWORD'] = $level['LEVEL_CAN_SEE_PASSWORD'] == 'true' ? substr($level['password'], 1) : 'No password for you :)';
	
	$level['LEVEL_CAN_MANAGE'] = Library::checkPermission($person, "dashboardManageLevels") ? 'true' : 'false';
	
	$pageBase = '../../';
	$level['LEVEL_IS_NOTHING_OPENED'] = 'true';
	$level['LEVEL_ADDITIONAL_PAGE'] = '';
	
	if(isset($parameters[1])) {
		$additionalPage = '';
		$pageBase = '../../../';
		$level['LEVEL_IS_NOTHING_OPENED'] = 'false';
		
		$pageOffset = is_numeric($_GET["page"]) ? abs(Escape::number($_GET["page"]) - 1) * 10 : 0;
		
		switch($parameters[1]) {
			case 'comments':
				$mode = isset($_GET['mode']) ? Escape::number($_GET["mode"]) : 0;
				$sortMode = $mode ? "comments.likes - comments.dislikes" : "comments.timestamp";
				
				$comments = Library::getCommentsOfLevel($levelID, $sortMode, $pageOffset);
				
				foreach($comments['comments'] AS &$comment) $additionalPage .= Dashboard::renderCommentCard($comment, $person);
				
				$pageNumber = ceil($pageOffset / 10) + 1 ?: 1;
				$pageCount = floor(($comments['count'] - 1) / 10) + 1;
				
				if($pageCount == 0) $pageCount = 1;
				
				$emojisDiv = Dashboard::renderEmojisDiv();
				
				$additionalData = [
					'ADDITIONAL_PAGE' => $additionalPage,
					'COMMENT_NO_COMMENTS' => !$comments['count'] ? 'true' : 'false',
					'COMMENT_PAGE_TEXT' => sprintf(Dashboard::string('pageText'), $pageNumber, $pageCount),
					'LEVEL_ID' => $levelID,
					
					'COMMENT_CAN_POST' => 'true',
					
					'COMMENT_EMOJIS_DIV' => $emojisDiv,
					
					'IS_FIRST_PAGE' => $pageNumber == 1 ? 'true' : 'false',
					'IS_LAST_PAGE' => $pageNumber == $pageCount ? 'true' : 'false',
					
					'FIRST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY', 'settings')",
					'PREVIOUS_PAGE_BUTTON' => "getPage('@".(($pageNumber - 1) > 1 ? "page=".($pageNumber - 1) : 'page=REMOVE_QUERY')."', 'settings')",
					'NEXT_PAGE_BUTTON' => "getPage('@page=".($pageNumber + 1)."', 'settings')",
					'LAST_PAGE_BUTTON' => "getPage('@page=".$pageCount."', 'settings')"
				];
				
				if(!$additionalPage) $additionalData['COMMENT_NO_COMMENTS'] = 'true';
				
				$level['LEVEL_ADDITIONAL_PAGE'] = Dashboard::renderTemplate('browse/comments', $additionalData);
				break;
			case 'scores':
				$levelIsPlatformer = $level['levelLength'] == 5;
			
				$type = abs(Escape::number($_GET['type']) ?: 0);
				$mode = $_GET['mode'] == 1 ? 'points' : 'time';
				$dailyID = $_GET['isDaily'] ? 1 : 0;
				
				$scores = $levelIsPlatformer ? Library::getPlatformerLevelScores($levelID, $person, $type, $dailyID, $mode, $pageOffset) : Library::getLevelScores($levelID, $person, $type, $dailyID, $pageOffset);
				
				$pageNumber = ceil($pageOffset / 10) + 1 ?: 1;
				$pageCount = floor(($scores['count'] - 1) / 10) + 1;
				
				if($pageCount == 0) $pageCount = 1;
				
				$scoreNumber = $pageOffset;
				foreach($scores['scores'] AS &$score) {
					$scoreNumber++;
					
					$score['SCORE_NUMBER'] = $scoreNumber;
					$additionalPage .= Dashboard::renderScoreCard($score, $person, $levelIsPlatformer);
				}
				
				$pageNumber = ceil($pageOffset / 10) + 1 ?: 1;
				$pageCount = floor(($scores['count'] - 1) / 10) + 1;
				
				if($pageCount == 0) $pageCount = 1;
				
				$additionalData = [
					'ADDITIONAL_PAGE' => $additionalPage,
					'SCORE_NO_SCORES' => !$scores['count'] ? 'true' : 'false',
					'SCORE_IS_PLATFORMER' => $levelIsPlatformer ? 'true' : 'false',
					'SCORE_IS_DAILY' => $dailyID ? 'true' : 'false',
					'COMMENT_PAGE_TEXT' => sprintf(Dashboard::string('pageText'), $pageNumber, $pageCount),
					
					'SCORE_ENABLE_FILTERS' => 'true',
					
					'IS_FIRST_PAGE' => $pageNumber == 1 ? 'true' : 'false',
					'IS_LAST_PAGE' => $pageNumber == $pageCount ? 'true' : 'false',
					
					'FIRST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY', 'settings')",
					'PREVIOUS_PAGE_BUTTON' => "getPage('@".(($pageNumber - 1) > 1 ? "page=".($pageNumber - 1) : 'page=REMOVE_QUERY')."', 'settings')",
					'NEXT_PAGE_BUTTON' => "getPage('@page=".($pageNumber + 1)."', 'settings')",
					'LAST_PAGE_BUTTON' => "getPage('@page=".$pageCount."', 'settings')"
				];
				
				$level['LEVEL_ADDITIONAL_PAGE'] = Dashboard::renderTemplate('browse/scores', $additionalData);
				break;
			case 'songs':
				if(empty($level['LEVEL_SONG_IDS'])) {
					$pageNumber = $pageCount = 1;
				
					$additionalData = [
						'ADDITIONAL_PAGE' => '',
						'SONG_PAGE_TEXT' => sprintf(Dashboard::string('pageText'), $pageNumber, $pageCount),
						'SONG_NO_SONGS' => 'true',
						
						'IS_FIRST_PAGE' => $pageNumber == 1 ? 'true' : 'false',
						'IS_LAST_PAGE' => $pageNumber == $pageCount ? 'true' : 'false',
						
						'FIRST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY', 'settings')",
						'PREVIOUS_PAGE_BUTTON' => "getPage('@".(($pageNumber - 1) > 1 ? "page=".($pageNumber - 1) : 'page=REMOVE_QUERY')."', 'settings')",
						'NEXT_PAGE_BUTTON' => "getPage('@page=".($pageNumber + 1)."', 'settings')",
						'LAST_PAGE_BUTTON' => "getPage('@page=".$pageCount."', 'settings')"
					];
					
					$level['LEVEL_ADDITIONAL_PAGE'] = Dashboard::renderTemplate("browse/songs", $additionalData);
					break;
				}
				
				$favouriteSongs = [];
				if($person['success']) {
					$favouriteSongsArray = Library::getFavouriteSongs($person, 0, false);
					
					foreach($favouriteSongsArray['songs'] AS &$favouriteSong) $favouriteSongs[] = $favouriteSong["songID"];
				}
				
				$filters = ["songs.ID IN (".Escape::multiple_ids($level['LEVEL_SONG_IDS']).")", "songs.isDisabled = 0"];
				$additionalPage = '';

				$songs = Library::getSongs($filters, '', '', '', $pageOffset, 10);

				foreach($songs['songs'] AS &$song) $additionalPage .= Dashboard::renderSongCard($song, $person, $favouriteSongs);

				$pageNumber = ceil($pageOffset / 10) + 1 ?: 1;
				$pageCount = floor(($songs['count'] - 1) / 10) + 1;

				$additionalData = [
					'ADDITIONAL_PAGE' => $additionalPage,
					'SONG_PAGE_TEXT' => sprintf(Dashboard::string('pageText'), $pageNumber, $pageCount),
					'SONG_NO_SONGS' => empty($additionalPage) ? 'true' : 'false',
					
					'IS_FIRST_PAGE' => $pageNumber == 1 ? 'true' : 'false',
					'IS_LAST_PAGE' => $pageNumber == $pageCount ? 'true' : 'false',
					
					'FIRST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY', 'settings')",
					'PREVIOUS_PAGE_BUTTON' => "getPage('@".(($pageNumber - 1) > 1 ? "page=".($pageNumber - 1) : 'page=REMOVE_QUERY')."', 'settings')",
					'NEXT_PAGE_BUTTON' => "getPage('@page=".($pageNumber + 1)."', 'settings')",
					'LAST_PAGE_BUTTON' => "getPage('@page=".$pageCount."', 'settings')"
				];
				
				$level['LEVEL_ADDITIONAL_PAGE'] = Dashboard::renderTemplate("browse/songs", $additionalData);
				break;
			case 'sfxs':
				if(empty($sfxIDs)) {
					$pageNumber = $pageCount = 1;
				
					$additionalData = [
						'ADDITIONAL_PAGE' => '',
						'SFX_PAGE_TEXT' => sprintf(Dashboard::string('pageText'), $pageNumber, $pageCount),
						'SFX_NO_SFXS' => 'true',
						
						'IS_FIRST_PAGE' => $pageNumber == 1 ? 'true' : 'false',
						'IS_LAST_PAGE' => $pageNumber == $pageCount ? 'true' : 'false',
						
						'FIRST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY', 'settings')",
						'PREVIOUS_PAGE_BUTTON' => "getPage('@".(($pageNumber - 1) > 1 ? "page=".($pageNumber - 1) : 'page=REMOVE_QUERY')."', 'settings')",
						'NEXT_PAGE_BUTTON' => "getPage('@page=".($pageNumber + 1)."', 'settings')",
						'LAST_PAGE_BUTTON' => "getPage('@page=".$pageCount."', 'settings')"
					];
					
					$level['LEVEL_ADDITIONAL_PAGE'] = Dashboard::renderTemplate("browse/sfxs", $additionalData);
					break;
				}

				$sfxs = Library::getSFXsByLibraryIDs($sfxIDs, $pageOffset, 10);

				foreach($sfxs['sfxs'] AS &$sfx) $additionalPage .= Dashboard::renderSFXCard($sfx, $person);

				$pageNumber = ceil($pageOffset / 10) + 1 ?: 1;
				$pageCount = floor(($sfxs['count'] - 1) / 10) + 1;

				$additionalData = [
					'ADDITIONAL_PAGE' => $additionalPage,
					'SFX_PAGE_TEXT' => sprintf(Dashboard::string('pageText'), $pageNumber, $pageCount),
					'SFX_NO_SFXS' => empty($additionalPage) ? 'true' : 'false',
					
					'IS_FIRST_PAGE' => $pageNumber == 1 ? 'true' : 'false',
					'IS_LAST_PAGE' => $pageNumber == $pageCount ? 'true' : 'false',
					
					'FIRST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY', 'settings')",
					'PREVIOUS_PAGE_BUTTON' => "getPage('@".(($pageNumber - 1) > 1 ? "page=".($pageNumber - 1) : 'page=REMOVE_QUERY')."', 'settings')",
					'NEXT_PAGE_BUTTON' => "getPage('@page=".($pageNumber + 1)."', 'settings')",
					'LAST_PAGE_BUTTON' => "getPage('@page=".$pageCount."', 'settings')"
				];
				
				$level['LEVEL_ADDITIONAL_PAGE'] = Dashboard::renderTemplate("browse/sfxs", $additionalData);
				break;
			case 'manage':
				if(!Library::checkPermission($person, "dashboardManageLevels")) exit(Dashboard::renderErrorPage(Dashboard::string("levelsTitle"), Dashboard::string("errorNoPermission"), '../../../'));
				
				$levelRateTypes = [Dashboard::string('noRating'), 'Featured', 'Epic', 'Legendary', 'Mythic'];
				$songTypes = [Dashboard::string('officialSong'), Dashboard::string('customSong')];
				$levelPrivacyNames = [Dashboard::string('publicLevel'), Dashboard::string('privateLevel'), Dashboard::string('unlistedLevel')];
				
				$levelAuthor = Library::getUserByAccountID($level['extID']);
				
				$levelRateType = $level['starEpic'] + ($level['starFeatured'] ? 1 : 0);
				$levelRateTypeName = $levelRateTypes[$levelRateType];
				
				$songType = $level['songID'] != 0 ? 1 : 0;
				$songTypeName = $songTypes[$songType];
				
				$audioTrack = Library::getAudioTrack($level['audioTrack']);
				$audioTrackName = $audioTrack['authorName'].' - '.$audioTrack['name'];
				
				$songID = $level['songID'];
				$song = $songType ? Library::getSongByID($songID) : false;
				$songName = $song ? $song['authorName'].' - '.$song['name'] : '';
				
				$levelPrivacy = $level['unlisted'];
				$levelPrivacyName = $levelPrivacyNames[$levelPrivacy];
				
				$difficulty = Escape::latin_no_spaces(Library::prepareDifficultyForRating(($level['starDifficulty'] / $level['difficultyDenominator']), $level['starAuto'], $level['starDemon'], $level['starDemonDiff']));
				$difficultyArray = Library::getLevelDifficulty($difficulty);
				$difficultyName = $difficultyArray['name'];
				
				$additionalData = [
					'LEVEL_ID' => $level['levelID'],
					
					'LEVEL_NAME' => htmlspecialchars($level['levelName']),
					'LEVEL_DESC' => htmlspecialchars(Escape::url_base64_decode($level['levelDesc'])),
					
					'LEVEL_AUTHOR_ID' => $level['extID'],
					'LEVEL_AUTHOR_NAME' => htmlspecialchars($levelAuthor['userName']),
					
					'LEVEL_STARS' => $level['starStars'],
					'LEVEL_RATE_TYPE' => $levelRateType,
					'LEVEL_RATE_TYPE_NAME' => $levelRateTypeName,
					
					'SONG_TYPE' => $songType,
					'SONG_TYPE_NAME' => $songTypeName,
					'AUDIO_TRACK' => $level['audioTrack'],
					'AUDIO_TRACK_NAME' => $audioTrackName,
					'SONG_ID' => $songID,
					'SONG_NAME' => htmlspecialchars($songName),
					
					'LEVEL_PASSWORD' => substr($level['password'], 1),
					
					'LEVEL_PRIVACY' => $levelPrivacy,
					'LEVEL_PRIVACY_NAME' => $levelPrivacyName,
					
					'DIFFICULTY' => $difficulty,
					'DIFFICULTY_NAME' => $difficultyName,
					
					'SILVER_COINS_VALUE' => $level["starCoins"] ? 1 : 0,
					'SILVER_COINS_REMOVE_CHECK' => !$level["starCoins"] ? 'checked' : '',
					
					'UPDATES_LOCK_VALUE' => $level["updateLocked"] ? 1 : 0,
					'UPDATES_LOCK_REMOVE_CHECK' => !$level["updateLocked"] ? 'checked' : '',
					
					'COMMENTING_LOCK_VALUE' => $level["commentLocked"] ? 1 : 0,
					'COMMENTING_LOCK_REMOVE_CHECK' => !$level["commentLocked"] ? 'checked' : '',
				];
				
				$level['LEVEL_ADDITIONAL_PAGE'] = Dashboard::renderTemplate('manage/level', $additionalData);
				break;
			default:
				exit(http_response_code(404));
		}
	}
	
	exit(Dashboard::renderPage("browse/level", htmlspecialchars($level['levelName']), $pageBase, $level));
}

// Search levels
$order = "uploadDate";
$orderSorting = "DESC";
$pageOffset = is_numeric($_GET["page"]) ? abs(Escape::number($_GET["page"]) - 1) * 10 : 0;
$page = '';

$getFilters = Library::getLevelSearchFilters($_GET, 22, true, false);
$filters = $getFilters['filters'];

$levels = Library::getLevels($filters, $order, $orderSorting, '', $pageOffset);

foreach($levels['levels'] AS &$level) $page .= Dashboard::renderLevelCard($level, $person, false);

$pageNumber = ceil($pageOffset / 10) + 1 ?: 1;
$pageCount = floor(($levels['count'] - 1) / 10) + 1;

$dataArray = [
	'ADDITIONAL_PAGE' => $page,
	'LEVEL_PAGE_TEXT' => sprintf(Dashboard::string('pageText'), $pageNumber, $pageCount),
	'LEVEL_NO_LEVELS' => empty($page) ? 'true' : 'false',
	
	'ENABLE_FILTERS' => 'true',
	
	'IS_FIRST_PAGE' => $pageNumber == 1 ? 'true' : 'false',
	'IS_LAST_PAGE' => $pageNumber == $pageCount ? 'true' : 'false',
	
	'FIRST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY', 'list')",
	'PREVIOUS_PAGE_BUTTON' => "getPage('@".(($pageNumber - 1) > 1 ? "page=".($pageNumber - 1) : 'page=REMOVE_QUERY')."', 'list')",
	'NEXT_PAGE_BUTTON' => "getPage('@page=".($pageNumber + 1)."', 'list')",
	'LAST_PAGE_BUTTON' => "getPage('@page=".$pageCount."', 'list')"
];

$fullPage = Dashboard::renderTemplate("browse/levels", $dataArray);

exit(Dashboard::renderPage("general/wide", Dashboard::string("levelsTitle"), "../", $fullPage));
?>