<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
$accountID = $person['accountID'];

$favouriteSongs = [];
if($person['success']) {
	$favouriteSongsArray = Library::getFavouriteSongs($person, 0, false);

	foreach($favouriteSongsArray['songs'] AS &$favouriteSong) $favouriteSongs[] = $favouriteSong["songID"];
}

if($_GET['id']) {
	$contextMenuData = [];
	$pageBase = '../../';
	
	$parameters = explode("/", Escape::text($_GET['id']));
	
	$songID = Escape::number($parameters[0]);
	
	$song = Library::getSongByID($songID);
	if(!$song || !$song['reuploadID']) exit(Dashboard::renderErrorPage(Dashboard::string("songsTitle"), Dashboard::string("errorSongNotFound"), '../../'));
	
	switch($parameters[1]) {
		case 'manage':
			$pageBase = '../../../';
			
			$manageSongPermission = Library::checkPermission($person, "dashboardManageSongs");
			if($accountID != $song['reuploadID'] && !$manageSongPermission) exit(Dashboard::renderErrorPage(Dashboard::string("songsTitle"), Dashboard::string("errorNoPermission"), '../../../'));

			$songReuploader = Library::getUserByAccountID($song['reuploadID']);
			
			$songArtist = htmlspecialchars($song['authorName']);
			$songTitle = htmlspecialchars($song['name']);
			
			$dataArray = [
				'SONG_ID' => $songID,
				
				'SONG_ARTIST' => $songArtist,
				'SONG_TITLE' => $songTitle,

				'SONG_ENABLED_VALUE' => $song["isDisabled"] ? 0 : 1,
				'SONG_ENABLED_REMOVE_CHECK' => $song["isDisabled"] ? 'checked' : '',
				'SONG_CAN_DISABLE' => $manageSongPermission ? 'true' : 'false',
			];
			
			exit(Dashboard::renderPage("manage/song", Dashboard::string("manageSongTitle"), $pageBase, $dataArray));
			break;
		case 'delete':
			$pageBase = '../../../';
			
			$manageSongPermission = Library::checkPermission($person, "dashboardManageSongs");
			if($accountID != $song['reuploadID'] && !$manageSongPermission) exit(Dashboard::renderErrorPage(Dashboard::string("songsTitle"), Dashboard::string("errorNoPermission"), '../../../'));
			
			$dataArray = [
				'INFO_TITLE' => Dashboard::string("deleteSong"),
				'INFO_DESCRIPTION' => Dashboard::string("deleteSongDesc"),
				'INFO_EXTRA' => Dashboard::renderSongCard($song, $person, $favouriteSongs),
				
				'INFO_BUTTON_TEXT_FIRST' => Dashboard::string("cancel"),
				'INFO_BUTTON_ONCLICK_FIRST' => "getPage('browse/songs', 'list')",
				'INFO_BUTTON_STYLE_FIRST' => "",
				'INFO_BUTTON_TEXT_SECOND' => Dashboard::string("deleteSong"),
				'INFO_BUTTON_ONCLICK_SECOND' => "postPage('manage/deleteSong', 'infoForm', 'list')",
				'INFO_BUTTON_STYLE_SECOND' => "error",
				
				'INFO_INPUT_NAME' => 'songID',
				'INFO_INPUT_VALUE' => $songID
			];
			
			exit(Dashboard::renderPage("general/infoDialogue", Dashboard::string("deleteSong"), $pageBase, $dataArray));
			break;
		default:
			exit(http_response_code(404));
	}
}

$order = "reuploadTime";
$orderSorting = "DESC";
$filters = ["songs.reuploadID > 0", "songs.isDisabled = 0"];
$pageOffset = is_numeric($_GET["page"]) ? abs(Escape::number($_GET["page"]) - 1) * 10 : 0;
$page = '';

$songs = Library::getSongs($filters, $order, $orderSorting, '', $pageOffset, 10);

foreach($songs['songs'] AS &$song) $page .= Dashboard::renderSongCard($song, $person, $favouriteSongs);

$pageNumber = ceil($pageOffset / 10) + 1 ?: 1;
$pageCount = floor(($songs['count'] - 1) / 10) + 1;

$dataArray = [
	'ADDITIONAL_PAGE' => $page,
	'SONG_PAGE_TEXT' => sprintf(Dashboard::string('pageText'), $pageNumber, $pageCount),
	'SONG_NO_SONGS' => empty($page) ? 'true' : 'false',
	
	'IS_FIRST_PAGE' => $pageNumber == 1 ? 'true' : 'false',
	'IS_LAST_PAGE' => $pageNumber == $pageCount ? 'true' : 'false',
	
	'FIRST_PAGE_BUTTON' => "getPage('@page=REMOVE_QUERY', 'list')",
	'PREVIOUS_PAGE_BUTTON' => "getPage('@".(($pageNumber - 1) > 1 ? "page=".($pageNumber - 1) : 'page=REMOVE_QUERY')."', 'list')",
	'NEXT_PAGE_BUTTON' => "getPage('@page=".($pageNumber + 1)."', 'list')",
	'LAST_PAGE_BUTTON' => "getPage('@page=".$pageCount."', 'list')"
];

$fullPage = Dashboard::renderTemplate("browse/songs", $dataArray);

exit(Dashboard::renderPage("general/wide", Dashboard::string("songsTitle"), "../", $fullPage));
?>