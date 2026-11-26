<?php
chdir(dirname(__FILE__));
require_once "../lib/connection.php";
require_once "../lib/GJPCheck.php";
require_once "../lib/exploitPatch.php";
require_once "../lib/mainLib.php";

$gs = new mainLib();

$accountID = !empty($_POST["accountID"]) ? GJPCheck::getAccountIDOrDie() : 0;
$soundID = ExploitPatch::number($_POST["soundID"] ?? "");

$query = $db->prepare("SELECT soundName, soundDesc, userName, accountID, actionCount, soundCount, emote, version, downloads, likes, rated, unlisted, uploadDate, updateDate FROM soundpacks WHERE soundID = :soundID");
$query->execute([":soundID" => $soundID]);
$soundpack = $query->fetch(PDO::FETCH_ASSOC);

if (!$soundpack) exit("-1");

$ownerID = $soundpack["accountID"];
if ($soundpack["unlisted"] == 3 && $accountID != $ownerID && ($accountID == 0 || !$gs->isFriends($accountID, $ownerID)))
    exit("-1");

$query = $db->prepare("UPDATE soundpacks SET downloads = downloads + 1 WHERE soundID = :soundID");
$query->execute(["soundID" => $soundID]);

$soundData = file_get_contents("../../data/soundpacks/$soundID");
echo "1:" . $soundID . ":2:" . $soundpack["soundName"] . ":3:" . $soundpack["soundDesc"] . ":5:" . $soundpack["version"] . ":10:" . $soundpack["downloads"] . ":14:" . $soundpack["likes"] . ":19:" . $soundpack["rated"] . ":28:" . $soundpack["uploadDate"] . ":29:" . $soundpack["updateDate"] . ":49:" . $ownerID . ":50:" . $soundpack["userName"] . ":51:" . $soundData . ":59:" . $soundpack["actionCount"] . ":60:" . $soundpack["soundCount"] . ":61:" . $soundpack["unlisted"] . ":64:" . $soundpack["emote"];
echo "#f5da5823d94bbe7208dd83a30ff427c7d88fdb99"; // robtop
?>