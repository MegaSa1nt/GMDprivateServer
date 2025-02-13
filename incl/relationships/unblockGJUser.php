<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$person = $sec->loginPlayer();
if(!$person["success"]) exit(CommonError::InvalidRequest);

$targetAccountID = Escape::latin_no_spaces($_POST['targetAccountID']);

$unblockUser = Library::unblockUser($person, $targetAccountID);
if(!$unblockUser) exit(CommonError::InvalidRequest);

exit(CommonError::Success);
?>