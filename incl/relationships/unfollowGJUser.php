<?php
chdir(dirname(__FILE__));
require "../lib/connection.php";
require "../../config/misc.php";
require_once "../lib/GJPCheck.php";
require_once "../lib/exploitPatch.php";
require_once "../lib/mainLib.php";
require_once "../lib/cron.php";

$gs = new mainLib();

if (empty($_POST["targetAccountID"])) exit("1");

$accountID = GJPCheck::getAccountIDOrDie();
$targetAccountID = ExploitPatch::remove($_POST["targetAccountID"]);

if(!$targetAccountID || $accountID == $targetAccountID) exit("1");

$query = $db->prepare("DELETE FROM follows WHERE person1 = :accountID AND person2 = :targetAccountID");
$query->execute([':accountID' => $accountID, ':targetAccountID' => $targetAccountID]);

if ($query->rowCount() > 0) {
    $query = $db->prepare("UPDATE accounts SET followersCount = followersCount - 1 WHERE accountID = :targetAccountID");
    $query->execute([':targetAccountID' => $targetAccountID]);
    
    if ($automaticCron) Cron::updateFriendsCount($accountID, false);
    // $gs->logAction($accountID, SOMETHING_NOT_TAKEN, $targetAccountID);
}

echo 1;
?>