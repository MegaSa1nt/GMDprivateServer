<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();

$order = "reuploadTime";
$orderSorting = "DESC";
$filters = ["sfxs.reuploadID > 0"];
$pageOffset = is_numeric($_GET["page"]) ? abs(Escape::number($_GET["page"]) - 1) * 10 : 0;
$page = '';

$sfxs = Library::getSFXs($filters, $order, $orderSorting, '', $pageOffset, 10);

foreach($sfxs['sfxs'] AS &$sfx) $page .= Dashboard::renderSFXCard($sfx, $person);

$pageNumber = ceil($pageOffset / 10) + 1 ?: 1;
$pageCount = floor($sfxs['count'] / 10) + 1;

$dataArray = [
	'ADDITIONAL_PAGE' => $page,
	'SFX_PAGE_TEXT' => sprintf(Dashboard::string('pageText'), $pageNumber, $pageCount),
	'SFX_NO_SFXS' => empty($page) ? 'true' : 'false',
	
	'IS_FIRST_PAGE' => $pageNumber == 1 ? 'true' : 'false',
	'IS_LAST_PAGE' => $pageNumber == $pageCount ? 'true' : 'false',
	
	'FIRST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY', 'list')",
	'PREVIOUS_PAGE_BUTTON' => "getPage('@".(($pageNumber - 1) > 1 ? "page=".($pageNumber - 1) : 'page=REMOVE_QUERY')."', 'list')",
	'NEXT_PAGE_BUTTON' => "getPage('@page=".($pageNumber + 1)."', 'list')",
	'LAST_PAGE_BUTTON' => "getPage('@page=".$pageCount."', 'list')"
];

$fullPage = Dashboard::renderTemplate("browse/sfxs", $dataArray);

exit(Dashboard::renderPage("general/wide", Dashboard::string("sfxsTitle"), "../", $fullPage));
?>