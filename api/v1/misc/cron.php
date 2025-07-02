<?php
@header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once __DIR__."/../../../incl/lib/security.php";
require_once __DIR__."/../../../incl/lib/cron.php";
$sec = new Security();

// Check if the user is correctly authenticated
$person = $sec->loginPlayer();
if(!$person["success"]) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'cause' => 'Invalid credentials']));
}

// Check if cron's on cooldown
$runCron = Cron::doEverything($person, true);
if(!$runCron) {
	http_response_code(403);
    exit(json_encode(['success' => false, 'cause' => 'Please wait a bit before running Cron again'])); 
}

exit(json_encode(['success' => true])); 
?>