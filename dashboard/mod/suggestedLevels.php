<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if(!$person['success']) exit(Dashboard::renderErrorPage(Dashboard::string("suggestedLevelsTitle"), Dashboard::string("errorLoginRequired")));

if(!Library::checkPermission($person, "dashboardModeratorTools")) exit(Dashboard::renderErrorPage(Dashboard::string("suggestedLevelsTitle"), Dashboard::string("errorNoPermission"), '../'));

$accountID = $person['accountID'];

$order = "uploadDate";
$orderSorting = "DESC";
$pageOffset = is_numeric($_GET["page"]) ? abs(Escape::number($_GET["page"]) - 1) * 10 : 0;
$page = '';

$getFilters = Library::getLevelSearchFilters($_GET, 22, true, true);
$filters = $getFilters['filters'];

$filters[] = "levels.unlisted = 0";
if(!$unlistedLevelsForAdmins || !Library::isAccountAdministrator($accountID)) {
	$friendsString = Library::getFriendsQueryString($accountID);
	
	$filters[] = "levels.unlisted != 1 OR (levels.unlisted = 1 AND (levels.extID IN (".$friendsString.")))";
}
$queryJoin = 'INNER JOIN suggest ON levels.levelID = suggest.suggestLevelId';

$levels = Library::getLevels($filters, $order, $orderSorting, $queryJoin, $pageOffset);

foreach($levels['levels'] AS &$level) $page .= Dashboard::renderLevelCard($level, $person);

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

exit(Dashboard::renderPage("general/wide", Dashboard::string("suggestedLevelsTitle"), "../", $fullPage));
?>