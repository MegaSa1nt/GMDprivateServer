<?php
chdir(dirname(__FILE__));
require_once "../lib/connection.php";
require_once "../lib/exploitPatch.php";
require_once "../lib/GJPCheck.php";

$accountID = 0;
if (isset($_POST["accountID"])) $accountID = GJPCheck::getAccountIDOrDie();

$songID = ExploitPatch::remove($_POST["songID"] ?? "");

$visibility = "unlisted = 0";
$params = [":songID" => $songID];

if ($accountID > 0) {
    $visibility .= " OR extID = :acc1
        OR (unlisted = 1 AND EXISTS (
            SELECT 1 FROM friendships
            WHERE (person1 = :acc2 AND person2 = levels.extID)
               OR (person2 = :acc3 AND person1 = levels.extID)
        ))";
    $params += [":acc1" => $accountID, ":acc2" => $accountID, ":acc3" => $accountID];
}

$ratedCondition = "(starStars > 0 OR starFeatured > 0 OR starEpic > 0)";

$query = $db->prepare("SELECT
    COUNT(*) AS totalUsing,
    COALESCE(SUM(starStars > 0 OR starFeatured > 0 OR starEpic > 0), 0) AS ratedUsing,
    COALESCE(SUM(CASE WHEN (starStars > 0 OR starFeatured > 0 OR starEpic > 0) THEN downloads ELSE 0 END), 0) AS ratedDownloadsUsing
    FROM levels
    WHERE songID = :songID AND ($visibility)
");
$query->execute($params);
$levelUsage = $query->fetch(PDO::FETCH_ASSOC);

echo implode(",", [
    $levelUsage["totalUsing"],
    $levelUsage["ratedUsing"],
    $levelUsage["ratedDownloadsUsing"],
]);

echo "#";

$query = $db->prepare("SELECT
    CASE
        WHEN starEpic > 0 THEN starEpic + 1
        WHEN starFeatured > 0 THEN 1
        ELSE 0
    END AS rating,
    CASE
        WHEN starDemon = 1 THEN 6
        WHEN starAuto = 1 THEN 0

        WHEN starDifficulty = 10 THEN 1
        WHEN starDifficulty = 20 THEN 2
        WHEN starDifficulty = 30 THEN 3
        WHEN starDifficulty = 40 THEN 4
        WHEN starDifficulty = 50 THEN 5

        ELSE 0
    END AS difficulty,
    COUNT(*) AS amount
    FROM levels
    WHERE songID = :songID AND (starStars > 0 OR starFeatured > 0 OR starEpic > 0) AND ($visibility)
    GROUP BY rating, difficulty
    ORDER BY rating, difficulty
");
$query->execute($params);
$groupings = $query->fetchAll(PDO::FETCH_ASSOC);

$parts = [];
foreach ($groupings as $grouping) {
    $parts[] = implode(":", [$grouping["rating"], $grouping["difficulty"], $grouping["amount"]]);
}

echo implode("|", $parts);
?>