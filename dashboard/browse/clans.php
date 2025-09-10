<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
$userID = $person['userID'];

// Search clans
$order = "creationDate";
$orderSorting = "DESC";
$pageOffset = is_numeric($_GET["page"]) ? abs(Escape::number($_GET["page"]) - 1) * 10 : 0;
$page = '';

$clans = Library::getClans([], $order, $orderSorting, $pageOffset, 10);

foreach($clans['clans'] AS &$clan) $page .= Dashboard::renderClanCard($clan, $person);

$pageNumber = ceil($pageOffset / 10) + 1 ?: 1;
$pageCount = floor(($clans['count'] - 1) / 10) + 1;

$dataArray = [
	'ADDITIONAL_PAGE' => $page,
	'CLAN_PAGE_TEXT' => sprintf(Dashboard::string('pageText'), $pageNumber, $pageCount),
	'CLAN_NO_CLANS' => empty($page) ? 'true' : 'false',
	
	'IS_FIRST_PAGE' => $pageNumber == 1 ? 'true' : 'false',
	'IS_LAST_PAGE' => $pageNumber == $pageCount ? 'true' : 'false',
	
	'FIRST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY', 'list')",
	'PREVIOUS_PAGE_BUTTON' => "getPage('@".(($pageNumber - 1) > 1 ? "page=".($pageNumber - 1) : 'page=REMOVE_QUERY')."', 'list')",
	'NEXT_PAGE_BUTTON' => "getPage('@page=".($pageNumber + 1)."', 'list')",
	'LAST_PAGE_BUTTON' => "getPage('@page=".$pageCount."', 'list')"
];

$fullPage = Dashboard::renderTemplate("browse/clans", $dataArray);

exit(Dashboard::renderPage("general/wide", Dashboard::string("clansListTitle"), "../", $fullPage));
?>