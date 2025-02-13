<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$person = $sec->loginPlayer();
if(!$person["success"]) exit(CommonError::InvalidRequest);

$gauntletsString = $hashString = '';

$gauntlets = Library::getGauntlets();

foreach($gauntlets as &$gauntlet) {
	$levels = $gauntlet["level1"].",".$gauntlet["level2"].",".$gauntlet["level3"].",".$gauntlet["level4"].",".$gauntlet["level5"];
	$gauntletsString .= "1:".$gauntlet["ID"].":3:".$levels."|";
	$hashString .= $gauntlet["ID"].$levels;
}

exit(rtrim($gauntletsString, "|")."#".Security::generateSecondHash($hashString));
?>