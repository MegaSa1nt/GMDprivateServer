<?php
require __DIR__."/../../config/dailyChests.php";
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/XOR.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$person = $sec->loginPlayer();
if(!$person["success"]) exit(CommonError::InvalidRequest);
$accountID = $person["accountID"];
$userID = $person["userID"];

$time = time();
$smallChestStuff = $bigChestStuff = '0,0,0,0';

$rewardType = abs(Escape::number($_POST["rewardType"]));
$chk = XORCipher::cipher(Escape::url_base64_decode(substr(Escape::latin($_POST["chk"]), 5)), 59182);
$udid = Escape::base64($_POST["udid"]);

$getChestsTime = Library::getDailyChests($userID);

$smallChestCount = $getChestsTime['chest1count'];
$bigChestCount = $getChestsTime['chest2count'];
$smallChestTime = $time - $getChestsTime['chest1time'];
$bigChestTime = $time - $getChestsTime['chest2time'];

$smallChestLeft = max(0, $chest1wait - $smallChestTime);
$bigChestLeft = max(0, $chest2wait - $bigChestTime);

$smallChestItems = isset($chest1items) ? $chest1items : [1, 2, 3, 4, 5, 6];
$bigChestItems = isset($chest2items) ? $chest2items : [1, 2, 3, 4, 5, 6];

switch($rewardType) {
	case 1:
		if($smallChestLeft > 0) exit(CommonError::InvalidRequest);
		
		$smallChestCount++;
		Library::retrieveDailyChest($userID, 1);

		$smallChestStuff = rand($chest1minOrbs, $chest1maxOrbs).",".rand($chest1minDiamonds, $chest1maxDiamonds).",".$chest1items[array_rand($chest1items)].",".rand($chest1minKeys, $chest1maxKeys);
		$smallChestLeft = $chest1wait;

		break;
	case 2:
		if($bigChestLeft > 0) exit(CommonError::InvalidRequest);
		
		$bigChestCount++;
		Library::retrieveDailyChest($userID, 2);
		
		$bigChestStuff = rand($chest2minOrbs, $chest2maxOrbs).",".rand($chest2minDiamonds, $chest2maxDiamonds).",".$chest2items[array_rand($chest2items)].",".rand($chest2minKeys, $chest2maxKeys);
		$bigChestLeft = $chest2wait;
		
		break;
}

$string = Escape::url_base64_encode(XORCipher::cipher("1:".$userID.":".$chk.":".$udid.":".$accountID.":".$smallChestLeft.":".$smallChestStuff.":".$smallChestCount.":".$bigChestLeft.":".$bigChestStuff.":".$bigChestCount.":".$rewardType, 59182));
$hash = Security::generateFourthHash($string);

exit("PPunk".$string."|".$hash);
?>