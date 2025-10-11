<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$person = $sec->loginPlayer();
if(!$person["success"]) exit(CommonError::InvalidRequest);

$levelID = Escape::number($_POST['levelID']);
$stars = abs(Escape::number($_POST['stars']));
$feature = abs(Escape::number($_POST['feature']));

$level = Library::getLevelByID($levelID);
if(!$level) exit(CommonError::InvalidRequest);

switch(true) {
	case Library::checkPermission($person, 'gameRateLevel'):		
		Library::rateLevel($levelID, $person, $stars, $stars, ($level['coins'] > 0 ? 1 : 0), $feature);
		
		exit(CommonError::Success);
	case Library::checkPermission($person, 'gameSuggestLevel'):
		if($level['starStars']) exit(CommonError::InvalidRequest);
		
		Library::sendLevel($levelID, $person, $stars, $stars, $feature);
		
		exit(CommonError::Success);
}

exit(CommonError::InvalidRequest);
?>