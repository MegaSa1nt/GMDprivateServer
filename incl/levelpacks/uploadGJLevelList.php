<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$person = $sec->loginPlayer();
if(!$person["success"]) exit(CommonError::InvalidRequest);

$listID = Escape::number($_POST["listID"]);
$listName = Escape::latin($_POST["listName"]) ?: "Unnamed list";
$listDesc = Escape::translit(Escape::text(Escape::url_base64_decode($_POST["listDesc"]), 300)) ?: '';
$listLevels = Escape::multiple_ids($_POST["listLevels"]);
$difficulty = Security::limitValue(-1, Escape::number($_POST["difficulty"]), 10);
$original = Escape::number($_POST["original"]) ?: 0;
$unlisted = Security::limitValue(0, Escape::number($_POST["unlisted"]), 2);

if(count(explode(',', $listLevels)) == 0) exit(CommonError::InvalidRequest);

if(Security::checkFilterViolation($person, $listName, 3) || Security::checkFilterViolation($person, $listDesc, 3)) exit(CommonError::InvalidRequest);

$listDesc = Escape::url_base64_encode(Library::escapeDescriptionCrash($listDesc));

$listDetails = [
	'listName' => $listName,
	'listDesc' => $listDesc,
	'listLevels' => $listLevels,
	'difficulty' => $difficulty,
	'original' => $original,
	'unlisted' => $unlisted
];

$listID = Library::uploadList($person, $listID, $listDetails);
if(!$listID) exit(CommonError::InvalidRequest);

exit((string)$listID);
?>