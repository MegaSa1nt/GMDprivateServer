<?php
@header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__."/../../../incl/lib/security.php";
require_once __DIR__."/../../../incl/lib/exploitPatch.php";
require_once __DIR__."/../../../incl/lib/mainLib.php";
$sec = new Security();

// Check if the user is correctly authenticated
$person = $sec->loginPlayer();
if(!$person["success"]) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'cause' => 'Invalid credentials']));
}

$levelID = Escape::number($_GET['levelID']);

// Check if the level ID is valid (if its empty before/after checks)
if(!$levelID) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'cause' => 'You must specify a valid "levelID" parameter!']));
}

// Check if the level even exists or if the player can even see it
$level = Library::getLevelByID($levelID);
if(!$level || !Library::canAccountPlayLevel($person, $levelID)) {
    http_response_code(404);
    exit(json_encode(['success' => false, 'cause' => 'Level not found!']));
}

// Check if there was an error getting the GMD
$GMDFile = Library::getGMDFile($levelID);
if(!$GMDFile) {
    http_response_code(500);
    exit(json_encode(['success' => false, 'cause' => 'Could not get the level\'s data']));
}

exit(json_encode(['success' => true, 'level' => ['id' => $levelID, 'name' => $level['levelName'], 'gmd' => base64_encode($GMDFile)]]));
?>
