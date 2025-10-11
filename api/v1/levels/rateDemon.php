<?php
@header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require __DIR__."/../../../incl/lib/mainLib.php";
require_once __DIR__."/../../../incl/lib/exploitPatch.php";
require_once __DIR__."/../../../incl/lib/security.php";
$sec = new Security();

// Check if the user is correctly authenticated
$person = $sec->loginPlayer();
if(!$person["success"]) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'cause' => 'Invalid credentials']));
}

http_response_code(400); // Set the bad request response code now to not repeat it after

$levelID = isset($_POST['levelID']) ? Escape::number(urldecode($_POST['levelID'])) : exit(json_encode(['success' => false, 'cause' => 'Please specify a valid "levelID" parameter']));
$demon = isset($_POST['demon']) ? Escape::number(urldecode($_POST['demon'])) : exit(json_encode(['success' => false, 'cause' => 'Please specify a demon difficulty']));

// Check for valid demon difficulty
if(!empty($demon) && $demon < 0 || $demon > 5) exit(json_encode(['success' => false, 'cause' => 'Please specify a valid demon difficulty']));

$ratingArray = [7, 8, 6, 9, 10];
$ratingNumber = $ratingArray[$demon - 1] ?? 6;

// Check for rate demon permission
if(!Library::checkPermission($person, "gameSetDifficulty")) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'cause' => 'You do not have the necessary permission to change the demon difficulty of a level!']));
}

$level = Library::getLevelByID($levelID);
// Check if the level exists
if(!$level) {
    http_response_code(404);
    exit(json_encode(['success' => false, 'cause' => 'This level does not exist!']));
}

// Check if the level is a demon level
if($level["starStars"] <= 10) {
    http_response_code(404);
    exit(json_encode(['success' => false, 'cause' => 'This level is not a Demon level!']));
}

Library::rateLevel($levelID, $person, Library::prepareDifficultyForRating($ratingNumber), $level['starStars'], $level['starCoins'], ($level['starEpic'] + ($level['starFeatured'] ? 1 : 0)));

http_response_code(200); // Set back the response code to 200 before exitting
exit(json_encode(['success' => true, 'level' => ['ID' => $levelID]]));
?>