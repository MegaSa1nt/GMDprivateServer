<?php
require_once __DIR__ . '/../lib/connection.php';
require_once __DIR__ . '/../lib/GJPCheck.php';
require_once __DIR__ . '/../lib/exploitPatch.php';
require_once __DIR__ . '/../lib/modernData.php';

$accountID = GJPCheck::getAccountIDOrDie();
$levelID = ExploitPatch::number(modernRequestValue(['levelID', 'levelId']));
if (!$levelID) exit('-1');
ensureModernDataTables($db);
$query = $db->prepare('SELECT deathData FROM death_data WHERE levelID = :levelID AND accountID = :accountID');
$query->execute([':levelID' => $levelID, ':accountID' => $accountID]);
$deathData = $query->fetchColumn();
if ($deathData === false) exit('-1');
echo $deathData;

