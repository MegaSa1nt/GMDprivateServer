<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if(!$person['success']) exit(Dashboard::renderErrorPage(Dashboard::string("yourLevelsTitle"), Dashboard::string("errorLoginRequired")));
$userID = $person['userID'];

$order = "uploadDate";
$orderSorting = "DESC";
$pageOffset = is_numeric($_GET["page"]) ? abs(Escape::number($_GET["page"]) - 1) * 10 : 0;
$page = '';

$getFilters = Library::getLevelSearchFilters($_GET, 22, true, true);
$filters = $getFilters['filters'];

$filters[] = "levels.userID = '".$userID."'";

$levels = Library::getLevels($filters, $order, $orderSorting, '', $pageOffset);

foreach($levels['levels'] AS &$level) $page .= Dashboard::renderLevelCard($level, $person);

$pageNumber = ceil($pageOffset / 10) + 1 ?: 1;
$pageCount = floor($levels['count'] / 10) + 1;

$dataArray = [
	'ADDITIONAL_PAGE' => $page,
	'LEVEL_PAGE_TEXT' => sprintf(Dashboard::string('pageText'), $pageNumber, $pageCount),
	'LEVEL_NO_LEVELS' => empty($page) ? 'true' : 'false',
	
	'ENABLE_FILTERS' => 'true',
	
	'IS_FIRST_PAGE' => $pageNumber == 1 ? 'true' : 'false',
	'IS_LAST_PAGE' => $pageNumber == $pageCount ? 'true' : 'false',
	
	'FIRST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY')",
	'PREVIOUS_PAGE_BUTTON' => "getPage('@".(($pageNumber - 1) > 1 ? "page=".($pageNumber - 1) : 'page=REMOVE_QUERY')."')",
	'NEXT_PAGE_BUTTON' => "getPage('@page=".($pageNumber + 1)."')",
	'LAST_PAGE_BUTTON' => "getPage('@page=".$pageCount."')"
];

$fullPage = Dashboard::renderTemplate("browse/levels", $dataArray);

exit(Dashboard::renderPage("general/wide", Dashboard::string("yourLevelsTitle"), "../", $fullPage));
?>