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
$user = Library::getUserByID($userID);

$contextMenuData = [];

$parameters = explode("/", Escape::text($_GET['id']));
if(!$parameters[1]) $parameters[1] = '';

$clanName = Escape::text($parameters[0]);

$clan = Library::getClanByName($clanName);
if(!$clan) exit(http_response_code(404));
$clanID = $clan['clanID'];
$clanMembers = explode(',', $clan['clanMembers']);

$isClanOwner = $accountID == $clan['clanOwner']; 
$isInClan = $user['clanID'] != 0;

$canPostComments = in_array($accountID, $clanMembers) || Library::checkPermission($person, 'dashboardManageClans');
$canOpenSettings = $isClanOwner || Library::checkPermission($person, 'dashboardManageClans');

$additionalPage = '';
$pageOffset = is_numeric($_GET["page"]) ? abs(Escape::number($_GET["page"]) - 1) * 10 : 0;

switch($parameters[1]) {
	case 'posts':
		$mode = isset($_GET['mode']) ? Escape::number($_GET["mode"]) : 0;
		$sortMode = $mode ? "clancomments.likes - clancomments.dislikes" : "clancomments.timestamp";
		
		$comments = Library::getCommentsOfClan($clanID, $sortMode, $pageOffset);
		
		foreach($comments['comments'] AS &$comment) $additionalPage .= Dashboard::renderPostCard($comment, $person);
		
		$pageNumber = ceil($pageOffset / 10) + 1 ?: 1;
		$pageCount = floor(($comments['count'] - 1) / 10) + 1;
				
		if($pageCount == 0) $pageCount = 1;
		
		$emojisDiv = Dashboard::renderEmojisDiv();
		
		$additionalData = [
			'ADDITIONAL_PAGE' => $additionalPage,
			'CLAN_NO_POSTS' => !$comments['count'] ? 'true' : 'false',
			'CLAN_PAGE_TEXT' => sprintf(Dashboard::string('pageText'), $pageNumber, $pageCount),
			
			'CLAN_ID' => $clanID,
			'CLAN_CAN_POST' => $canPostComments ? 'true' : 'false',
			
			'CLAN_EMOJIS_DIV' => $emojisDiv,
			
			'IS_FIRST_PAGE' => $pageNumber == 1 ? 'true' : 'false',
			'IS_LAST_PAGE' => $pageNumber == $pageCount ? 'true' : 'false',
			
			'FIRST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY')",
			'PREVIOUS_PAGE_BUTTON' => "getPage('@".(($pageNumber - 1) > 1 ? "page=".($pageNumber - 1) : 'page=REMOVE_QUERY')."')",
			'NEXT_PAGE_BUTTON' => "getPage('@page=".($pageNumber + 1)."')",
			'LAST_PAGE_BUTTON' => "getPage('@page=".$pageCount."')"
		];
		
		$clan['CLAN_ADDITIONAL_PAGE'] = Dashboard::renderTemplate('browse/clanposts', $additionalData);
		$pageBase = "../../";
		break;
	case '': // Clan members
		$accountsArray = explode(',', $clan['clanMembers']);
		$accountsText = '';
		
		foreach($accountsArray AS $accountKey => $accountID) $accountsText .= 'WHEN accounts.accountID = '.$accountID.' THEN '.($accountID == $clan['clanOwner'] ? 1 : $accountKey + 2).PHP_EOL;
		
		$order = 'CASE
			'.$accountsText.'
		END';
		$orderSorting = 'ASC';
		$filters = ["accounts.isActive != 0", "accounts.accountID IN (".Escape::multiple_ids($clan['clanMembers']).")"];
		$pageOffset = is_numeric($_GET["page"]) ? abs(Escape::number($_GET["page"]) - 1) * 10 : 0;
		$page = '';

		$accounts = Library::getAccounts($filters, $order, $orderSorting, $pageOffset, false);

		foreach($accounts['accounts'] AS &$account) {
			if($account['accountID'] == $clan['clanOwner']) {
				$extraIcon = 'crown';
				$extraIconTitle = sprintf(Dashboard::string('clanOwnerTitle'), htmlspecialchars($clan['clanName']));
			} else $extraIcon = $extraIconTitle = '';
			
			$additionalPage .= Dashboard::renderUserCard($account, $person, $extraIcon, $extraIconTitle);
		}
		
		$pageNumber = ceil($pageOffset / 10) + 1 ?: 1;
		$pageCount = floor(($accounts['count'] - 1) / 10) + 1;
				
		if($pageCount == 0) $pageCount = 1;
		
		$additionalData = [
			'ADDITIONAL_PAGE' => $additionalPage,
			'USER_NO_USERS' => empty($additionalPage) ? 'true' : 'false',
			'USER_PAGE_TEXT' => sprintf(Dashboard::string('pageText'), $pageNumber, $pageCount),
			
			'IS_FIRST_PAGE' => $pageNumber == 1 ? 'true' : 'false',
			'IS_LAST_PAGE' => $pageNumber == $pageCount ? 'true' : 'false',
			
			'FIRST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY')",
			'PREVIOUS_PAGE_BUTTON' => "getPage('@".(($pageNumber - 1) > 1 ? "page=".($pageNumber - 1) : 'page=REMOVE_QUERY')."')",
			'NEXT_PAGE_BUTTON' => "getPage('@page=".($pageNumber + 1)."')",
			'LAST_PAGE_BUTTON' => "getPage('@page=".$pageCount."')"
		];
		
		$clan['CLAN_ADDITIONAL_PAGE'] = Dashboard::renderTemplate('browse/accounts', $additionalData);
		$pageBase = "../";
		break;
	default:
		exit(http_response_code(404));
}

$clan['CLAN_PAGE_TEXT'] = sprintf(Dashboard::string('pageText'), $pageNumber, $pageCount);

$clan['CLAN_CREATION_DATE'] = $clan['creationDate'];

$clan['CLAN_NAME'] = $contextMenuData['MENU_NAME'] = htmlspecialchars($clan['clanName']);
$clan['CLAN_TITLE'] = sprintf(Dashboard::string("clanProfile"), htmlspecialchars($clan['clanName']));
$clan['CLAN_COLOR'] = "color: #".$clan['clanColor']."; text-shadow: 0px 0px 20px #".$clan['clanColor']."61;";
$clan['CLAN_TAG'] = htmlspecialchars($clan['clanTag']);

$clan['CLAN_HAS_RANK'] = $clan['clanRank'] != 0 ? 'true' : 'false';
$clan['CLAN_IS_TOP_100'] = $clan['clanRank'] <= 100 ? 'true' : 'false';

$clan['CLAN_IS_CLOSED'] = $clan['isClosed'] ? 'true' : 'false';
$clan['CLAN_CAN_JOIN'] = !$isInClan ? 'true' : 'false';

$ownerUser = Library::getUserByAccountID($clan['clanOwner']);
$ownerUserName = $ownerUser ? $ownerUser['userName'] : 'Undefined';
$userMetadata = Dashboard::getUserMetadata($ownerUser);

$clan['CLAN_OWNER_CARD'] = Dashboard::getUsernameString($person, $ownerUser, $ownerUserName, $userMetadata['mainIcon'], $userMetadata['userAppearance'], $userMetadata['userAttributes']);

$clanStats = Library::getClanStatsCount($person, $clanID);

$clan['CLAN_STARS_COUNT'] = $clanStats['stars'];
$clan['CLAN_MOONS_COUNT'] = $clanStats['moons'];
$clan['CLAN_DIAMONDS_COUNT'] = $clanStats['diamonds'];
$clan['CLAN_COINS_COUNT'] = $clanStats['coins'];
$clan['CLAN_USER_COINS_COUNT'] = $clanStats['userCoins'];
$clan['CLAN_DEMONS_COUNT'] = $clanStats['demons'];
$clan['CLAN_CREATOR_POINTS_COUNT'] = $clanStats['creatorPoints'];
$clan['CLAN_MEMBERS_COUNT'] = $clanStats['members'];
$clan['CLAN_POSTS_COUNT'] = $clanStats['posts'];

$contextMenuData['MENU_SHOW_NAME'] = 'false';

$clan['CLAN_CAN_OPEN_SETTINGS'] = $contextMenuData['MENU_CAN_OPEN_SETTINGS'] = $canOpenSettings ? 'true' : 'false';

$contextMenuData['MENU_SHOW_MANAGE_HR'] = ($contextMenuData['MENU_CAN_SEE_BANS'] == 'true' || $contextMenuData['MENU_CAN_OPEN_SETTINGS'] == 'true' || $contextMenuData['MENU_CAN_BLOCK'] == 'true' || $contextMenuData['MENU_CAN_BAN'] == 'true') ? 'true' : 'false';

$clan['CLAN_CONTEXT_MENU'] = Dashboard::renderTemplate('components/menus/clan', $contextMenuData);

exit(Dashboard::renderPage("browse/clan", $clan['CLAN_TITLE'], $pageBase, $clan));
?>