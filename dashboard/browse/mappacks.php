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
	
	$mapPack['MAPPACK_TITLE'] =  htmlspecialchars($mapPack['name']);
	$mapPack['MAPPACK_DIFFICULTY_IMAGE'] = Library::getMapPackDifficultyImage($mapPack);
	
	$mapPack['MAPPACK_ATTRIBUTES'] = 'style="--href-color: rgb('.$mapPackColor.'); --href-shadow-color: rgb('.$mapPackColor.' / 38%)"';
	
	$mapPack['MAPPACK_LEVELS_COUNT'] = count(explode(',', $mapPack['levels']));
	
	$contextMenuData['MENU_ID'] = $mapPack['ID'];
	
	$contextMenuData['MENU_CAN_MANAGE'] = Library::checkPermission($person, "dashboardLevelPackCreate") ? 'true' : 'false';
	
	$mapPack['MAPPACK_CONTEXT_MENU'] = Dashboard::renderTemplate('components/menus/mappack', $contextMenuData);
		
	$pageBase = '../../';
	$mapPack['MAPPACK_ADDITIONAL_PAGE'] = '';
	$additionalPage = '';
		
	$pageOffset = is_numeric($_GET["page"]) ? abs(Escape::number($_GET["page"]) - 1) * 10 : 0;
	
	switch($parameters[1]) {
		case '':
			$mapPackLevels = Escape::multiple_ids($mapPack['levels']);
			
			$friendsArray = Library::getFriends($accountID);
			$friendsArray[] = $accountID;
			$friendsString = "'".implode("','", $friendsArray)."'";
				
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
			
			$mapPack['MAPPACK_ADDITIONAL_PAGE'] = Dashboard::renderTemplate('browse/levels', $additionalData);
			break; 
		case 'manage':
			$pageBase = '../../../';
			if(!Library::checkPermission($person, "dashboardLevelPackCreate")) exit(Dashboard::renderErrorPage(Dashboard::string("mapPacksTitle"), Dashboard::string("errorNoPermission"), '../../../'));
			$mapPack['MAPPACK_ADDITIONAL_PAGE'] = Dashboard::renderTemplate('browse/manage', $additionalData);
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
$pageCount = floor(($mapPack['count'] - 1) / 10) + 1;

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