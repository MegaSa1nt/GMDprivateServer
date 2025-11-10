<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
$accountID = $person['accountID'];

// Gauntlet page
if($_GET['id']) {
	$contextMenuData = [];
	
	$parameters = explode("/", Escape::text($_GET['id']));
	
	$gauntletID = Escape::number($parameters[0]);
	
	$gauntlet = Library::getGauntletByID($gauntletID);
	if(!$gauntlet) exit(Dashboard::renderErrorPage(Dashboard::string("gauntletsTitle"), Dashboard::string("errorGauntletNotFound"), '../../'));
	
	$gauntlet['GAUNTLET_TITLE'] = Library::getGauntletName($gauntlet['ID']).' Gauntlet';
	$gauntlet['GAUNTLET_DIFFICULTY_IMAGE'] = Library::getGauntletImage($gauntlet['ID']);
	
	$contextMenuData['MENU_ID'] = $gauntlet['ID'];
	
	$contextMenuData['MENU_CAN_MANAGE'] = Library::checkPermission($person, "dashboardManageGauntlets") ? 'true' : 'false';
	
	$gauntlet['GAUNTLET_CONTEXT_MENU'] = Dashboard::renderTemplate('components/menus/gauntlet', $contextMenuData);
	
	$gauntletLevelsArray = [$gauntlet["level1"], $gauntlet["level2"], $gauntlet["level3"], $gauntlet["level4"], $gauntlet["level5"]];
	$gauntletLevels = implode(',', $gauntletLevelsArray);
		
	$pageBase = '../../';
	$gauntlet['GAUNTLET_ADDITIONAL_PAGE'] = '';
	$additionalPage = '';
		
	$pageOffset = is_numeric($_GET["page"]) ? abs(Escape::number($_GET["page"]) - 1) * 10 : 0;
	
	switch($parameters[1]) {
		case '':
			$friendsString = Library::getFriendsQueryString($accountID);
				
			$filters = [
				"levelID IN (".$gauntletLevels.")",
				"(
					unlisted != 1 OR
					(unlisted = 1 AND (extID IN (".$friendsString.")))
				)"
			];
			
			$levelsText = '';
			foreach($gauntletLevelsArray AS $levelKey => $levelID) $levelsText .= 'WHEN levelID = '.$levelID.' THEN '.($levelKey + 1).PHP_EOL;
			
			$order = 'CASE
				'.$levelsText.'
			END';
			$orderSorting = 'ASC';
			
			$levels = Library::getLevels($filters, $order, $orderSorting, '', $pageOffset);
			
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
				
				'FIRST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY', 'settings')",
				'PREVIOUS_PAGE_BUTTON' => "getPage('@".(($pageNumber - 1) > 1 ? "page=".($pageNumber - 1) : 'page=REMOVE_QUERY')."', 'settings')",
				'NEXT_PAGE_BUTTON' => "getPage('@page=".($pageNumber + 1)."', 'settings')",
				'LAST_PAGE_BUTTON' => "getPage('@page=".$pageCount."', 'settings')"
			];
			
			$gauntlet['GAUNTLET_ADDITIONAL_PAGE'] = Dashboard::renderTemplate('browse/levels', $additionalData);
			break; 
		case 'manage':
			$pageBase = '../../../';
			if(!Library::checkPermission($person, "dashboardManageGauntlets")) exit(Dashboard::renderErrorPage(Dashboard::string("gauntletsTitle"), Dashboard::string("errorNoPermission"), $pageBase));
			
			$friendsString = Library::getFriendsQueryString($accountID);
				
			$filters = [
				"levelID IN (".$gauntletLevels.")",
				"(
					unlisted != 1 OR
					(unlisted = 1 AND (levels.extID IN (".$friendsString.")))
				)"
			];
			
			$queryJoin = "INNER JOIN users ON levels.userID = users.userID";
			
			$levelsText = '';
			foreach($gauntletLevelsArray AS $levelKey => $levelID) $levelsText .= 'WHEN levelID = '.$levelID.' THEN '.($levelKey + 1).PHP_EOL;
			
			$order = 'CASE
				'.$levelsText.'
			END';
			$orderSorting = 'ASC';
			
			$gauntletLevelsElements = '';
			
			$levels = Library::getLevels($filters, $order, $orderSorting, $queryJoin, 0);
			
			foreach($levels['levels'] AS &$level) {
				$userMetadata = Dashboard::getUserMetadata($level);
				
				$gauntletLevelsElements .= '<div class="option" value="'.$level['levelID'].'" dashboard-select-multiple-option>
						<i class="fa-solid fa-gamepad"></i>
						<text>'.sprintf(Dashboard::string("levelTitlePlain"), htmlspecialchars($level['levelName']), htmlspecialchars($level['userName'])).'</text>
						<img loading="lazy" src="'.$userMetadata['mainIcon'].'">
						
						<button type="button" class="eyeButton" style="margin-left: auto;">
							<i class="fa-solid fa-xmark"></i>
						</button>
					</div>';
			}
			
			$additionalData = [
				'GAUNTLET_ID' => $gauntletID,
				
				'GAUNTLET_LEVELS' => $gauntletLevels,
				'GAUNTLET_LEVELS_OPTIONS' => $gauntletLevelsElements
			];
			
			$gauntlet['GAUNTLET_ADDITIONAL_PAGE'] = Dashboard::renderTemplate('manage/gauntlet', $additionalData);
			break;
		case 'delete':
			if(!Library::checkPermission($person, "dashboardManageGauntlets")) exit(Dashboard::renderErrorPage(Dashboard::string("gauntletsTitle"), Dashboard::string("errorNoPermission"), $pageBase));
			
			$pageBase = '../../../';
			
			$dataArray = [
				'INFO_TITLE' => Dashboard::string("deleteGauntlet"),
				'INFO_DESCRIPTION' => Dashboard::string("deleteGauntletDesc"),
				'INFO_EXTRA' => '<p class="error">'.Dashboard::string("deleteGauntletNotice").'</p>
					'.Dashboard::renderGauntletCard($gauntlet, $person),
				
				'INFO_BUTTON_TEXT_FIRST' => Dashboard::string("cancel"),
				'INFO_BUTTON_ONCLICK_FIRST' => "getPage('browse/gauntlets', 'list')",
				'INFO_BUTTON_STYLE_FIRST' => "",
				'INFO_BUTTON_TEXT_SECOND' => Dashboard::string("deleteGauntlet"),
				'INFO_BUTTON_ONCLICK_SECOND' => "postPage('manage/deleteGauntlet', 'infoForm', 'list')",
				'INFO_BUTTON_STYLE_SECOND' => "error",
				
				'INFO_INPUT_NAME' => 'gauntletID',
				'INFO_INPUT_VALUE' => $gauntletID
			];
			
			exit(Dashboard::renderPage("general/infoDialogue", Dashboard::string("deleteGauntlet"), $pageBase, $dataArray));
		default:
			exit(http_response_code(404));
	}
	
	exit(Dashboard::renderPage("browse/gauntlet", $gauntlet['GAUNTLET_TITLE'], $pageBase, $gauntlet));
}

// Search Gauntlets
$pageOffset = is_numeric($_GET["page"]) ? abs(Escape::number($_GET["page"]) - 1) * 10 : 0;
$page = '';

$gauntlets = Library::getGauntlets($pageOffset);

foreach($gauntlets['gauntlets'] AS &$gauntlet) $page .= Dashboard::renderGauntletCard($gauntlet, $person);

$pageNumber = ceil($pageOffset / 10) + 1 ?: 1;
$pageCount = floor(($gauntlets['count'] - 1) / 10) + 1;

$dataArray = [
	'ADDITIONAL_PAGE' => $page,
	'GAUNTLET_PAGE_TEXT' => sprintf(Dashboard::string('pageText'), $pageNumber, $pageCount),
	'GAUNTLET_NO_GAUNTLETS' => empty($page) ? 'true' : 'false',
	
	'ENABLE_FILTERS' => 'false',
	
	'IS_FIRST_PAGE' => $pageNumber == 1 ? 'true' : 'false',
	'IS_LAST_PAGE' => $pageNumber == $pageCount ? 'true' : 'false',
	
	'FIRST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY', 'list')",
	'PREVIOUS_PAGE_BUTTON' => "getPage('@".(($pageNumber - 1) > 1 ? "page=".($pageNumber - 1) : 'page=REMOVE_QUERY')."', 'list')",
	'NEXT_PAGE_BUTTON' => "getPage('@page=".($pageNumber + 1)."', 'list')",
	'LAST_PAGE_BUTTON' => "getPage('@page=".$pageCount."', 'list')"
];

$fullPage = Dashboard::renderTemplate("browse/gauntlets", $dataArray);

exit(Dashboard::renderPage("general/wide", Dashboard::string("gauntletsTitle"), "../", $fullPage));
?>