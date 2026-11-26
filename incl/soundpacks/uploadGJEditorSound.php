<?php
chdir(dirname(__FILE__));
require_once "../lib/connection.php";
require_once "../lib/mainLib.php";
require_once "../lib/exploitPatch.php";
require_once "../lib/GJPCheck.php";

$gs = new mainLib();

$accountID = GJPCheck::getAccountIDOrDie();
$userID = $gs->getUserID($accountID);
$userName = $gs->getUserName($userID);

$soundID = ExploitPatch::number($_POST["soundID"] ?? "");
$soundName = ExploitPatch::remove($_POST["soundName"] ?? "");
$soundData = $_POST["soundData"] ?? "";
$soundDesc = $_POST["soundDesc"] ?? "";
$actionCount = ExploitPatch::number($_POST["actionCount"] ?? "");
$soundCount = ExploitPatch::number($_POST["soundCount"] ?? "");
$emote = ExploitPatch::remove($_POST["emote"] ?? "");
$unlisted = ExploitPatch::number($_POST["unlisted"] ?? "");

if (empty($soundName) || empty($soundData) || !preg_match('/^[A-Za-z0-9_\-]*={0,2}$/D', $soundDesc) || !preg_match('/^[A-Za-z0-9_\-]*={0,2}$/D', $soundData) || !in_array($unlisted, ["", "0", "3", "4"]))
    exit("-1");

$fields = ["soundName" => $soundName];

if (isset($_POST["soundDesc"])) $fields["soundDesc"] = $soundDesc;
if ($actionCount != "") $fields["actionCount"] = $actionCount;
if ($soundCount != "") $fields["soundCount"] = $soundCount;
if (isset($_POST["emote"])) $fields["emote"] = $emote;
if ($unlisted != "") $fields["unlisted"] = $unlisted;

if (!empty($soundID)) {
    $query = $db->prepare("SELECT 1 FROM soundpacks WHERE soundID = :soundID AND accountID = :accountID");
    $query->execute([":soundID" => $soundID, ":accountID" => $accountID]);
    
    if (!$query->fetchColumn())
        exit("-1");

    $fields["updateDate"] = time();
} else {
    $fields += ["userName" => $userName, "accountID" => $accountID, "userID" => $userID, "uploadDate" => time()];
}

$columns = array_keys($fields);
$params = [];

foreach ($fields as $column => $value)
    $params[":$column"] = $value;

if (!empty($soundID)) {
    $set = implode(", ", array_map(fn($column) => "$column = :$column", $columns));

    $query = $db->prepare("UPDATE soundpacks SET version = version + 1, $set WHERE soundID = :soundID AND accountID = :ownerID");
    $query->execute($params + [":soundID" => $soundID, ":ownerID" => $accountID]);
} else {
    $query = $db->prepare("INSERT INTO soundpacks (" . implode(", ", $columns) . ") VALUES (" . implode(", ", array_keys($params)) . ")");
    $query->execute($params);
    $soundID = $db->lastInsertId();
}

file_put_contents("../../data/soundpacks/$soundID", $soundData, LOCK_EX);
echo $soundID;
?>