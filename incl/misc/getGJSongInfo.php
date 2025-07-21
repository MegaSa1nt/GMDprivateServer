<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$person = $sec->loginPlayer();
if(!$person["success"]) exit(CommonError::InvalidRequest);

$songID = abs(Escape::number($_POST['songID']));

$song = Library::getSongByID($songID);
if(!$song) {
	$song = Library::saveNewgroundsSong($songID);
	
	if(!$song) exit(CommonError::InvalidRequest);
}

if($song['isDisabled']) exit(CommonError::Disabled);

$songString = Library::getSongString($songID);

exit($songString);
?>