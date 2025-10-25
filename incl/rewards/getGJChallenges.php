<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/XOR.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$person = $sec->loginPlayer();
if(!$person["success"]) exit(CommonError::InvalidRequest);
$accountID = $person["accountID"];
$userID = $person["userID"];

$chk = XORCipher::cipher(Escape::url_base64_decode(substr(Escape::latin($_POST["chk"]), 5)), 19847);
$udid = Escape::base64($_POST["udid"]);

$questID = floor(time() / 100000);
$timeLeft = strtotime("tomorrow 00:00:00") - time();

$quests = Library::getQuests();
if(!$quests[2]) exit(CommonError::InvalidRequest);

$quest1 = $questID.",".$quests[0]["type"].",".$quests[0]["amount"].",".$quests[0]["reward"].",".Escape::dat($quests[0]["name"]);
$quest2 = ($questID + 1).",".$quests[1]["type"].",".$quests[1]["amount"].",".$quests[1]["reward"].",".Escape::dat($quests[1]["name"]);
$quest3 = ($questID + 2).",".$quests[2]["type"].",".$quests[2]["amount"].",".$quests[2]["reward"].",".Escape::dat($quests[2]["name"]);

$string = Escape::url_base64_encode(XORCipher::cipher("M336G:".$userID.":".$chk.":".$udid.":".$accountID.":".$timeLeft.":".$quest1.":".$quest2.":".$quest3, 19847));
$hash = Security::generateThirdHash($string);
exit("M336G".$string."|".$hash);
?>