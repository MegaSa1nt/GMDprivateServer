<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if(!$person['success']) exit(Dashboard::renderErrorPage(Dashboard::string("yourLevelsTitle"), Dashboard::string("errorLoginRequired")));
$accountID = $person['accountID'];

// Search lists
$order = "uploadDate";
$pageOffset = is_numeric($_GET["page"]) ? abs(Escape::number($_GET["page"]) - 1) * 10 : 0;
$page = '';

$getFilters = Library::getListSearchFilters($_GET, true, true);
$filters = $getFilters['filters'];

$filters[] = "lists.accountID = '".$accountID."'";

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
	
	'FIRST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY', 'list')",
	'PREVIOUS_PAGE_BUTTON' => "getPage('@".(($pageNumber - 1) > 1 ? "page=".($pageNumber - 1) : 'page=REMOVE_QUERY')."', 'list')",
	'NEXT_PAGE_BUTTON' => "getPage('@page=".($pageNumber + 1)."', 'list')",
	'LAST_PAGE_BUTTON' => "getPage('@page=".$pageCount."', 'list')"
];

$fullPage = Dashboard::renderTemplate("browse/lists", $dataArray);

exit(Dashboard::renderPage("general/wide", Dashboard::string("yourListsTitle"), "../", $fullPage));
?>