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
	
	$mapPackID = Escape::number($parameters[0]);
	
	$mapPack = Library::getMapPackByID($mapPackID);
	
	$mapPackColor = str_replace(',', ' ', $mapPack['rgbcolors']);
	
	$mapPack['MAPPACK_TITLE'] = htmlspecialchars($mapPack['name']);
	$mapPack['MAPPACK_DIFFICULTY_IMAGE'] = Library::getMapPackDifficultyImage($mapPack);
	
	$mapPack['MAPPACK_ATTRIBUTES'] = 'style="--href-color: rgb('.$mapPackColor.'); --href-shadow-color: rgb('.$mapPackColor.' / 38%)"';
	
	$mapPack['MAPPACK_LEVELS_COUNT'] = count(explode(',', $mapPack['levels']));
	
	$contextMenuData['MENU_ID'] = $mapPack['ID'];
	
	$contextMenuData['MENU_CAN_MANAGE'] = Library::checkPermission($person, "dashboardLevelPackCreate") ? 'true' : 'false';
	
	$mapPack['MAPPACK_CONTEXT_MENU'] = Dashboard::renderTemplate('components/menus/mappack', $contextMenuData);
		
	$pageBase = '../../';
	$mapPack['MAPPACK_ADDITIONAL_PAGE'] = '';
	$additionalPage = '';
		
	$mapPackLevels = Escape::multiple_ids($mapPack['levels']);
	$mapPackLevelsArray = explode(',', $mapPackLevels);
	
	$pageOffset = is_numeric($_GET["page"]) ? abs(Escape::number($_GET["page"]) - 1) * 10 : 0;
	
	switch($parameters[1]) {
		case '':
			$friendsString = Library::getFriendsQueryString($accountID);
				
			$filters = [
				"levelID IN (".$mapPackLevels.")",
				"(
					unlisted != 1 OR
					(unlisted = 1 AND (extID IN (".$friendsString.")))
				)"
			];
			
			$levelsArray = explode(',', $mapPackLevels);
			$levelsText = '';
			
			foreach($levelsArray AS $levelKey => $levelID) $levelsText .= 'WHEN levelID = '.$levelID.' THEN '.($levelKey + 1).PHP_EOL;
			
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
				
				'FIRST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY')",
				'PREVIOUS_PAGE_BUTTON' => "getPage('@".(($pageNumber - 1) > 1 ? "page=".($pageNumber - 1) : 'page=REMOVE_QUERY')."')",
				'NEXT_PAGE_BUTTON' => "getPage('@page=".($pageNumber + 1)."')",
				'LAST_PAGE_BUTTON' => "getPage('@page=".$pageCount."')"
			];
			
			$mapPack['MAPPACK_ADDITIONAL_PAGE'] = Dashboard::renderTemplate('browse/levels', $additionalData);
			break; 
		case 'manage':
			$pageBase = '../../../';
			if(!Library::checkPermission($person, "dashboardLevelPackCreate")) exit(Dashboard::renderErrorPage(Dashboard::string("mapPacksTitle"), Dashboard::string("errorNoPermission"), $pageBase));
			
			$friendsString = Library::getFriendsQueryString($accountID);
				
			$filters = [
				"levelID IN (".$mapPackLevels.")",
				"(
					unlisted != 1 OR
					(unlisted = 1 AND (levels.extID IN (".$friendsString.")))
				)"
			];
			
			$queryJoin = "INNER JOIN users ON levels.userID = users.userID";
			
			$levelsText = '';
			foreach($mapPackLevelsArray AS $levelKey => $levelID) $levelsText .= 'WHEN levelID = '.$levelID.' THEN '.($levelKey + 1).PHP_EOL;
			
			$order = 'CASE
				'.$levelsText.'
			END';
			$orderSorting = 'ASC';
			
			$mapPackLevelsElements = '';
			
			$levels = Library::getLevels($filters, $order, $orderSorting, $queryJoin, 0);
			
			foreach($levels['levels'] AS &$level) {
				$userMetadata = Dashboard::getUserMetadata($level);
				
				$mapPackLevelsElements .= '<div class="option" value="'.$level['levelID'].'" dashboard-select-multiple-option>
						<i class="fa-solid fa-gamepad"></i>
						<text>'.sprintf(Dashboard::string("levelTitlePlain"), htmlspecialchars($level['levelName']), htmlspecialchars($level['userName'])).'</text>
						<img loading="lazy" src="'.$userMetadata['mainIcon'].'">
						
						<button type="button" class="eyeButton" style="margin-left: auto;">
							<i class="fa-solid fa-xmark"></i>
						</button>
					</div>';
			}
			
			$barColor = Library::convertRGBToHEX($mapPack['rgbcolors']);
			$textColor = $mapPack['colors2'] ? Library::convertRGBToHEX($mapPack['colors2']) : $textColor;
			
			$additionalData = [
				'MAPPACK_ID' => $mapPackID,
				'MAPPACK_NAME' => $mapPack['MAPPACK_TITLE'],
				
				'MAPPACK_STARS' => $mapPack['stars'],
				'MAPPACK_COINS' => $mapPack['coins'],
				'MAPPACK_DIFFICULTY' => $mapPack['difficulty'],
				'MAPPACK_DIFFICULTY_NAME' => Library::getMapPackDifficultyName($mapPack),
				
				'MAPPACK_TEXT_COLOR' => $textColor,
				'MAPPACK_BAR_COLOR' => $barColor,
				
				'MAPPACK_LEVELS' => $mapPackLevels,
				'MAPPACK_LEVELS_OPTIONS' => $mapPackLevelsElements
			];
			
			$mapPack['MAPPACK_ADDITIONAL_PAGE'] = Dashboard::renderTemplate('manage/mappack', $additionalData);
			break;
		default:
			exit(http_response_code(404));
	}
	
	exit(Dashboard::renderPage("browse/mappack", htmlspecialchars($mapPack['name']), $pageBase, $mapPack));
}

// Search lists
$pageOffset = is_numeric($_GET["page"]) ? abs(Escape::number($_GET["page"]) - 1) * 10 : 0;
$page = '';

$mapPacks = Library::getMapPacks($pageOffset);

foreach($mapPacks['mapPacks'] AS &$mapPack) $page .= Dashboard::renderMapPackCard($mapPack, $person);

$pageNumber = ceil($pageOffset / 10) + 1 ?: 1;
$pageCount = floor(($mapPacks['count'] - 1) / 10) + 1;

$dataArray = [
	'ADDITIONAL_PAGE' => $page,
	'MAPPACK_PAGE_TEXT' => sprintf(Dashboard::string('pageText'), $pageNumber, $pageCount),
	'MAPPACK_NO_MAPPACKS' => empty($page) ? 'true' : 'false',
	
	'ENABLE_FILTERS' => 'false',
	
	'IS_FIRST_PAGE' => $pageNumber == 1 ? 'true' : 'false',
	'IS_LAST_PAGE' => $pageNumber == $pageCount ? 'true' : 'false',
	
	'FIRST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY')",
	'PREVIOUS_PAGE_BUTTON' => "getPage('@".(($pageNumber - 1) > 1 ? "page=".($pageNumber - 1) : 'page=REMOVE_QUERY')."')",
	'NEXT_PAGE_BUTTON' => "getPage('@page=".($pageNumber + 1)."')",
	'LAST_PAGE_BUTTON' => "getPage('@page=".$pageCount."')"
];

$fullPage = Dashboard::renderTemplate("browse/mappacks", $dataArray);

exit(Dashboard::renderPage("general/wide", Dashboard::string("mapPacksTitle"), "../", $fullPage));
?>