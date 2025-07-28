<?php
require __DIR__."/../../config/misc.php";
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/XOR.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$person = $sec->loginPlayer();
if(!$person["success"]) exit(CommonError::InvalidRequest);

if(!is_numeric($_POST["levelID"])) exit(CommonError::InvalidRequest);

$levelID = Escape::number($_POST["levelID"]);
$gameVersion = !empty($_POST["gameVersion"]) ? abs(Escape::number($_POST["gameVersion"])) : 1;
$extras = !empty($_POST["extras"]) && $_POST["extras"];

$feaID = 0;
if($levelID < 0) {
	$daily = Library::getDailyLevelID($levelID);
	if(!$daily) exit(CommonError::InvalidRequest);
	
	$levelID = $daily['levelID'];
	$feaID = $daily['feaID'];
}

$level = Library::getLevelByID($levelID);
if(!$level || !Library::canAccountPlayLevel($person, $level)) exit(CommonError::InvalidRequest);

Library::addDownloadToLevel($person, $levelID);

$uploadDate = Library::makeTime($level["uploadDate"]);
if($level["updateDate"]) $updateDate = Library::makeTime($level["updateDate"]);

$levelDesc = Escape::translit(Escape::text(Escape::url_base64_decode($level["levelDesc"])));
$levelString = file_exists(__DIR__."/../../data/levels/".$levelID) ? file_get_contents(__DIR__."/../../data/levels/".$levelID) : $level["levelString"];

$pass = $xorPass = $level['password'];
if($gameVersion > 18) {
	if(substr($levelString, 0, 3) == 'kS1') $levelString = Escape::url_base64_encode(gzcompress($levelString));
	if($gameVersion > 19) {
		if($pass != 0) $xorPass = Escape::url_base64_encode(XORCipher::cipher($pass, 26364));
		$levelDesc = Escape::url_base64_encode($levelDesc);
	}
}
$response = "1:".$level["levelID"].":2:".Escape::translit($level["levelName"]).":3:".$levelDesc.":4:".$levelString.":5:".$level["levelVersion"].":6:".$level["userID"].":8:".$level["difficultyDenominator"].":9:".$level["starDifficulty"].":10:".$level["downloads"].":11:1:12:".$level["audioTrack"].":13:".$level["gameVersion"].":14:".$level["likes"].":16:".$level["dislikes"].":17:".$level["starDemon"].":43:".$level["starDemonDiff"].":25:".$level["starAuto"].":18:".$level["starStars"].":19:".$level["starFeatured"].":42:".$level["starEpic"].":45:".$level["objects"].":15:".$level["levelLength"].":30:".$level["original"].":31:".$level['twoPlayer'].":28:".$uploadDate.(isset($updateDate) ? ":29:".$updateDate : '').":35:".$level["songID"].":36:".$level["extraString"].":37:".$level["coins"].":38:".$level["starCoins"].":39:".$level["requestedStars"].":46:".$level["wt"].":47:".$level["wt2"].":48:".$level["settingsString"].":40:".$level["isLDM"].":27:".$xorPass.":52:".$level["songIDs"].":53:".$level["sfxIDs"].":57:".$level['ts'];

if($feaID) $response .= ":41:".$feaID;
if($extras) $response .= ":26:".$level["levelInfo"];

$response .= "#".Security::generateFirstHash($levelString);

$someString = $level["userID"].",".$level["starStars"].",".$level["starDemon"].",".$level["levelID"].",".$level["starCoins"].",".$level["starFeatured"].",".$pass.",".$feaID;
$response .= "#".Security::generateSecondHash($someString);

if($feaID) $response .= "#".Library::getUserString($level);

exit($response);
?>