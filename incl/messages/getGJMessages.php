<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$person = $sec->loginPlayer();
if(!$person["success"]) exit(CommonError::InvalidRequest);

$messagesString = '';
$getSent = abs(Escape::number($_POST["getSent"]) ?: 0);
$page = abs(Escape::number($_POST["page"]) ?: 0);
$pageOffset = $page * 10;

$messages = Library::getAccountMessages($person, $getSent, $pageOffset);
if(!$messages['messages']) exit(CommonError::NothingFound);

foreach($messages['messages'] AS &$message) {
	$message['subject'] = Escape::url_base64_encode(Escape::translit(Escape::url_base64_decode($message["subject"])));
	$message["userName"] = Library::makeClanUsername($message["extID"]);
	
	$messagesString .= "6:".$message["userName"].":3:".$message["userID"].":2:".$message["extID"].":1:".$message["messageID"].":4:".$message["subject"].":8:".$message["isNew"].":9:".$getSent.":7:".$uploadDate."|";
}

exit(rtrim($messagesString, "|")."#".$messages['count'].":".$pageOffset.":10");
?>