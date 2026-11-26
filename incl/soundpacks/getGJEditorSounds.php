<?php
chdir(dirname(__FILE__));
require_once "../lib/connection.php";
require_once "../lib/GJPCheck.php";
require_once "../lib/exploitPatch.php";
require_once "../lib/mainLib.php";

$gs = new mainLib();

$accountID = !empty($_POST["accountID"]) ? GJPCheck::getAccountIDOrDie() : 0;

$str = ExploitPatch::remove($_POST["str"] ?? "");
$type = ExploitPatch::number($_POST["type"] ?? "");
$page = (int)ExploitPatch::number($_POST["page"] ?? "");

$type = $type == "" ? 2 : $type;

$where = [];
$params = [];
$visibility = "unlisted = 0";
$orderBy = "uploadDate DESC";

if (in_array($type, [0, 5]) && empty($str)) exit("-1");

switch ($type) {
    case 0: // search
        $where[] = "soundName LIKE :str ESCAPE '!'";
        $params[":str"] = "%" . strtr($str, ["!" => "!!", "%" => "!%", "_" => "!_"]) . "%";
        $orderBy = "likes DESC";
        break;
    case 1: // most downloaded
        $orderBy = "downloads DESC";
        break;
    case 2: // most liked
        $orderBy = "likes DESC";
        break;
    case 3: // trending
        $orderBy = "(downloads + likes) / (1 + (UNIX_TIMESTAMP() - uploadDate) / 86400) DESC";
        break;
    case 4: // recent
        break;
    case 5: // a user's packs
        $where[] = "accountID = :ownerID";
        $params[":ownerID"] = $str;
        if ($accountID == $str) {
            $visibility = "";
        } elseif ($accountID !== 0 && $gs->isFriends($accountID, $str)) {
            $visibility = "unlisted IN (0, 3)";
        }
        break;
    case 6: // featured
        $where[] = "rated = 1";
        break;
    case 13: // friends' packs
        if (empty($accountID)) exit("-1");
        $friends = $gs->getFriends($accountID);
        if (!$friends) {
            $where[] = "1 = 0";
            break;
        }

        $placeholders = [];
        foreach (array_values($friends) as $id => $friendID) {
            $placeholders[] = ":friend$id";
            $params[":friend$id"] = $friendID;
        }

        $where[] = "accountID IN (" . implode(", ", $placeholders) . ")";
        $visibility = "unlisted IN (0, 3)";
        break;
    default:
        exit("-1");
}

if (!empty($visibility)) $where[] = $visibility;
$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

$query = $db->prepare("SELECT COUNT(*) FROM soundpacks $whereSQL");
$query->execute($params);
$total = $query->fetchColumn();

$offset = $page * 10;
$query = $db->prepare("SELECT soundID, soundName, soundDesc, userName, userID, accountID, actionCount, soundCount, emote, version, downloads, likes, rated, unlisted, uploadDate, updateDate FROM soundpacks $whereSQL ORDER BY $orderBy, soundID DESC LIMIT " . 10 . " OFFSET $offset");
$query->execute($params);
$soundpacks = $query->fetchAll(PDO::FETCH_ASSOC);

$packs = [];
$creators = [];
foreach ($soundpacks as $pack) {
    $packs[] = "1:" . $pack["soundID"] . ":2:" . $pack["soundName"] . ":3:" . $pack["soundDesc"] . ":5:" . $pack["version"] . ":10:" . $pack["downloads"] . ":14:" . $pack["likes"] . ":19:" . $pack["rated"] . ":28:" . $pack["uploadDate"] . ":29:" . $pack["updateDate"] . ":49:" . $pack["accountID"] . ":50:" . $pack["userName"] . ":59:" . $pack["actionCount"] . ":60:" . $pack["soundCount"] . ":61:" . $pack["unlisted"] . ":64:" . $pack["emote"];
    $creators[$pack["userID"]] = $pack["userID"] . ":" . $pack["userName"] . ":" . $pack["accountID"];
}

echo implode("|", $packs) . "#" . implode("|", $creators) . "#" . $total . ":" . $offset . ":" . 10;
echo "#f5da5823d94bbe7208dd83a30ff427c7d88fdb99";
?>