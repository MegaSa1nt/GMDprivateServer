<?php
chdir(dirname(__FILE__));
require_once "../lib/connection.php";
require_once "../lib/GJPCheck.php";
require_once "../lib/exploitPatch.php";

$accountID = GJPCheck::getAccountIDOrDie();
$soundID = ExploitPatch::number($_POST["soundID"] ?? "");

if (empty($soundID)) exit("-1");

$query = $db->prepare("DELETE FROM soundpacks WHERE soundID = :soundID AND accountID = :accountID");
$query->execute([":soundID" => $soundID, ":accountID" => $accountID]);

if($query->rowCount() > 0) {
    unlink("../../data/soundpacks/$soundID");
    echo "1";
} else {
    echo "-1";
}
?>