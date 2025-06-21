<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
$accountID = $person['accountID'];
$userName = $person['userName'];
$userID = $person['userID'];

$contextMenuData = [];

$parameters = explode("/", Escape::text($_GET['id']));
if(!$parameters[1]) $parameters[1] = '';

$profileUserName = $parameters[0] ? Escape::latin($parameters[0]) : $userName;

$account = Library::getAccountByUserName($profileUserName);
if(!$account) exit(http_response_code(404));
$user = Library::getUserByAccountID($account['accountID']);

$isPersonThemselves = $accountID == $user['extID']; 

$accountClan = Library::getAccountClan($account['accountID']);
$iconKit = Dashboard::getUserIconKit($user['userID']);
$userMetadata = Dashboard::getUserMetadata($user);
$profileStats = Library::getProfileStatsCount($person, $user['userID']);
$userRank = Library::getUserRank($user['stars'], $user['moons'], $user['userName']);

$canSeeCommentHistory = Library::canSeeCommentsHistory($person, $user['userID']);

$canSeeBans = ($accountID == $account['accountID'] || Library::checkPermission($person, "dashboardModTools"));
$canOpenSettings = ($accountID == $account['accountID'] || Library::checkPermission($person, "dashboardManageAccounts"));

$additionalPage = '';
$pageOffset = is_numeric($_GET["page"]) ? abs(Escape::number($_GET["page"]) - 1) * 10 : 0;

switch($parameters[1]) {
	case 'comments':
		$mode = isset($_GET['mode']) ? Escape::number($_GET["mode"]) : 0;
		$sortMode = $mode ? "comments.likes - comments.dislikes" : "comments.timestamp";
		
		if(!$canSeeCommentHistory) {
			$additionalData = [
				'ADDITIONAL_PAGE' => '',
				'LEVEL_NO_COMMENTS' => 'true',
				'COMMENT_PAGE_TEXT' => sprintf(Dashboard::string('pageText'), 1, 1),
				'LEVEL_ID' => 0,
				
				'COMMENT_CAN_POST' => 'false',
				
				'IS_FIRST_PAGE' => 'true',
				'IS_LAST_PAGE' => 'true',
				
				'FIRST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY')",
				'PREVIOUS_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY')",
				'NEXT_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY')",
				'LAST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY')"
			];
			
			$user['PROFILE_ADDITIONAL_PAGE'] = Dashboard::renderTemplate('browse/comments', $additionalData);
			$pageBase = "../../";
			break;
		}
		
		$comments = Library::getCommentsOfUser($user['userID'], $sortMode, $pageOffset);
		
		foreach($comments['comments'] AS &$comment) $additionalPage .= Dashboard::renderCommentCard($comment, $person, true);
		
		$pageNumber = ceil($pageOffset / 10) + 1 ?: 1;
		$pageCount = floor(($comments['count'] - 1) / 10) + 1;
				
		if($pageCount == 0) $pageCount = 1;
		
		$additionalData = [
			'ADDITIONAL_PAGE' => $additionalPage,
			'LEVEL_NO_COMMENTS' => !$comments['count'] ? 'true' : 'false',
			'COMMENT_PAGE_TEXT' => sprintf(Dashboard::string('pageText'), $pageNumber, $pageCount),
			
			'COMMENT_CAN_POST' => 'false',
			
			'IS_FIRST_PAGE' => $pageNumber == 1 ? 'true' : 'false',
			'IS_LAST_PAGE' => $pageNumber == $pageCount ? 'true' : 'false',
			
			'FIRST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY')",
			'PREVIOUS_PAGE_BUTTON' => "getPage('@".(($pageNumber - 1) > 1 ? "page=".($pageNumber - 1) : 'page=REMOVE_QUERY')."')",
			'NEXT_PAGE_BUTTON' => "getPage('@page=".($pageNumber + 1)."')",
			'LAST_PAGE_BUTTON' => "getPage('@page=".$pageCount."')"
		];
		
		$user['PROFILE_ADDITIONAL_PAGE'] = Dashboard::renderTemplate('browse/comments', $additionalData);
		$pageBase = "../../";
		break;
	case 'scores':
		$scores = Library::getUserLevelScores($user['extID'], $pageOffset);
		
		$pageNumber = $pageOffset * -1;
		$scoreNumber = $pageOffset;
		
		foreach($scores['scores'] AS &$score) {
			$scoreNumber++;
			
			$isPlatformer = $score['isPlatformer'] == 1;
			
			$score['SCORE_NUMBER'] = $scoreNumber;
			$additionalPage .= Dashboard::renderScoreCard($score, $person, $isPlatformer, true);
		}
		
		$pageNumber = ceil($pageOffset / 10) + 1 ?: 1;
		$pageCount = floor(($scores['count'] - 1) / 10) + 1;
		
		if($pageCount == 0) $pageCount = 1;
		
		$additionalData = [
			'ADDITIONAL_PAGE' => $additionalPage,
			'SCORE_NO_SCORES' => !$scores['count'] ? 'true' : 'false',
			'SCORE_IS_DAILY' => 'false',
			'COMMENT_PAGE_TEXT' => sprintf(Dashboard::string('pageText'), $pageNumber, $pageCount),
			
			'SCORE_ENABLE_FILTERS' => 'false',
			
			'IS_FIRST_PAGE' => $pageNumber == 1 ? 'true' : 'false',
			'IS_LAST_PAGE' => $pageNumber == $pageCount ? 'true' : 'false',
			
			'FIRST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY')",
			'PREVIOUS_PAGE_BUTTON' => "getPage('@".(($pageNumber - 1) > 1 ? "page=".($pageNumber - 1) : 'page=REMOVE_QUERY')."')",
			'NEXT_PAGE_BUTTON' => "getPage('@page=".($pageNumber + 1)."')",
			'LAST_PAGE_BUTTON' => "getPage('@page=".$pageCount."')"
		];
		
		$user['PROFILE_ADDITIONAL_PAGE'] = Dashboard::renderTemplate('browse/scores', $additionalData);
		$pageBase = "../../";
		break;
	case 'songs':
		$favouriteSongs = [];

		$favouriteSongsArray = Library::getFavouriteSongs($person, 0, false);
		foreach($favouriteSongsArray['songs'] AS &$favouriteSong) $favouriteSongs[] = $favouriteSong["songID"];

		$order = "reuploadTime";
		$orderSorting = "DESC";
		$filters = ["songs.reuploadID = '".$user['extID']."'"];

		$songs = Library::getSongs($filters, $order, $orderSorting, '', $pageOffset, 10);

		foreach($songs['songs'] AS &$song) $additionalPage .= Dashboard::renderSongCard($song, $person, $favouriteSongs);

		$pageNumber = ceil($pageOffset / 10) + 1 ?: 1;
		$pageCount = floor($songs['count'] / 10) + 1;

		$additionalData = [
			'ADDITIONAL_PAGE' => $additionalPage,
			'SONG_PAGE_TEXT' => sprintf(Dashboard::string('pageText'), $pageNumber, $pageCount),
			'SONG_NO_SONGS' => !$songs['count'] ? 'true' : 'false',
			
			'IS_FIRST_PAGE' => $pageNumber == 1 ? 'true' : 'false',
			'IS_LAST_PAGE' => $pageNumber == $pageCount ? 'true' : 'false',
			
			'FIRST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY')",
			'PREVIOUS_PAGE_BUTTON' => "getPage('@".(($pageNumber - 1) > 1 ? "page=".($pageNumber - 1) : 'page=REMOVE_QUERY')."')",
			'NEXT_PAGE_BUTTON' => "getPage('@page=".($pageNumber + 1)."')",
			'LAST_PAGE_BUTTON' => "getPage('@page=".$pageCount."')"
		];
		
		$user['PROFILE_ADDITIONAL_PAGE'] = Dashboard::renderTemplate('browse/songs', $additionalData);
		$pageBase = "../../";
		break;
	case 'sfxs':
		$order = "reuploadTime";
		$orderSorting = "DESC";
		$filters = ["sfxs.reuploadID = '".$user['extID']."'"];

		$sfxs = Library::getSFXs($filters, $order, $orderSorting, '', $pageOffset, 10);

		foreach($sfxs['sfxs'] AS &$sfx) $additionalPage .= Dashboard::renderSFXCard($sfx, $person);

		$pageNumber = ceil($pageOffset / 10) + 1 ?: 1;
		$pageCount = floor($sfxs['count'] / 10) + 1;

		$additionalData = [
			'ADDITIONAL_PAGE' => $additionalPage,
			'SFX_PAGE_TEXT' => sprintf(Dashboard::string('pageText'), $pageNumber, $pageCount),
			'SFX_NO_SFXS' => !$sfxs['count'] ? 'true' : 'false',
			
			'IS_FIRST_PAGE' => $pageNumber == 1 ? 'true' : 'false',
			'IS_LAST_PAGE' => $pageNumber == $pageCount ? 'true' : 'false',
			
			'FIRST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY')",
			'PREVIOUS_PAGE_BUTTON' => "getPage('@".(($pageNumber - 1) > 1 ? "page=".($pageNumber - 1) : 'page=REMOVE_QUERY')."')",
			'NEXT_PAGE_BUTTON' => "getPage('@page=".($pageNumber + 1)."')",
			'LAST_PAGE_BUTTON' => "getPage('@page=".$pageCount."')"
		];
		
		$user['PROFILE_ADDITIONAL_PAGE'] = Dashboard::renderTemplate('browse/sfxs', $additionalData);
		$pageBase = "../../";
		break;
	case '': // User posts
		$mode = isset($_GET['mode']) ? Escape::number($_GET["mode"]) : 0;
		$sortMode = $mode ? "acccomments.likes - acccomments.dislikes" : "acccomments.timestamp";
		
		$accountComments = Library::getAccountComments($user['userID'], $pageOffset, $sortMode);
		
		foreach($accountComments['comments'] AS &$accountPost) $additionalPage .= Dashboard::renderPostCard($accountPost, $person);
		
		$pageNumber = ceil($pageOffset / 10) + 1 ?: 1;
		$pageCount = floor(($accountComments['count'] - 1) / 10) + 1;
				
		if($pageCount == 0) $pageCount = 1;
		
		$additionalData = [
			'ADDITIONAL_PAGE' => $additionalPage,
			'PROFILE_NO_POSTS' => !$accountComments['count'] ? 'true' : 'false',
			'POST_PAGE_TEXT' => sprintf(Dashboard::string('pageText'), $pageNumber, $pageCount),
			
			'PROFILE_CAN_POST' => $userID == $user['userID'] ? 'true' : 'false',
			
			'IS_FIRST_PAGE' => $pageNumber == 1 ? 'true' : 'false',
			'IS_LAST_PAGE' => $pageNumber == $pageCount ? 'true' : 'false',
			
			'FIRST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY')",
			'PREVIOUS_PAGE_BUTTON' => "getPage('@".(($pageNumber - 1) > 1 ? "page=".($pageNumber - 1) : 'page=REMOVE_QUERY')."')",
			'NEXT_PAGE_BUTTON' => "getPage('@page=".($pageNumber + 1)."')",
			'LAST_PAGE_BUTTON' => "getPage('@page=".$pageCount."')"
		];
		
		$user['PROFILE_ADDITIONAL_PAGE'] = Dashboard::renderTemplate('browse/posts', $additionalData);
		$pageBase = "../";
		break;
	default:
		exit(http_response_code(404));
}

$user['PROFILE_PAGE_TEXT'] = sprintf(Dashboard::string('pageText'), $pageNumber, $pageCount);

$user['PROFILE_REGISTER_DATE'] = $account['registerDate'];

$user['PROFILE_USERNAME'] = $contextMenuData['MENU_NAME'] = htmlspecialchars($account['userName']);
$user['PROFILE_TITLE'] = sprintf(Dashboard::string("userProfile"), htmlspecialchars($account['userName']));

$user['PROFILE_HAS_CLAN'] = $accountClan ? 'true' : 'false';
$user['PROFILE_CLAN_NAME'] = $accountClan ? $accountClan['clan'] : Dashboard::string('notInClan');
$user['PROFILE_CLAN_COLOR'] = $accountClan ? "color: #".$accountClan['color']."" : '';
$user['PROFILE_CLAN_TITLE'] = $accountClan ? sprintf(Dashboard::string("clanProfile"), htmlspecialchars($accountClan['clan'])) : '';

$user['PROFILE_HAS_BADGE'] =  $userMetadata["userAppearance"]["modBadgeLevel"] > 0 ? 'true' : 'false';
$user['PROFILE_MODERATOR_BADGE'] = $userMetadata["userAppearance"]["modBadgeLevel"];
$user['PROFILE_ROLE'] = htmlspecialchars($userMetadata["userAppearance"]['roleName']);

$user['PROFILE_HAS_RANK'] = $userRank > 0 ? 'true' : 'false';
$user['PROFILE_IS_TOP_100'] = $userRank <= 100 ? 'true' : 'false';
$user['PROFILE_RANK'] = $userRank;

$user['PROFILE_MAIN_ICON_URL'] = $iconKit['main'];
$user['PROFILE_ICON_CUBE'] = $iconKit['cube'];
$user['PROFILE_ICON_SHIP'] = $iconKit['ship'];
$user['PROFILE_ICON_BALL'] = $iconKit['ball'];
$user['PROFILE_ICON_UFO'] = $iconKit['ufo'];
$user['PROFILE_ICON_WAVE'] = $iconKit['wave'];
$user['PROFILE_ICON_ROBOT'] = $iconKit['robot'];
$user['PROFILE_ICON_SPIDER'] = $iconKit['spider'];
$user['PROFILE_ICON_SWING'] = $iconKit['swing'];
$user['PROFILE_ICON_JETPACK'] = $iconKit['jetpack'];

$user['PROFILE_POSTS_COUNT'] = $profileStats['posts'];
$user['PROFILE_COMMENTS_COUNT'] = $profileStats['comments'];
$user['PROFILE_SCORES_COUNT'] = $profileStats['scores'];
$user['PROFILE_SONGS_COUNT'] = $profileStats['songs'];
$user['PROFILE_SFXS_COUNT'] = $profileStats['sfxs'];
$user['PROFILE_BANS_COUNT'] = $canSeeBans ? $profileStats['bans'] : 'So sneaky! :)';

$contextMenuData['MENU_SHOW_NAME'] = 'false';

$user['PROFILE_CAN_SEE_COMMENT_HISTORY'] = $contextMenuData['MENU_CAN_SEE_COMMENT_HISTORY'] = $canSeeCommentHistory ? 'true' : 'false';

$user['PROFILE_CAN_SEE_BANS'] = $contextMenuData['MENU_CAN_SEE_BANS'] = $canSeeBans ? 'true' : 'false';
$user['PROFILE_CAN_OPEN_SETTINGS'] = $contextMenuData['MENU_CAN_OPEN_SETTINGS'] = $canOpenSettings ? 'true' : 'false';
$contextMenuData['MENU_CAN_BLOCK'] = ($person['accountID'] != 0 && !$isPersonThemselves) ? 'true' : 'false';
$contextMenuData['MENU_CAN_BAN'] = (!$isPersonThemselves && Library::checkPermission($person, "dashboardModTools")) ? 'true' : 'false';

$contextMenuData['MENU_SHOW_MANAGE_HR'] = ($contextMenuData['MENU_CAN_SEE_BANS'] == 'true' || $contextMenuData['MENU_CAN_OPEN_SETTINGS'] == 'true' || $contextMenuData['MENU_CAN_BLOCK'] == 'true' || $contextMenuData['MENU_CAN_BAN'] == 'true') ? 'true' : 'false';

$user['PROFILE_CONTEXT_MENU'] = Dashboard::renderTemplate('components/menus/user', $contextMenuData);

exit(Dashboard::renderPage("browse/profile", $user['PROFILE_TITLE'], $pageBase, $user));
?>