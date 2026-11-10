<?php
chdir(dirname(__FILE__));
require_once "../lib/connection.php";

$query = $db->prepare("SELECT
    COALESCE((SELECT feaID FROM dailyfeatures WHERE timestamp <= :current AND type = 0 ORDER BY timestamp DESC LIMIT 1), 0) AS dailyNumber,
    COALESCE((SELECT feaID FROM dailyfeatures WHERE timestamp <= :current AND type = 1 ORDER BY timestamp DESC LIMIT 1), 0) AS weeklyNumber,
    COALESCE((SELECT feaID FROM events WHERE timestamp <= :current ORDER BY timestamp DESC LIMIT 1), 0) AS eventNumber,
    (SELECT COUNT(*) FROM gauntlets) AS gauntletVersion,
    COALESCE((SELECT ID FROM gauntlets ORDER BY timestamp DESC LIMIT 1), 0) AS latestGauntlet
");
$query->execute([":current" => time()]);
$info = $query->fetch(PDO::FETCH_ASSOC);

echo implode(",", [
    $info["dailyNumber"],
    $info["weeklyNumber"] + 100000,
    $info["eventNumber"] + 200000,
    $info["gauntletVersion"],
    $info["latestGauntlet"]
]);
?>
