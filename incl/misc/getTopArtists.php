<?php
require __DIR__."/../../config/misc.php";
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$songsString = '';
$page = abs(Escape::number($_POST['page']));
$pageOffset = $page * 20;

if($topArtistsFromGD) {
	$cachedTopArtists = file_exists(__DIR__."/topArtists.cache") ? filemtime(__DIR__."/topArtists.cache") : 0;
	
	if($cachedTopArtists > time() - 3600) {
		$topArtists = file_get_contents(__DIR__."/topArtists.cache");
		
		exit($topArtists);
	}
	
	$data = ['offset' => $pageOffset, 'secret' => 'Wmfd2893gb7'];
	$headers = ['Content-type: application/x-www-form-urlencoded'];
		
	$topArtists = Library::sendRequest('https://www.boomlings.com/database/getGJTopArtists.php', http_build_query($data), $headers, "POST", false);
	
	file_put_contents(__DIR__."/topArtists.cache", $topArtists);
	
	exit($topArtists);
}

$person = $sec->loginPlayer();
if(!$person["success"]) exit(CommonError::InvalidRequest);

$favouriteSongs = Library::getFavouriteSongs($person, $pageOffset);
if(!$favouriteSongs["count"]) exit("4:You liked 0 songs!");

foreach($favouriteSongs["songs"] AS &$song) {
	$songsString .= "4:".Escape::translit($song["authorName"])." - ".Escape::translit($song["name"]).", ".$song["ID"];
	$songsString .= ":7:../redirect?q=".urlencode($song["download"]);
	$songsString .= "|";
}

exit($songsString."#".$favouriteSongs["count"].":".$pageOffset.":20");
?>