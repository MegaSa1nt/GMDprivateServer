<?php
@header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once __DIR__."/../../../incl/lib/mainLib.php";
require_once __DIR__."/../../../incl/lib/security.php";
$sec = new Security();

$person = $sec->loginPlayer();
if(!$person['success']) {
	http_response_code(401);
	exit(json_encode(['success' => false, 'cause' => 'Invalid credentials']));
}
$accountID = $person['accountID'];
$userID = $person['userID'];

Library::logAction($person, Action::SuccessfulLogin);

unset($person['IP']);

exit(json_encode($person));
?>