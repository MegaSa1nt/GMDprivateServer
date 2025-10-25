<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/XOR.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$person = $sec->loginPlayer();
if(!$person["success"]) exit(CommonError::InvalidRequest);

$toAccountID = Escape::latin_no_spaces($_POST["toAccountID"]);
$subject = Escape::base64($_POST["subject"]);
$body = Escape::base64($_POST["body"]);

$canMessage = Library::canSendMessage($person, $toAccountID);
if(!$canMessage) exit(CommonError::InvalidRequest);

Library::sendMessage($person, $toAccountID, $subject, $body);

exit(CommonError::Success);
?>