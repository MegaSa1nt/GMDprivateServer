<?php
@header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__."/../../../incl/lib/exploitPatch.php";
require_once __DIR__."/../../../incl/lib/mainLib.php";

$levelID = Escape::number(urldecode($_GET['level']));

// Check if 'levelID' is supplied/valid
if(empty($levelID)) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'cause' => "You must specify a valid \"levelID\" parameter!"]));
}

// Check if the level exists
$levelExists = Library::getLevelByID($levelID);
if(!$levelExists) {
    http_response_code(404);
    exit(json_encode(['success' => false, 'cause' => "This level was not found"]));
}

// Fetch all sends from the database & check if the level has any sends
$sendsInfo = Library::getLatestSendsByLevelID($levelID);
if(!$sendsInfo) {
    http_response_code(404);
    exit(json_encode(['success' => false, 'cause' => "This level was never sent"]));
}

$sends = [];
foreach($sendsInfo AS $send) {
    $user = Library::getUserFromSearch($send["suggestBy"]);
    $sends[] = [
        "mod" => [
            "userName" => $user["userName"],
            "accountID" => $user["extID"],
            "userID" => $user["userID"],
        ],
        "send" => [
            "difficulty" => Library::prepareDifficultyForRating($send["suggestDifficulty"], $send["suggestAuto"], $send["suggestDemon"]),
            "stars" => $send["suggestStars"],
            "featured" => $send["suggestFeatured"]
        ],
        "timestamp" => $send["timestamp"]
    ];
}

exit(json_encode(['success' => true, 'sends' => $sends]));
?>