<?php
require_once __DIR__ . "/../incl/lib/connection.php";
require_once __DIR__ . "/../incl/lib/mainLib.php";
require __DIR__ . "/../config/dashboard.php";
require __DIR__ . "/../config/proxy.php";
$gs = new mainLib();
$file = trim(basename($_GET['request']));
switch($file) {
	case 'sfxlibrary.dat':
		$datFile = isset($_GET['dashboard']) ? 'standalone.dat' : 'gdps.dat';
		$datPath = __DIR__ . '/' . $datFile;
		if(!file_exists($datPath)) {
			$time = $db->prepare('SELECT reuploadTime FROM sfxs ORDER BY reuploadTime DESC LIMIT 1');
			$time->execute();
			$time = $time->fetchColumn();
			$gs->updateLibraries($_GET['token'], $_GET['expires'], $time, 0);
		}
		echo file_exists($datPath) ? file_get_contents($datPath) : '';
		break;
	case 'sfxlibrary_version.txt': 
		$time = $db->prepare('SELECT reuploadTime FROM sfxs WHERE reuploadTime > 0 ORDER BY reuploadTime DESC LIMIT 1');
		$time->execute();
		$time = $time->fetchColumn();
		if(!$time) $time = 1;
		$gs->updateLibraries($_GET['token'], $_GET['expires'], $time, 0);
		$times = [];
		foreach($customLibrary AS $library) {
			if($library[2] !== null && file_exists(__DIR__ . '/s'.$library[0].'.txt')) $times[] = explode(', ', file_get_contents(__DIR__ . '/s'.$library[0].'.txt'))[1];
		}
		$times[] = $time;
		rsort($times);
		echo $times[0];
		break;
	default:
		$servers = [];
		foreach($customLibrary AS $library) {
			$servers[$library[0]] = $library[2];
		}
		$sfxID = explode('.', substr($file, 1, strlen($file)))[0];
		if(!file_exists(__DIR__ . '/ids.json')) {
			$time = $db->prepare('SELECT reuploadTime FROM sfxs ORDER BY reuploadTime DESC LIMIT 1');
			$time->execute();
			$time = $time->fetchColumn();
			$gs->updateLibraries($_GET['token'], $_GET['expires'], $time, 0);
		}
		$song = $gs->getLibrarySongInfo($sfxID, 'sfx');
		$url = urldecode($song['download']);
		header("Location: $url");
		break;
}
?>
