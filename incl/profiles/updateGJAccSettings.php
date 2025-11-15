<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$person = $sec->loginPlayer();
if(!$person["success"]) exit(CommonError::InvalidRequest);
$accountID = $person['accountID'];

$messagesState = Security::limitValue(0, Escape::number($_POST["mS"]), 2);
$friendRequestsState = $_POST["frS"] ? 1 : 0;
$commentsState = Security::limitValue(0, Escape::number($_POST["cS"]), 2);
$socialsYouTube = Escape::text($_POST["yt"], 30);
$socialsTwitter = Escape::text($_POST["twitter"], 20);
$socialsTwitch = Escape::text($_POST["twitch"], 30);

Library::updateAccountSettings($person, $accountID, $messagesState, $friendRequestsState, $commentsState, $socialsYouTube, $socialsTwitter, $socialsTwitch);

exit(CommonError::Success);
?>