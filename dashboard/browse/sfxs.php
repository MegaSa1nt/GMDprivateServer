<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
$accountID = $person['accountID'];

if($_GET['id']) {
	$contextMenuData = [];
	$pageBase = '../../';
	
	$parameters = explode("/", Escape::text($_GET['id']));
	
	$sfxID = Escape::number($parameters[0]);
	
	$sfx = Library::getSFXByID($sfxID);
	if(!$sfx || !$sfx['reuploadID']) exit(Dashboard::renderErrorPage(Dashboard::string("sfxsTitle"), Dashboard::string("errorSFXNotFound"), '../../'));
	
	switch($parameters[1]) {
		case 'manage':
			$pageBase = '../../../';
			
			$manageSFXPermission = Library::checkPermission($person, "dashboardManageSongs");
			if($accountID != $sfx['reuploadID'] && !$manageSFXPermission) exit(Dashboard::renderErrorPage(Dashboard::string("sfxsTitle"), Dashboard::string("errorNoPermission"), '../../../'));

			$sfxReuploader = Library::getUserByAccountID($sfx['reuploadID']);
			
			$sfxTitle = htmlspecialchars($sfx['name']);
			
			$dataArray = [
				'SFX_ID' => $sfxID,
				
				'SFX_TITLE' => $sfxTitle,

				'SFX_ENABLED_VALUE' => $sfx["isDisabled"] ? 0 : 1,
				'SFX_ENABLED_REMOVE_CHECK' => $sfx["isDisabled"] ? 'checked' : '',
				'SFX_CAN_DISABLE' => $manageSFXPermission ? 'true' : 'false',
			];
			
			exit(Dashboard::renderPage("manage/sfx", Dashboard::string("manageSFXTitle"), $pageBase, $dataArray));
			break;
		case 'delete':
			$pageBase = '../../../';
			
			$manageSFXPermission = Library::checkPermission($person, "dashboardManageSongs");
			if($accountID != $sfx['reuploadID'] && !$manageSFXPermission) exit(Dashboard::renderErrorPage(Dashboard::string("sfxsTitle"), Dashboard::string("errorNoPermission"), '../../../'));
			
			$dataArray = [
				'INFO_TITLE' => Dashboard::string("deleteSFX"),
				'INFO_DESCRIPTION' => Dashboard::string("deleteSFXDesc"),
				'INFO_EXTRA' => Dashboard::renderSFXCard($sfx, $person),
				
				'INFO_BUTTON_TEXT_FIRST' => Dashboard::string("cancel"),
				'INFO_BUTTON_ONCLICK_FIRST' => "getPage('browse/sfxs', 'list')",
				'INFO_BUTTON_STYLE_FIRST' => "",
				'INFO_BUTTON_TEXT_SECOND' => Dashboard::string("deleteSFX"),
				'INFO_BUTTON_ONCLICK_SECOND' => "postPage('manage/deleteSFX', 'infoForm', 'list')",
				'INFO_BUTTON_STYLE_SECOND' => "error",
				
				'INFO_INPUT_NAME' => 'sfxID',
				'INFO_INPUT_VALUE' => $sfxID
			];
			
			exit(Dashboard::renderPage("general/infoDialogue", Dashboard::string("deleteSFX"), $pageBase, $dataArray));
			break;
		default:
			exit(http_response_code(404));
	}
}

$order = "reuploadTime";
$orderSorting = "DESC";
$filters = ["sfxs.reuploadID > 0", "sfxs.isDisabled = 0"];
$pageOffset = is_numeric($_GET["page"]) ? abs(Escape::number($_GET["page"]) - 1) * 10 : 0;
$page = '';

$sfxs = Library::getSFXs($filters, $order, $orderSorting, '', $pageOffset, 10);

foreach($sfxs['sfxs'] AS &$sfx) $page .= Dashboard::renderSFXCard($sfx, $person);

$pageNumber = ceil($pageOffset / 10) + 1 ?: 1;
$pageCount = floor(($sfxs['count'] - 1) / 10) + 1;

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