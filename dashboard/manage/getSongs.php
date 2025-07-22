<?php
require_once __DIR__."/../incl/dashboardLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/mainLib.php";
require_once __DIR__."/../".$dbPath."incl/lib/exploitPatch.php";

$songs = [];

if(isset($_GET['search'])) {
	$search = trim(Escape::text($_GET['search']));
	if(empty($search)) exit(json_encode([]));
	
	$filters = ['name LIKE "%'.$search.'%" OR authorName LIKE "%'.$search.'%" OR ID LIKE "'.$search.'"', "reuploadID > 0"];
	
	$songsArray = Library::getSongs($filters, false, '', '', 0, 5);
	if(!$songsArray['songs']) exit(json_encode([]));
	
	foreach($songsArray['songs'] AS &$song) {
		$songs[] = [
			'ID' => $song['ID'],
			'name' => htmlspecialchars($song['authorName'].' - '.$song['name']),
			'icon' => '<i class="fa-solid fa-music"></i>'
		];
	}
	
	exit(json_encode($songs));
}

exit(json_encode([]));
?>