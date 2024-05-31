<?php
chdir(dirname(__FILE__));
include "../lib/connection.php";
require_once "../lib/GJPCheck.php";
require_once "../lib/exploitPatch.php";
require_once "../lib/mainLib.php";
$gs = new mainLib();
$gjp2check = isset($_POST['gjp2']) ? $_POST['gjp2'] : $_POST['gjp'];
$gjp = ExploitPatch::charclean($gjp2check);
$stars = ExploitPatch::number($_POST["stars"]);
$feature = ExploitPatch::number($_POST["feature"]);
$levelID = ExploitPatch::number($_POST["levelID"]);
$accountID = GJPCheck::getAccountIDOrDie();
$difficulty = $gs->getDiffFromStars($stars);
if($gs->checkPermission($accountID, "actionRateStars")) {
	$gs->featureLevel($accountID, $levelID, $feature);
	$gs->verifyCoinsLevel($accountID, $levelID, 1);
	$gs->rateLevel($accountID, $levelID, $stars, $difficulty["diff"], $difficulty["auto"], $difficulty["demon"], $feature);
	echo 1;
} elseif($gs->checkPermission($accountID, "actionSuggestRating")) {
	$gs->suggestLevel($accountID, $levelID, $difficulty["diff"], $stars, $feature, $difficulty["auto"], $difficulty["demon"]);
	echo 1;
} else echo -2;
?>
