<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$person = $sec->loginPlayer();
if(!$person["success"]) exit(CommonError::InvalidRequest);

$toAccountID = Escape::latin_no_spaces($_POST["toAccountID"]);
$comment = Escape::base64($_POST["comment"]);

$canSendFriendRequest = Library::canSendFriendRequest($person, $toAccountID);
if(!$canSendFriendRequest) exit(CommonError::InvalidRequest);

Library::sendFriendRequest($person, $toAccountID, $comment);

exit(CommonError::Success);
?>