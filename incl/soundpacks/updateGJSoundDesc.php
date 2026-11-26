<?php
chdir(dirname(__FILE__));
require_once "../lib/connection.php";
require_once "../lib/exploitPatch.php";
require_once "../lib/GJPCheck.php";

$accountID = GJPCheck::getAccountIDOrDie();

$soundID = ExploitPatch::number($_POST["soundID"] ?? "");
$soundDesc = ExploitPatch::remove($_POST["soundDesc"] ?? "");
$emote = ExploitPatch::remove($_POST["emote"] ?? "");

if (empty($soundDesc) || !preg_match('/^[A-Za-z0-9_\-]*={0,2}$/D', $soundDesc))
    exit("-1");

$query = $db->prepare("SELECT 1 FROM soundpacks WHERE soundID = :soundID AND accountID = :accountID");
$query->execute([":soundID" => $soundID, ":accountID" => $accountID]);
if (!$query->fetchColumn())
    exit("-1");

$query = $db->prepare("UPDATE soundpacks SET soundDesc = :soundDesc, emote = :emote WHERE soundID = :soundID");
$query->execute([":soundDesc" => $soundDesc, ":emote" => $emote, ":soundID" => $soundID]);

echo "1";
?>