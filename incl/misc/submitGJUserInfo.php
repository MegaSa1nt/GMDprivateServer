<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/security.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/XOR.php";
require_once __DIR__."/../lib/enums.php";
$sec = new Security();

$person = $sec->loginPlayer();
if(!$person["success"]) exit(CommonError::InvalidRequest);

if(empty($_POST["levelsInfo"])) exit(CommonError::NothingFound);

/* GD doesn't XOR encrypt this data, i just want to encrypt it */
$data = Escape::url_base64_encode(XORCipher::cipher($_POST["levelsInfo"], 24157));
file_put_contents(__DIR__."/../../data/info/".$person['accountID'], $data);

exit(CommonError::Success);
?>