<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();

$type = Escape::latin($_GET["type"]) ?: 'top';
$pageOffset = is_numeric($_GET["page"]) ? abs(Escape::number($_GET["page"]) - 1) * 10 : 0;

$leaderboard = Library::getLeaderboard($person, $type, 100);
$rank = $leaderboard['rank'];

$pageNumber = $pageOffset * -1;
$entriesCount = 0;
foreach($leaderboard['leaderboard'] AS &$account) {
	$pageNumber++;
	if($pageNumber < 1) continue;
					
	$entriesCount++;
	if($entriesCount > $pageOffset + 10) break;
	
	$rank++;
	$account["USER_RANK"] = $rank + $pageOffset;
	
	$page .= Dashboard::renderUserCard($account, $person);
}

$pageNumber = ceil($pageOffset / 10) + 1 ?: 1;
$pageCount = floor(($leaderboard['count'] - 1) / 10) + 1;

$dataArray = [
	'ADDITIONAL_PAGE' => $page,
	'USER_PAGE_TEXT' => sprintf(Dashboard::string('pageText'), $pageNumber, $pageCount),
	'USER_NO_USERS' => empty($page) ? 'true' : 'false',
	
	'IS_FIRST_PAGE' => $pageNumber == 1 ? 'true' : 'false',
	'IS_LAST_PAGE' => $pageNumber == $pageCount ? 'true' : 'false',
	
	'FIRST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY', 'list')",
	'PREVIOUS_PAGE_BUTTON' => "getPage('@".(($pageNumber - 1) > 1 ? "page=".($pageNumber - 1) : 'page=REMOVE_QUERY')."', 'list')",
	'NEXT_PAGE_BUTTON' => "getPage('@page=".($pageNumber + 1)."', 'list')",
	'LAST_PAGE_BUTTON' => "getPage('@page=".$pageCount."', 'list')"
];

$fullPage = Dashboard::renderTemplate("browse/leaderboards", $dataArray);

exit(Dashboard::renderPage("general/wide", Dashboard::string("leaderboardsTitle"), "../", $fullPage));
?>