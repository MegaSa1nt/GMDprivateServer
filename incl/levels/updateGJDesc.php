<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$person = $sec->loginPlayer();
if(!$person["success"]) exit(CommonError::InvalidRequest);
$userID = $person['userID'];

$levelID = Escape::number($_POST['levelID']);
$levelDesc = Library::escapeDescriptionCrash(Escape::text(Escape::url_base64_decode($_POST['levelDesc'])));

$level = Library::getLevelByID($levelID);
if(!$level || $level['userID'] != $userID) exit(CommonError::InvalidRequest);

Library::changeLevelDescription($levelID, $person, $levelDesc);

exit(CommonError::Success);
?>