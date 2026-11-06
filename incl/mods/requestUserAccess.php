<?php
chdir(dirname(__FILE__));
require_once "../lib/GJPCheck.php";
require_once "../lib/exploitPatch.php";
require_once "../lib/mainLib.php";
$gs = new mainLib();

$accountID = GJPCheck::getAccountIDOrDie();

if($gs->getMaxValuePermission($accountID,"actionRequestMod") >= 1) {
	$permState = $gs->getMaxValuePermission($accountID,"modBadgeLevel");	   
	if($permState >= 2) exit("2");
	
	exit((string)$permState); 
}

exit("-1");
?>