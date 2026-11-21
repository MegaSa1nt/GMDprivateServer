<?php
chdir(dirname(__FILE__));
require_once "../lib/connection.php";
require_once "../lib/GJPCheck.php";
require_once "../lib/exploitPatch.php";

$accountID = GJPCheck::getAccountIDOrDie();

$targetAccountID = ExploitPatch::number($_POST["targetAccountID"] ?? 0);
$mute = ExploitPatch::number($_POST["mute"] ?? 1);

if (!$targetAccountID || $accountID == $targetAccountID) exit("-1");

if ($mute) {
    $query = $db->prepare("SELECT 1 FROM accounts WHERE accountID = :targetAccountID");
    $query->execute([":id" => $targetAccountID]);
    if ($query->fetchColumn()) {
        $query = $db->prepare("INSERT IGNORE INTO mutes (person1, person2, timestamp) VALUES (:person1, :person2, :timestamp)");
        $query->execute([":person1" => $accountID, ":person2" => $targetAccountID, ":timestamp" => time()]);
    }
} else {
    $query = $db->prepare("DELETE FROM mutes WHERE person1 = :person1 AND person2 = :person2");
    $query->execute([":person1" => $accountID, ":person2" => $targetAccountID]);
}

echo "1";
?>