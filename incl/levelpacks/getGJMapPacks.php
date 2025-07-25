<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/enums.php";
require_once __DIR__."/../lib/security.php";

$mapPackString = '';
$page = abs(Escape::number($_POST['page']) ?: 0);
$pageOffset = $page * 10;

$mapPacks = Library::getMapPacks($pageOffset);

foreach($mapPacks['mapPacks'] AS &$mapPack) {
	$hashArray[] = ['levelID' => $mapPack["ID"], 'stars' => $mapPack["stars"], 'coins' => $mapPack["coins"]];
	
	$mapPack["rgbcolors"] = $mapPack["rgbcolors"] == "none" || empty($mapPack["rgbcolors"]) ? '0,0,0' : $mapPack["rgbcolors"];
	$mapPack["colors2"] = $mapPack["colors2"] == "none" || empty($mapPack["colors2"]) ? $mapPack["rgbcolors"] : $mapPack["colors2"];
	
	$mapPackString .= "1:".$mapPack["ID"].":2:".Escape::translit($mapPack["name"]).":3:".$mapPack["levels"].":4:".$mapPack["stars"].":5:".$mapPack["coins"].":6:".$mapPack["difficulty"].":7:".$mapPack["rgbcolors"].":8:".$mapPack["colors2"]."|";
}

exit(rtrim($mapPackString, "|").'#'.$mapPacks['count'].':'.$pageOffset.':10#'.Security::generateLevelsHash($hashArray));
?>