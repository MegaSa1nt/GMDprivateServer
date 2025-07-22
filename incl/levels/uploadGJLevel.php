<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/enums.php";
$lib = new Library();
$sec = new Security();

$person = $sec->loginPlayer();
if(!$person["success"]) exit(CommonError::InvalidRequest);

$gameVersion = abs(Escape::number($_POST["gameVersion"]));
$levelID = Escape::number($_POST["levelID"]);
$levelName = Escape::latin($_POST["levelName"], 25) ?: 'Unnamed level';
$levelDesc = $gameVersion >= 20 ? Escape::translit(Escape::text(Escape::url_base64_decode($_POST["levelDesc"]), 300)) : Escape::translit(Escape::text($_POST["levelDesc"], 300));
$levelDesc = Escape::url_base64_encode(Library::escapeDescriptionCrash($levelDesc));
$levelLength = abs(Escape::number($_POST["levelLength"]));
$audioTrack = abs(Escape::number($_POST["audioTrack"]));

$binaryVersion = !empty($_POST["binaryVersion"]) ? abs(Escape::number($_POST["binaryVersion"])) : 0;
$auto = !empty($_POST["auto"]) ? Security::limitValue(0, Escape::number($_POST["auto"]), 1) : 0;
$original = !empty($_POST["original"]) ? abs(Escape::number($_POST["original"])) : 0;
$twoPlayer = !empty($_POST["twoPlayer"]) ? Security::limitValue(0, Escape::number($_POST["twoPlayer"]), 1) : 0;
$songID = !empty($_POST["songID"]) ? abs(Escape::number($_POST["songID"])) : 0;
$objects = !empty($_POST["objects"]) ? abs(Escape::number($_POST["objects"])) : 0;
$coins = !empty($_POST["coins"]) ? Security::limitValue(0, Escape::number($_POST["coins"]), 3) : 0;
$requestedStars = !empty($_POST["requestedStars"]) ? Security::limitValue(0, Escape::number($_POST["requestedStars"]), 10) : 0;
$extraString = !empty($_POST["extraString"]) ? Escape::text($_POST["extraString"]) : "29_29_29_40_29_29_29_29_29_29_29_29_29_29_29_29";
$levelString = Escape::text($_POST["levelString"]) ?: '';
$levelInfo = !empty($_POST["levelInfo"]) ? Escape::text($_POST["levelInfo"]) : "";
switch(true) {
	case isset($_POST['unlisted2']):
		$unlisted = Security::limitValue(0, Escape::number($_POST["unlisted2"]), 2);
		break;
	case isset($_POST['unlisted1']):
		$unlisted = Security::limitValue(0, Escape::number($_POST["unlisted1"]), 2);
		break;
	default:
		$unlisted = Security::limitValue(0, Escape::number($_POST["unlisted"]), 2);
		break;
}
$isLDM = !empty($_POST["ldm"]) ? Security::limitValue(0, Escape::number($_POST["ldm"]), 1) : 0;
$wt = !empty($_POST["wt"]) ? abs(Escape::number($_POST["wt"])) : 0;
$wt2 = !empty($_POST["wt2"]) ? abs(Escape::number($_POST["wt2"])) : 0;
$settingsString = !empty($_POST["settingsString"]) ? Escape::text($_POST["settingsString"]) : "";
$songIDs = !empty($_POST["songIDs"]) ? Escape::multiple_ids($_POST["songIDs"]) : '';
$sfxIDs = !empty($_POST["sfxIDs"]) ? Escape::multiple_ids($_POST["sfxIDs"]) : '';
$ts = !empty($_POST["ts"]) ? abs(Escape::number($_POST["ts"])) : 0;
$password = !empty($_POST["password"]) ? abs(Escape::number($_POST["password"])) : ($gameVersion > 21 ? 1 : 0);

$isAbleToUploadLevel = Library::isAbleToUploadLevel($person, $levelName, $levelDesc);
if(!$isAbleToUploadLevel['success']) exit(CommonError::InvalidRequest);

$levelDetails = [
	'gameVersion' => $gameVersion,
	'binaryVersion' => $binaryVersion,
	'levelDesc' => $levelDesc,
	'levelLength' => $levelLength,
	'audioTrack' => $audioTrack,
	'auto' => $auto,
	'original' => $original,
	'twoPlayer' => $twoPlayer,
	'songID' => $songID,
	'objects' => $objects,
	'coins' => $coins,
	'requestedStars' => $requestedStars,
	'extraString' => $extraString,
	'levelInfo' => $levelInfo,
	'unlisted' => $unlisted,
	'isLDM' => $isLDM,
	'wt' => $wt,
	'wt2' => $wt2,
	'settingsString' => $settingsString,
	'songIDs' => $songIDs,
	'sfxIDs' => $sfxIDs,
	'ts' => $ts,
	'password' => $password
];

$uploadLevel = Library::uploadLevel($person, $levelID, $levelName, $levelString, $levelDetails);
if(!$uploadLevel['success']) exit(CommonError::InvalidRequest);

exit($uploadLevel['levelID']);
?>