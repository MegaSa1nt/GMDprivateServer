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

$level = Library::getLevelByID($levelID);
if(!$level || ($level['userID'] != $userID && !Library::checkPermission($person, 'gameDeleteLevel'))) exit(CommonError::InvalidRequest);

$deleteLevel = Library::deleteLevel($levelID, $person);
if(!$deleteLevel) exit(CommonError::InvalidRequest);

exit(CommonError::Success);
?>