<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header("Access-Control-Allow-Headers: X-Requested-With");
require "../incl/dashboardLib.php";
include "../".$dbPath."incl/lib/connection.php";
include "../".$dbPath."incl/lib/mainLib.php";
require "../".$dbPath."incl/lib/exploitPatch.php";
require "../".$dbPath."incl/lib/GJPCheck.php";
$gs = new mainLib();
if(!isset($_GET)) $_GET = json_decode(file_get_contents('php://input'), true);

function getAccountIDOrDie(){	
	if(empty($_POST['accountID'])) exit("-1");

	$accountID = ExploitPatch::remove($_POST["accountID"]);

	if(!empty($_POST['gjp'])) GJPCheck::validateGJPOrDie($_POST['gjp'], $accountID);
	elseif(!empty($_POST['gjp2'])) GJPCheck::validateGJP2OrDie($_POST['gjp2'], $accountID);
	else exit("-1");

	return $accountID;
}

$accountID = getAccountIDOrDie();

$starAuto = 0;

$levelID = ExploitPatch::number(urldecode($_POST['levelID']));
$stars = ExploitPatch::number(urldecode($_POST['stars']));
$feature = ExploitPatch::number(urldecode($_POST['feature']));
$featuredID = ExploitPatch::number(urldecode($_POST['featuredID']));
$demon = isset($_POST['demon']) ? ExploitPatch::number(urldecode($_POST['demon'])) : 0;
$coins = ExploitPatch::number(urldecode($_POST['coins']));

if (empty($levelID)) {
    http_response_code(400);
    exit(json_encode(['dashboard' => true, 'success' => false, 'error' => 2, 'message' => 'Please supply a valid level ID.']));
}

if (!empty($stars) && $stars >= 1) $starAuto = 1;

if($gs->getMaxValuePermission($accountID, "commandRate") < 1 && $gs->getMaxValuePermission($accountID, "actionRateStars") < 1) {
    http_response_code(403);
    exit(json_encode(['dashboard' => true, 'success' => false, 'error' => 1, 'message' => 'You do not have the necessary permissions to use this!']));
}

if(!empty($feature) && $feature >= 1) {
	if ($gs->getMaxValuePermission($accountID, "commandFeature") < 1 || $gs->getMaxValuePermission($accountID, "commandEpic") < 1) {
		http_response_code(403);
		exit(json_encode(['dashboard' => true, 'success' => false, 'error' => 1, 'message' => 'You do not have the necessary permissions to feature a level!']));
	}
}

if (!empty($demon) && $demon < 0 || $demon > 6) {
    http_response_code(400);
    exit(json_encode(['dashboard' => true, 'success' => false, 'error' => 2, 'message' => 'Please specify a valid demon difficulty']));
}

if (!empty($feature) && $feature < 0 || $feature > 5) {
    http_response_code(400);
    exit(json_encode(['dashboard' => true, 'success' => false, 'error' => 2, 'message' => 'Please specify a valid feature state']));
}

if (!empty($coins) && $coins < 0 || $coins > 1) {
    http_response_code(400);
    exit(json_encode(['dashboard' => true, 'success' => false, 'error' => 2, 'message' => 'Please supply a valid true/false boolean for the coins parameter']));
}

$query = $db->prepare("SELECT * FROM levels WHERE levelID = :levelID");
$query->execute([':levelID' => $levelID]);
$query = $query->fetch();

if (!$query) {
    http_response_code(404);
    exit(json_encode(['dashboard' => true, 'success' => false, 'error' => 3, 'message' => 'This level does not exist!']));
}

// Set star auto from database if it cant fetch stars from parameters
if (empty($stars)) $starAuto = $query["starAuto"];

// Check for coins only update
if (empty($stars) && empty($feature) && $demon == 0 && empty($featuredID) && !empty($coins)) {
	if ($query["starStars"] != 0) {
		$query = $db->prepare("UPDATE levels SET starCoins=:starCoins WHERE levelID=:levelID");
		$query->execute([':starCoins' => $coins, ':levelID' => $levelID]);
		exit(json_encode(['dashboard' => true, 'success' => true]));
	} else {
		exit(json_encode(['dashboard' => true, 'success' => false, 'error' => 2, 'message' => 'This level has not been star rated!']));
	}
}

// Set coins to 0 if the level is set to be unrated
if (!empty($stars) && $stars == 0 && $query['starStars'] >= 1 && $coins == 0) $coins = 0;
// Set coins from database if it cant fetch coins from parameters
if (empty($coins)) $coins = $query["starCoins"];

// Check for featured ID only update
if (empty($stars) && empty($feature) && $demon == 0 && !empty($featuredID)) {
	if ($query["starStars"] != 0) {
		$query = $db->prepare("UPDATE levels SET starFeatured=:starFeatured WHERE levelID = :levelID");
		$query->execute([':starFeatured' => $featuredID, ':levelID' => $levelID]);
		exit(json_encode(['dashboard' => true, 'success' => true]));
	} else {
		exit(json_encode(['dashboard' => true, 'success' => false, 'error' => 2, 'message' => 'This level has not been star rated!']));
	}
}

// Check for feature only update
if (empty($stars) && !empty($feature) && $demon == 0) {
	if ($query["starStars"] != 0) {
		if (!empty($featuredID) && $featuredID >= 1) {
			switch($feature) {
				case 0:
					$feature = $featuredID;
					$epic = 0;
					break;
				case 1:
				case 2:
				case 3:
				case 4:
					$feature = $featuredID;
					$epic = $feature - 1;
					break;
			}
			$query = $db->prepare("UPDATE levels SET starFeatured=:feature, starEpic=:epic WHERE levelID=:levelID");
			$query->execute([':feature' => $feature, ':epic' => $epic, ':levelID' => $levelID]);
		} else {
			$gs->featureLevel($accountID, $levelID, $feature);
		}
		exit(json_encode(['dashboard' => true, 'success' => true]));
	} else {
		exit(json_encode(['dashboard' => true, 'success' => false, 'error' => 2, 'message' => 'This level has not been star rated!']));
	}
}

if (empty($stars)) {
	http_response_code(400);
	exit(json_encode(['dashboard' => true, 'success' => false, 'error' => 2, 'message' => 'Please supply a valid amount of stars.']));
}

// Set the rate w/o feature for now
$query = $db->prepare("UPDATE levels SET starStars=:stars, starAuto=:starAuto, starCoins=:starCoins, starDemon=:starDemon, rateDate = :rateDate WHERE levelID = :levelID");
$query->execute([':stars' => $stars, ':starAuto' => $starAuto, ':starDemon' => $demon, ':starCoins' => $coins, ':rateDate' => time(), ':levelID' => $levelID]);

// Insert action into mod actions
$query = $db->prepare("INSERT INTO modactions (type, value, value2, value3, timestamp, account) VALUES ('1', :value, :value2, :value3, :timestamp, :id)");
$query->execute([':value' => $gs->getDiffFromStars($stars), ':value2' => $stars, ':value3' => $feature, ':timestamp' => time(), ':id' => $accountID]);

// Set the feature/epic if it is set
if (!empty($featuredID) && $featuredID >= 1) {
	switch($feature) {
		case 0:
			$feature = $featuredID;
			$epic = 0;
			break;
		case 1:
		case 2:
		case 3:
		case 4:
			$feature = $featuredID;
			$epic = $feature - 1;
			break;
	}
	$query = $db->prepare("UPDATE levels SET starFeatured=:feature, starEpic=:epic WHERE levelID=:levelID");
	$query->execute([':feature' => $feature, ':epic' => $epic, ':levelID' => $levelID]);
} else {
	$gs->featureLevel($accountID, $levelID, $feature);
}

exit(json_encode(['dashboard' => true, 'success' => true]));
?>