<?php
require __DIR__."/../../config/dashboard.php";
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/security.php";
require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";
require_once __DIR__."/../".$dbPath."incl/lib/enums.php";
$sec = new Security();

$person = Dashboard::loginDashboardUser();
if(!$person['success']) exit(Dashboard::renderErrorPage(Dashboard::string("uploadSongTitle"), Dashboard::string("errorLoginRequired")));

if(strpos($songEnabled, '1') === false && strpos($songEnabled, '2') === false) exit(Dashboard::renderErrorPage(Dashboard::string("uploadSongTitle"), Dashboard::string("errorPageIsDisabled")));

if(isset($_POST['songAuthor']) && isset($_POST['songTitle']) && isset($_POST['songType'])) {
	$songType = abs(Escape::number($_POST['songType']) ?: 0);
	$songAuthor = Escape::text($_POST['songAuthor'], 40) ?: '';
	$songTitle = Escape::text($_POST['songTitle'], 35) ?: '';
	$songFile = $_FILES && $_FILES['songFile']['error'] == UPLOAD_ERR_OK ? $_FILES['songFile'] : false;
	$songURL = isset($_POST['songURL']) ? filter_var($_POST['songURL'], FILTER_SANITIZE_URL) : false;
	
	$pathToSongsFolder = realpath(__DIR__.'/../songs/');
	
	$uploadSong = Library::uploadSong($person, $songType, $songAuthor, $songTitle, $songFile, $songURL, $pathToSongsFolder);
	if(!$uploadSong['success']) {
		switch($uploadSong['error']) {
			case SongError::UnknownError:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorFileIsEmpty"), "error"));
			case SongError::Banned:
				exit(Dashboard::renderToast("gavel", sprintf(Dashboard::string("bannedToast"), htmlspecialchars(Escape::url_base64_decode($uploadSong['info']['reason'])), '<text dashboard-date="'.$uploadSong['info']['expires'].'"></text>'), "error"));
			case SongError::Disabled:
				exit(Dashboard::renderErrorPage(Dashboard::string("uploadSongTitle"), Dashboard::string("errorPageIsDisabled")));
			case SongError::RateLimit:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorSongRateLimit"), "error"));
			case SongError::InvalidURL:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorInvalidURL"), "error"));
			case SongError::InvalidFile:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorCouldntReadFile"), "error"));
			case SongError::NotAnAudio:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorIsNotAnAudio"), "error"));
			case SongError::AlreadyUploaded:
				exit(Dashboard::renderToast("xmark", sprintf(Dashboard::string("errorAlreadyReuploaded"), $uploadSong['songID']), "error"));
			case SongError::TooBig:
				exit(Dashboard::renderToast("xmark", sprintf(Dashboard::string("errorMaxFileSize"), $songSize), "error"));
			case SongError::BadSongArtist:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorBadArtist"), "error"));
			case SongError::BadSongTitle:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorBadName"), "error"));
			default:
				exit(Dashboard::renderToast("xmark", Dashboard::string("errorTitle"), "error"));
		}
	}
	
	$song = Library::getSongByID($uploadSong['songID']);
	
	$dataArray = [
		'INFO_TITLE' => Dashboard::string("uploadSongTitle"),
		'INFO_DESCRIPTION' => Dashboard::string("successUploadedSong"),
		'INFO_EXTRA' => Dashboard::renderSongCard($song, $person, []),
		'INFO_BUTTON_TEXT' => Dashboard::string("uploadSongTitle"),
		'INFO_BUTTON_ONCLICK' => "getPage('@')"
	];
	
	exit(Dashboard::renderPage("general/info", Dashboard::string("uploadSongTitle"), "../", $dataArray));
}

$dataArray = [
	'UPLOAD_SONG_FILE_ENABLED' => strpos($songEnabled, '1') !== false ? 'true' : 'false',
	'UPLOAD_SONG_URL_ENABLED' => strpos($songEnabled, '2') !== false ? 'true' : 'false',
	
	'UPLOAD_SONG_BUTTON_ONCLICK' => "handleSongUpload('uploadSongForm')"
];

exit(Dashboard::renderPage("upload/song", Dashboard::string("uploadSongTitle"), "../", $dataArray));
?>