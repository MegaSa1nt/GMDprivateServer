<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
$accountID = $person['accountID'];

// List page
if($_GET['id']) {
	$contextMenuData = [];
	
	$parameters = explode("/", Escape::text($_GET['id']));
	
	$listID = Escape::number($parameters[0]);
	
	$list = Library::getListByID($listID);
	if(!$list || !Library::canAccountSeeList($person, $list)) exit(Dashboard::renderErrorPage(Dashboard::string("listsTitle"), Dashboard::string("errorListNotFound"), '../../'));
	$isPersonThemselves = $accountID == $list['accountID'];

	$user = Library::getUserByAccountID($list['accountID']);
	$userName = $user ? $user['userName'] : 'Undefined';
	
	$userAttributes = [];
	
	$userMetadata = Dashboard::getUserMetadata($user);
	
	$list['LIST_TITLE'] = sprintf(Dashboard::string('levelTitle'), $list['listName'], Dashboard::getUsernameString($person, $user, $userName, $userMetadata['mainIcon'], $userMetadata['userAppearance'], $userMetadata['userAttributes']));
	$list['LIST_DESCRIPTION'] = Dashboard::parseMentions($person, htmlspecialchars(Escape::url_base64_decode($list['listDesc']))) ?: "<i>".Dashboard::string('noDescription')."</i>";
	$list['LIST_DIFFICULTY_IMAGE'] = Library::getListDifficultyImage($list);
		
	$contextMenuData['MENU_SHOW_NAME'] = 'false';
		
	$contextMenuData['MENU_ID'] = $list['listID'];
	
	$list['LIST_CAN_MANAGE'] = $contextMenuData['MENU_CAN_MANAGE'] = ($isPersonThemselves || Library::checkPermission($person, "dashboardManageLevels")) ? 'true' : 'false';
	$contextMenuData['MENU_CAN_DELETE'] = ($isPersonThemselves || Library::checkPermission($person, "commandDelete")) ? 'true' : 'false';
	
	$contextMenuData['MENU_SHOW_MANAGE_HR'] = ($contextMenuData['MENU_CAN_MANAGE'] == 'true' || $contextMenuData['MENU_CAN_DELETE'] == 'true') ? 'true' : 'false';
	
	$list['LIST_CONTEXT_MENU'] = Dashboard::renderTemplate('components/menus/list', $contextMenuData);
	
	$listStatsCount = Library::getListStatsCount($person, $listID);

	$list['LIST_LEVELS'] = $listStatsCount['levels'];
	$list['LIST_COMMENTS'] = $listStatsCount['comments'];
	
	$pageBase = '../../';
	$list['LIST_ADDITIONAL_PAGE'] = '';
	$additionalPage = '';
		
	$pageOffset = is_numeric($_GET["page"]) ? abs(Escape::number($_GET["page"]) - 1) * 10 : 0;
	
	switch($parameters[1]) {
		case '':
			$listLevels = Escape::multiple_ids($list['listlevels']);
			
			$friendsArray = Library::getFriends($accountID);
			$friendsArray[] = $accountID;
			$friendsString = "'".implode("','", $friendsArray)."'";
				
			$filters = [
				"levelID IN (".$listLevels.")",
				"(
					unlisted != 1 OR
					(unlisted = 1 AND (extID IN (".$friendsString.")))
				)"
			];
			
			$levelsArray = explode(',', $listLevels);
			$levelsText = '';
			
			foreach($levelsArray AS $levelKey => $levelID) $levelsText .= 'WHEN levelID = '.$levelID.' THEN '.($levelKey + 1).PHP_EOL;
			
			$order = 'CASE
				'.$levelsText.'
			END';
			$orderSorting = 'ASC';
			
			$levels = Library::getLevels($filters, $order, $orderSorting, '', $pageOffset, false);
			
			foreach($levels['levels'] AS &$level) $additionalPage .= Dashboard::renderLevelCard($level, $person);

			$pageNumber = ceil($pageOffset / 10) + 1 ?: 1;
			$pageCount = floor(($levels['count'] - 1) / 10) + 1;

			$additionalData = [
				'ADDITIONAL_PAGE' => $additionalPage,
				'LEVEL_PAGE_TEXT' => sprintf(Dashboard::string('pageText'), $pageNumber, $pageCount),
				'LEVEL_NO_LEVELS' => empty($additionalPage) ? 'true' : 'false',
				
				'ENABLE_FILTERS' => 'false',
				
				'IS_FIRST_PAGE' => $pageNumber == 1 ? 'true' : 'false',
				'IS_LAST_PAGE' => $pageNumber == $pageCount ? 'true' : 'false',
				
				'FIRST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY')",
				'PREVIOUS_PAGE_BUTTON' => "getPage('@".(($pageNumber - 1) > 1 ? "page=".($pageNumber - 1) : 'page=REMOVE_QUERY')."')",
				'NEXT_PAGE_BUTTON' => "getPage('@page=".($pageNumber + 1)."')",
				'LAST_PAGE_BUTTON' => "getPage('@page=".$pageCount."')"
			];
			
			$list['LIST_ADDITIONAL_PAGE'] = Dashboard::renderTemplate('browse/levels', $additionalData);
			break;
		case 'comments':
			$pageBase = '../../../';
			
			$mode = isset($_GET['mode']) ? Escape::number($_GET["mode"]) : 0;
			$sortMode = $mode ? "comments.likes - comments.dislikes" : "comments.timestamp";
			
			$comments = Library::getCommentsOfList($listID, $sortMode, $pageOffset);
			
			foreach($comments['comments'] AS &$comment) $additionalPage .= Dashboard::renderCommentCard($comment, $person);
			
			$pageNumber = ceil($pageOffset / 10) + 1 ?: 1;
			$pageCount = floor(($comments['count'] - 1) / 10) + 1;
			
			if($pageCount == 0) $pageCount = 1;
			
			$additionalData = [
				'ADDITIONAL_PAGE' => $additionalPage,
				'COMMENT_NO_COMMENTS' => !$comments['count'] ? 'true' : 'false',
				'COMMENT_PAGE_TEXT' => sprintf(Dashboard::string('pageText'), $pageNumber, $pageCount),
				'LEVEL_ID' => $listID * -1,
				
				'COMMENT_CAN_POST' => 'true',
				
				'IS_FIRST_PAGE' => $pageNumber == 1 ? 'true' : 'false',
				'IS_LAST_PAGE' => $pageNumber == $pageCount ? 'true' : 'false',
				
				'FIRST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY')",
				'PREVIOUS_PAGE_BUTTON' => "getPage('@".(($pageNumber - 1) > 1 ? "page=".($pageNumber - 1) : 'page=REMOVE_QUERY')."')",
				'NEXT_PAGE_BUTTON' => "getPage('@page=".($pageNumber + 1)."')",
				'LAST_PAGE_BUTTON' => "getPage('@page=".$pageCount."')"
			];
			
			$list['LIST_ADDITIONAL_PAGE'] = Dashboard::renderTemplate('browse/comments', $additionalData);
			break;
		case 'manage':
			$pageBase = '../../../';
			
			if(!Library::checkPermission($person, "dashboardManageLevels")) exit(Dashboard::renderErrorPage(Dashboard::string("listsTitle"), Dashboard::string("errorNoPermission"), '../../../'));
			$list['LIST_ADDITIONAL_PAGE'] = Dashboard::renderTemplate('browse/manage', $additionalData);
			break;
		default:
			exit(http_response_code(404));
	}
	
	exit(Dashboard::renderPage("browse/list", htmlspecialchars($list['listName']), $pageBase, $list));
}

// Search lists
$order = "uploadDate";
$pageOffset = is_numeric($_GET["page"]) ? abs(Escape::number($_GET["page"]) - 1) * 10 : 0;
$page = '';

$getFilters = Library::getListSearchFilters($_GET, true, false);
$filters = $getFilters['filters'];

$lists = Library::getLists($person, $filters, $order, $pageOffset);

foreach($lists['lists'] AS &$list) $page .= Dashboard::renderListCard($list, $person);

$pageNumber = ceil($pageOffset / 10) + 1 ?: 1;
$pageCount = floor(($lists['count'] - 1) / 10) + 1;

$dataArray = [
	'ADDITIONAL_PAGE' => $page,
	'LIST_PAGE_TEXT' => sprintf(Dashboard::string('pageText'), $pageNumber, $pageCount),
	'LIST_NO_LISTS' => empty($page) ? 'true' : 'false',
	
	'ENABLE_FILTERS' => 'true',
	
	'IS_FIRST_PAGE' => $pageNumber == 1 ? 'true' : 'false',
	'IS_LAST_PAGE' => $pageNumber == $pageCount ? 'true' : 'false',
	
	'FIRST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY')",
	'PREVIOUS_PAGE_BUTTON' => "getPage('@".(($pageNumber - 1) > 1 ? "page=".($pageNumber - 1) : 'page=REMOVE_QUERY')."')",
	'NEXT_PAGE_BUTTON' => "getPage('@page=".($pageNumber + 1)."')",
	'LAST_PAGE_BUTTON' => "getPage('@page=".$pageCount."')"
];

$fullPage = Dashboard::renderTemplate("browse/lists", $dataArray);

exit(Dashboard::renderPage("general/wide", Dashboard::string("listsTitle"), "../", $fullPage));
?>