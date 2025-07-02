<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();

$order = "registerDate";
$orderSorting = "DESC";
$filters = ["accounts.isActive != 0"];
$pageOffset = is_numeric($_GET["page"]) ? abs(Escape::number($_GET["page"]) - 1) * 10 : 0;
$page = '';

$accounts = Library::getAccounts($filters, $order, $orderSorting, $pageOffset, false);

foreach($accounts['accounts'] AS &$account) $page .= Dashboard::renderUserCard($account, $person);

$pageNumber = ceil($pageOffset / 10) + 1 ?: 1;
$pageCount = floor($accounts['count'] / 10) + 1;

$dataArray = [
	'ADDITIONAL_PAGE' => $page,
	'USER_PAGE_TEXT' => sprintf(Dashboard::string('pageText'), $pageNumber, $pageCount),
	'USER_NO_USERS' => empty($page) ? 'true' : 'false',
	
	'IS_FIRST_PAGE' => $pageNumber == 1 ? 'true' : 'false',
	'IS_LAST_PAGE' => $pageNumber == $pageCount ? 'true' : 'false',
	
	'FIRST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY')",
	'PREVIOUS_PAGE_BUTTON' => "getPage('@".(($pageNumber - 1) > 1 ? "page=".($pageNumber - 1) : 'page=REMOVE_QUERY')."')",
	'NEXT_PAGE_BUTTON' => "getPage('@page=".($pageNumber + 1)."')",
	'LAST_PAGE_BUTTON' => "getPage('@page=".$pageCount."')"
];

$fullPage = Dashboard::renderTemplate("browse/accounts", $dataArray);

exit(Dashboard::renderPage("general/wide", Dashboard::string("accountsTitle"), "../", $fullPage));
?>